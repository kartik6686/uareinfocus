<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * A single, generic CRUD layer used by every entity (bookings, customers,
 * payments, employees, pipeline, expenses). Keeps the plugin small — no
 * repeated boilerplate per entity.
 */
class Infocus_ERP_CRUD {

	public static function table( $name ) {
		$t = Infocus_ERP_DB::tables();
		return isset( $t[ $name ] ) ? $t[ $name ] : '';
	}

	public static function get_all( $name, $args = array() ) {
		global $wpdb;
		$table = self::table( $name );
		if ( ! $table ) return array();

		$orderby = isset( $args['orderby'] ) ? preg_replace( '/[^a-zA-Z0-9_]/', '', $args['orderby'] ) : 'id';
		$order   = ( isset( $args['order'] ) && strtoupper( $args['order'] ) === 'ASC' ) ? 'ASC' : 'DESC';

		$where  = '';
		$params = array();
		if ( ! empty( $args['where'] ) && is_array( $args['where'] ) ) {
			$clauses = array();
			foreach ( $args['where'] as $col => $val ) {
				$col       = preg_replace( '/[^a-zA-Z0-9_]/', '', $col );
				$clauses[] = "$col = %s";
				$params[]  = $val;
			}
			$where = 'WHERE ' . implode( ' AND ', $clauses );
		}

		$limit = '';
		if ( ! empty( $args['limit'] ) ) {
			$limit    = 'LIMIT %d';
			$params[] = (int) $args['limit'];
		}

		$sql = "SELECT * FROM $table $where ORDER BY $orderby $order $limit";
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params );
		}
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public static function get( $name, $id ) {
		global $wpdb;
		$table = self::table( $name );
		if ( ! $table ) return null;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
	}

	public static function insert( $name, $data ) {
		global $wpdb;
		$table = self::table( $name );
		if ( ! $table ) return 0;
		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = $data['created_at'];
		$data = self::normalize_empty_to_null( $data );
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	public static function update( $name, $id, $data ) {
		global $wpdb;
		$table = self::table( $name );
		if ( ! $table ) return false;
		$data['updated_at'] = current_time( 'mysql' );
		$data = self::normalize_empty_to_null( $data );
		return $wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	/**
	 * Empty strings sent to a DATE column get silently coerced by MySQL
	 * into "0000-00-00" on non-strict server configurations — and that
	 * string is NOT considered "empty" by PHP, which caused real bugs
	 * (e.g. a blank "Actually Delivered" date being misread as "delivered").
	 * Converting empty strings to true NULL before every insert/update
	 * closes that off everywhere, for every field, permanently.
	 */
	private static function normalize_empty_to_null( $data ) {
		foreach ( $data as $key => $value ) {
			if ( $value === '' ) $data[ $key ] = null;
		}
		return $data;
	}

	public static function delete( $name, $id ) {
		global $wpdb;
		$table = self::table( $name );
		if ( ! $table ) return false;
		return $wpdb->delete( $table, array( 'id' => $id ) );
	}

	/** True only for a genuinely set date — guards against legacy "0000-00-00" rows saved before empty values were normalized to NULL. */
	public static function is_real_date( $value ) {
		if ( empty( $value ) ) return false;
		if ( strpos( (string) $value, '0000-00-00' ) === 0 ) return false;
		return true;
	}

	public static function count( $name, $where = array() ) {
		global $wpdb;
		$table = self::table( $name );
		if ( ! $table ) return 0;
		if ( empty( $where ) ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		}
		$clauses = array();
		$params  = array();
		foreach ( $where as $col => $val ) {
			$col       = preg_replace( '/[^a-zA-Z0-9_]/', '', $col );
			$clauses[] = "$col = %s";
			$params[]  = $val;
		}
		$sql = "SELECT COUNT(*) FROM $table WHERE " . implode( ' AND ', $clauses );
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
	}
}
