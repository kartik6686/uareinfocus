<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The public "Book a Session" calendar — delivered as a shortcode, same
 * pattern as the other public-facing forms in class-public-forms.php.
 *
 *   [infocus_book_session]
 *
 * How confirmation works (deliberately manual, never automatic):
 *   - Morning (09:00) and Post-Lunch (14:00) are the two daily slots.
 *   - A slot is "available" while the number of non-cancelled bookings
 *     already on that date+time is below infocus_erp_max_bookings_per_slot
 *     (default 1). Once capacity is reached, the slot still accepts
 *     submissions, but the calendar visually tags it "Request Only" and
 *     the client is told a human will follow up on WhatsApp.
 *   - Sundays are always treated as request-only, regardless of capacity —
 *     the studio's day off, available only for urgent bookings.
 *   - EVERY submission — available slot or not — lands in the `inquiries`
 *     table as "Pending Review". Nothing is ever auto-confirmed just by
 *     someone picking a date; Kartik (or Claude, via the confirm_booking
 *     MCP tool, only after being told to) has to actually turn it into a
 *     real Confirmed booking. This matches the advance-payment reality:
 *     no slot is truly locked in without a human + payment.
 */
class Infocus_ERP_Booking_Calendar {

	const MORNING_TIME    = '09:00:00';
	const POST_LUNCH_TIME = '14:00:00';

	public static function init() {
		add_shortcode( 'infocus_book_session', array( __CLASS__, 'render' ) );

		add_action( 'rest_api_init', array( __CLASS__, 'register_availability_route' ) );

		add_action( 'admin_post_infocus_erp_submit_booking', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_infocus_erp_submit_booking', array( __CLASS__, 'handle_submit' ) );
	}

	/** Public, unauthenticated route — only ever returns availability (no names/phones), so no API key needed. */
	public static function register_availability_route() {
		register_rest_route( Infocus_ERP_REST_API::NS, '/booking-availability', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function ( $request ) {
				$year  = (int) $request->get_param( 'year' );
				$month = (int) $request->get_param( 'month' );
				if ( ! $year || ! $month ) {
					return new WP_Error( 'bad_request', 'year and month are required.', array( 'status' => 400 ) );
				}
				return rest_ensure_response( self::month_availability( $year, $month ) );
			},
		) );
	}

