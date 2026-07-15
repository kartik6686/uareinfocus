<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Invoicing. Two ways an invoice gets created:
 *  1. "Generate Invoice" on a Bookings row — auto-pulls the package price,
 *     any advance already paid, and the extra-image charge from Image
 *     Selections if that booking has one.
 *  2. A custom invoice from Studio ERP → Invoices — pick a customer, add
 *     your own line items.
 *
 * Either way, the client sees the same branded, printable page via
 * [infocus_invoice] and a private per-invoice link (same token pattern as
 * the other two forms) — no PDF library dependency, just the browser's own
 * print-to-PDF.
 */
class Infocus_ERP_Invoices {

	const BRAND_BG      = '#F8F8FF';
	const BRAND_NAVY    = '#131357';
	const BRAND_GOLD    = '#D4AF37';
	const BRAND_SLATE   = '#6B7A8F';
	const BRAND_CRIMSON = '#9A1F1F';
	const BRAND_TEAL    = '#1A6B6B';

	public static function init() {
		add_shortcode( 'infocus_invoice', array( __CLASS__, 'render_invoice_page' ) );
	}

	/* ------------------------------------------------------------------ */

	/**
	 * Creates (or reuses, if one already exists for this booking) an
	 * invoice from a booking — auto-pulling package price, advance paid,
	 * and any extra-image charge. Shared by the admin Bookings screen (via REST)
	 * button and the MCP get_invoice_link tool.
	 */
	public static function generate_invoice_for_booking( $booking_id ) {
		$booking = Infocus_ERP_CRUD::get( 'bookings', $booking_id );
		if ( ! $booking ) return new WP_Error( 'not_found', 'Booking not found.' );

		global $wpdb;
		$t = Infocus_ERP_DB::tables();

		// Reuse an existing invoice for this booking rather than duplicating.
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['invoices']} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking_id ), ARRAY_A );
		if ( $existing ) {
			return array( 'invoice_id' => (int) $existing['id'], 'invoice_number' => $existing['invoice_number'], 'link' => self::build_link( $existing['token'] ) );
		}

		$line_items = array();
		if ( $booking['package_price'] > 0 ) {
			$line_items[] = array(
				'description' => $booking['service_type'] . ' Package' . ( $booking['included_edits'] ? ' (' . (int) $booking['included_edits'] . ' images included)' : '' ),
				'amount'      => (float) $booking['package_price'],
			);
		}

		$image_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['image_selections']} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking_id ), ARRAY_A );
		if ( $image_row && $image_row['extra_count'] > 0 ) {
			$line_items[] = array(
				'description' => 'Extra image edits — ' . (int) $image_row['extra_count'] . ' images beyond package',
				'amount'      => (float) $image_row['estimated_extra_charge'],
			);
		}

		$subtotal     = array_sum( array_column( $line_items, 'amount' ) );
		$advance_paid = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$t['payments']} WHERE booking_id = %d", $booking_id ) );
		$token        = wp_generate_password( 40, false, false );

		$invoice_id = Infocus_ERP_CRUD::insert( 'invoices', array(
			'invoice_number' => self::next_invoice_number(),
			'booking_id'     => $booking_id,
			'customer_id'    => $booking['customer_id'],
			'token'          => $token,
			'invoice_date'   => current_time( 'Y-m-d' ),
			'line_items'     => wp_json_encode( $line_items ),
			'subtotal'       => $subtotal,
			'advance_paid'   => $advance_paid,
			'balance_due'    => $subtotal - $advance_paid,
		) );

		$invoice = Infocus_ERP_CRUD::get( 'invoices', $invoice_id );
		return array( 'invoice_id' => $invoice_id, 'invoice_number' => $invoice['invoice_number'], 'link' => self::build_link( $token ) );
	}

	public static function build_link( $token ) {
		$page_url = get_option( 'infocus_erp_invoice_page_url', home_url( '/invoice/' ) );
		return add_query_arg( 'rid', $token, $page_url );
	}

	/**
	 * Creates a one-off custom invoice (not tied to a booking) — a customer,
	 * free-form line items, and any advance already paid. Used by the
	 * Invoices screen's "New custom invoice" form via REST.
	 */
	public static function create_custom_invoice( $customer_id, $raw_line_items, $advance_paid, $notes ) {
		if ( ! $customer_id || ! Infocus_ERP_CRUD::get( 'customers', $customer_id ) ) {
			return new WP_Error( 'bad_request', 'Please choose a customer.', array( 'status' => 400 ) );
		}

		$line_items = array();
		foreach ( $raw_line_items as $item ) {
			$desc   = sanitize_text_field( $item['description'] ?? '' );
			$amount = isset( $item['amount'] ) ? floatval( $item['amount'] ) : 0;
			if ( $desc === '' && $amount == 0 ) continue;
			$line_items[] = array( 'description' => $desc, 'amount' => $amount );
		}

		$subtotal = array_sum( array_column( $line_items, 'amount' ) );

		$invoice_id = Infocus_ERP_CRUD::insert( 'invoices', array(
			'invoice_number' => self::next_invoice_number(),
			'booking_id'     => null,
			'customer_id'    => $customer_id,
			'token'          => wp_generate_password( 40, false, false ),
			'invoice_date'   => current_time( 'Y-m-d' ),
			'line_items'     => wp_json_encode( $line_items ),
			'subtotal'       => $subtotal,
			'advance_paid'   => $advance_paid,
			'balance_due'    => $subtotal - $advance_paid,
			'notes'          => sanitize_textarea_field( $notes ),
		) );

		$invoice = Infocus_ERP_CRUD::get( 'invoices', $invoice_id );
		return array( 'invoice_id' => $invoice_id, 'invoice_number' => $invoice['invoice_number'], 'link' => self::build_link( $invoice['token'] ) );
	}

	private static function next_invoice_number() {
		$next = (int) get_option( 'infocus_erp_invoice_counter', 0 ) + 1;
		update_option( 'infocus_erp_invoice_counter', $next );
		return 'INV-' . str_pad( $next, 4, '0', STR_PAD_LEFT );
	}

	/* ------------------------------------------------------------------ */

	public static function render_invoice_page() {
		$token   = isset( $_GET['rid'] ) ? sanitize_text_field( $_GET['rid'] ) : '';
		$invoice = $token ? self::find_by_token( $token ) : null;

		if ( ! $invoice ) {
			return '<div style="max-width:500px;padding:20px;background:' . self::BRAND_BG . ';border-radius:10px;color:#4B0082;">This invoice link isn\'t valid. Please check the link, or ask for a new one.</div>';
		}

		$customer   = Infocus_ERP_CRUD::get( 'customers', $invoice['customer_id'] );
		$booking    = $invoice['booking_id'] ? Infocus_ERP_CRUD::get( 'bookings', $invoice['booking_id'] ) : null;
		$line_items = ! empty( $invoice['line_items'] ) ? json_decode( $invoice['line_items'], true ) : array();
		$logo_id    = get_option( 'infocus_erp_logo_id' );
		$logo_html  = $logo_id ? wp_get_attachment_image( $logo_id, 'medium', false, array( 'style' => 'max-height:56px;max-width:220px;object-fit:contain;' ) ) : '<div style="font-size:12px;letter-spacing:0.35em;color:' . self::BRAND_GOLD . ';font-weight:500;">INFOCUS</div>';

		ob_start();
		?>
		<style id="infocus-invoice-styles">
		.inv-page{background:<?php echo self::BRAND_NAVY; ?>;padding:32px 16px;border-radius:4px;}
		.inv-wrap{max-width:600px;margin:0 auto;background:<?php echo self::BRAND_BG; ?>;border:1px solid rgba(212,175,55,0.5);position:relative;font-family:inherit;}
		.inv-frame{position:absolute;top:8px;left:8px;right:8px;bottom:8px;border:0.5px solid rgba(212,175,55,0.35);pointer-events:none;}
		.inv-head{padding:48px 48px 28px;text-align:center;}
		.inv-tagline{font-family:Georgia,'Times New Roman',serif;font-style:italic;font-size:13px;color:<?php echo self::BRAND_SLATE; ?>;margin:14px 0 18px;}
		.inv-rule{width:48px;height:1px;background:<?php echo self::BRAND_GOLD; ?>;margin:0 auto 18px;}
		.inv-title{font-family:Georgia,'Times New Roman',serif;font-size:26px;color:<?php echo self::BRAND_NAVY; ?>;letter-spacing:0.02em;}
		.inv-meta{padding:0 48px 28px;display:flex;justify-content:space-between;font-size:12px;color:<?php echo self::BRAND_SLATE; ?>;}
		.inv-cols{padding:0 48px 32px;display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;}
		.inv-col{flex:1;min-width:200px;}
		.inv-col.right{text-align:right;}
		.inv-label{font-size:10px;letter-spacing:0.15em;color:<?php echo self::BRAND_GOLD; ?>;margin-bottom:8px;}
		.inv-name{font-family:Georgia,'Times New Roman',serif;font-size:17px;color:<?php echo self::BRAND_NAVY; ?>;margin-bottom:4px;}
		.inv-detail{font-size:12px;color:<?php echo self::BRAND_SLATE; ?>;line-height:1.7;}
		.inv-divider{margin:0 48px;border-top:0.5px solid rgba(212,175,55,0.4);}
		.inv-items{padding:28px 48px 8px;}
		.inv-item-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:0.5px solid rgba(107,122,143,0.15);}
		.inv-item-desc{font-size:13px;color:<?php echo self::BRAND_NAVY; ?>;padding-right:16px;}
		.inv-item-amt{font-family:Georgia,'Times New Roman',serif;font-size:13px;color:<?php echo self::BRAND_NAVY; ?>;white-space:nowrap;}
		.inv-totals-wrap{padding:16px 48px 8px;display:flex;justify-content:flex-end;}
		.inv-totals{width:230px;}
		.inv-totals-row{display:flex;justify-content:space-between;font-size:12px;color:<?php echo self::BRAND_SLATE; ?>;padding:4px 0;}
		.inv-totals-row.paid{color:<?php echo self::BRAND_TEAL; ?>;}
		.inv-due{border-top:1px solid <?php echo self::BRAND_GOLD; ?>;margin-top:8px;padding-top:10px;display:flex;justify-content:space-between;align-items:baseline;}
		.inv-due-label{font-size:11px;letter-spacing:0.1em;color:<?php echo self::BRAND_SLATE; ?>;}
		.inv-due-amt{font-family:Georgia,'Times New Roman',serif;font-size:26px;color:<?php echo self::BRAND_CRIMSON; ?>;}
		.inv-close{text-align:center;padding:36px 48px 44px;}
		.inv-close-line{font-family:Georgia,'Times New Roman',serif;font-style:italic;font-size:13px;color:<?php echo self::BRAND_SLATE; ?>;}
		.inv-actions{text-align:center;padding:0 0 24px;}
		.inv-btn{background:<?php echo self::BRAND_GOLD; ?>;border:1px solid <?php echo self::BRAND_GOLD; ?>;color:<?php echo self::BRAND_NAVY; ?>;border-radius:20px;padding:9px 20px;font-size:12px;font-weight:600;cursor:pointer;transition:background 0.2s ease,transform 0.2s ease;}
		.inv-btn:hover{background:#e8c968;transform:translateY(-1px);}
		.inv-btn:active{transform:translateY(0);}
		</style>
		<div class="inv-page">
		<div class="inv-wrap" id="infocus-invoice-content">
			<div class="inv-frame"></div>
			<div class="inv-head">
				<?php echo $logo_html; ?>
				<div class="inv-tagline">by The Lens of Maharana</div>
				<div class="inv-rule"></div>
				<div class="inv-title">Invoice</div>
			</div>
			<div class="inv-meta">
				<span>No. <?php echo esc_html( $invoice['invoice_number'] ); ?></span>
				<span><?php echo esc_html( date_i18n( 'j F Y', strtotime( $invoice['invoice_date'] ) ) ); ?></span>
			</div>
			<div class="inv-cols">
				<div class="inv-col">
					<div class="inv-label">PREPARED FOR</div>
					<div class="inv-name"><?php echo esc_html( $customer['name'] ?? '—' ); ?></div>
					<div class="inv-detail"><?php echo esc_html( $customer['phone'] ?? '' ); ?><br><?php echo esc_html( $customer['email'] ?? '' ); ?></div>
				</div>
				<?php if ( $booking ) : ?>
				<div class="inv-col right">
					<div class="inv-label">THE EXPERIENCE</div>
					<div class="inv-name"><?php echo esc_html( $booking['service_type'] ); ?></div>
					<div class="inv-detail"><?php echo esc_html( $booking['session_date'] ?: '—' ); ?><br><?php echo esc_html( $booking['location'] ); ?></div>
				</div>
				<?php endif; ?>
			</div>
			<div class="inv-divider"></div>
			<div class="inv-items">
				<?php foreach ( $line_items as $item ) : ?>
				<div class="inv-item-row">
					<span class="inv-item-desc"><?php echo esc_html( $item['description'] ); ?></span>
					<span class="inv-item-amt">₹<?php echo number_format( $item['amount'], 2 ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
			<div class="inv-totals-wrap">
				<div class="inv-totals">
					<div class="inv-totals-row"><span>Subtotal</span><span>₹<?php echo number_format( $invoice['subtotal'], 2 ); ?></span></div>
					<?php if ( $invoice['advance_paid'] > 0 ) : ?>
					<div class="inv-totals-row paid"><span>Advance received</span><span>−₹<?php echo number_format( $invoice['advance_paid'], 2 ); ?></span></div>
					<?php endif; ?>
					<div class="inv-due">
						<span class="inv-due-label">BALANCE DUE</span>
						<span class="inv-due-amt">₹<?php echo number_format( $invoice['balance_due'], 2 ); ?></span>
					</div>
				</div>
			</div>
			<?php if ( ! empty( $invoice['notes'] ) ) : ?><p style="padding:0 48px;font-size:12px;color:<?php echo self::BRAND_SLATE; ?>;"><?php echo esc_html( $invoice['notes'] ); ?></p><?php endif; ?>
			<div class="inv-close">
				<div class="inv-rule"></div>
				<div class="inv-close-line">It has been our privilege to capture your story.</div>
			</div>
		</div>
		<div class="inv-actions">
			<button class="inv-btn" id="infocus-print-invoice">Print / Save as PDF</button>
		</div>
		</div>
		<script>
		(function(){
			var btn = document.getElementById('infocus-print-invoice');
			if (!btn) return;
			btn.addEventListener('click', function(){
				var content = document.getElementById('infocus-invoice-content').outerHTML;
				var styles = document.getElementById('infocus-invoice-styles').outerHTML;
				var win = window.open('', '_blank', 'width=700,height=900');
				win.document.write('<!DOCTYPE html><html><head><title>Invoice <?php echo esc_js( $invoice['invoice_number'] ); ?></title>' + styles + '<style>body{margin:0;padding:24px;background:#fff;}</style></head><body>' + content + '</body></html>');
				win.document.close();
				win.focus();
				setTimeout(function(){ win.print(); }, 300);
			});
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	private static function find_by_token( $token ) {
		global $wpdb;
		$t = Infocus_ERP_DB::tables();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['invoices']} WHERE token = %s", $token ), ARRAY_A );
	}
}
