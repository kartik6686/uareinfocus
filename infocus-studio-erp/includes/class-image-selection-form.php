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

	const BRAND_BG      = '#F8F8FF';
	const BRAND_NAVY    = '#131357';
	const BRAND_GOLD    = '#D4AF37';
	const BRAND_SLATE   = '#6B7A8F';
	const BRAND_CRIMSON = '#9A1F1F';
	const BRAND_TEAL    = '#1A6B6B';

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

	private static function styles() {
		return '<style>
@import url(\'https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Jost:wght@400;500;600&display=swap\');
.ifs-wrap{width:100%;font-family:\'Jost\',\'Segoe UI\',sans-serif;box-sizing:border-box;}
.ifs-wrap *{box-sizing:border-box;}
.ifs-head{text-align:center;margin-bottom:24px;}
.ifs-eyebrow{display:inline-block;font-size:18px;letter-spacing:.24em;text-transform:uppercase;color:' . self::BRAND_GOLD . ';font-weight:600;margin-bottom:12px;}
.ifs-title{font-family:\'Fraunces\',Georgia,serif;font-weight:600;font-size:clamp(26px,4vw,34px);color:' . self::BRAND_NAVY . ';}
.ifs-sub{margin-top:8px;font-size:18px;color:' . self::BRAND_SLATE . ';}
.ifs-details{background:' . self::BRAND_NAVY . ';border-radius:16px;padding:22px 26px;margin-bottom:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
.ifs-details-item{color:#F8F8FF;}
.ifs-details-item b{display:block;font-size:12px;color:' . self::BRAND_GOLD . ';font-weight:600;margin-bottom:4px;letter-spacing:.02em;text-transform:uppercase;}
.ifs-details-item span{font-size:18px;}
@media (max-width:768px){.ifs-details{grid-template-columns:1fr 1fr;}}
@media (max-width:480px){.ifs-details{grid-template-columns:1fr;}}
.ifs-section{background:#fff;border-radius:20px;box-shadow:0 20px 50px -22px rgba(19,19,87,.16);padding:32px;margin-bottom:24px;}
.ifs-section-head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:20px;flex-wrap:wrap;gap:8px;}
.ifs-section-title{font-family:\'Fraunces\',Georgia,serif;font-weight:600;font-size:20px;color:' . self::BRAND_NAVY . ';}
.ifs-section-count{font-size:18px;color:' . self::BRAND_SLATE . ';}
.ifs-imgrow{display:grid;grid-template-columns:70px 1fr 1fr;gap:12px;align-items:center;background:#FBF7F0;border-radius:12px;padding:14px;margin-bottom:12px;}
.ifs-imgrow.has-remove{grid-template-columns:70px 1fr 1fr auto;}
.ifs-imgrow input{padding:12px 14px;border-radius:8px;border:1.5px solid rgba(19,19,87,.12);background:#fff;color:' . self::BRAND_NAVY . ';font-size:18px;font-family:inherit;}
.ifs-imgrow input:focus{outline:none;border-color:' . self::BRAND_GOLD . ';}
.ifs-imgrow .num-label{font-size:16px;color:' . self::BRAND_GOLD . ';font-weight:700;}
@media (max-width:600px){.ifs-imgrow,.ifs-imgrow.has-remove{grid-template-columns:1fr;}}
.ifs-remove{background:#F9E4E4;color:' . self::BRAND_CRIMSON . ';border:none;border-radius:8px;width:40px;height:40px;font-size:18px;cursor:pointer;}
.ifs-add{background:none;border:1.5px dashed rgba(19,19,87,.3);color:' . self::BRAND_NAVY . ';border-radius:10px;padding:12px 16px;font-size:18px;font-weight:600;cursor:pointer;width:100%;}
.ifs-add:hover{border-color:' . self::BRAND_GOLD . ';}
.ifs-estimate{background:#FCEFD9;border:1px solid rgba(180,120,20,.35);border-radius:12px;padding:16px 20px;margin-top:16px;display:none;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
.ifs-estimate.is-visible{display:flex;}
.ifs-estimate span{font-size:18px;color:#7a5c1f;}
.ifs-estimate strong{font-size:18px;color:' . self::BRAND_CRIMSON . ';}
.ifs-notes textarea{width:100%;padding:14px 15px;border-radius:10px;border:1.5px solid rgba(19,19,87,.12);background:#FBF7F0;color:' . self::BRAND_NAVY . ';font-size:18px;font-family:inherit;}
.ifs-notes textarea:focus{outline:none;border-color:' . self::BRAND_GOLD . ';background:#fff;}
.ifs-btn{width:100%;background:' . self::BRAND_NAVY . ';color:#F8F8FF;border:none;border-radius:24px;padding:16px;font-size:18px;font-weight:600;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease;}
.ifs-btn:hover{transform:translateY(-2px);box-shadow:0 16px 32px -14px rgba(19,19,87,.4);}
.ifs-lock-note{text-align:center;font-size:18px;color:' . self::BRAND_SLATE . ';font-style:italic;margin-top:14px;}
.ifs-locked-item{background:#FBF7F0;border-radius:10px;padding:12px 16px;margin-bottom:10px;font-size:18px;color:' . self::BRAND_NAVY . ';}
.ifx-notice{padding:14px 18px;border-radius:10px;font-size:18px;margin-bottom:18px;}
.ifx-notice-ok{background:rgba(26,107,107,0.1);color:' . self::BRAND_TEAL . ';}
.ifx-notice-err{background:rgba(75,0,130,0.08);color:#4B0082;}
</style>';
	}

	public static function render_form() {
		$token = isset( $_GET['rid'] ) ? sanitize_text_field( $_GET['rid'] ) : '';
		$row   = $token ? self::find_by_token( $token ) : null;

		ob_start();
		echo self::styles();

		if ( ! $row ) {
			echo '<div class="ifs-wrap"><div class="ifx-notice ifx-notice-err">This link isn\'t valid. Please check the link your photographer sent you, or ask for a new one.</div></div>';
			return ob_get_clean();
		}

		$booking  = Infocus_ERP_CRUD::get( 'bookings', $row['booking_id'] );
		$customer = $booking ? Infocus_ERP_CRUD::get( 'customers', $booking['customer_id'] ) : null;
		$included = $booking ? (int) $booking['included_edits'] : 0;

		$existing       = ! empty( $row['selected_images'] ) ? json_decode( $row['selected_images'], true ) : null;
		$included_items = is_array( $existing ) && isset( $existing['included'] ) ? $existing['included'] : array_fill( 0, max( 1, $included ), array( 'image' => '', 'comment' => '' ) );
		$extra_items    = is_array( $existing ) && isset( $existing['extra'] ) ? $existing['extra'] : array();
		?>
		<div class="ifs-wrap">
			<div class="ifs-head">
				<span class="ifs-eyebrow">Choose Your Favourites</span>
				<h2 class="ifs-title">Tell Us Which Images You'd Like Edited</h2>
				<p class="ifs-sub">Enter the image number from your gallery for each slot below.</p>
			</div>

			<?php if ( isset( $_GET['infocus_ok'] ) ) : ?>
				<div class="ifx-notice ifx-notice-ok">Thank you — your image selection has been saved.</div>
			<?php endif; ?>

			<?php if ( $booking ) : ?>
				<div class="ifs-details">
					<div class="ifs-details-item"><b>Client</b><span><?php echo esc_html( $customer['name'] ?? '—' ); ?></span></div>
					<div class="ifs-details-item"><b>Package</b><span><?php echo esc_html( $booking['service_type'] ) . ( $booking['package_price'] ? ' — ₹' . number_format( $booking['package_price'], 0 ) : '' ); ?></span></div>
					<div class="ifs-details-item"><b>Session Date</b><span><?php echo esc_html( $booking['session_date'] ?: '—' ); ?></span></div>
					<div class="ifs-details-item"><b>Images Included</b><span><?php echo (int) $included; ?></span></div>
				</div>
			<?php endif; ?>

			<?php if ( self::is_locked( $row ) ) : ?>

				<div class="ifs-section">
					<div class="ifx-notice ifx-notice-ok">Your selections have been finalized and are being worked on. Contact us if you need to change something.</div>
					<div class="ifs-section-head"><span class="ifs-section-title">Included in Your Package</span></div>
					<?php foreach ( $included_items as $item ) :
						if ( empty( $item['image'] ) ) continue; ?>
						<div class="ifs-locked-item"><strong><?php echo esc_html( $item['image'] ); ?></strong><?php echo ! empty( $item['comment'] ) ? '<br>' . esc_html( $item['comment'] ) : ''; ?></div>
					<?php endforeach; ?>

					<?php if ( ! empty( array_filter( $extra_items, function ( $i ) { return ! empty( $i['image'] ); } ) ) ) : ?>
						<div class="ifs-section-head" style="margin-top:20px;"><span class="ifs-section-title">Additional Images</span></div>
						<?php foreach ( $extra_items as $item ) :
							if ( empty( $item['image'] ) ) continue; ?>
							<div class="ifs-locked-item"><strong><?php echo esc_html( $item['image'] ); ?></strong><?php echo ! empty( $item['comment'] ) ? '<br>' . esc_html( $item['comment'] ) : ''; ?></div>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( ! empty( $row['extra_notes'] ) ) : ?>
						<div class="ifs-section-head" style="margin-top:20px;"><span class="ifs-section-title">Notes</span></div>
						<p style="font-size:18px;color:<?php echo self::BRAND_NAVY; ?>;"><?php echo esc_html( $row['extra_notes'] ); ?></p>
					<?php endif; ?>
				</div>

			<?php else : ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ifs-image-form">
					<?php wp_nonce_field( 'infocus_erp_public_image_selection', 'infocus_erp_nonce' ); ?>
					<input type="hidden" name="action" value="infocus_erp_submit_image_selection">
					<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">

					<!-- 1. Included first — this is what's already paid for. Row count always equals the package's included_edits. -->
					<div class="ifs-section">
						<div class="ifs-section-head">
							<span class="ifs-section-title">Included in Your Package</span>
							<span class="ifs-section-count"><?php echo (int) $included; ?> image slots</span>
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
					<div class="ifs-section">
						<div class="ifs-section-head">
							<span class="ifs-section-title">Additional Images (Optional)</span>
							<span class="ifs-section-count">Beyond your included <?php echo (int) $included; ?></span>
						</div>
						<div id="ifs-extra-list">
							<?php foreach ( $extra_items as $item ) : ?>
								<div class="ifs-imgrow has-remove">
									<span class="num-label">Extra</span>
									<input type="text" class="ifs-extra-input" name="extra_image[]" value="<?php echo esc_attr( $item['image'] ?? '' ); ?>" placeholder="e.g. DSC1594">
									<input type="text" name="extra_comment[]" value="<?php echo esc_attr( $item['comment'] ?? '' ); ?>" placeholder="Direction for this image (optional)">
									<button type="button" class="ifs-remove" aria-label="Remove">✕</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" id="ifs-add-extra" class="ifs-add">+ Add an extra image</button>

						<div class="ifs-estimate" id="ifs-estimate">
							<span id="ifs-estimate-text">0 extra images selected</span>
							<strong id="ifs-estimate-charge">Estimated charge: ₹0</strong>
						</div>
					</div>

					<!-- 3. Anything else — freeform, last -->
					<div class="ifs-section ifs-notes">
						<div class="ifs-section-head"><span class="ifs-section-title">Anything Else</span></div>
						<textarea name="extra_notes" rows="3" placeholder="Any other general notes for the whole set"><?php echo esc_textarea( $row['extra_notes'] ); ?></textarea>
					</div>

					<button type="submit" class="ifs-btn">Submit Selection</button>
					<p class="ifs-lock-note">Your selections lock a few hours after your last edit — reach out if you need more time.</p>
				</form>

				<script>
				(function(){
					var rate = <?php echo self::RUPEES_PER_TWO_EXTRA; ?>;
					var extraList = document.getElementById('ifs-extra-list');
					var addBtn = document.getElementById('ifs-add-extra');
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

					function wireRemove(row){
						row.querySelector('.ifs-remove').addEventListener('click', function(){
							row.remove();
							recalc();
						});
					}

					addBtn.addEventListener('click', function(){
						var row = document.createElement('div');
						row.className = 'ifs-imgrow has-remove';
						row.innerHTML = '<span class="num-label">Extra</span><input type="text" class="ifs-extra-input" name="extra_image[]" placeholder="e.g. DSC1594"><input type="text" name="extra_comment[]" placeholder="Direction for this image (optional)"><button type="button" class="ifs-remove" aria-label="Remove">✕</button>';
						row.querySelector('.ifs-extra-input').addEventListener('input', recalc);
						wireRemove(row);
						extraList.appendChild(row);
						recalc();
					});

					extraList.querySelectorAll('.ifs-imgrow').forEach(function(row){
						row.querySelector('.ifs-extra-input').addEventListener('input', recalc);
						wireRemove(row);
					});
					recalc();
				})();
				</script>

			<?php endif; ?>
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