	/**
	 * Returns, for every day in the given month, whether Morning/Post-Lunch
	 * are available or already at capacity. Sundays always come back as
	 * unavailable (request-only) regardless of actual bookings.
	 */
	public static function month_availability( $year, $month ) {
		global $wpdb;
		$t        = Infocus_ERP_DB::tables();
		$capacity = max( 1, (int) get_option( 'infocus_erp_max_bookings_per_slot', 1 ) );

		$start = sprintf( '%04d-%02d-01', $year, $month );
		$end   = date( 'Y-m-d', strtotime( $start . ' +1 month' ) );

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT session_date, session_time, COUNT(*) AS taken
			 FROM {$t['bookings']}
			 WHERE session_date >= %s AND session_date < %s AND status != 'Cancelled'
			 GROUP BY session_date, session_time",
			$start, $end
		), ARRAY_A );

		$taken_map = array(); // 'YYYY-MM-DD|HH:MM:SS' => count
		foreach ( $rows as $row ) {
			$taken_map[ $row['session_date'] . '|' . $row['session_time'] ] = (int) $row['taken'];
		}

		$days_in_month = (int) date( 't', strtotime( $start ) );
		$result        = array();

		for ( $d = 1; $d <= $days_in_month; $d++ ) {
			$date       = sprintf( '%04d-%02d-%02d', $year, $month, $d );
			$is_sunday  = ( (int) date( 'w', strtotime( $date ) ) === 0 );
			$morning_ok = ! $is_sunday && ( ( $taken_map[ $date . '|' . self::MORNING_TIME ] ?? 0 ) < $capacity );
			$lunch_ok   = ! $is_sunday && ( ( $taken_map[ $date . '|' . self::POST_LUNCH_TIME ] ?? 0 ) < $capacity );

			$result[ $date ] = array(
				'is_sunday' => $is_sunday,
				'morning'   => $morning_ok,
				'postlunch' => $lunch_ok,
			);
		}

		return $result;
	}

	/** True/false availability check for one specific date+slot, used again at submit time so we never trust the client's JS state. */
	private static function slot_is_available( $date, $time ) {
		global $wpdb;
		$t = Infocus_ERP_DB::tables();

		if ( (int) date( 'w', strtotime( $date ) ) === 0 ) return false; // Sunday — always request-only

		$capacity = max( 1, (int) get_option( 'infocus_erp_max_bookings_per_slot', 1 ) );
		$taken    = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$t['bookings']} WHERE session_date = %s AND session_time = %s AND status != 'Cancelled'",
			$date, $time
		) );
		return $taken < $capacity;
	}

	public static function render() {
		ob_start();

		$rest_url = esc_url( get_rest_url( null, Infocus_ERP_REST_API::NS . '/booking-availability' ) );
		?>
		<div class="infocus-public">
		<div class="ifxbc-wrap">
			<div class="ifc-head">
				<span class="ifc-eyebrow">Book a Session</span>
				<h2 class="ifc-title">Pick a Date That Works for You</h2>
				<p class="ifc-sub">Select a session type, choose a date, and pick a time. We'll confirm your request personally.</p>
			</div>

			<?php if ( isset( $_GET['infocus_booked'] ) ) : ?>
				<div class="ifxbc-summary" style="margin-bottom:20px;">Thanks — your request has been logged. We'll reach out on WhatsApp to confirm.</div>
			<?php endif; ?>

			<div class="ifc-pills" id="ifxbcTypes" style="justify-content:center;">
				<button class="ifc-pill is-active" data-type="Maternity" type="button">Maternity</button>
				<button class="ifc-pill" data-type="Newborn" type="button">Newborn</button>
				<button class="ifc-pill" data-type="Kids" type="button">Kids</button>
				<button class="ifc-pill" data-type="Family" type="button">Family</button>
			</div>

			<div class="ifxbc-panel">
				<div class="ifxbc-cal">
					<div class="ifc-cal-head">
						<button class="ifc-cal-nav" id="ifxbcPrev" type="button">‹</button>
						<span class="ifc-cal-month" id="ifxbcMonthLabel"></span>
						<button class="ifc-cal-nav" id="ifxbcNext" type="button">›</button>
					</div>
					<div class="ifc-cal-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
					<div class="ifc-cal-grid" id="ifxbcGrid"></div>
				</div>
				<div class="ifxbc-slots">
					<h3 id="ifxbcSlotsTitle">Select a date</h3>
					<div id="ifxbcSlotList"><p class="ifxbc-empty">Choose a date on the calendar to see options.</p></div>
				</div>
			</div>

			<form class="ifxbc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ifxbcForm">
				<?php wp_nonce_field( 'infocus_erp_public_booking', 'infocus_erp_nonce' ); ?>
				<input type="hidden" name="action" value="infocus_erp_submit_booking">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">
				<div style="position:absolute;left:-9999px;" aria-hidden="true"><label>Leave blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

				<input type="hidden" name="service_type" id="ifxbcServiceType" value="Maternity">
				<input type="hidden" name="preferred_date" id="ifxbcSelectedDate" value="">
				<input type="hidden" name="slot_label" id="ifxbcSelectedSlotLabel" value="">
				<input type="hidden" name="slot_time" id="ifxbcSelectedSlotTime" value="">

				<div class="ifxbc-summary" id="ifxbcSummary" style="display:none;"></div>

				<input class="ifc-input" type="text" name="name" placeholder="Your Name" required>
				<input class="ifc-input" type="tel" name="phone" placeholder="Phone Number" required>
				<button class="ifc-btn" type="submit">Confirm Booking Request</button>
				<p class="ifxbc-form-note">Your booking will be confirmed only after receiving an advance amount.</p>
			</form>
		</div>
		</div>

		<script>
		(function(){
			var restUrl = <?php echo wp_json_encode( $rest_url ); ?>;
			var today = new Date(); var viewYear = today.getFullYear(); var viewMonth = today.getMonth();
			var monthData = {}; var selectedDate = null; var selectedSlot = null; var selectedIsRequest = false; var selectedType = 'Maternity';

			var monthLabel = document.getElementById('ifxbcMonthLabel');
			var grid = document.getElementById('ifxbcGrid');
			var slotList = document.getElementById('ifxbcSlotList');
			var slotsTitle = document.getElementById('ifxbcSlotsTitle');
			var prevBtn = document.getElementById('ifxbcPrev');
			var nextBtn = document.getElementById('ifxbcNext');
			var summary = document.getElementById('ifxbcSummary');
			var MONTHS = InfocusPublic.MONTHS;

			function fetchAvailability(y, m, cb){
				fetch(restUrl + '?year=' + y + '&month=' + (m+1))
					.then(function(r){ return r.json(); })
					.then(function(data){ monthData = data || {}; cb(); })
					.catch(function(){ monthData = {}; cb(); });
			}

			function renderCalendar(){
				InfocusPublic.renderMonth({
					year: viewYear,
					month: viewMonth,
					gridEl: grid,
					monthLabelEl: monthLabel,
					prevBtn: prevBtn,
					dayClass: 'ifc-cal-day ifxbc-cal-day',
					decorate: function(btn, y, m, d, isPast){
						var key = InfocusPublic.dateKey(y, m, d);
						var info = monthData[key] || { is_sunday:false, morning:true, postlunch:true };
						if(!isPast){
							if(info.is_sunday) btn.classList.add('is-sunday');
							else if(info.morning || info.postlunch) btn.classList.add('has-slots');
							else btn.classList.add('is-full');
						}
						if(key === selectedDate) btn.classList.add('is-selected');
					},
					onDayClick: function(y, m, d, btn){
						var key = InfocusPublic.dateKey(y, m, d);
						var info = monthData[key] || { is_sunday:false, morning:true, postlunch:true };
						selectDate(key, MONTHS[m] + ' ' + d + ', ' + y, info);
					}
				});
			}

			function selectDate(key, label, info){
				selectedDate = key; selectedSlot = null; selectedIsRequest = false;
				renderCalendar();
				slotsTitle.textContent = label;
				slotList.innerHTML = '';

				if(info.is_sunday){
					slotList.innerHTML = '<div class="ifxbc-note is-sunday"><strong>Sunday is our day off.</strong><br>We\'re only available for urgent bookings. Request it anyway and we\'ll confirm on WhatsApp if we can accommodate.</div>';
				}

				var slots = [
					{ label: 'Morning Session', time: '<?php echo self::MORNING_TIME; ?>', available: info.morning },
					{ label: 'Post-Lunch', time: '<?php echo self::POST_LUNCH_TIME; ?>', available: info.postlunch }
				];

				var anyAvailable = slots.some(function(s){ return s.available; });
				if(!anyAvailable && !info.is_sunday){
					var full = document.createElement('div');
					full.className = 'ifxbc-note';
					full.innerHTML = '<strong>This date is fully booked.</strong> You can still request it below — we\'ll do our best to adjust and confirm on WhatsApp.';
					slotList.appendChild(full);
				}

				slots.forEach(function(s){
					var b = document.createElement('button');
					b.type = 'button'; b.className = 'ifxbc-slot';
					if(!s.available) b.classList.add('is-requestonly');
					b.innerHTML = s.available ? s.label : (s.label + ' <span class="ifxbc-tag">Request Only</span>');
					b.addEventListener('click', function(){
						selectedSlot = s; selectedIsRequest = !s.available;
						document.querySelectorAll('.ifxbc-slot').forEach(function(el){ el.classList.remove('is-selected'); });
						b.classList.add('is-selected');
						updateSummary(label);
					});
					slotList.appendChild(b);
				});
			}

			function updateSummary(label){
				if(!selectedDate || !selectedSlot) return;
				document.getElementById('ifxbcSelectedDate').value = selectedDate;
				document.getElementById('ifxbcSelectedSlotLabel').value = selectedSlot.label;
				document.getElementById('ifxbcSelectedSlotTime').value = selectedSlot.time;
				summary.style.display = 'block';
				summary.textContent = selectedType + ' session — request logged for ' + label + ' (' + selectedSlot.label + '). We\'ll confirm with you on WhatsApp.';
			}

			prevBtn.addEventListener('click', function(){
				viewMonth--; if(viewMonth<0){viewMonth=11;viewYear--;}
				fetchAvailability(viewYear, viewMonth, renderCalendar);
			});
			nextBtn.addEventListener('click', function(){
				viewMonth++; if(viewMonth>11){viewMonth=0;viewYear++;}
				fetchAvailability(viewYear, viewMonth, renderCalendar);
			});
			document.querySelectorAll('#ifxbcTypes .ifc-pill').forEach(function(btn){
				btn.addEventListener('click', function(){
					document.querySelectorAll('#ifxbcTypes .ifc-pill').forEach(function(b){ b.classList.remove('is-active'); });
					btn.classList.add('is-active');
					selectedType = btn.dataset.type;
					document.getElementById('ifxbcServiceType').value = selectedType;
				});
			});

			document.getElementById('ifxbcForm').addEventListener('submit', function(e){
				if(!selectedDate || !selectedSlot){
					e.preventDefault();
					alert('Please select a date and time first.');
				}
			});

			fetchAvailability(viewYear, viewMonth, renderCalendar);
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Converts a "Pending Review" booking inquiry (one submitted through
	 * this calendar) into a real Confirmed booking — occupying the slot.
	 * This is the ONLY way a calendar submission ever becomes a real
	 * booking; nothing here happens automatically. The MCP tool wrapping
	 * this must only call it after Kartik has explicitly said to confirm
	 * that specific request — never on Claude's own judgment.
	 */
	public static function confirm_booking_request( $inquiry_id, $session_time = null, $package_price = null ) {
		$inquiry = Infocus_ERP_CRUD::get( 'inquiries', $inquiry_id );
		if ( ! $inquiry ) return new WP_Error( 'not_found', 'Inquiry not found.' );
		if ( $inquiry['status'] !== 'Pending Review' ) {
			return new WP_Error( 'bad_request', 'This inquiry isn\'t pending review (already handled).' );
		}
		if ( empty( $inquiry['preferred_date'] ) ) {
			return new WP_Error( 'bad_request', 'This inquiry has no preferred_date to book against.' );
		}

		// Work out which slot this was, from the explicit arg or by reading the logged message.
		if ( ! $session_time ) {
			if ( stripos( $inquiry['message'], 'Morning' ) !== false ) {
				$session_time = self::MORNING_TIME;
			} elseif ( stripos( $inquiry['message'], 'Post-Lunch' ) !== false ) {
				$session_time = self::POST_LUNCH_TIME;
			}
		}

		$customer_result = Infocus_ERP_REST_API::resolve_or_create_customer( $inquiry['name'], $inquiry['phone'] );
		if ( isset( $customer_result['needs_confirmation'] ) ) {
			return $customer_result; // ambiguous name match — surface to Kartik rather than guessing
		}

		$booking_id = Infocus_ERP_CRUD::insert( 'bookings', array(
			'customer_id'   => (int) $customer_result['id'],
			'service_type'  => $inquiry['service_type'],
			'session_date'  => $inquiry['preferred_date'],
			'session_time'  => $session_time,
			'package_price' => $package_price !== null ? floatval( $package_price ) : 0,
			'status'        => 'Confirmed',
			'notes'         => 'Confirmed from website booking request (inquiry #' . $inquiry_id . '). ' . $inquiry['message'],
		) );

		Infocus_ERP_CRUD::update( 'inquiries', $inquiry_id, array(
			'matched_customer_id' => $customer_result['id'],
			'status'              => 'New',
		) );

		return array(
			'confirmed'    => true,
			'booking_id'   => $booking_id,
			'customer'     => $customer_result['name'],
			'session_date' => $inquiry['preferred_date'],
			'session_time' => $session_time,
			'message'      => "Confirmed {$customer_result['name']}'s booking for {$inquiry['preferred_date']}.",
		);
	}

	public static function handle_submit() {
		if ( ! isset( $_POST['infocus_erp_nonce'] ) || ! wp_verify_nonce( $_POST['infocus_erp_nonce'], 'infocus_erp_public_booking' ) ) {
			wp_die( 'Security check failed. Please go back and try again.' );
		}
		if ( ! empty( $_POST['website'] ) ) {
			wp_die( 'Submission rejected.' ); // honeypot tripped
		}
		if ( ! Infocus_ERP_Security::rate_limit_ok( 'booking_' . Infocus_ERP_Security::client_ip(), 10, HOUR_IN_SECONDS ) ) {
			wp_die( 'Too many submissions — please try again later.' );
		}

		$name           = sanitize_text_field( $_POST['name'] ?? '' );
		$phone          = sanitize_text_field( $_POST['phone'] ?? '' );
		$service_type   = sanitize_text_field( $_POST['service_type'] ?? '' );
		$preferred_date = sanitize_text_field( $_POST['preferred_date'] ?? '' );
		$slot_label     = sanitize_text_field( $_POST['slot_label'] ?? '' );
		$slot_time      = sanitize_text_field( $_POST['slot_time'] ?? '' );

		if ( ! $name || ! $phone || ! $preferred_date || ! $slot_time ) {
			wp_die( 'Please fill in all required fields and select a date/time.' );
		}

		// Authoritative re-check server-side — never trust what the client's JS claimed.
		$is_available = self::slot_is_available( $preferred_date, $slot_time );
		$is_sunday    = ( (int) date( 'w', strtotime( $preferred_date ) ) === 0 );
		$is_request   = ! $is_available || $is_sunday;

		$message = $is_request
			? "Requested slot: {$slot_label}. This slot is at capacity or falls on a Sunday — logged as a REQUEST, not a confirmed booking. Follow up on WhatsApp to confirm or renegotiate."
			: "Requested slot: {$slot_label} (was showing as available at time of submission — still needs manual confirmation).";

		Infocus_ERP_CRUD::insert( 'inquiries', array(
			'name'           => $name,
			'phone'          => $phone,
			'service_type'   => $service_type ?: 'Other',
			'preferred_date' => $preferred_date,
			'message'        => $message,
			'is_new_client'  => 1,
			'quality_flag'   => '',
			'status'         => 'Pending Review',
		) );

		$redirect = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'infocus_booked', '1', $redirect ) );
		exit;
	}
}
