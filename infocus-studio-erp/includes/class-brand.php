<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Single source of the brand palette. Previously each public-facing form
 * (public-forms, image-selection-form, invoices) declared its own identical
 * copy of these six constants — a real duplication risk if the palette ever
 * changes, since nothing would force all four files to be updated together.
 */
class Infocus_ERP_Brand {
	const BG      = '#F8F8FF';
	const NAVY    = '#131357';
	const GOLD    = '#D4AF37';
	const SLATE   = '#6B7A8F';
	const CRIMSON = '#9A1F1F';
	const TEAL    = '#1A6B6B';
}
