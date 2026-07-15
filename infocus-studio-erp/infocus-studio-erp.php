<?php
/**
 * Plugin Name: Infocus Studio ERP
 * Plugin URI: https://uareinfocus.com
 * Description: Standalone studio management system with a redesigned app-style dashboard (calendar, 15-day editing deadline tracker), bookings, payments, customers, employees, editor pipeline, expenses, reporting, packages with brochures, invoicing (fixed clean printing), a WhatsApp link workflow, a public inquiry form with an approval gate, a public booking calendar with manual/MCP confirmation, private shoot-requirements and image-selection forms, and a built-in MCP server for connecting directly to Claude.
 * Version: 1.11.1
 * Author: Kartik Maharana
 * Text Domain: infocus-erp
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'INFOCUS_ERP_VERSION', '1.11.1' );
define( 'INFOCUS_ERP_PATH', plugin_dir_path( __FILE__ ) );
define( 'INFOCUS_ERP_URL', plugin_dir_url( __FILE__ ) );

require_once INFOCUS_ERP_PATH . 'includes/class-brand.php';
require_once INFOCUS_ERP_PATH . 'includes/class-db.php';
require_once INFOCUS_ERP_PATH . 'includes/class-crud.php';
require_once INFOCUS_ERP_PATH . 'includes/class-security.php';
require_once INFOCUS_ERP_PATH . 'includes/class-reports.php';
require_once INFOCUS_ERP_PATH . 'includes/class-export.php';
require_once INFOCUS_ERP_PATH . 'includes/class-rest-api.php';
require_once INFOCUS_ERP_PATH . 'includes/class-mcp-server.php';
require_once INFOCUS_ERP_PATH . 'includes/class-public-assets.php';
require_once INFOCUS_ERP_PATH . 'includes/class-public-forms.php';
require_once INFOCUS_ERP_PATH . 'includes/class-booking-calendar.php';
require_once INFOCUS_ERP_PATH . 'includes/class-image-selection-form.php';
require_once INFOCUS_ERP_PATH . 'includes/class-invoices.php';
require_once INFOCUS_ERP_PATH . 'includes/class-admin-pages.php';

register_activation_hook( __FILE__, array( 'Infocus_ERP_DB', 'install' ) );

add_action(
	'plugins_loaded',
	function () {
		Infocus_ERP_Admin_Pages::init();
		Infocus_ERP_Export::init();
		Infocus_ERP_REST_API::init();
		Infocus_ERP_MCP_Server::init();
		Infocus_ERP_Public_Assets::init();
		Infocus_ERP_Public_Forms::init();
		Infocus_ERP_Booking_Calendar::init();
		Infocus_ERP_Image_Selection_Form::init();
		Infocus_ERP_Invoices::init();

		// Silent upgrade path: if the plugin files were updated (e.g. re-uploaded
		// as a new version) without a full deactivate/reactivate cycle, this
		// catches it and runs the (safe, non-destructive) table installer again
		// so new tables/columns appear without any manual step.
		if ( get_option( 'infocus_erp_db_version' ) !== INFOCUS_ERP_VERSION && is_admin() ) {
			Infocus_ERP_DB::install();
		}
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( strpos( $hook, 'infocus-erp' ) === false ) {
			return;
		}
		wp_enqueue_style( 'infocus-erp-admin', INFOCUS_ERP_URL . 'assets/css/admin.css', array(), INFOCUS_ERP_VERSION );
		wp_enqueue_script( 'infocus-erp-admin', INFOCUS_ERP_URL . 'assets/js/admin.js', array(), INFOCUS_ERP_VERSION, true );

		// The React-rendered dashboard (Phase 1 of the redesign). Other screens
		// are still the classic PHP-rendered pages and don't load this bundle.
		if ( 'toplevel_page_infocus-erp' === $hook ) {
			$asset_file = INFOCUS_ERP_PATH . 'build/dashboard.asset.php';
			if ( file_exists( $asset_file ) ) {
				$asset = require $asset_file;
				wp_enqueue_script( 'infocus-erp-dashboard', INFOCUS_ERP_URL . 'build/dashboard.js', $asset['dependencies'], $asset['version'], true );
				wp_enqueue_style( 'infocus-erp-dashboard', INFOCUS_ERP_URL . 'build/dashboard.css', array(), $asset['version'] );
				wp_localize_script( 'infocus-erp-dashboard', 'infocusErpAdmin', array(
					'adminUrl'        => admin_url( '/' ),
					'currentUserName' => wp_get_current_user()->display_name,
				) );
			}
		}

		// The React-rendered entity screens (Phase 2). Only the seven generic
		// CRUD entities (bookings, customers, ...) load this bundle — the
		// bespoke screens (Inquiries, Settings, Invoices, ...) are untouched.
		$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$entity = strpos( $page, 'infocus-erp-' ) === 0 ? substr( $page, strlen( 'infocus-erp-' ) ) : '';
		if ( $entity && array_key_exists( $entity, Infocus_ERP_Admin_Pages::entities() ) ) {
			$asset_file = INFOCUS_ERP_PATH . 'build/entity-app.asset.php';
			if ( file_exists( $asset_file ) ) {
				$asset = require $asset_file;
				wp_enqueue_script( 'infocus-erp-entity-app', INFOCUS_ERP_URL . 'build/entity-app.js', $asset['dependencies'], $asset['version'], true );
				wp_enqueue_style( 'infocus-erp-entity-app', INFOCUS_ERP_URL . 'build/entity-app.css', array(), $asset['version'] );
			}
		}

		// The React-rendered client-workflow screens (Phase 3): Inquiries,
		// Shoot Requirements, Image Selections, Invoices. Bespoke screens, not
		// part of the generic entities() config, so they share one bundle
		// keyed by the page slug instead of the entity-app bundle above.
		$workflow_screens = array(
			'infocus-erp-inquiries'        => 'inquiries',
			'infocus-erp-requirements'     => 'requirements',
			'infocus-erp-image-selections' => 'image-selections',
			'infocus-erp-invoices'         => 'invoices',
			'infocus-erp-export'           => 'export',
		);
		if ( isset( $workflow_screens[ $page ] ) ) {
			$asset_file = INFOCUS_ERP_PATH . 'build/workflow.asset.php';
			if ( file_exists( $asset_file ) ) {
				$asset = require $asset_file;
				wp_enqueue_script( 'infocus-erp-workflow', INFOCUS_ERP_URL . 'build/workflow.js', $asset['dependencies'], $asset['version'], true );
				wp_enqueue_style( 'infocus-erp-workflow', INFOCUS_ERP_URL . 'build/workflow.css', array(), $asset['version'] );
				$workflow_data = array(
					'adminUrl' => admin_url( '/' ),
					'userName' => wp_get_current_user()->display_name,
				);
				if ( 'export' === $workflow_screens[ $page ] ) {
					$export_data                        = Infocus_ERP_Admin_Pages::export_localize_data();
					$workflow_data['exportSections']     = $export_data['sections'];
					$workflow_data['exportFullBackupUrl'] = $export_data['fullBackupUrl'];
				}
				wp_localize_script( 'infocus-erp-workflow', 'infocusErpWorkflow', $workflow_data );
			}
		}
	}
);
