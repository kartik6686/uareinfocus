<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * All the aggregate numbers shown on the Dashboard/Reports screen, and
 * reused by the REST API's /summary endpoint so Claude can ask the same
 * questions the dashboard answers.
 */
class Infocus_ERP_Reports {

	public static function summary( $args = array() ) {
		global $wpdb;
		$t = Infocus_ERP_DB::tables();

		$date_from = ! empty( $args['from'] ) ? $args['from'] : null;
		$date_to   = ! empty( $args['to'] ) ? $args['to'] : null;

		$date_where_payments = '';
		$date_where_expenses = '';
		if ( $date_from && $date_to ) {
			$date_where_payments = $wpdb->prepare( 'WHERE payment_date BETWEEN %s AND %s', $date_from, $date_to );
			$date_where_expenses = $wpdb->prepare( 'WHERE expense_date BETWEEN %s AND %s', $date_from, $date_to );
		}

		$total_revenue = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$t['payments']} $date_where_payments" );
		$total_expense = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$t['expenses']} $date_where_expenses" );

		// Outstanding = sum of booking package prices minus payments received per booking,
		// only counting bookings that are not cancelled.
		$outstanding = (float) $wpdb->get_var(
			"SELECT COALESCE(SUM(b.package_price - COALESCE(p.paid,0)),0)
			 FROM {$t['bookings']} b
			 LEFT JOIN (
				 SELECT booking_id, SUM(amount) AS paid FROM {$t['payments']} GROUP BY booking_id
			 ) p ON p.booking_id = b.id
			 WHERE b.status != 'Cancelled'"
		);

		$expense_by_category = $wpdb->get_results(
			"SELECT category, COALESCE(SUM(amount),0) AS total
			 FROM {$t['expenses']} $date_where_expenses
			 GROUP BY category ORDER BY total DESC",
			ARRAY_A
		);

		$pipeline_by_status = $wpdb->get_results(
			"SELECT status, COUNT(*) AS total FROM {$t['pipeline']} GROUP BY status",
			ARRAY_A
		);

		$overdue_pipeline = $wpdb->get_results(
			"SELECT pl.*, b.customer_id, b.service_type, b.session_date
			 FROM {$t['pipeline']} pl
			 JOIN {$t['bookings']} b ON b.id = pl.booking_id
			 WHERE pl.status != 'Delivered' AND pl.expected_date IS NOT NULL AND pl.expected_date < CURDATE()
			 ORDER BY pl.expected_date ASC",
			ARRAY_A
		);

		$upcoming_bookings = $wpdb->get_results(
			"SELECT b.*, c.name AS customer_name
			 FROM {$t['bookings']} b
			 LEFT JOIN {$t['customers']} c ON c.id = b.customer_id
			 WHERE b.session_date >= CURDATE() AND b.status != 'Cancelled'
			 ORDER BY b.session_date ASC LIMIT 10",
			ARRAY_A
		);

		$unpaid_bookings = $wpdb->get_results(
			"SELECT b.id, b.package_price, c.name AS customer_name, b.session_date,
					COALESCE(p.paid,0) AS paid, (b.package_price - COALESCE(p.paid,0)) AS balance
			 FROM {$t['bookings']} b
			 LEFT JOIN {$t['customers']} c ON c.id = b.customer_id
			 LEFT JOIN (
				 SELECT booking_id, SUM(amount) AS paid FROM {$t['payments']} GROUP BY booking_id
			 ) p ON p.booking_id = b.id
			 WHERE b.status != 'Cancelled' AND (b.package_price - COALESCE(p.paid,0)) > 0
			 ORDER BY balance DESC",
			ARRAY_A
		);

		return array(
			'total_revenue_collected' => $total_revenue,
			'total_expenses'          => $total_expense,
			'net_profit'              => $total_revenue - $total_expense,
			'total_outstanding'       => $outstanding,
			'expense_by_category'     => $expense_by_category,
			'pipeline_by_status'      => $pipeline_by_status,
			'overdue_pipeline'        => $overdue_pipeline,
			'upcoming_bookings'       => $upcoming_bookings,
			'unpaid_bookings'         => $unpaid_bookings,
			'editing_deadlines'       => self::editing_deadlines(),
			'all_pending_editing'     => self::editing_deadlines( 15, 10, false ),
			'counts'                  => array(
				'customers' => Infocus_ERP_CRUD::count( 'customers' ),
				'bookings'  => Infocus_ERP_CRUD::count( 'bookings' ),
				'employees' => Infocus_ERP_CRUD::count( 'employees' ),
			),
		);
	}

	/**
	 * Tracks editing turnaround from the moment a client submits their image
	 * selection (or from when it was assigned to an editor, if that's set).
	 * Defaults to a 15-day deadline from that start date — but if a Pipeline
	 * entry for the booking has its own "Expected Delivery" date set, that
	 * manual date always wins. This is what gives full freedom to override
	 * the deadline for urgent jobs: just set/edit the Expected Delivery date
	 * on the Editor Pipeline screen, and everything here (Dashboard banner,
	 * progress bars, Pipeline screen timeline) recalculates around it.
	 */
	public static function editing_deadlines( $default_days = 15, $warn_from_day = 10, $only_urgent = true ) {
		global $wpdb;
		$t = Infocus_ERP_DB::tables();

		$rows = $wpdb->get_results(
			"SELECT s.booking_id, s.submitted_at, b.service_type, b.session_date, b.status, c.name AS customer_name,
					p.assigned_date, p.expected_date, p.status AS pipeline_status, e.name AS editor_name
			 FROM {$t['image_selections']} s
			 JOIN {$t['bookings']} b ON b.id = s.booking_id
			 LEFT JOIN {$t['customers']} c ON c.id = b.customer_id
			 LEFT JOIN {$t['pipeline']} p ON p.booking_id = s.booking_id
			 LEFT JOIN {$t['employees']} e ON e.id = p.employee_id
			 WHERE s.submitted_at IS NOT NULL AND COALESCE(p.status,'') != 'Delivered' AND b.status != 'Cancelled'
			 ORDER BY s.submitted_at ASC",
			ARRAY_A
		);

		$deadlines = array();
		foreach ( $rows as $row ) {
			$start_ts = Infocus_ERP_CRUD::is_real_date( $row['assigned_date'] ) ? strtotime( $row['assigned_date'] ) : strtotime( $row['submitted_at'] );
			$deadline_ts = Infocus_ERP_CRUD::is_real_date( $row['expected_date'] )
				? strtotime( $row['expected_date'] )
				: strtotime( '+' . $default_days . ' days', $start_ts );

			$days_elapsed = (int) floor( ( current_time( 'timestamp' ) - $start_ts ) / DAY_IN_SECONDS );
			$days_total   = max( 1, (int) round( ( $deadline_ts - $start_ts ) / DAY_IN_SECONDS ) );
			$days_left    = (int) floor( ( $deadline_ts - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );

			if ( $only_urgent && $days_elapsed < $warn_from_day ) continue;

			$deadlines[] = array(
				'booking_id'    => (int) $row['booking_id'],
				'customer_name' => $row['customer_name'],
				'service_type'  => $row['service_type'],
				'editor_name'   => $row['editor_name'],
				'days_elapsed'  => max( 0, $days_elapsed ),
				'days_total'    => $days_total,
				'days_left'     => $days_left,
				'percent'       => min( 100, max( 0, (int) round( ( $days_elapsed / $days_total ) * 100 ) ) ),
				'is_overdue'    => $days_left < 0,
				'is_manual'     => Infocus_ERP_CRUD::is_real_date( $row['expected_date'] ),
			);
		}
		return $deadlines;
	}

	/** Bookings for a given month, keyed by day-of-month, for the Dashboard calendar. */
	public static function bookings_for_month( $year, $month ) {
		global $wpdb;
		$t     = Infocus_ERP_DB::tables();
		$start = sprintf( '%04d-%02d-01', $year, $month );
		$end   = date( 'Y-m-d', strtotime( $start . ' +1 month' ) );

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.id, b.session_date, b.session_time, b.service_type, c.name AS customer_name, c.phone
			 FROM {$t['bookings']} b
			 LEFT JOIN {$t['customers']} c ON c.id = b.customer_id
			 WHERE b.session_date >= %s AND b.session_date < %s AND b.status != 'Cancelled'
			 ORDER BY b.session_date ASC, b.session_time ASC",
			$start, $end
		), ARRAY_A );

		$by_day = array();
		foreach ( $rows as $row ) {
			$day = (int) date( 'j', strtotime( $row['session_date'] ) );
			$by_day[ $day ][] = array(
				'name'    => $row['customer_name'] ?: 'Unknown client',
				'phone'   => $row['phone'] ?: '',
				'time'    => $row['session_time'] ? date( 'g:i A', strtotime( $row['session_time'] ) ) : '',
				'service' => $row['service_type'],
			);
		}
		return $by_day;
	}
}
