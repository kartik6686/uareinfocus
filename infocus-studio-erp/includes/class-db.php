<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles table names and installation of the plugin's own database tables.
 * Custom tables are used (not post types / postmeta) so the ERP data stays
 * fast to query and completely independent of anything Elementor or the
 * theme does.
 */
class Infocus_ERP_DB {

	public static function tables() {
		global $wpdb;
		$p = $wpdb->prefix . 'infocus_';
		return array(
			'customers'            => $p . 'customers',
			'bookings'             => $p . 'bookings',
			'payments'             => $p . 'payments',
			'employees'            => $p . 'employees',
			'pipeline'             => $p . 'pipeline',
			'expenses'             => $p . 'expenses',
			'inquiries'            => $p . 'inquiries',
			'shoot_requirements'   => $p . 'shoot_requirements',
			'image_selections'     => $p . 'image_selections',
			'packages'             => $p . 'packages',
			'invoices'             => $p . 'invoices',
		);
	}

	public static function install() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$t               = self::tables();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql   = array();
		$sql[] = "CREATE TABLE {$t['customers']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			phone VARCHAR(30) DEFAULT '',
			email VARCHAR(191) DEFAULT '',
			source VARCHAR(100) DEFAULT '',
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['bookings']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			customer_id BIGINT UNSIGNED NOT NULL,
			package_id BIGINT UNSIGNED NULL,
			service_type VARCHAR(100) DEFAULT '',
			session_date DATE NULL,
			session_time TIME NULL,
			location VARCHAR(191) DEFAULT '',
			package_price DECIMAL(10,2) DEFAULT 0,
			included_edits INT UNSIGNED DEFAULT 0,
			status VARCHAR(50) DEFAULT 'Inquiry',
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY customer_id (customer_id),
			KEY package_id (package_id),
			KEY session_date (session_date),
			KEY status (status)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['payments']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id BIGINT UNSIGNED NOT NULL,
			amount DECIMAL(10,2) DEFAULT 0,
			payment_date DATE NULL,
			method VARCHAR(50) DEFAULT '',
			type VARCHAR(20) DEFAULT 'Advance',
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['employees']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			role VARCHAR(100) DEFAULT '',
			phone VARCHAR(30) DEFAULT '',
			email VARCHAR(191) DEFAULT '',
			rate_type VARCHAR(20) DEFAULT '',
			rate_amount DECIMAL(10,2) DEFAULT 0,
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['pipeline']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id BIGINT UNSIGNED NOT NULL,
			employee_id BIGINT UNSIGNED NULL,
			assigned_date DATE NULL,
			expected_date DATE NULL,
			delivered_date DATE NULL,
			status VARCHAR(30) DEFAULT 'Not Started',
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY employee_id (employee_id),
			KEY status (status)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['expenses']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			category VARCHAR(100) DEFAULT '',
			amount DECIMAL(10,2) DEFAULT 0,
			expense_date DATE NULL,
			booking_id BIGINT UNSIGNED NULL,
			vendor VARCHAR(191) DEFAULT '',
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY category (category),
			KEY expense_date (expense_date)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['inquiries']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			phone VARCHAR(30) DEFAULT '',
			email VARCHAR(191) DEFAULT '',
			service_type VARCHAR(100) DEFAULT '',
			additional_requirements TEXT NULL,
			preferred_date DATE NULL,
			message TEXT NULL,
			matched_customer_id BIGINT UNSIGNED NULL,
			is_new_client TINYINT(1) DEFAULT 1,
			quality_flag VARCHAR(30) DEFAULT '',
			status VARCHAR(30) DEFAULT 'New',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY matched_customer_id (matched_customer_id),
			KEY status (status)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['shoot_requirements']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id BIGINT UNSIGNED NOT NULL,
			token VARCHAR(64) NOT NULL,
			preferred_time VARCHAR(191) DEFAULT '',
			theme VARCHAR(191) DEFAULT '',
			outfit VARCHAR(191) DEFAULT '',
			location_preference VARCHAR(191) DEFAULT '',
			reference_links TEXT NULL,
			additional_notes TEXT NULL,
			submitted_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY booking_id (booking_id)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['image_selections']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id BIGINT UNSIGNED NOT NULL,
			token VARCHAR(64) NOT NULL,
			selected_images TEXT NULL,
			extra_notes TEXT NULL,
			extra_count INT UNSIGNED DEFAULT 0,
			estimated_extra_charge DECIMAL(10,2) DEFAULT 0,
			lock_override VARCHAR(20) DEFAULT '',
			submitted_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY booking_id (booking_id)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['packages']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			category VARCHAR(50) DEFAULT '',
			price DECIMAL(10,2) DEFAULT 0,
			included_edits INT UNSIGNED DEFAULT 0,
			is_popular TINYINT(1) NOT NULL DEFAULT 0,
			description TEXT NULL,
			brochure_url VARCHAR(500) DEFAULT '',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY category (category)
		) $charset_collate;";

		$sql[] = "CREATE TABLE {$t['invoices']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			invoice_number VARCHAR(30) NOT NULL,
			booking_id BIGINT UNSIGNED NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			token VARCHAR(64) NOT NULL,
			invoice_date DATE NULL,
			line_items TEXT NULL,
			subtotal DECIMAL(10,2) DEFAULT 0,
			advance_paid DECIMAL(10,2) DEFAULT 0,
			balance_due DECIMAL(10,2) DEFAULT 0,
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY customer_id (customer_id),
			KEY booking_id (booking_id)
		) $charset_collate;";

		self::migrate_is_popular_to_boolean( $t['packages'] );

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		// Generate a REST API key on first install so the Claude Bridge / MCP
		// connection has something to authenticate with right away.
		if ( ! get_option( 'infocus_erp_api_key' ) ) {
			update_option( 'infocus_erp_api_key', wp_generate_password( 40, false, false ) );
		}

		// Generate the MCP connection token (used in the connector URL itself).
		if ( ! get_option( 'infocus_erp_mcp_token' ) ) {
			update_option( 'infocus_erp_mcp_token', wp_generate_password( 48, false, false ) );
		}

		// Default: image selections lock 4 hours after the client's most recent submission.
		if ( get_option( 'infocus_erp_lock_after_hours', '' ) === '' ) {
			update_option( 'infocus_erp_lock_after_hours', 4 );
		}

		update_option( 'infocus_erp_db_version', INFOCUS_ERP_VERSION );
	}

	/**
	 * packages.is_popular started life as VARCHAR(5) 'Yes'/'No'. dbDelta never
	 * alters an existing column's type, so on upgrade this converts it to a
	 * real TINYINT(1) in place, preserving existing data. Safe to run on every
	 * install/upgrade — it's a no-op once the column is already an integer type.
	 */
	private static function migrate_is_popular_to_boolean( $packages_table ) {
		global $wpdb;

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $packages_table ) );
		if ( ! $table_exists ) {
			return;
		}

		$column = $wpdb->get_row( $wpdb->prepare( 'SHOW COLUMNS FROM ' . $packages_table . ' LIKE %s', 'is_popular' ) );
		if ( ! $column || stripos( $column->Type, 'varchar' ) === false ) {
			return;
		}

		$wpdb->query( "UPDATE {$packages_table} SET is_popular = IF( LOWER( TRIM( is_popular ) ) = 'yes', '1', '0' )" );
		$wpdb->query( "ALTER TABLE {$packages_table} MODIFY is_popular TINYINT(1) NOT NULL DEFAULT 0" );
	}
}
