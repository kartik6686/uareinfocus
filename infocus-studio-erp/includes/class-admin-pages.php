<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the admin menu and renders every screen. One generic
 * list/add/edit renderer is reused for all six entities (configured below)
 * instead of writing six near-identical sets of screens.
 */
class Infocus_ERP_Admin_Pages {

	public static function entities() {
		return array(
			'bookings' => array(
				'label'   => 'Bookings',
				'singular'=> 'Booking',
				'columns' => array( 'id' => 'ID', 'customer_id' => 'Customer', 'service_type' => 'Service', 'session_date' => 'Date', 'status' => 'Status', 'package_price' => 'Price (₹)', 'included_edits' => 'Edits Incl.' ),
				'fields'  => array(
					'customer_id'    => array( 'label' => 'Customer', 'type' => 'ref', 'ref' => 'customers', 'required' => true ),
					'package_id'     => array( 'label' => 'Package (optional — auto-fills price & images below)', 'type' => 'ref', 'ref' => 'packages', 'required' => false ),
					'service_type'   => array( 'label' => 'Service Type', 'type' => 'select', 'options' => array( 'Maternity', 'Newborn', 'Kids', 'Family', 'Commercial', 'Corporate & Events', 'Other' ) ),
					'session_date'   => array( 'label' => 'Session Date', 'type' => 'date' ),
					'session_time'   => array( 'label' => 'Session Time', 'type' => 'time' ),
					'location'       => array( 'label' => 'Location', 'type' => 'text' ),
					'package_price'  => array( 'label' => 'Package Price (₹)', 'type' => 'number' ),
					'included_edits' => array( 'label' => 'Images Included in Package', 'type' => 'number' ),
					'status'         => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'Inquiry', 'Confirmed', 'Shot', 'Editing', 'Delivered', 'Cancelled' ) ),
					'notes'          => array( 'label' => 'Notes', 'type' => 'textarea' ),
				),
			),
			'customers' => array(
				'label'   => 'Customers',
				'singular'=> 'Customer',
				'columns' => array( 'id' => 'ID', 'name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'source' => 'Source' ),
				'fields'  => array(
					'name'   => array( 'label' => 'Full Name', 'type' => 'text', 'required' => true ),
					'phone'  => array( 'label' => 'Phone', 'type' => 'text' ),
					'email'  => array( 'label' => 'Email', 'type' => 'text' ),
					'source' => array( 'label' => 'Source', 'type' => 'select', 'options' => array( 'Instagram', 'Referral', 'Google', 'Walk-in', 'WhatsApp', 'Other' ) ),
					'notes'  => array( 'label' => 'Notes', 'type' => 'textarea' ),
				),
			),
			'payments' => array(
				'label'   => 'Payments',
				'singular'=> 'Payment',
				'columns' => array( 'id' => 'ID', 'booking_id' => 'Booking', 'amount' => 'Amount (₹)', 'payment_date' => 'Date', 'method' => 'Method', 'type' => 'Type' ),
				'fields'  => array(
					'booking_id'   => array( 'label' => 'Booking', 'type' => 'ref', 'ref' => 'bookings', 'required' => true ),
					'amount'       => array( 'label' => 'Amount (₹)', 'type' => 'number', 'required' => true ),
					'payment_date' => array( 'label' => 'Payment Date', 'type' => 'date' ),
					'method'       => array( 'label' => 'Method', 'type' => 'select', 'options' => array( 'Cash', 'UPI', 'Bank Transfer', 'Card', 'Other' ) ),
					'type'         => array( 'label' => 'Type', 'type' => 'select', 'options' => array( 'Advance', 'Balance', 'Full', 'Refund' ) ),
					'notes'        => array( 'label' => 'Notes', 'type' => 'textarea' ),
				),
			),
			'employees' => array(
				'label'   => 'Employees',
				'singular'=> 'Employee',
				'columns' => array( 'id' => 'ID', 'name' => 'Name', 'role' => 'Role', 'phone' => 'Phone', 'rate_amount' => 'Rate (₹)' ),
				'fields'  => array(
					'name'        => array( 'label' => 'Name', 'type' => 'text', 'required' => true ),
					'role'        => array( 'label' => 'Role', 'type' => 'select', 'options' => array( 'Editor', 'Second Shooter', 'Assistant', 'Other' ) ),
					'phone'       => array( 'label' => 'Phone', 'type' => 'text' ),
					'email'       => array( 'label' => 'Email', 'type' => 'text' ),
					'rate_type'   => array( 'label' => 'Rate Type', 'type' => 'select', 'options' => array( 'Per Project', 'Monthly', 'Hourly' ) ),
					'rate_amount' => array( 'label' => 'Rate Amount (₹)', 'type' => 'number' ),
					'notes'       => array( 'label' => 'Notes', 'type' => 'textarea' ),
				),
			),
			'pipeline' => array(
				'label'   => 'Editor Pipeline',
				'singular'=> 'Pipeline Item',
				'columns' => array( 'id' => 'ID', 'booking_id' => 'Booking', 'employee_id' => 'Assigned To', 'expected_date' => 'Expected', 'status' => 'Status' ),
				'fields'  => array(
					'booking_id'     => array( 'label' => 'Booking', 'type' => 'ref', 'ref' => 'bookings', 'required' => true ),
					'employee_id'    => array( 'label' => 'Assigned Editor', 'type' => 'ref', 'ref' => 'employees' ),
					'assigned_date'  => array( 'label' => 'Assigned Date', 'type' => 'date' ),
					'expected_date'  => array( 'label' => 'Expected Delivery', 'type' => 'date' ),
					'delivered_date' => array( 'label' => 'Actually Delivered', 'type' => 'date' ),
					'status'         => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'Not Started', 'In Progress', 'Delivered', 'Revision' ) ),
					'notes'          => array( 'label' => 'Notes', 'type' => 'textarea' ),
				),
			),
			'expenses' => array(
				'label'   => 'Expenses',
				'singular'=> 'Expense',
				'columns' => array( 'id' => 'ID', 'category' => 'Category', 'amount' => 'Amount (₹)', 'expense_date' => 'Date', 'vendor' => 'Vendor' ),
				'fields'  => array(
					'category'     => array( 'label' => 'Category', 'type' => 'select', 'options' => array( 'Gear', 'Props', 'Studio Rent', 'Editor Payout', 'Marketing/Ads', 'Travel', 'Software/Subscriptions', 'Other' ) ),
					'amount'       => array( 'label' => 'Amount (₹)', 'type' => 'number', 'required' => true ),
					'expense_date' => array( 'label' => 'Date', 'type' => 'date' ),
					'booking_id'   => array( 'label' => 'Linked Booking (optional)', 'type' => 'ref', 'ref' => 'bookings', 'required' => false ),
					'vendor'       => array( 'label' => 'Vendor / Paid To', 'type' => 'text' ),
					'notes'        => array( 'label' => 'Notes', 'type' => 'textarea' ),
				),
			),
			'packages' => array(
				'label'   => 'Packages',
				'singular'=> 'Package',
				'columns' => array( 'id' => 'ID', 'name' => 'Name', 'category' => 'Category', 'price' => 'Price (₹)', 'included_edits' => 'Images Included', 'is_popular' => 'Popular' ),
				'fields'  => array(
					'name'           => array( 'label' => 'Package Name', 'type' => 'text', 'required' => true ),
					'category'       => array( 'label' => 'Category', 'type' => 'select', 'options' => array( 'Maternity', 'Newborn', 'Kids', 'Combo', 'Milestone' ) ),
					'price'          => array( 'label' => 'Price (₹)', 'type' => 'number', 'required' => true ),
					'included_edits' => array( 'label' => 'Images Included', 'type' => 'number' ),
					'is_popular'     => array( 'label' => 'Mark as Popular (★)', 'type' => 'select', 'options' => array( 'No', 'Yes' ) ),
					'brochure_url'   => array( 'label' => 'Brochure PDF URL (optional — paste from Media Library)', 'type' => 'text' ),
					'description'    => array( 'label' => 'Description', 'type' => 'textarea' ),
				),
			),
		);
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_menu() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) return;

		add_menu_page( 'Studio ERP', 'Studio ERP', Infocus_ERP_Security::CAP, 'infocus-erp', array( __CLASS__, 'render_dashboard' ), 'dashicons-camera', 26 );
		add_submenu_page( 'infocus-erp', 'Dashboard', 'Dashboard', Infocus_ERP_Security::CAP, 'infocus-erp', array( __CLASS__, 'render_dashboard' ) );

		foreach ( self::entities() as $key => $cfg ) {
			add_submenu_page( 'infocus-erp', $cfg['label'], $cfg['label'], Infocus_ERP_Security::CAP, 'infocus-erp-' . $key, function () use ( $key ) {
				self::render_entity_screen( $key );
			} );
		}

		add_submenu_page( 'infocus-erp', 'Inquiries', 'Inquiries', Infocus_ERP_Security::CAP, 'infocus-erp-inquiries', array( __CLASS__, 'render_inquiries' ) );
		add_submenu_page( 'infocus-erp', 'Shoot Requirements', 'Shoot Requirements', Infocus_ERP_Security::CAP, 'infocus-erp-requirements', array( __CLASS__, 'render_requirements_list' ) );
		add_submenu_page( 'infocus-erp', 'Image Selections', 'Image Selections', Infocus_ERP_Security::CAP, 'infocus-erp-image-selections', array( __CLASS__, 'render_image_selections_list' ) );
		add_submenu_page( 'infocus-erp', 'Invoices', 'Invoices', Infocus_ERP_Security::CAP, 'infocus-erp-invoices', array( __CLASS__, 'render_invoices' ) );
		add_submenu_page( 'infocus-erp', 'Export / Backup', 'Export / Backup', Infocus_ERP_Security::CAP, 'infocus-erp-export', array( __CLASS__, 'render_export' ) );
		add_submenu_page( 'infocus-erp', 'Settings (Claude Connection)', 'Settings', Infocus_ERP_Security::CAP, 'infocus-erp-settings', array( __CLASS__, 'render_settings' ) );
	}

	/* ---------------------------------------------------------------- */

	public static function render_entity_screen( $entity ) {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		$config  = self::entities()[ $entity ];
		$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
		$view    = ( $edit_id || ( isset( $_GET['action'] ) && $_GET['action'] === 'add' ) ) ? 'form' : 'list';

		$prefill = array();
		foreach ( $config['fields'] as $field => $def ) {
			if ( isset( $_GET[ 'prefill_' . $field ] ) ) {
				$prefill[ $field ] = sanitize_text_field( wp_unslash( $_GET[ 'prefill_' . $field ] ) );
			}
		}

		wp_localize_script( 'infocus-erp-entity-app', 'infocusErpEntity', array(
			'entity'   => $entity,
			'label'    => $config['label'],
			'singular' => $config['singular'],
			'columns'  => $config['columns'],
			'fields'   => $config['fields'],
			'view'     => $view,
			'editId'   => $edit_id,
			'prefill'  => $prefill,
			'adminUrl' => admin_url( '/' ),
			'userName' => wp_get_current_user()->display_name,
		) );
		?>
		<div id="infocus-erp-app" style="margin-left:-20px;"></div>
		<?php
	}

	/* ---------------------------------------------------------------- */

	public static function render_dashboard() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		?>
		<div id="infocus-erp-app" style="margin-left:-20px;"></div>
		<?php
	}


	/* ---------------------------------------------------------------- */

	public static function render_export() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		?>
		<div class="wrap infocus-erp-wrap">
			<h1>Export / Backup</h1>
			<p>Download your data any time. These files are plain CSV — they open in Excel, Google Sheets, or any spreadsheet tool, and stay usable even if this website or plugin is removed.</p>

			<h2>Full Backup</h2>
			<p><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_export_all' ), 'infocus_erp_export' ) ); ?>">Download Everything (ZIP of all CSVs)</a></p>

			<h2>Individual Sections</h2>
			<p>
			<?php foreach ( self::entities() as $key => $cfg ) : ?>
				<a class="button" style="margin:0 6px 6px 0;" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_export_csv&entity=' . $key ), 'infocus_erp_export' ) ); ?>"><?php echo esc_html( $cfg['label'] ); ?> CSV</a>
			<?php endforeach; ?>
			<a class="button" style="margin:0 6px 6px 0;" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_export_csv&entity=inquiries' ), 'infocus_erp_export' ) ); ?>">Inquiries CSV</a>
			<a class="button" style="margin:0 6px 6px 0;" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_export_csv&entity=shoot_requirements' ), 'infocus_erp_export' ) ); ?>">Shoot Requirements CSV</a>
			<a class="button" style="margin:0 6px 6px 0;" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_export_csv&entity=image_selections' ), 'infocus_erp_export' ) ); ?>">Image Selections CSV</a>
			<a class="button" style="margin:0 6px 6px 0;" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_export_csv&entity=invoices' ), 'infocus_erp_export' ) ); ?>">Invoices CSV</a>
			</p>
			<p><em>Tip: schedule a monthly reminder to download the full backup — it takes one click.</em></p>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */

	public static function render_settings() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );

		if ( isset( $_POST['infocus_erp_regenerate_key'] ) && check_admin_referer( 'infocus_erp_settings' ) ) {
			Infocus_ERP_Security::regenerate_api_key();
			echo '<div class="notice notice-success"><p>New API key generated.</p></div>';
		}

		if ( isset( $_POST['infocus_erp_regenerate_mcp'] ) && check_admin_referer( 'infocus_erp_settings' ) ) {
			Infocus_ERP_Security::regenerate_mcp_token();
			echo '<div class="notice notice-success"><p>New connector URL generated. Reconnect in Claude with the URL below.</p></div>';
		}

		if ( isset( $_POST['infocus_erp_save_page_urls'] ) && check_admin_referer( 'infocus_erp_settings' ) ) {
			update_option( 'infocus_erp_requirements_page_url', esc_url_raw( $_POST['infocus_erp_requirements_page_url'] ?? '' ) );
			update_option( 'infocus_erp_image_selection_page_url', esc_url_raw( $_POST['infocus_erp_image_selection_page_url'] ?? '' ) );
			update_option( 'infocus_erp_invoice_page_url', esc_url_raw( $_POST['infocus_erp_invoice_page_url'] ?? '' ) );
			echo '<div class="notice notice-success"><p>Saved.</p></div>';
		}

		if ( isset( $_POST['infocus_erp_save_logo'] ) && check_admin_referer( 'infocus_erp_settings' ) ) {
			update_option( 'infocus_erp_logo_id', (int) ( $_POST['infocus_erp_logo_id'] ?? 0 ) );
			echo '<div class="notice notice-success"><p>Logo saved.</p></div>';
		}

		if ( isset( $_POST['infocus_erp_save_lock_hours'] ) && check_admin_referer( 'infocus_erp_settings' ) ) {
			$hours = max( 0.1, (float) ( $_POST['infocus_erp_lock_after_hours'] ?? 4 ) );
			update_option( 'infocus_erp_lock_after_hours', $hours );
			echo '<div class="notice notice-success"><p>Saved.</p></div>';
		}

		if ( isset( $_POST['infocus_erp_save_booking_settings'] ) && check_admin_referer( 'infocus_erp_settings' ) ) {
			update_option( 'infocus_erp_booking_page_url', esc_url_raw( $_POST['infocus_erp_booking_page_url'] ?? '' ) );
			$capacity = max( 1, (int) ( $_POST['infocus_erp_max_bookings_per_slot'] ?? 1 ) );
			update_option( 'infocus_erp_max_bookings_per_slot', $capacity );
			echo '<div class="notice notice-success"><p>Saved.</p></div>';
		}

		wp_enqueue_media();

		$key      = Infocus_ERP_Security::get_api_key();
		$rest_url = get_rest_url( null, Infocus_ERP_REST_API::NS );
		$mcp_url  = Infocus_ERP_Security::get_mcp_url();
		?>
		<div class="wrap infocus-erp-wrap">
			<h1>Settings — Claude Connection</h1>
			<p>Two ways Claude can work with this data: the MCP connector (recommended — one URL, paste it into Claude and you're done) or the raw REST API (for building something custom).</p>

			<h2>MCP Connector (recommended)</h2>
			<p>Paste this single URL into Claude → Settings → Connectors → Add custom connector. The token is built into the URL, so there's no separate login step.</p>
			<table class="form-table">
				<tr><th>Connector URL</th><td><input type="text" readonly value="<?php echo esc_attr( $mcp_url ); ?>" style="width:70%;" onclick="this.select();"></td></tr>
			</table>
			<form method="post">
				<?php wp_nonce_field( 'infocus_erp_settings' ); ?>
				<p><button type="submit" name="infocus_erp_regenerate_mcp" class="button" onclick="return confirm('This will invalidate the old connector URL immediately — you will need to reconnect in Claude. Continue?');">Regenerate Connector URL</button></p>
			</form>

			<h2>Raw REST API (for custom integrations)</h2>
			<table class="form-table">
				<tr><th>REST Base URL</th><td><code><?php echo esc_html( $rest_url ); ?></code></td></tr>
				<tr><th>API Key</th><td><code><?php echo esc_html( $key ); ?></code></td></tr>
				<tr><th>Auth Header</th><td><code>X-Infocus-Api-Key: <?php echo esc_html( $key ); ?></code></td></tr>
			</table>

			<form method="post">
				<?php wp_nonce_field( 'infocus_erp_settings' ); ?>
				<p><button type="submit" name="infocus_erp_regenerate_key" class="button" onclick="return confirm('This will invalidate the old key immediately. Continue?');">Regenerate API Key</button></p>
			</form>

			<h2>Available Endpoints</h2>
			<ul style="list-style:disc;padding-left:20px;">
				<li><code>GET /summary</code> — dashboard numbers (revenue, expenses, outstanding, pipeline, overdue, upcoming)</li>
				<li><code>GET/POST /bookings</code>, <code>/customers</code>, <code>/payments</code>, <code>/employees</code>, <code>/pipeline</code>, <code>/expenses</code></li>
				<li><code>GET /inquiries</code> — read-only; inquiries are created by the public form, not the API</li>
				<li><code>GET/PUT /{entity}/{id}</code> — a single record</li>
				<li><code>POST /quick-expense</code> — log an expense from a plain description; category is auto-detected (e.g. "4000 on instagram ads" → Marketing/Ads)</li>
				<li><code>POST /quick-payment</code> — log a payment by customer name; the right booking is found automatically, or you're asked to confirm if it's ambiguous</li>
				<li><code>POST /quick-booking</code> — create a new booking (and the customer, if they're new) plus an optional advance payment, all in one call</li>
			</ul>

			<h2>Shortcode Pages</h2>
			<p>Set the URLs where you've placed each shortcode, so links generated elsewhere in the ERP point to the right place.</p>
			<h2>Invoice Logo</h2>
			<p>Shown at the top of every invoice. Upload once here — change it any time, no coding needed.</p>
			<form method="post">
				<?php wp_nonce_field( 'infocus_erp_settings' ); ?>
				<div id="infocus-logo-preview" style="margin-bottom:10px;">
					<?php
					$logo_id = get_option( 'infocus_erp_logo_id' );
					echo $logo_id ? wp_get_attachment_image( $logo_id, 'medium', false, array( 'style' => 'max-height:60px;max-width:220px;object-fit:contain;display:block;' ) ) : '<em>No logo set yet.</em>';
					?>
				</div>
				<input type="hidden" name="infocus_erp_logo_id" id="infocus-logo-id" value="<?php echo (int) get_option( 'infocus_erp_logo_id' ); ?>">
				<p><button type="button" id="infocus-choose-logo" class="button">Choose Logo</button></p>
				<p><button type="submit" name="infocus_erp_save_logo" class="button button-primary">Save Logo</button></p>
			</form>
			<script>(function(){
				var chooseBtn=document.getElementById('infocus-choose-logo');
				if(!chooseBtn) return;
				var frame;
				chooseBtn.addEventListener('click', function(e){
					e.preventDefault();
					if(frame){ frame.open(); return; }
					frame = wp.media({ title: 'Select your logo', button: { text: 'Use this logo' }, multiple: false });
					frame.on('select', function(){
						var attachment = frame.state().get('selection').first().toJSON();
						document.getElementById('infocus-logo-id').value = attachment.id;
						document.getElementById('infocus-logo-preview').innerHTML = '<img src="'+attachment.url+'" style="max-height:60px;max-width:220px;object-fit:contain;display:block;">';
					});
					frame.open();
				});
			})();</script>

			<h2>Image Selection Locking</h2>
			<p>Once a client hasn't touched their image selection for this many hours since their last submission, it locks automatically — no more edits until you unlock it. Set to a small number for a tighter window, or larger if you want to give clients more time to reconsider.</p>
			<form method="post">
				<?php wp_nonce_field( 'infocus_erp_settings' ); ?>
				<table class="form-table">
					<tr><th><label for="infocus_lock_hours">Lock after (hours)</label></th>
						<td><input type="number" step="0.5" min="0.5" id="infocus_lock_hours" name="infocus_erp_lock_after_hours" value="<?php echo esc_attr( get_option( 'infocus_erp_lock_after_hours', 4 ) ); ?>" style="width:100px;"></td></tr>
				</table>
				<p><button type="submit" name="infocus_erp_save_lock_hours" class="button button-primary">Save</button></p>
			</form>
			<p>You can also lock or unlock any individual client's selection manually any time from <strong>Studio ERP → Image Selections</strong>, regardless of this setting.</p>

			<h2>Shortcode Pages</h2>
			<p>Set the URLs where you've placed each shortcode, so links generated elsewhere in the ERP point to the right place. Each shortcode is shown next to its field — paste it into an Elementor Shortcode widget on the matching page.</p>
			<form method="post">
				<?php wp_nonce_field( 'infocus_erp_settings' ); ?>
				<table class="form-table">
					<tr><th><label for="infocus_inquiry_url">Public Inquiry Form</label><br><code>[infocus_inquiry_form]</code></th>
						<td>Set up on your Contact page — no URL needed to be saved here, the shortcode works wherever you paste it.</td></tr>
					<tr><th><label for="infocus_req_url">Shoot Requirements page URL</label><br><code>[infocus_shoot_requirements]</code></th>
						<td><input type="text" id="infocus_req_url" name="infocus_erp_requirements_page_url" value="<?php echo esc_attr( get_option( 'infocus_erp_requirements_page_url', home_url( '/shoot-consultation/' ) ) ); ?>" style="width:70%;"></td></tr>
					<tr><th><label for="infocus_img_url">Image Selection page URL</label><br><code>[infocus_image_selection]</code></th>
						<td><input type="text" id="infocus_img_url" name="infocus_erp_image_selection_page_url" value="<?php echo esc_attr( get_option( 'infocus_erp_image_selection_page_url', home_url( '/select-images/' ) ) ); ?>" style="width:70%;"></td></tr>
					<tr><th><label for="infocus_inv_url">Invoice page URL</label><br><code>[infocus_invoice]</code></th>
						<td><input type="text" id="infocus_inv_url" name="infocus_erp_invoice_page_url" value="<?php echo esc_attr( get_option( 'infocus_erp_invoice_page_url', home_url( '/invoice/' ) ) ); ?>" style="width:70%;"></td></tr>
				</table>
				<p><button type="submit" name="infocus_erp_save_page_urls" class="button button-primary">Save</button></p>
			</form>

			<h2>Booking Calendar</h2>
			<p>Shortcode: <code>[infocus_book_session]</code>. Set the page it lives on, and how many confirmed bookings each Morning/Post-Lunch slot can hold per day before it shows as fully booked. Extra requests beyond this number are still logged (as "Request Only") for you to review and renegotiate manually — they never auto-confirm.</p>
			<form method="post">
				<?php wp_nonce_field( 'infocus_erp_settings' ); ?>
				<table class="form-table">
					<tr><th><label for="infocus_booking_url">Booking page URL</label></th>
						<td><input type="text" id="infocus_booking_url" name="infocus_erp_booking_page_url" value="<?php echo esc_attr( get_option( 'infocus_erp_booking_page_url', home_url( '/book-a-session/' ) ) ); ?>" style="width:70%;"></td></tr>
					<tr><th><label for="infocus_max_per_slot">Max confirmed bookings per slot</label></th>
						<td><input type="number" min="1" step="1" id="infocus_max_per_slot" name="infocus_erp_max_bookings_per_slot" value="<?php echo esc_attr( get_option( 'infocus_erp_max_bookings_per_slot', 1 ) ); ?>" style="width:100px;">
						<p class="description">Default is 1 (one session per Morning or Post-Lunch slot). Raise this on days you've got extra help covering the studio.</p></td></tr>
				</table>
				<p><button type="submit" name="infocus_erp_save_booking_settings" class="button button-primary">Save</button></p>
			</form>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */

	public static function render_inquiries() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		?>
		<div id="infocus-erp-app" data-screen="inquiries" style="margin-left:-20px;"></div>
		<?php
	}


	public static function render_requirements_list() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		?>
		<div id="infocus-erp-app" data-screen="requirements" style="margin-left:-20px;"></div>
		<?php
	}

	public static function render_image_selections_list() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		?>
		<div id="infocus-erp-app" data-screen="image-selections" style="margin-left:-20px;"></div>
		<?php
	}

	public static function render_invoices() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		?>
		<div id="infocus-erp-app" data-screen="invoices" style="margin-left:-20px;"></div>
		<?php
	}
}
