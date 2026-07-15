<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Two client-facing forms, both delivered as shortcodes so they drop into
 * any Elementor page without depending on Elementor's own widget engine:
 *
 *   [infocus_inquiry_form]        — public, anyone can submit
 *   [infocus_shoot_requirements]  — private, only works with a valid ?rid=
 *                                    token generated per-booking from the
 *                                    Bookings screen
 *
 * Styled inline with the Infocus brand palette so it looks native to the
 * site rather than a bolted-on tool.
 */
class Infocus_ERP_Public_Forms {

	const BRAND_BG     = '#F8F8FF';
	const BRAND_NAVY   = '#131357';
	const BRAND_GOLD   = '#D4AF37';
	const BRAND_SLATE  = '#6B7A8F';
	const BRAND_CRIMSON = '#9A1F1F';
	const BRAND_TEAL   = '#1A6B6B';

	public static function init() {
		add_shortcode( 'infocus_inquiry_form', array( __CLASS__, 'render_inquiry_form' ) );
		add_shortcode( 'infocus_shoot_requirements', array( __CLASS__, 'render_requirements_form' ) );

		add_action( 'admin_post_infocus_erp_submit_inquiry', array( __CLASS__, 'handle_inquiry_submit' ) );
		add_action( 'admin_post_nopriv_infocus_erp_submit_inquiry', array( __CLASS__, 'handle_inquiry_submit' ) );

		add_action( 'admin_post_infocus_erp_submit_requirements', array( __CLASS__, 'handle_requirements_submit' ) );
		add_action( 'admin_post_nopriv_infocus_erp_submit_requirements', array( __CLASS__, 'handle_requirements_submit' ) );

		add_action( 'admin_post_infocus_erp_generate_requirements_link', array( __CLASS__, 'handle_generate_link' ) );

		add_action( 'wp_footer', array( __CLASS__, 'render_thankyou_toast' ) );
	}

	/**
	 * Redirects to the site's home page with a one-time "thank you" flag,
	 * rather than trying to return to the page that hosted the form. This
	 * sidesteps a blank-page issue some Elementor/page-builder setups hit
	 * when redirecting back to a shortcode's own page. The home page always
	 * exists and always renders correctly, and the toast below confirms the
	 * submission regardless of which page the client started on.
	 */
	private static function redirect_with_thankyou( $message ) {
		wp_safe_redirect( add_query_arg( 'infocus_thankyou', rawurlencode( $message ), home_url( '/' ) ) );
		exit;
	}

