<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Access control for both the admin dashboard and the REST API.
 */
class Infocus_ERP_Security {

	// WordPress capability required to see the ERP menu at all.
	const CAP = 'manage_options';

	public static function current_user_allowed() {
		return is_user_logged_in() && current_user_can( self::CAP );
	}

	public static function get_api_key() {
		return get_option( 'infocus_erp_api_key', '' );
	}

	public static function regenerate_api_key() {
		$key = wp_generate_password( 40, false, false );
		update_option( 'infocus_erp_api_key', $key );
		return $key;
	}

	public static function get_mcp_token() {
		return get_option( 'infocus_erp_mcp_token', '' );
	}

	public static function regenerate_mcp_token() {
		$token = wp_generate_password( 48, false, false );
		update_option( 'infocus_erp_mcp_token', $token );
		return $token;
	}

	/** Full URL to paste into Claude's "Add custom connector" box. The token lives in the URL itself — no separate auth step needed. */
	public static function get_mcp_url() {
		return rest_url( Infocus_ERP_REST_API::NS . '/mcp/' . self::get_mcp_token() );
	}

	/** Used as the permission_callback for the MCP route — checks the token in the URL path rather than a header. */
	public static function check_mcp_token( WP_REST_Request $request ) {
		$configured = self::get_mcp_token();
		$provided   = $request->get_param( 'token' );
		if ( empty( $configured ) || empty( $provided ) ) return false;
		return hash_equals( $configured, $provided );
	}

	/**
	 * Lightweight abuse guard for the public-facing forms (no CAPTCHA
	 * dependency — keeps the plugin self-contained). Returns false once a
	 * given key (typically an IP address) exceeds $max submissions within
	 * $window seconds.
	 */
	public static function rate_limit_ok( $key, $max = 5, $window = HOUR_IN_SECONDS ) {
		$transient_key = 'infocus_erp_rl_' . md5( $key );
		$count         = (int) get_transient( $transient_key );
		if ( $count >= $max ) return false;
		set_transient( $transient_key, $count + 1, $window );
		return true;
	}

	public static function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	}

	/**
	 * Used as the permission_callback for every REST route. The key must be
	 * sent as a header: X-Infocus-Api-Key: <key>
	 */
	public static function check_api_key( WP_REST_Request $request ) {
		$configured = self::get_api_key();
		$provided   = $request->get_header( 'x-infocus-api-key' );

		if ( empty( $configured ) || empty( $provided ) ) {
			return false;
		}
		return hash_equals( $configured, $provided );
	}
}
