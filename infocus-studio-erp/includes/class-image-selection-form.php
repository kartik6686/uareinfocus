<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [infocus_image_selection] — a third private, per-booking form, same
 * token-in-link pattern as the shoot requirements form. After delivering
 * raw images, the client lists which image numbers (e.g. DSC1594) they
 * want edited, with an optional per-image direction/comment. Images beyond
 * the package's included count go in a visually separate "extra" section
 * with a live, prominent charge estimate — costed at ₹1000 per 2 extra
 * images, recalculated server-side on submit so it can't be spoofed.
 */
class Infocus_ERP_Image_Selection_Form {

	const RUPEES_PER_TWO_EXTRA = 1000;

	public static function init() {
		add_shortcode( 'infocus_image_selection', array( __CLASS__, 'render_form' ) );
		add_action( 'admin_post_infocus_erp_submit_image_selection', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_infocus_erp_submit_image_selection', array( __CLASS__, 'handle_submit' ) );
	}

	/**
	 * Whether this image selection is currently locked from further client
	 * edits. Three states, checked in order:
	 *  - lock_override = 'locked'   → always locked (you locked it manually)
	 *  - lock_override = 'unlocked' → always open (you unlocked it manually,
	 *    e.g. to let a client fix something after auto-lock)
	 *  - lock_override = '' (default) → automatic: open until
	 *    infocus_erp_lock_after_hours have passed since their last
	 *    submission, then locked. A fresh submission always resets this
	 *    to '' so the normal window restarts from the new submission time.
	 */
	public static function is_locked( $row ) {
		if ( $row['lock_override'] === 'locked' ) return true;
		if ( $row['lock_override'] === 'unlocked' ) return false;
		if ( empty( $row['submitted_at'] ) ) return false;
		$hours = (float) get_option( 'infocus_erp_lock_after_hours', 4 );
		return ( current_time( 'timestamp' ) - strtotime( $row['submitted_at'] ) ) >= ( $hours * HOUR_IN_SECONDS );
	}

	public static function render_form() {
		$token = isset( $_GET['rid'] ) ? sanitize_text_field( $_GET['rid'] ) : '';
		$row   = $token ? self::find_by_token( $token ) : null;

		ob_start();

		if ( ! $row ) {
			echo '<div class="infocus-public"><div class="ifs-wrap"><div class="ifc-notice ifc-notice-err">This link isn\'t valid. Please check the link your photographer sent you, or ask for a new one.</div></div></div>';
			return ob_get_clean();
		}

		$booking  = Infocus_ERP_CRUD::get( 'bookings', $row['booking_id'] );
		$customer = $booking ? Infocus_ERP_CRUD::get( 'customers', $booking['customer_id'] ) : null;
		$included = $booking ? (int) $booking['included_edits'] : 0;

		$existing       = ! empty( $row['selected_images'] ) ? json_decode( $row['selected_images'], true ) : null;
		$included_items = is_array( $existing ) && isset( $existing['included'] ) ? $existing['included'] : array_fill( 0, max( 1, $included ), array( 'image' => '', 'comment' => '' ) );
		$extra_items    = is_array( $existing ) && isset( $existing['extra'] ) ? $existing['extra'] : array();
		?>
		<div class="infocus-public">
		<div class="ifs-wrap">
			<div class="ifc-head">
				<span class="ifc-eyebrow">Choose Your Favourites</span>
				<h2 class="ifc-title">Tell Us Which Images You'd Like Edited</h2>
				<p class="ifc-sub">Enter the image number from your gallery for each slot below.</p>
			</div>

			<?php if ( isset( $_GET['infocus_ok'] ) ) : ?>
				<div class="ifc-notice ifc-notice-ok">Thank you — your image selection has been saved.</div>
			<?php endif; ?>

			<?php if ( $booking ) : ?>
				<div class="ifc-details-strip">
					<div class="ifc-details-item"><b>Client</b><span><?php echo esc_html( $customer['name'] ?? '—' ); ?></span></div>
					<div class="ifc-details-item"><b>Package</b><span><?php echo esc_html( $booking['service_type'] ) . ( $booking['package_price'] ? ' — ₹' . number_format( $booking['package_price'], 0 ) : '' ); ?></span></div>
					<div class="ifc-details-item"><b>Session Date</b><span><?php echo esc_html( $booking['session_date'] ?: '—' ); ?></span></div>
					<div class="ifc-details-item"><b>Images Included</b><span><?php echo (int) $included; ?></span></div>
				</div>
			<?php endif; ?>

			<?php if ( self::is_locked( $row ) ) : ?>

				<div class="ifc-card">
					<div class="ifc-notice ifc-notice-ok">Your selections have been finalized and are being worked on. Contact us if you need to change something.</div>
					<div class="ifc-card-head"><span class="ifc-card-title">Included in Your Package</span></div>
					<?php foreach ( $included_items as $item ) :
						if ( empty( $item['image'] ) ) continue; ?>
						<div class="ifs-locked-item"><strong><?php echo esc_html( $item['image'] ); ?></strong><?php echo ! empty( $item['comment'] ) ? '<br>' . esc_html( $item['comment'] ) : ''; ?></div>
					<?php endforeach; ?>

					<?php if ( ! empty( array_filter( $extra_items, function ( $i ) { return ! empty( $i['image'] ); } ) ) ) : ?>
						<div class="ifc-card-head" style="margin-top:20px;"><span class="ifc-card-title">Additional Images</span></div>
						<?php foreach ( $extra_items as $item ) :
							if ( empty( $item['image'] ) ) continue; ?>
							<div class="ifs-locked-item"><strong><?php echo esc_html( $item['image'] ); ?></strong><?php echo ! empty( $item['comment'] ) ? '<br>' . esc_html( $item['comment'] ) : ''; ?></div>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( ! empty( $row['extra_notes'] ) ) : ?>
						<div class="ifc-card-head" style="margin-top:20px;"><span class="ifc-card-title">Notes</span></div>
						<p style="font-size:14px;color:var(--ifc-navy);"><?php echo esc_html( $row['extra_notes'] ); ?></p>
					<?php endif; ?>
				</div>

			<?php else : ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ifs-image-form">
					<?php wp_nonce_field( 'infocus_erp_public_image_selection', 'infocus_erp_nonce' ); ?>
					<input type="hidden" name="action" value="infocus_erp_submit_image_selection">
					<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">

					<!-- 1. Included first — this is what's already paid for. Row count always equals the package's included_edits. -->
					<div class="ifc-card">
						<div class="ifc-card-head">
							<span class="ifc-card-title">Included in Your Package</span>
							<span class="ifc-card-count"><?php echo (int) $included; ?> image slots</span>
						</div>
						<div id="ifs-included-list">
							<?php foreach ( $included_items as $i => $item ) : ?>
								<div class="ifs-imgrow">
									<span class="num-label">#<?php echo $i + 1; ?></span>
									<input type="text" name="included_image[]" value="<?php echo esc_attr( $item['image'] ?? '' ); ?>" placeholder="e.g. DSC1594">
									<input type="text" name="included_comment[]" value="<?php echo esc_attr( $item['comment'] ?? '' ); ?>" placeholder="Direction for this image (optional)">
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- 2. Extras second — optional add-on beyond the package -->
					<div class="ifc-card">
						<div class="ifc-card-head">
							<span class="ifc-card-title">Additional Images (Optional)</span>
							<span class="ifc-card-count">Beyond your included <?php echo (int) $included; ?></span>
						</div>
						<div id="ifs-extra-list">
							<?php foreach ( $extra_items as $item ) : ?>
								<div class="ifs-imgrow has-remove">
									<span class="num-label">Extra</span>
									<input type="text" class="ifs-extra-input" name="extra_image[]" value="<?php echo esc_attr( $item['image'] ?? '' ); ?>" placeholder="e.g. DSC1594">
									<input type="text" name="extra_comment[]" value="<?php echo esc_attr( $item['comment'] ?? '' ); ?>" placeholder="Direction for this image (optional)">
									<button type="button" class="ifc-remove-btn" aria-label="Remove">✕</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" id="ifs-add-extra" class="ifc-add-btn">+ Add an extra image</button>

						<div class="ifs-estimate" id="ifs-estimate">
							<span id="ifs-estimate-text">0 extra images selected</span>
							<strong id="ifs-estimate-charge">Estimated charge: ₹0</strong>
						</div>
					</div>

					<!-- 3. Anything else — freeform, last -->
					<div class="ifc-card">
						<div class="ifc-card-head"><span class="ifc-card-title">Anything Else</span></div>
						<textarea class="ifc-textarea" style="margin-bottom:0;" name="extra_notes" rows="3" placeholder="Any other general notes for the whole set"><?php echo esc_textarea( $row['extra_notes'] ); ?></textarea>
					</div>

					<button type="submit" class="ifc-btn">Submit Selection</button>
					<p class="ifc-note">Your selections lock a few hours after your last edit — reach out if you need more time.</p>
				</form>

				<script>
				(function(){
					var rate = <?php echo self::RUPEES_PER_TWO_EXTRA; ?>;
					var extraList = document.getElementById('ifs-extra-list');
					var estimate = document.getElementById('ifs-estimate');
					var estimateText = document.getElementById('ifs-estimate-text');
					var estimateCharge = document.getElementById('ifs-estimate-charge');

					function recalc(){
						var inputs = extraList.querySelectorAll('.ifs-extra-input');
						var filled = 0;
						inputs.forEach(function(i){ if(i.value.trim() !== '') filled++; });
						if(filled > 0){
							var charge = Math.ceil(filled/2) * rate;
							estimate.classList.add('is-visible');
							estimateText.textContent = filled + ' extra image' + (filled===1?'':'s') + ' selected';
							estimateCharge.textContent = 'Estimated charge: ₹' + charge.toLocaleString('en-IN');
						} else {
							estimate.classList.remove('is-visible');
						}
					}

					InfocusPublic.repeatableRows({
						listEl: extraList,
						addBtn: document.getElementById('ifs-add-extra'),
						rowHtml: function(){
							return '<div class="ifs-imgrow has-remove"><span class="num-label">Extra</span><input type="text" class="ifs-extra-input" name="extra_image[]" placeholder="e.g. DSC1594"><input type="text" name="extra_comment[]" placeholder="Direction for this image (optional)"><button type="button" class="ifc-remove-btn" aria-label="Remove">✕</button></div>';
						},
						onAdd: function(rowEl){
							var input = rowEl.querySelector('.ifs-extra-input');
							if (input) input.addEventListener('input', recalc);
						},
						onRemove: recalc
					});

					recalc();
				})();
				</script>

			<?php endif; ?>
		</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function handle_submit() {
		if ( ! isset( $_POST['infocus_erp_nonce'] ) || ! wp_verify_nonce( $_POST['infocus_erp_nonce'], 'infocus_erp_public_image_selection' ) ) {
			wp_die( 'Security check failed. Please go back and try again.' );
		}

		$token = sanitize_text_field( $_POST['token'] ?? '' );
		$row   = self::find_by_token( $token );
		if ( ! $row ) {
			wp_die( 'This link is no longer valid.' );
		}

		if ( self::is_locked( $row ) ) {
			wp_safe_redirect( add_query_arg( 'infocus_thankyou', rawurlencode( 'Your selections have already been finalized. Contact us if you need to change something.' ), home_url( '/' ) ) );
			exit;
		}

		if ( ! Infocus_ERP_Security::rate_limit_ok( 'imgsel_' . Infocus_ERP_Security::client_ip(), 10, HOUR_IN_SECONDS ) ) {
			wp_die( 'Too many submissions — please try again later.' );
		}

		$included = self::collect_pairs( $_POST['included_image'] ?? array(), $_POST['included_comment'] ?? array() );
		$extra    = self::collect_pairs( $_POST['extra_image'] ?? array(), $_POST['extra_comment'] ?? array() );

		$extra_count  = count( $extra );
		$extra_charge = ceil( $extra_count / 2 ) * self::RUPEES_PER_TWO_EXTRA;

		Infocus_ERP_CRUD::update( 'image_selections', $row['id'], array(
			'selected_images'        => wp_json_encode( array( 'included' => $included, 'extra' => $extra ) ),
			'extra_notes'            => sanitize_textarea_field( $_POST['extra_notes'] ?? '' ),
			'extra_count'            => $extra_count,
			'estimated_extra_charge' => $extra_charge,
			'lock_override'          => '', // a fresh submission always resumes the normal auto-lock timer
			'submitted_at'           => current_time( 'mysql' ),
		) );

		wp_safe_redirect( add_query_arg( 'infocus_thankyou', rawurlencode( 'Thank you — your image selection has been saved.' ), home_url( '/' ) ) );
		exit;
	}

	/** Zips parallel image[]/comment[] arrays into [{image, comment}, ...], dropping rows with no image number. */
	private static function collect_pairs( $images, $comments ) {
		$pairs = array();
		if ( ! is_array( $images ) ) return $pairs;
		foreach ( array_slice( $images, 0, 300 ) as $i => $img ) {
			$img = sanitize_text_field( trim( $img ) );
			if ( $img === '' ) continue;
			$comment = isset( $comments[ $i ] ) ? sanitize_text_field( trim( $comments[ $i ] ) ) : '';
			$pairs[] = array( 'image' => $img, 'comment' => $comment );
		}
		return $pairs;
	}

	private static function find_by_token( $token ) {
		global $wpdb;
		$t = Infocus_ERP_DB::tables();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['image_selections']} WHERE token = %s", $token ), ARRAY_A );
	}

	/** Creates (or reuses) an image-selection link for a booking. Shared by the admin Bookings screen (via REST) and the MCP get_image_selection_link tool. */
	public static function get_or_create_image_link( $booking_id ) {
		if ( ! $booking_id || ! Infocus_ERP_CRUD::get( 'bookings', $booking_id ) ) {
			return new WP_Error( 'not_found', 'Booking not found.' );
		}

		global $wpdb;
		$t        = Infocus_ERP_DB::tables();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['image_selections']} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking_id ), ARRAY_A );

		if ( $existing ) {
			$row_id = (int) $existing['id'];
			$token  = $existing['token'];
		} else {
			$token  = wp_generate_password( 40, false, false );
			$row_id = Infocus_ERP_CRUD::insert( 'image_selections', array( 'booking_id' => $booking_id, 'token' => $token ) );
		}

		$page_url = get_option( 'infocus_erp_image_selection_page_url', home_url( '/select-images/' ) );
		return array( 'row_id' => $row_id, 'link' => add_query_arg( 'rid', $token, $page_url ) );
	}
}