	/** Shows a small floating confirmation toast on whatever page infocus_thankyou lands on, then removes the query string from the address bar. */
	public static function render_thankyou_toast() {
		if ( empty( $_GET['infocus_thankyou'] ) ) return;
		$message = sanitize_text_field( wp_unslash( $_GET['infocus_thankyou'] ) );
		?>
		<div id="infocus-toast" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#131357;color:#F8F8FF;padding:14px 22px;border-radius:12px;font-size:14px;box-shadow:0 4px 18px rgba(0,0,0,0.18);z-index:99999;display:flex;align-items:center;gap:10px;max-width:90vw;">
			<span style="color:#1A6B6B;font-weight:700;">&#10003;</span>
			<span><?php echo esc_html( $message ); ?></span>
		</div>
		<script>
		(function(){
			setTimeout(function(){
				var t = document.getElementById('infocus-toast');
				if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 400); }
			}, 5000);
			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.delete('infocus_thankyou');
				window.history.replaceState({}, document.title, url.toString());
			}
		})();
		</script>
		<?php
	}

	private static function shared_styles() {
		return '<style>
@import url(\'https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Jost:wght@400;500;600&display=swap\');
.ifq-wrap,.ifr-wrap{width:100%;font-family:\'Jost\',\'Segoe UI\',sans-serif;box-sizing:border-box;}
.ifq-wrap *,.ifr-wrap *{box-sizing:border-box;}
.ifq-head,.ifr-head{text-align:center;margin-bottom:24px;}
.ifq-eyebrow,.ifr-eyebrow{display:inline-block;font-size:18px;letter-spacing:.24em;text-transform:uppercase;color:' . self::BRAND_GOLD . ';font-weight:600;margin-bottom:12px;}
.ifq-title,.ifr-title{font-family:\'Fraunces\',Georgia,serif;font-weight:600;font-size:clamp(26px,4vw,34px);color:' . self::BRAND_NAVY . ';}
.ifq-sub,.ifr-sub{margin-top:8px;font-size:18px;color:' . self::BRAND_SLATE . ';}
.ifq-card,.ifr-card{background:#fff;border-radius:20px;box-shadow:0 20px 50px -22px rgba(19,19,87,.16);padding:32px;position:relative;}
.ifq-label,.ifr-label{display:block;font-size:18px;font-weight:600;color:' . self::BRAND_SLATE . ';margin-bottom:8px;}
.ifq-hint,.ifr-hint{font-size:18px;color:' . self::BRAND_SLATE . ';margin:-4px 0 10px;font-style:italic;}
.ifq-input,.ifq-select,.ifq-textarea,.ifr-input,.ifr-textarea{width:100%;padding:14px 15px;border-radius:10px;border:1.5px solid rgba(19,19,87,.12);background:#FBF7F0;color:' . self::BRAND_NAVY . ';font-size:18px;font-family:inherit;margin-bottom:18px;transition:border-color .2s ease;}
.ifq-input:focus,.ifq-select:focus,.ifq-textarea:focus,.ifr-input:focus,.ifr-textarea:focus{outline:none;border-color:' . self::BRAND_GOLD . ';background:#fff;}
.ifq-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.ifq-row .ifq-input{margin-bottom:0;}
.ifq-row-wrap{margin-bottom:18px;}
@media (max-width:640px){.ifq-row{grid-template-columns:1fr;gap:18px;}}
.ifq-services{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;}
.ifq-service-pill{font-size:18px;font-weight:600;color:' . self::BRAND_NAVY . ';background:#FBF7F0;border:1.5px solid rgba(19,19,87,.15);border-radius:999px;padding:10px 18px;cursor:pointer;transition:all .2s ease;}
.ifq-service-pill.is-active{background:' . self::BRAND_NAVY . ';color:#F8F8FF;border-color:' . self::BRAND_NAVY . ';}
.ifq-btn,.ifr-btn{width:100%;background:' . self::BRAND_NAVY . ';color:#F8F8FF;border:none;border-radius:24px;padding:16px;font-size:18px;font-weight:600;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease;}
.ifq-btn:hover,.ifr-btn:hover{transform:translateY(-2px);box-shadow:0 16px 32px -14px rgba(19,19,87,.4);}
.ifq-note{margin-top:14px;text-align:center;font-size:18px;color:' . self::BRAND_SLATE . ';font-style:italic;}
.ifq-datefield{position:relative;}
.ifq-date-display{width:100%;padding:14px 15px;border-radius:10px;border:1.5px solid rgba(19,19,87,.12);background:#FBF7F0;color:' . self::BRAND_NAVY . ';font-size:18px;font-family:inherit;margin-bottom:18px;cursor:pointer;text-align:left;display:flex;justify-content:space-between;align-items:center;}
.ifq-date-display:hover{border-color:' . self::BRAND_GOLD . ';}
.ifq-date-display .placeholder{color:' . self::BRAND_SLATE . ';}
.ifq-cal-panel{position:absolute;top:calc(100% - 12px);left:0;right:0;z-index:10;background:#fff;border:1px solid rgba(19,19,87,.12);border-radius:14px;box-shadow:0 20px 45px -18px rgba(19,19,87,.28);padding:20px;display:none;}
.ifq-cal-panel.is-open{display:block;}
.ifq-cal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.ifq-cal-month{font-family:\'Fraunces\',Georgia,serif;font-weight:600;font-size:18px;color:' . self::BRAND_NAVY . ';}
.ifq-cal-nav{width:32px;height:32px;border-radius:50%;border:1px solid rgba(19,19,87,.15);background:#fff;color:' . self::BRAND_NAVY . ';font-size:18px;cursor:pointer;}
.ifq-cal-nav:disabled{opacity:.3;cursor:not-allowed;}
.ifq-cal-dow{display:grid;grid-template-columns:repeat(7,1fr);text-align:center;margin-bottom:6px;}
.ifq-cal-dow span{font-size:18px;color:' . self::BRAND_SLATE . ';font-weight:600;}
.ifq-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;max-height:260px;overflow-y:auto;}
.ifq-cal-day{aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:18px;color:' . self::BRAND_NAVY . ';cursor:pointer;border:none;background:transparent;}
.ifq-cal-day:hover:not(:disabled):not(.is-empty){background:rgba(212,175,55,.18);}
.ifq-cal-day.is-selected{background:' . self::BRAND_GOLD . ';color:' . self::BRAND_NAVY . ';font-weight:600;}
.ifq-cal-day:disabled{color:rgba(19,19,87,.2);cursor:not-allowed;}
.ifq-cal-day.is-empty{visibility:hidden;}
.ifr-details{background:' . self::BRAND_NAVY . ';border-radius:16px;padding:22px 26px;margin-bottom:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
.ifr-details-item{color:#F8F8FF;}
.ifr-details-item b{display:block;font-size:12px;color:' . self::BRAND_GOLD . ';font-weight:600;margin-bottom:4px;letter-spacing:.02em;text-transform:uppercase;}
.ifr-details-item span{font-size:18px;}
@media (max-width:768px){.ifr-details{grid-template-columns:1fr 1fr;}}
@media (max-width:480px){.ifr-details{grid-template-columns:1fr;}}
.ifr-linkrow{display:flex;gap:10px;margin-bottom:10px;}
.ifr-linkrow .ifr-input{margin-bottom:0;flex:1;}
.ifr-linkrow button{flex:0 0 auto;background:#F9E4E4;color:' . self::BRAND_CRIMSON . ';border:none;border-radius:10px;width:44px;font-size:18px;cursor:pointer;}
.ifr-add{background:none;border:1.5px dashed rgba(19,19,87,.3);color:' . self::BRAND_NAVY . ';border-radius:10px;padding:11px 16px;font-size:18px;font-weight:600;cursor:pointer;margin-bottom:20px;width:100%;}
.ifr-add:hover{border-color:' . self::BRAND_GOLD . ';}
.ifx-notice{padding:14px 18px;border-radius:10px;font-size:18px;margin-bottom:18px;}
.ifx-notice-ok{background:rgba(26,107,107,0.1);color:' . self::BRAND_TEAL . ';}
.ifx-notice-err{background:rgba(75,0,130,0.08);color:#4B0082;}
</style>';
	}

	/* =============================== INQUIRY FORM =============================== */

	public static function render_inquiry_form() {
		ob_start();
		echo self::shared_styles();
		?>
		<div class="ifq-wrap">
			<div class="ifq-head">
				<span class="ifq-eyebrow">Let's Talk</span>
				<h2 class="ifq-title">Let's Create Something Beautiful</h2>
				<p class="ifq-sub">Tell us a little about what you're dreaming up.</p>
			</div>

			<?php if ( isset( $_GET['infocus_ok'] ) ) : ?>
				<div class="ifx-notice ifx-notice-ok">Thanks — we've received your details and will be in touch shortly.</div>
			<?php elseif ( isset( $_GET['infocus_err'] ) ) : ?>
				<div class="ifx-notice ifx-notice-err">Something didn't go through. Please check the form and try again.</div>
			<?php endif; ?>

			<div class="ifq-card">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ifq-form">
					<?php wp_nonce_field( 'infocus_erp_public_inquiry', 'infocus_erp_nonce' ); ?>
					<input type="hidden" name="action" value="infocus_erp_submit_inquiry">
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">
					<div style="position:absolute;left:-9999px;" aria-hidden="true"><label>Leave blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

					<div class="ifq-row-wrap">
						<div class="ifq-row">
							<div><label class="ifq-label">Name *</label><input class="ifq-input" type="text" name="name" required></div>
							<div><label class="ifq-label">Phone *</label><input class="ifq-input" type="tel" name="phone" required></div>
						</div>
					</div>

					<label class="ifq-label">Email (optional)</label>
					<input class="ifq-input" type="email" name="email">

					<label class="ifq-label">Service Interested In</label>
					<div class="ifq-services" id="ifq-services">
						<?php foreach ( array( 'Maternity', 'Newborn', 'Kids', 'Family', 'Other' ) as $i => $opt ) : ?>
							<button type="button" class="ifq-service-pill<?php echo $i === 0 ? ' is-active' : ''; ?>" data-value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></button>
						<?php endforeach; ?>
					</div>
					<input type="hidden" name="service_type" id="ifq-service-type" value="Maternity">

					<label class="ifq-label">Preferred Date</label>
					<div class="ifq-datefield">
						<button type="button" class="ifq-date-display" id="ifq-date-display">
							<span class="placeholder" id="ifq-date-text">Select a date</span>
							<span>📅</span>
						</button>
						<div class="ifq-cal-panel" id="ifq-cal-panel">
							<div class="ifq-cal-head">
								<button type="button" class="ifq-cal-nav" id="ifq-cal-prev">‹</button>
								<span class="ifq-cal-month" id="ifq-cal-month"></span>
								<button type="button" class="ifq-cal-nav" id="ifq-cal-next">›</button>
							</div>
							<div class="ifq-cal-dow"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
							<div class="ifq-cal-grid" id="ifq-cal-grid"></div>
						</div>
					</div>
					<input type="hidden" name="preferred_date" id="ifq-preferred-date" value="">

					<label class="ifq-label">Additional Requirements (optional)</label>
					<textarea class="ifq-textarea" name="additional_requirements" rows="3" placeholder="Any specific requirements, extra touches, or anything else you'd like us to know"></textarea>

					<button type="submit" class="ifq-btn">Send Inquiry</button>
					<p class="ifq-note">We'll get back to you personally, usually within a day.</p>
				</form>
			</div>
		</div>

		<script>
		(function(){
			document.querySelectorAll('.ifq-service-pill').forEach(function(btn){
				btn.addEventListener('click', function(){
					document.querySelectorAll('.ifq-service-pill').forEach(function(b){ b.classList.remove('is-active'); });
					btn.classList.add('is-active');
					document.getElementById('ifq-service-type').value = btn.dataset.value;
				});
			});

			var today = new Date();
			var viewYear = today.getFullYear(), viewMonth = today.getMonth();
			var selected = null;
			var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

			var display = document.getElementById('ifq-date-display');
			var dateText = document.getElementById('ifq-date-text');
			var hiddenDate = document.getElementById('ifq-preferred-date');
			var panel = document.getElementById('ifq-cal-panel');
			var monthLabel = document.getElementById('ifq-cal-month');
			var grid = document.getElementById('ifq-cal-grid');
			var prevBtn = document.getElementById('ifq-cal-prev');
			var nextBtn = document.getElementById('ifq-cal-next');

			function pad(n){ return n < 10 ? '0'+n : ''+n; }

			function render(){
				monthLabel.textContent = MONTHS[viewMonth] + ' ' + viewYear;
				grid.innerHTML = '';
				var firstDay = new Date(viewYear, viewMonth, 1).getDay();
				var daysInMonth = new Date(viewYear, viewMonth+1, 0).getDate();
				var isCurrentMonth = (viewYear === today.getFullYear() && viewMonth === today.getMonth());

				for(var i=0;i<firstDay;i++){
					var e = document.createElement('button');
					e.type='button'; e.className='ifq-cal-day is-empty'; e.disabled=true;
					grid.appendChild(e);
				}
				for(var d=1; d<=daysInMonth; d++){
					var b = document.createElement('button');
					b.type='button'; b.className='ifq-cal-day'; b.textContent=d;
					var isPast = isCurrentMonth && d < today.getDate();
					if(isPast){ b.disabled = true; }
					else {
						b.addEventListener('click', (function(y,m,day){
							return function(){
								selected = { y:y, m:m, d:day };
								dateText.textContent = MONTHS[m] + ' ' + day + ', ' + y;
								dateText.classList.remove('placeholder');
								hiddenDate.value = y + '-' + pad(m+1) + '-' + pad(day);
								panel.classList.remove('is-open');
								render();
							};
						})(viewYear, viewMonth, d));
					}
					if(selected && selected.y===viewYear && selected.m===viewMonth && selected.d===d) b.classList.add('is-selected');
					grid.appendChild(b);
				}
				prevBtn.disabled = isCurrentMonth;
			}

			display.addEventListener('click', function(e){
				e.stopPropagation();
				panel.classList.toggle('is-open');
			});
			document.addEventListener('click', function(e){
				if(!panel.contains(e.target) && e.target !== display) panel.classList.remove('is-open');
			});
			prevBtn.addEventListener('click', function(){ viewMonth--; if(viewMonth<0){viewMonth=11;viewYear--;} render(); });
			nextBtn.addEventListener('click', function(){ viewMonth++; if(viewMonth>11){viewMonth=0;viewYear++;} render(); });

			render();
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	public static function handle_inquiry_submit() {
		if ( ! isset( $_POST['infocus_erp_nonce'] ) || ! wp_verify_nonce( $_POST['infocus_erp_nonce'], 'infocus_erp_public_inquiry' ) ) {
			wp_die( 'Security check failed. Please go back and try again.' );
		}

		// Honeypot filled → silently pretend success, don't store anything.
		if ( ! empty( $_POST['website'] ) ) {
			self::redirect_with_thankyou( "Thanks — we've received your details and will be in touch shortly." );
		}

		if ( ! Infocus_ERP_Security::rate_limit_ok( 'inquiry_' . Infocus_ERP_Security::client_ip(), 5, HOUR_IN_SECONDS ) ) {
			self::redirect_with_thankyou( "That didn't go through — please try again in a little while." );
		}

		$name  = sanitize_text_field( $_POST['name'] ?? '' );
		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		if ( empty( $name ) || empty( $phone ) ) {
			self::redirect_with_thankyou( 'Please fill in your name and phone number and try again.' );
		}

		$email        = sanitize_email( $_POST['email'] ?? '' );
		$service_type = sanitize_text_field( $_POST['service_type'] ?? '' );
		$additional   = sanitize_textarea_field( $_POST['additional_requirements'] ?? '' );
		$pref_date    = sanitize_text_field( $_POST['preferred_date'] ?? '' );

		// Existing customers (matched by phone or email) skip the approval gate —
		// they're already a known, vetted person messaging you again. Brand-new
		// names go to "Pending Review" and do NOT create a customer record until
		// you (or Claude, at your instruction) approve them.
		$match = self::find_existing_customer( $phone, $email );
		$is_new_client = ! $match;

		Infocus_ERP_CRUD::insert( 'inquiries', array(
			'name'                     => $name,
			'phone'                    => $phone,
			'email'                    => $email,
			'service_type'             => $service_type,
			'additional_requirements'  => $additional,
			'preferred_date'           => $pref_date ?: null,
			'message'                  => '', // no longer a separate field in this form — additional_requirements covers it
			'matched_customer_id'      => $match ? $match['id'] : null,
			'is_new_client'            => $is_new_client ? 1 : 0,
			'quality_flag'             => $is_new_client ? self::assess_quality( $name, $phone, $email, $additional ) : '',
			'status'                   => $is_new_client ? 'Pending Review' : 'New',
		) );

		self::notify_admin_new_inquiry( $name, $phone, $service_type, $is_new_client );

		self::redirect_with_thankyou( "Thanks, {$name} — we've received your details and will be in touch shortly." );
	}

	/** Looks for an existing customer by phone or email. Returns null if nothing matches — does NOT create one. */
	private static function find_existing_customer( $phone, $email ) {
		global $wpdb;
		$t            = Infocus_ERP_DB::tables();
		$phone_digits = preg_replace( '/\D+/', '', $phone );
		$found        = null;

		if ( $phone_digits ) {
			$found = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM {$t['customers']} WHERE REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+','') LIKE %s",
				'%' . $wpdb->esc_like( $phone_digits ) . '%'
			), ARRAY_A );
		}
		if ( ! $found && $email ) {
			$found = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$t['customers']} WHERE LOWER(email) = %s", strtolower( $email ) ), ARRAY_A );
		}

		return $found ? array( 'id' => (int) $found['id'] ) : null;
	}

	/**
	 * Rule-based, instant, no-API-call quality check for brand-new leads —
	 * flags the kind of low-effort junk that shows up on any public form.
	 * This is a first-pass filter, not a verdict: Claude, or you, reading
	 * the actual message content will always catch more than these rules
	 * can. Returns "Likely genuine", "Needs review", or "Looks suspicious".
	 */
	private static function assess_quality( $name, $phone, $email, $message ) {
		$red_flags = 0;

		if ( strlen( trim( $name ) ) < 2 || ! preg_match( '/[a-zA-Z]{2,}/', $name ) ) $red_flags++;
		if ( preg_match( '/^(test|asdf|xxx|abc|fake|spam|qwerty)$/i', trim( $name ) ) ) $red_flags++;

		$phone_digits = preg_replace( '/\D+/', '', $phone );
		$phone_digits = preg_replace( '/^91/', '', $phone_digits ); // strip country code if present
		if ( ! preg_match( '/^[6-9]\d{9}$/', $phone_digits ) ) $red_flags++;

		if ( $email ) {
			$disposable = array( 'mailinator.com', 'guerrillamail.com', 'tempmail.com', '10minutemail.com', 'yopmail.com', 'throwawaymail.com', 'trashmail.com' );
			$domain     = strtolower( substr( strrchr( $email, '@' ), 1 ) );
			if ( in_array( $domain, $disposable, true ) ) $red_flags++;
		}

		if ( $message && ! preg_match( '/[a-zA-Z]{3,}/', $message ) ) $red_flags++; // keyboard-mash check, only penalises if something was written and it's gibberish

		if ( $red_flags === 0 ) return 'Likely genuine';
		if ( $red_flags === 1 ) return 'Needs review';
		return 'Looks suspicious';
	}

	/**
	 * Admin action: approves a "Pending Review" inquiry — creates the
	 * customer record now (this is the moment a lead actually becomes a
	 * client in your CRM) and resumes the normal New/Contacted/Converted
	 * workflow. Shared with the MCP approve_inquiry tool.
	 */
	public static function approve_inquiry( $inquiry_id ) {
		$inquiry = Infocus_ERP_CRUD::get( 'inquiries', $inquiry_id );
		if ( ! $inquiry ) return new WP_Error( 'not_found', 'Inquiry not found.' );
		if ( $inquiry['status'] !== 'Pending Review' ) return new WP_Error( 'bad_request', 'This inquiry isn\'t pending review.' );

		$customer_id = Infocus_ERP_CRUD::insert( 'customers', array(
			'name'   => $inquiry['name'],
			'phone'  => $inquiry['phone'],
			'email'  => $inquiry['email'],
			'source' => 'Website Form',
		) );

		Infocus_ERP_CRUD::update( 'inquiries', $inquiry_id, array(
			'matched_customer_id' => $customer_id,
			'status'              => 'New',
		) );

		return array( 'approved' => true, 'customer_id' => $customer_id );
	}

	/** Admin action: rejects a pending inquiry. The row is permanently deleted — rejected inquiries (spam, bots, fake leads) should not clutter the database. */
	public static function reject_inquiry( $inquiry_id ) {
		$inquiry = Infocus_ERP_CRUD::get( 'inquiries', $inquiry_id );
		if ( ! $inquiry ) return new WP_Error( 'not_found', 'Inquiry not found.' );
		Infocus_ERP_CRUD::delete( 'inquiries', $inquiry_id );
		return array( 'rejected' => true, 'deleted' => true );
	}

	/** Builds a wa.me click-to-chat link with a pre-filled message. Shared by the Inquiries screen button and the MCP get_whatsapp_link tool. */
	public static function build_whatsapp_link( $phone, $message ) {
		$digits = preg_replace( '/\D+/', '', $phone );
		if ( strlen( $digits ) === 10 ) $digits = '91' . $digits; // assume Indian mobile if no country code given
		return 'https://wa.me/' . $digits . '?text=' . rawurlencode( $message );
	}

	/** Finds a package whose name matches (loosely) a given service type, for brochure lookups. */
	public static function find_matching_package( $service_type ) {
		$packages = Infocus_ERP_CRUD::get_all( 'packages' );
		foreach ( $packages as $p ) {
			if ( stripos( $p['name'], $service_type ) !== false || stripos( $service_type, $p['name'] ) !== false ) {
				return $p;
			}
		}
		return null;
	}

	private static function notify_admin_new_inquiry( $name, $phone, $service, $is_new ) {
		$to      = get_option( 'admin_email' );
		$subject = 'New ' . ( $is_new ? 'client' : 'existing client' ) . ' inquiry — ' . $name;
		$body    = "New inquiry received on the website:\n\nName: $name\nPhone: $phone\nService: $service\nClient type: " . ( $is_new ? 'New client' : 'Existing client' );

		$package = self::find_matching_package( $service );
		if ( $package && ! empty( $package['brochure_url'] ) ) {
			$body .= "\n\nMatching package brochure: " . $package['brochure_url'];
		}

		$body .= "\n\nOpen Studio ERP → Inquiries to see full details.";
		wp_mail( $to, $subject, $body );
	}

	/* ========================= SHOOT REQUIREMENTS FORM ========================= */

	public static function render_requirements_form() {
		$token = isset( $_GET['rid'] ) ? sanitize_text_field( $_GET['rid'] ) : '';
		$row   = $token ? self::find_requirement_by_token( $token ) : null;

		ob_start();
		echo self::shared_styles();

		if ( ! $row ) {
			echo '<div class="ifr-wrap"><div class="ifx-notice ifx-notice-err">This link isn\'t valid. Please check the link your photographer sent you, or ask for a new one.</div></div>';
			return ob_get_clean();
		}

		$booking  = Infocus_ERP_CRUD::get( 'bookings', $row['booking_id'] );
		$customer = $booking ? Infocus_ERP_CRUD::get( 'customers', $booking['customer_id'] ) : null;

		$links = ! empty( $row['reference_links'] ) ? json_decode( $row['reference_links'], true ) : array();
		if ( ! is_array( $links ) || empty( $links ) ) $links = array( '' );
		?>
		<div class="ifr-wrap">
			<div class="ifr-head">
				<span class="ifr-eyebrow">Let's Plan Your Shoot</span>
				<h2 class="ifr-title">A Few Details to Bring Your Vision to Life</h2>
				<p class="ifr-sub">Everything here helps us prepare exactly what your session needs.</p>
			</div>

			<?php if ( isset( $_GET['infocus_ok'] ) ) : ?>
				<div class="ifx-notice ifx-notice-ok">Thank you — your preferences have been saved.</div>
			<?php endif; ?>

			<?php if ( $booking ) : ?>
				<div class="ifr-details">
					<div class="ifr-details-item"><b>Client</b><span><?php echo esc_html( $customer['name'] ?? '—' ); ?></span></div>
					<div class="ifr-details-item"><b>Package</b><span><?php echo esc_html( $booking['service_type'] ) . ( $booking['package_price'] ? ' — ₹' . number_format( $booking['package_price'], 0 ) : '' ); ?></span></div>
					<div class="ifr-details-item"><b>Session Date</b><span><?php echo esc_html( $booking['session_date'] ?: '—' ); ?></span></div>
					<div class="ifr-details-item"><b>Images Included</b><span><?php echo (int) $booking['included_edits']; ?></span></div>
				</div>
			<?php endif; ?>

			<div class="ifr-card">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'infocus_erp_public_requirements', 'infocus_erp_nonce' ); ?>
					<input type="hidden" name="action" value="infocus_erp_submit_requirements">
					<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">

					<label class="ifr-label">1. Location Preference</label>
					<input class="ifr-input" type="text" name="location_preference" value="<?php echo esc_attr( $row['location_preference'] ); ?>" placeholder="Studio, outdoor spot, home, or a specific place">

					<label class="ifr-label">2. Theme or Concept</label>
					<input class="ifr-input" type="text" name="theme" value="<?php echo esc_attr( $row['theme'] ); ?>" placeholder="e.g. fairytale, vintage, minimalist">

					<label class="ifr-label">3. Outfit / Wardrobe Ideas</label>
					<input class="ifr-input" type="text" name="outfit" value="<?php echo esc_attr( $row['outfit'] ); ?>" placeholder="e.g. the fairy dress we discussed">

					<label class="ifr-label">4. Preferred Time / Lighting</label>
					<input class="ifr-input" type="text" name="preferred_time" value="<?php echo esc_attr( $row['preferred_time'] ); ?>" placeholder="e.g. golden hour, sunset, bright daylight">

					<label class="ifr-label">5. Reference Links</label>
					<div class="ifr-hint">Pinterest, Instagram, or any visual references</div>
					<div id="ifr-links-list">
						<?php foreach ( $links as $link ) : ?>
							<div class="ifr-linkrow">
								<input class="ifr-input" type="url" name="reference_links[]" value="<?php echo esc_attr( $link ); ?>" placeholder="Paste a reference link">
								<button type="button" onclick="this.parentElement.remove()">✕</button>
							</div>
						<?php endforeach; ?>
					</div>
					<button type="button" id="ifr-add-link" class="ifr-add">+ Add another link</button>

					<label class="ifr-label">6. Anything Else</label>
					<textarea class="ifr-input" name="additional_notes" rows="3" placeholder="Any other special requests for the shoot"><?php echo esc_textarea( $row['additional_notes'] ); ?></textarea>

					<button type="submit" class="ifr-btn">Submit Requirements</button>
				</form>
			</div>
		</div>

		<script>
		document.getElementById('ifr-add-link').addEventListener('click', function(){
			var row = document.createElement('div');
			row.className = 'ifr-linkrow';
			row.innerHTML = '<input class="ifr-input" type="url" name="reference_links[]" placeholder="Paste a reference link"><button type="button" onclick="this.parentElement.remove()">✕</button>';
			document.getElementById('ifr-links-list').appendChild(row);
		});
		</script>
		<?php
		return ob_get_clean();
	}

	public static function handle_requirements_submit() {
		if ( ! isset( $_POST['infocus_erp_nonce'] ) || ! wp_verify_nonce( $_POST['infocus_erp_nonce'], 'infocus_erp_public_requirements' ) ) {
			wp_die( 'Security check failed. Please go back and try again.' );
		}

		$token = sanitize_text_field( $_POST['token'] ?? '' );
		$row   = self::find_requirement_by_token( $token );
		if ( ! $row ) {
			wp_die( 'This link is no longer valid.' );
		}

		if ( ! Infocus_ERP_Security::rate_limit_ok( 'reqs_' . Infocus_ERP_Security::client_ip(), 10, HOUR_IN_SECONDS ) ) {
			wp_die( 'Too many submissions — please try again later.' );
		}

		$links = array();
		if ( ! empty( $_POST['reference_links'] ) && is_array( $_POST['reference_links'] ) ) {
			foreach ( array_slice( $_POST['reference_links'], 0, 20 ) as $link ) {
				$link = esc_url_raw( trim( $link ) );
				if ( $link ) $links[] = $link;
			}
		}

		Infocus_ERP_CRUD::update( 'shoot_requirements', $row['id'], array(
			'preferred_time'       => sanitize_text_field( $_POST['preferred_time'] ?? '' ),
			'theme'                => sanitize_text_field( $_POST['theme'] ?? '' ),
			'outfit'               => sanitize_text_field( $_POST['outfit'] ?? '' ),
			'location_preference'  => sanitize_text_field( $_POST['location_preference'] ?? '' ),
			'reference_links'      => wp_json_encode( $links ),
			'additional_notes'     => sanitize_textarea_field( $_POST['additional_notes'] ?? '' ),
			'submitted_at'         => current_time( 'mysql' ),
		) );

		self::redirect_with_thankyou( 'Thank you — your preferences have been saved.' );
	}

	public static function handle_generate_link() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		check_admin_referer( 'infocus_erp_gen_link' );

		$booking_id = (int) ( $_GET['booking_id'] ?? 0 );
		$result     = self::get_or_create_requirements_link( $booking_id );
		if ( is_wp_error( $result ) ) wp_die( $result->get_error_message() );

		wp_safe_redirect( admin_url( 'admin.php?page=infocus-erp-bookings&link_generated=' . $result['row_id'] ) );
		exit;
	}

	/** Creates (or reuses) a shoot-requirements link for a booking. Shared by the admin button and the MCP get_requirements_link tool. */
	public static function get_or_create_requirements_link( $booking_id ) {
		if ( ! $booking_id || ! Infocus_ERP_CRUD::get( 'bookings', $booking_id ) ) {
			return new WP_Error( 'not_found', 'Booking not found.' );
		}

		global $wpdb;
		$t        = Infocus_ERP_DB::tables();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['shoot_requirements']} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking_id ), ARRAY_A );

		if ( $existing ) {
			$row_id = (int) $existing['id'];
			$token  = $existing['token'];
		} else {
			$token  = wp_generate_password( 40, false, false );
			$row_id = Infocus_ERP_CRUD::insert( 'shoot_requirements', array( 'booking_id' => $booking_id, 'token' => $token ) );
		}

		$page_url = get_option( 'infocus_erp_requirements_page_url', home_url( '/shoot-consultation/' ) );
		return array( 'row_id' => $row_id, 'link' => add_query_arg( 'rid', $token, $page_url ) );
	}

	private static function find_requirement_by_token( $token ) {
		global $wpdb;
		$t = Infocus_ERP_DB::tables();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['shoot_requirements']} WHERE token = %s", $token ), ARRAY_A );
	}
}
