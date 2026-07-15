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
					'service_type'   => array( 'label' => 'Service Type', 'type' => 'select', 'options' => array( 'Maternity', 'Newborn', 'Kids', 'Family', 'Wedding', 'Commercial', 'Corporate & Events', 'Other' ) ),
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
		add_action( 'admin_init', array( __CLASS__, 'handle_form_submissions' ) );
		add_action( 'admin_post_infocus_erp_update_inquiry_status', array( __CLASS__, 'handle_inquiry_status_update' ) );
		add_action( 'admin_post_infocus_erp_approve_inquiry', array( __CLASS__, 'handle_approve_inquiry' ) );
		add_action( 'admin_post_infocus_erp_reject_inquiry', array( __CLASS__, 'handle_reject_inquiry' ) );
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

	public static function handle_form_submissions() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) return;
		if ( ! isset( $_POST['infocus_erp_action'] ) ) return;
		check_admin_referer( 'infocus_erp_save' );

		$entity = sanitize_key( $_POST['entity'] );
		$config = self::entities();
		if ( ! isset( $config[ $entity ] ) ) return;

		if ( $_POST['infocus_erp_action'] === 'delete' ) {
			Infocus_ERP_CRUD::delete( $entity, (int) $_POST['id'] );
			wp_safe_redirect( admin_url( 'admin.php?page=infocus-erp-' . $entity . '&deleted=1' ) );
			exit;
		}

		$data = array();
		foreach ( $config[ $entity ]['fields'] as $field => $def ) {
			$value = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
			if ( $def['type'] === 'textarea' ) {
				$data[ $field ] = sanitize_textarea_field( $value );
			} elseif ( $def['type'] === 'number' ) {
				$data[ $field ] = $value === '' ? 0 : floatval( $value );
			} elseif ( $def['type'] === 'ref' ) {
				$data[ $field ] = $value === '' ? null : (int) $value;
			} else {
				$data[ $field ] = sanitize_text_field( $value );
			}
		}

		if ( $_POST['infocus_erp_action'] === 'create' ) {
			Infocus_ERP_CRUD::insert( $entity, $data );
			wp_safe_redirect( admin_url( 'admin.php?page=infocus-erp-' . $entity . '&created=1' ) );
		} else {
			Infocus_ERP_CRUD::update( $entity, (int) $_POST['id'], $data );
			wp_safe_redirect( admin_url( 'admin.php?page=infocus-erp-' . $entity . '&updated=1' ) );
		}
		exit;
	}

	/* ---------------------------------------------------------------- */

	private static function ref_label( $ref_entity, $id ) {
		if ( ! $id ) return '—';
		$row = Infocus_ERP_CRUD::get( $ref_entity, $id );
		if ( ! $row ) return '#' . $id;
		if ( $ref_entity === 'bookings' ) {
			$c = Infocus_ERP_CRUD::get( 'customers', $row['customer_id'] );
			$parts = array();
			$parts[] = $c['name'] ?? 'Unknown client';
			if ( ! empty( $c['phone'] ) ) $parts[] = $c['phone'];
			$parts[] = $row['service_type'];
			if ( ! empty( $row['session_date'] ) ) $parts[] = $row['session_date'];
			return esc_html( implode( ' — ', array_filter( $parts ) ) );
		}
		if ( isset( $row['name'] ) ) return esc_html( $row['name'] );
		return '#' . $id;
	}

	public static function render_entity_screen( $entity ) {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		$config = self::entities()[ $entity ];
		$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
		$editing = $edit_id ? Infocus_ERP_CRUD::get( $entity, $edit_id ) : null;

		echo '<div class="wrap infocus-erp-wrap">';
		echo '<h1>' . esc_html( $config['label'] ) . '</h1>';

		if ( isset( $_GET['created'] ) ) echo '<div class="notice notice-success"><p>Saved.</p></div>';
		if ( isset( $_GET['updated'] ) ) echo '<div class="notice notice-success"><p>Updated.</p></div>';
		if ( isset( $_GET['deleted'] ) ) echo '<div class="notice notice-success"><p>Deleted.</p></div>';

		if ( $entity === 'bookings' && ! empty( $_GET['link_generated'] ) ) {
			$row = Infocus_ERP_CRUD::get( 'shoot_requirements', (int) $_GET['link_generated'] );
			if ( $row ) {
				$page_url = get_option( 'infocus_erp_requirements_page_url', home_url( '/shoot-consultation/' ) );
				$link     = add_query_arg( 'rid', $row['token'], $page_url );
				echo '<div class="notice notice-success"><p><strong>Requirements link ready:</strong> <input type="text" readonly value="' . esc_attr( $link ) . '" style="width:60%;" onclick="this.select();"> — copy this and send it to the client.</p></div>';
			}
		}

		if ( $entity === 'bookings' && ! empty( $_GET['image_link_generated'] ) ) {
			$row = Infocus_ERP_CRUD::get( 'image_selections', (int) $_GET['image_link_generated'] );
			if ( $row ) {
				$page_url = get_option( 'infocus_erp_image_selection_page_url', home_url( '/select-images/' ) );
				$link     = add_query_arg( 'rid', $row['token'], $page_url );
				echo '<div class="notice notice-success"><p><strong>Image selection link ready:</strong> <input type="text" readonly value="' . esc_attr( $link ) . '" style="width:60%;" onclick="this.select();"> — copy this and send it to the client.</p></div>';
			}
		}

		echo '<div class="infocus-erp-columns">';

		// ---- Form ----
		echo '<div class="infocus-erp-form-panel"><h2>' . ( $editing ? 'Edit ' . esc_html( $config['singular'] ) : 'Add New ' . esc_html( $config['singular'] ) ) . '</h2>';
		echo '<form method="post">';
		wp_nonce_field( 'infocus_erp_save' );
		echo '<input type="hidden" name="entity" value="' . esc_attr( $entity ) . '">';
		echo '<input type="hidden" name="infocus_erp_action" value="' . ( $editing ? 'update' : 'create' ) . '">';
		if ( $editing ) echo '<input type="hidden" name="id" value="' . (int) $editing['id'] . '">';

		foreach ( $config['fields'] as $field => $def ) {
			$val = $editing[ $field ] ?? ( isset( $_GET[ 'prefill_' . $field ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'prefill_' . $field ] ) ) : '' );
			echo '<p class="infocus-field"><label>' . esc_html( $def['label'] ) . ( ! empty( $def['required'] ) ? ' *' : '' ) . '</label>';

			if ( $def['type'] === 'select' ) {
				echo '<select name="' . esc_attr( $field ) . '">';
				echo '<option value="">— Select —</option>';
				foreach ( $def['options'] as $opt ) {
					echo '<option value="' . esc_attr( $opt ) . '"' . selected( $val, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
				}
				echo '</select>';
			} elseif ( $def['type'] === 'ref' ) {
				$rows = Infocus_ERP_CRUD::get_all( $def['ref'], array( 'orderby' => 'id', 'order' => 'DESC' ) );
				echo '<select name="' . esc_attr( $field ) . '"' . ( $def['ref'] === 'packages' ? ' id="infocus-package-select"' : '' ) . '>';
				echo '<option value="">— Select —</option>';
				foreach ( $rows as $r ) {
					if ( $def['ref'] === 'bookings' ) {
						$c     = Infocus_ERP_CRUD::get( 'customers', $r['customer_id'] );
						$label = trim( implode( ' — ', array_filter( array( $c['name'] ?? 'Unknown client', $c['phone'] ?? '', $r['service_type'], $r['session_date'] ?? '' ) ) ) );
					} else {
						$label = $r['name'] ?? ( 'Booking #' . $r['id'] );
					}
					$extra_attrs = '';
					if ( $def['ref'] === 'packages' ) {
						$extra_attrs = ' data-price="' . esc_attr( $r['price'] ) . '" data-images="' . esc_attr( $r['included_edits'] ) . '"';
					}
					echo '<option value="' . (int) $r['id'] . '"' . selected( $val, $r['id'], false ) . $extra_attrs . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
			} elseif ( $def['type'] === 'textarea' ) {
				echo '<textarea name="' . esc_attr( $field ) . '" rows="3">' . esc_textarea( $val ) . '</textarea>';
			} else {
				$type = $def['type'] === 'number' ? 'number' : ( $def['type'] === 'date' ? 'date' : ( $def['type'] === 'time' ? 'time' : 'text' ) );
				$step = $def['type'] === 'number' ? ' step="0.01"' : '';
				echo '<input type="' . esc_attr( $type ) . '" id="infocus-field-' . esc_attr( $field ) . '" name="' . esc_attr( $field ) . '" value="' . esc_attr( $val ) . '"' . $step . '>';
			}
			echo '</p>';
		}

		echo '<p><button type="submit" class="button button-primary">' . ( $editing ? 'Update' : 'Save' ) . '</button>';
		if ( $editing ) echo ' <a href="' . esc_url( admin_url( 'admin.php?page=infocus-erp-' . $entity ) ) . '" class="button">Cancel</a>';
		echo '</p></form></div>';

		if ( $entity === 'bookings' ) {
			echo '<script>(function(){
var sel=document.getElementById("infocus-package-select");
var price=document.getElementById("infocus-field-package_price");
var images=document.getElementById("infocus-field-included_edits");
if(sel&&price&&images){
  sel.addEventListener("change",function(){
    var opt=sel.options[sel.selectedIndex];
    if(opt&&opt.value){
      if(opt.dataset.price!==undefined) price.value=opt.dataset.price;
      if(opt.dataset.images!==undefined) images.value=opt.dataset.images;
    }
  });
}
})();</script>';
		}

		// ---- List ----
		echo '<div class="infocus-erp-list-panel">';
		$rows = Infocus_ERP_CRUD::get_all( $entity, array( 'orderby' => 'id', 'order' => 'DESC' ) );
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
		foreach ( $config['columns'] as $label ) echo '<th>' . esc_html( $label ) . '</th>';
		if ( $entity === 'pipeline' ) echo '<th>Timeline</th>';
		echo '<th>Actions</th></tr></thead><tbody>';

		if ( empty( $rows ) ) {
			echo '<tr><td colspan="' . ( count( $config['columns'] ) + 1 + ( $entity === 'pipeline' ? 1 : 0 ) ) . '">No records yet.</td></tr>';
		}

		foreach ( $rows as $row ) {
			echo '<tr>';
			foreach ( array_keys( $config['columns'] ) as $col ) {
				$def = $config['fields'][ $col ] ?? null;
				$v   = $row[ $col ] ?? '';
				if ( $col === 'id' ) {
					echo '<td>#' . (int) $v . '</td>';
				} elseif ( $def && $def['type'] === 'ref' ) {
					echo '<td>' . self::ref_label( $def['ref'], $v ) . '</td>';
				} else {
					echo '<td>' . esc_html( $v ) . '</td>';
				}
			}
			if ( $entity === 'pipeline' ) {
				echo '<td>' . self::pipeline_timeline_cell( $row ) . '</td>';
			}
			echo '<td>';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=infocus-erp-' . $entity . '&edit=' . $row['id'] ) ) . '">Edit</a> | ';
			if ( $entity === 'bookings' ) {
				echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_generate_requirements_link&booking_id=' . $row['id'] ), 'infocus_erp_gen_link' ) ) . '">Requirements Link</a> | ';
				echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_generate_image_link&booking_id=' . $row['id'] ), 'infocus_erp_gen_link' ) ) . '">Image Selection Link</a> | ';
				echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_generate_invoice&booking_id=' . $row['id'] ), 'infocus_erp_gen_link' ) ) . '">Generate Invoice</a> | ';
			}
			echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Delete this record?\');">';
			wp_nonce_field( 'infocus_erp_save' );
			echo '<input type="hidden" name="entity" value="' . esc_attr( $entity ) . '">';
			echo '<input type="hidden" name="infocus_erp_action" value="delete">';
			echo '<input type="hidden" name="id" value="' . (int) $row['id'] . '">';
			echo '<button type="submit" class="button-link-delete">Delete</button>';
			echo '</form>';
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
		echo '</div></div>';
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
		$rows = Infocus_ERP_CRUD::get_all( 'inquiries', array( 'orderby' => 'id', 'order' => 'DESC' ) );
		?>
		<div class="wrap infocus-erp-wrap">
			<h1>Inquiries</h1>
			<p>Shortcode for this form: <code>[infocus_inquiry_form]</code>. Submissions from the public inquiry form on your website. Brand-new leads land here as <strong>Pending Review</strong> — approve them to add them to your customer list, or reject to discard. Existing clients (matched by phone/email) skip straight to the normal workflow.</p>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Service</th><th>Client</th><th>Quality</th><th>Status</th><th>Received</th><th>Actions</th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="9">No inquiries yet.</td></tr>
				<?php else : foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['name'] ); ?></td>
						<td><?php echo esc_html( $row['phone'] ); ?></td>
						<td><?php echo esc_html( $row['email'] ); ?></td>
						<td><?php echo esc_html( $row['service_type'] ); ?></td>
						<td><?php echo $row['is_new_client'] ? '<span style="color:#2271b1;font-weight:600;">New client</span>' : '<span style="color:#646970;">Existing client</span>'; ?></td>
						<td>
							<?php
							$flag = $row['quality_flag'];
							$color = $flag === 'Likely genuine' ? '#1A6B6B' : ( $flag === 'Looks suspicious' ? '#9A1F1F' : ( $flag ? '#D4AF37' : '#646970' ) );
							echo $flag ? '<span style="color:' . esc_attr( $color ) . ';font-weight:600;">' . esc_html( $flag ) . '</span>' : '—';
							?>
						</td>
						<td>
							<?php if ( $row['status'] === 'Pending Review' ) : ?>
								<span style="font-weight:600;color:#9A1F1F;">Pending Review</span>
							<?php else : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:6px;">
									<?php wp_nonce_field( 'infocus_erp_inquiry_status' ); ?>
									<input type="hidden" name="action" value="infocus_erp_update_inquiry_status">
									<input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
									<select name="status" onchange="this.form.submit()">
										<?php foreach ( array( 'New', 'Contacted', 'Converted' ) as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $row['status'], $opt ); ?>><?php echo esc_html( $opt ); ?></option>
										<?php endforeach; ?>
									</select>
								</form>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row['created_at'] ); ?></td>
						<td>
							<?php if ( $row['status'] === 'Pending Review' ) : ?>
								<a class="button button-small button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_approve_inquiry&id=' . $row['id'] ), 'infocus_erp_inquiry_approval' ) ); ?>">Approve</a>
								<a class="button button-small" style="color:#9A1F1F;" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_reject_inquiry&id=' . $row['id'] ), 'infocus_erp_inquiry_approval' ) ); ?>" onclick="return confirm('Reject and permanently delete this inquiry? This cannot be undone.');">Reject</a>
							<?php else : ?>
								<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=infocus-erp-bookings&prefill_customer_id=' . (int) $row['matched_customer_id'] . '&prefill_service_type=' . rawurlencode( $row['service_type'] ) . '&prefill_notes=' . rawurlencode( trim( $row['additional_requirements'] . ' ' . $row['message'] ) ) ) ); ?>">Convert to booking</a>
							<?php endif; ?>
							<?php if ( ! empty( $row['phone'] ) ) :
								$package = Infocus_ERP_Public_Forms::find_matching_package( $row['service_type'] );
								$wa_message = "Hi " . $row['name'] . "! Thanks for your interest in our " . $row['service_type'] . " package." . ( $package && ! empty( $package['brochure_url'] ) ? " Here's our full package guide: " . $package['brochure_url'] : '' );
								$wa_link = Infocus_ERP_Public_Forms::build_whatsapp_link( $row['phone'], $wa_message );
							?>
								<a class="button button-small" style="color:#1A6B6B;" href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function handle_inquiry_status_update() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		check_admin_referer( 'infocus_erp_inquiry_status' );
		Infocus_ERP_CRUD::update( 'inquiries', (int) $_POST['id'], array( 'status' => sanitize_text_field( $_POST['status'] ) ) );
		wp_safe_redirect( admin_url( 'admin.php?page=infocus-erp-inquiries' ) );
		exit;
	}

	public static function handle_approve_inquiry() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		check_admin_referer( 'infocus_erp_inquiry_approval' );
		Infocus_ERP_Public_Forms::approve_inquiry( (int) $_GET['id'] );
		wp_safe_redirect( admin_url( 'admin.php?page=infocus-erp-inquiries' ) );
		exit;
	}

	public static function handle_reject_inquiry() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		check_admin_referer( 'infocus_erp_inquiry_approval' );
		Infocus_ERP_Public_Forms::reject_inquiry( (int) $_GET['id'] );
		wp_safe_redirect( admin_url( 'admin.php?page=infocus-erp-inquiries' ) );
		exit;
	}

	public static function render_requirements_list() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		$rows     = Infocus_ERP_CRUD::get_all( 'shoot_requirements', array( 'orderby' => 'id', 'order' => 'DESC' ) );
		$page_url = get_option( 'infocus_erp_requirements_page_url', home_url( '/shoot-consultation/' ) );
		?>
		<div class="wrap infocus-erp-wrap">
			<h1>Shoot Requirements</h1>
			<p>Shortcode for this form: <code>[infocus_shoot_requirements]</code>. Links are generated from the Bookings screen ("Requirements Link"). Each link is unique to one booking and only that client can use it.</p>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Booking</th><th>Preferred time</th><th>Theme</th><th>Outfit</th><th>Location</th><th>Reference links</th><th>Notes</th><th>Status</th><th>Link</th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="9">No requirements links generated yet.</td></tr>
				<?php else : foreach ( $rows as $row ) :
					$links = ! empty( $row['reference_links'] ) ? json_decode( $row['reference_links'], true ) : array();
					$link  = add_query_arg( 'rid', $row['token'], $page_url );
				?>
					<tr>
						<td><?php echo self::ref_label( 'bookings', $row['booking_id'] ); ?></td>
						<td><?php echo esc_html( $row['preferred_time'] ); ?></td>
						<td><?php echo esc_html( $row['theme'] ); ?></td>
						<td><?php echo esc_html( $row['outfit'] ); ?></td>
						<td><?php echo esc_html( $row['location_preference'] ); ?></td>
						<td>
							<?php if ( empty( $links ) ) : ?>
								—
							<?php else : foreach ( $links as $i => $l ) : if ( ! $l ) continue; ?>
								<a href="<?php echo esc_url( $l ); ?>" target="_blank" rel="noopener noreferrer">Link <?php echo (int) $i + 1; ?></a><br>
							<?php endforeach; endif; ?>
						</td>
						<td><?php echo esc_html( $row['additional_notes'] ); ?></td>
						<td><?php echo $row['submitted_at'] ? 'Submitted' : 'Awaiting client'; ?></td>
						<td><input type="text" readonly value="<?php echo esc_attr( $link ); ?>" style="width:100%;font-size:11px;" onclick="this.select();"></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function render_image_selections_list() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		$rows = Infocus_ERP_CRUD::get_all( 'image_selections', array( 'orderby' => 'id', 'order' => 'DESC' ) );
		?>
		<div class="wrap infocus-erp-wrap">
			<h1>Image Selections</h1>
			<p>Shortcode for this form: <code>[infocus_image_selection]</code>. Which raw images each client picked for editing, plus any extras beyond their package with the estimated additional charge. Links are generated from the Bookings screen ("Image Selection Link").</p>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Booking</th><th>Included images</th><th>Extra images</th><th>Est. extra charge</th><th>Notes</th><th>Status</th><th>Lock</th><th>Export</th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="8">No image selections yet.</td></tr>
				<?php else : foreach ( $rows as $row ) :
					$data     = ! empty( $row['selected_images'] ) ? json_decode( $row['selected_images'], true ) : array();
					$included = is_array( $data ) && isset( $data['included'] ) ? $data['included'] : array();
					$extra    = is_array( $data ) && isset( $data['extra'] ) ? $data['extra'] : array();
					$booking  = Infocus_ERP_CRUD::get( 'bookings', $row['booking_id'] );
					$customer = $booking ? Infocus_ERP_CRUD::get( 'customers', $booking['customer_id'] ) : null;
					$fmt      = function ( $items ) {
						return implode( ', ', array_map( function ( $i ) {
							return $i['image'] . ( ! empty( $i['comment'] ) ? ' (' . $i['comment'] . ')' : '' );
						}, array_filter( $items, function ( $i ) { return ! empty( $i['image'] ); } ) ) );
					};
					$summary = ( $customer['name'] ?? 'Client' ) . ' — ' . ( $booking['service_type'] ?? '' ) . ' (' . count( $included ) . ' included' . ( $row['extra_count'] > 0 ? ', ' . (int) $row['extra_count'] . ' extra, est. ₹' . number_format( $row['estimated_extra_charge'], 0 ) . ' additional' : '' ) . ")\n\nIncluded: " . $fmt( $included ) . "\nExtra: " . $fmt( $extra );
					$locked   = Infocus_ERP_Image_Selection_Form::is_locked( $row );
				?>
					<tr>
						<td><?php echo self::ref_label( 'bookings', $row['booking_id'] ); ?></td>
						<td><?php echo esc_html( $fmt( $included ) ); ?></td>
						<td><?php echo esc_html( $fmt( $extra ) ); ?></td>
						<td>₹<?php echo number_format( $row['estimated_extra_charge'], 2 ); ?></td>
						<td><?php echo esc_html( $row['extra_notes'] ); ?></td>
						<td><?php echo $row['submitted_at'] ? 'Submitted' : 'Awaiting client'; ?></td>
						<td>
							<?php if ( $locked ) : ?>
								<span style="color:var(--infocus-crimson);font-weight:600;">Locked</span><br>
								<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_unlock_image_selection&id=' . $row['id'] ), 'infocus_erp_lock_toggle' ) ); ?>">Unlock</a>
							<?php else : ?>
								<span style="color:var(--infocus-teal);font-weight:600;">Open</span><br>
								<?php if ( $row['submitted_at'] ) : ?>
								<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=infocus_erp_lock_image_selection&id=' . $row['id'] ), 'infocus_erp_lock_toggle' ) ); ?>">Lock now</a>
								<?php endif; ?>
							<?php endif; ?>
						</td>
						<td><textarea readonly style="width:100%;font-size:11px;height:60px;" onclick="this.select();"><?php echo esc_textarea( $summary ); ?></textarea></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
			<p><em>Tip: click any box in the Export column to select all the text, then copy — ready to paste into WhatsApp, Excel, or a message to Claude.</em></p>
		</div>
		<?php
	}

	public static function render_invoices() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) wp_die( 'Not allowed.' );
		$page_url = get_option( 'infocus_erp_invoice_page_url', home_url( '/invoice/' ) );

		if ( ! empty( $_GET['invoice_generated'] ) ) {
			$inv = Infocus_ERP_CRUD::get( 'invoices', (int) $_GET['invoice_generated'] );
			if ( $inv ) {
				$link = add_query_arg( 'rid', $inv['token'], $page_url );
				echo '<div class="notice notice-success"><p><strong>Invoice ' . esc_html( $inv['invoice_number'] ) . ' ready:</strong> <input type="text" readonly value="' . esc_attr( $link ) . '" style="width:60%;" onclick="this.select();"> — copy this and send it to the client.</p></div>';
			}
		}

		$customers = Infocus_ERP_CRUD::get_all( 'customers', array( 'orderby' => 'id', 'order' => 'DESC' ) );
		$invoices  = Infocus_ERP_CRUD::get_all( 'invoices', array( 'orderby' => 'id', 'order' => 'DESC' ) );
		?>
		<div class="wrap infocus-erp-wrap">
			<h1>Invoices</h1>
			<p>Shortcode for the invoice page: <code>[infocus_invoice]</code>. Booking invoices are generated from the "Generate Invoice" link on the Bookings screen and automatically pull in the package price, any advance paid, and extra-image charges. Use the form below for a one-off custom invoice instead.</p>

			<div class="infocus-erp-columns">
				<div class="infocus-erp-form-panel">
					<h2>New Custom Invoice</h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'infocus_erp_custom_invoice' ); ?>
						<input type="hidden" name="action" value="infocus_erp_create_custom_invoice">
						<p class="infocus-field"><label>Customer *</label>
							<select name="customer_id" required>
								<option value="">— Select —</option>
								<?php foreach ( $customers as $c ) : ?>
									<option value="<?php echo (int) $c['id']; ?>"><?php echo esc_html( $c['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p class="infocus-field"><label>Line Items</label>
							<div id="infocus-invoice-items">
								<div style="display:flex;gap:6px;margin-bottom:6px;">
									<input type="text" name="item_description[]" placeholder="Description" style="flex:2;">
									<input type="number" step="0.01" name="item_amount[]" placeholder="Amount (₹)" style="flex:1;">
								</div>
							</div>
							<button type="button" id="infocus-add-item" class="button">+ Add line item</button>
						</p>
						<p class="infocus-field"><label>Advance Already Paid (₹, optional)</label><input type="number" step="0.01" name="advance_paid" value="0"></p>
						<p class="infocus-field"><label>Notes (optional)</label><textarea name="notes" rows="2"></textarea></p>
						<p><button type="submit" class="button button-primary">Create Invoice</button></p>
					</form>
				</div>

				<div class="infocus-erp-list-panel">
					<table class="wp-list-table widefat fixed striped">
						<thead><tr><th>Invoice #</th><th>Customer</th><th>Date</th><th>Subtotal (₹)</th><th>Balance Due (₹)</th><th>Link</th></tr></thead>
						<tbody>
						<?php if ( empty( $invoices ) ) : ?>
							<tr><td colspan="6">No invoices yet.</td></tr>
						<?php else : foreach ( $invoices as $inv ) :
							$link = add_query_arg( 'rid', $inv['token'], $page_url );
						?>
							<tr>
								<td><?php echo esc_html( $inv['invoice_number'] ); ?></td>
								<td><?php echo self::ref_label( 'customers', $inv['customer_id'] ); ?></td>
								<td><?php echo esc_html( $inv['invoice_date'] ); ?></td>
								<td>₹<?php echo number_format( $inv['subtotal'], 2 ); ?></td>
								<td>₹<?php echo number_format( $inv['balance_due'], 2 ); ?></td>
								<td><input type="text" readonly value="<?php echo esc_attr( $link ); ?>" style="width:100%;font-size:11px;" onclick="this.select();"></td>
							</tr>
						<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<script>(function(){
			var wrap=document.getElementById('infocus-invoice-items');
			var btn=document.getElementById('infocus-add-item');
			if(!wrap||!btn) return;
			btn.addEventListener('click', function(){
				var row=document.createElement('div');
				row.style.cssText='display:flex;gap:6px;margin-bottom:6px;';
				row.innerHTML='<input type="text" name="item_description[]" placeholder="Description" style="flex:2;"><input type="number" step="0.01" name="item_amount[]" placeholder="Amount (₹)" style="flex:1;">';
				wrap.appendChild(row);
			});
		})();</script>
		<?php
	}

	/** Renders the mini graphical progress bar shown in the Editor Pipeline list's Timeline column. */
	private static function pipeline_timeline_cell( $row ) {
		if ( $row['status'] === 'Delivered' || Infocus_ERP_CRUD::is_real_date( $row['delivered_date'] ) ) {
			return '<span style="color:var(--infocus-teal);font-size:12px;font-weight:600;">Delivered</span>';
		}
		if ( ! Infocus_ERP_CRUD::is_real_date( $row['expected_date'] ) ) {
			return '<span style="color:var(--infocus-slate);font-size:12px;">No deadline set — add an Expected Delivery date to track this one</span>';
		}

		$start_ts     = Infocus_ERP_CRUD::is_real_date( $row['assigned_date'] ) ? strtotime( $row['assigned_date'] ) : strtotime( $row['created_at'] );
		$deadline_ts  = strtotime( $row['expected_date'] );
		$days_elapsed = max( 0, (int) floor( ( current_time( 'timestamp' ) - $start_ts ) / DAY_IN_SECONDS ) );
		$days_total   = max( 1, (int) round( ( $deadline_ts - $start_ts ) / DAY_IN_SECONDS ) );
		$days_left    = (int) floor( ( $deadline_ts - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );
		$percent      = min( 100, max( 0, (int) round( ( $days_elapsed / $days_total ) * 100 ) ) );
		$is_overdue   = $days_left < 0;
		$color        = $is_overdue ? 'var(--infocus-crimson)' : ( $percent >= 66 ? 'var(--infocus-gold)' : 'var(--infocus-teal)' );
		$label        = $is_overdue ? 'Overdue' : $days_left . 'd left';

		return '<div style="font-size:11px;color:' . esc_attr( $color ) . ';font-weight:600;margin-bottom:3px;">' . esc_html( $label ) . '</div>'
			. '<div style="height:5px;width:100px;background:rgba(107,122,143,0.15);border-radius:4px;overflow:hidden;"><div style="height:5px;width:' . (int) $percent . '%;background:' . esc_attr( $color ) . ';"></div></div>';
	}
}
