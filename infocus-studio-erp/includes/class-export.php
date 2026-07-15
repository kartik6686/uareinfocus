<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CSV export for every entity, plus a single "export everything" ZIP so
 * Kartik always has a copy of his business data independent of the
 * website/domain.
 */
class Infocus_ERP_Export {

	public static function init() {
		add_action( 'admin_post_infocus_erp_export_csv', array( __CLASS__, 'export_single_csv' ) );
		add_action( 'admin_post_infocus_erp_export_all', array( __CLASS__, 'export_all_zip' ) );
	}

	private static function guard() {
		if ( ! Infocus_ERP_Security::current_user_allowed() ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'infocus_erp_export' );
	}

	public static function export_single_csv() {
		self::guard();
		$entity = isset( $_GET['entity'] ) ? sanitize_key( $_GET['entity'] ) : '';
		$rows   = Infocus_ERP_CRUD::get_all( $entity, array( 'orderby' => 'id', 'order' => 'ASC' ) );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $entity . '-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		if ( ! empty( $rows ) ) {
			fputcsv( $out, array_keys( $rows[0] ) );
			foreach ( $rows as $row ) {
				fputcsv( $out, $row );
			}
		} else {
			fputcsv( $out, array( 'No data' ) );
		}
		fclose( $out );
		exit;
	}

	public static function export_all_zip() {
		self::guard();

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( 'The ZipArchive PHP extension is not available on this server. Export each section individually as CSV instead.' );
		}

		$tables  = array_keys( Infocus_ERP_DB::tables() );
		$tmpdir  = get_temp_dir();
		$zipname = 'infocus-erp-full-export-' . gmdate( 'Y-m-d-His' ) . '.zip';
		$zippath = $tmpdir . $zipname;

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zippath, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			wp_die( 'Could not create the export ZIP file. Please try again or export each section individually as CSV.' );
		}

		foreach ( $tables as $entity ) {
			$rows = Infocus_ERP_CRUD::get_all( $entity, array( 'orderby' => 'id', 'order' => 'ASC' ) );
			$csv  = '';
			if ( ! empty( $rows ) ) {
				$fh = fopen( 'php://temp', 'r+' );
				fputcsv( $fh, array_keys( $rows[0] ) );
				foreach ( $rows as $row ) {
					fputcsv( $fh, $row );
				}
				rewind( $fh );
				$csv = stream_get_contents( $fh );
				fclose( $fh );
			}
			$zip->addFromString( $entity . '.csv', $csv );
		}

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $zipname );
		header( 'Content-Length: ' . filesize( $zippath ) );
		readfile( $zippath );
		unlink( $zippath );
		exit;
	}
}
