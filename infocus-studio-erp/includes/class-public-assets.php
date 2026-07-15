<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers and conditionally enqueues the CSS/JS shared across all five
 * public-facing shortcodes (booking calendar, inquiry form, shoot
 * requirements, image selection, invoice). Previously each of those four
 * classes wrote its own inline <style> block (full palette + component CSS
 * re-declared each time) and, for the calendar/inquiry-form pair and the
 * requirements/image-selection pair, its own hand-rolled copy of the same
 * calendar-grid and add/remove-row JavaScript. Consolidating those into one
 * enqueued, browser-cacheable file each — instead of re-downloading and
 * re-parsing the same CSS/JS inline on every single page load — is the
 * point of this class. It does not change what shortcode is used where,
 * or how any form behaves; only how the shared styling/behavior is shipped.
 */
class Infocus_ERP_Public_Assets {

	const SHORTCODES = array(
		'infocus_book_session',
		'infocus_inquiry_form',
		'infocus_shoot_requirements',
		'infocus_image_selection',
		'infocus_invoice',
	);

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
	}

	public static function maybe_enqueue() {
		if ( ! is_singular() ) return;
		$post = get_post();
		if ( ! $post ) return;

		$needs_assets = false;
		foreach ( self::SHORTCODES as $tag ) {
			if ( has_shortcode( $post->post_content, $tag ) ) {
				$needs_assets = true;
				break;
			}
		}
		if ( ! $needs_assets ) return;

		wp_enqueue_style( 'infocus-erp-public', INFOCUS_ERP_URL . 'assets/css/public.css', array(), INFOCUS_ERP_VERSION );
		// Loaded in <head>, not the footer: each shortcode's own inline
		// <script> (which calls window.InfocusPublic) runs immediately where
		// the shortcode appears in the page body, so the shared library has
		// to already exist by then rather than arriving after it at the
		// bottom of the page.
		wp_enqueue_script( 'infocus-erp-public', INFOCUS_ERP_URL . 'assets/js/public.js', array(), INFOCUS_ERP_VERSION, false );
	}
}
