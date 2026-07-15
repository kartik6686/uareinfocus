<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * REST API surface. Every route is protected by the X-Infocus-Api-Key header
 * (see class-security.php). All business logic lives in plain-array methods
 * here (self::do_*) so the MCP server (class-mcp-server.php) can call the
 * exact same logic without going through a WP_REST_Request object.
 *
 * Namespace: /wp-json/infocus-erp/v1/...
 */
class Infocus_ERP_REST_API {

	const NS = 'infocus-erp/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$perm = array( 'Infocus_ERP_Security', 'check_api_key' );

		register_rest_route( self::NS, '/summary', array(
			'methods'             => 'GET',
			'permission_callback' => array( 'Infocus_ERP_Security', 'check_admin_or_api_key' ),
			'callback'            => function ( $request ) {
				return self::wrap( self::do_summary( array( 'from' => $request->get_param( 'from' ), 'to' => $request->get_param( 'to' ) ) ) );
			},
		) );

		register_rest_route( self::NS, '/quick-expense', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => function ( $request ) {
				return self::wrap( self::do_quick_expense( (array) $request->get_json_params() ) );
			},
		) );

		register_rest_route( self::NS, '/quick-payment', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => function ( $request ) {
				return self::wrap( self::do_quick_payment( (array) $request->get_json_params() ) );
			},
		) );

		register_rest_route( self::NS, '/quick-booking', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => function ( $request ) {
				return self::wrap( self::do_quick_booking( (array) $request->get_json_params() ) );
			},
		) );

		register_rest_route( self::NS, '/mark-delivered', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => function ( $request ) {
				return self::wrap( self::do_mark_delivered( (array) $request->get_json_params() ) );
			},
		) );

		register_rest_route( self::NS, '/confirm-booking-request', array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => function ( $request ) {
				$body = (array) $request->get_json_params();
				if ( empty( $body['inquiry_id'] ) ) {
					return new WP_Error( 'bad_request', 'inquiry_id is required.', array( 'status' => 400 ) );
				}
				return self::wrap( Infocus_ERP_Booking_Calendar::confirm_booking_request(
					(int) $body['inquiry_id'],
					$body['session_time'] ?? null,
					$body['package_price'] ?? null
				) );
			},
		) );

		register_rest_route( self::NS, '/inquiries', array(
			'methods'             => 'GET',
			'permission_callback' => array( 'Infocus_ERP_Security', 'check_admin_or_api_key' ),
			'callback'            => function ( $request ) {
				$where = array();
				if ( $request->get_param( 'status' ) ) {
					$where['status'] = sanitize_text_field( $request->get_param( 'status' ) );
				}
				return self::wrap( self::do_list( 'inquiries', $where ) );
			},
		) );

		register_rest_route( self::NS, '/calendar-month', array(
			'methods'             => 'GET',
			'permission_callback' => array( 'Infocus_ERP_Security', 'check_admin_or_api_key' ),
			'callback'            => function ( $request ) {
				$year  = (int) ( $request->get_param( 'year' ) ?: gmdate( 'Y' ) );
				$month = (int) ( $request->get_param( 'month' ) ?: gmdate( 'n' ) );
				return self::wrap( Infocus_ERP_Reports::bookings_for_month( $year, $month ) );
			},
		) );

		register_rest_route( self::NS, '/invoices', array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => function ( $request ) {
				return self::wrap( self::do_list( 'invoices', array() ) );
			},
		) );

		$admin_perm = array( 'Infocus_ERP_Security', 'check_admin_or_api_key' );

		foreach ( array( 'customers', 'bookings', 'payments', 'employees', 'pipeline', 'expenses', 'packages' ) as $entity ) {
			register_rest_route( self::NS, "/$entity", array(
				array(
					'methods'             => 'GET',
					'permission_callback' => $admin_perm,
					'callback'            => function ( $request ) use ( $entity ) {
						$where = array();
						if ( $request->get_param( 'status' ) ) $where['status'] = sanitize_text_field( $request->get_param( 'status' ) );
						return self::wrap( self::do_list( $entity, $where ) );
					},
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => $admin_perm,
					'callback'            => function ( $request ) use ( $entity ) {
						return self::wrap( self::do_create( $entity, (array) $request->get_json_params() ) );
					},
				),
			) );

			register_rest_route( self::NS, "/$entity/(?P<id>\d+)", array(
				array(
					'methods'             => 'GET',
					'permission_callback' => $admin_perm,
					'callback'            => function ( $request ) use ( $entity ) {
						$row = Infocus_ERP_CRUD::get( $entity, (int) $request['id'] );
						return $row ? rest_ensure_response( $row ) : new WP_Error( 'not_found', 'Not found', array( 'status' => 404 ) );
					},
				),
				array(
					'methods'             => 'PUT',
					'permission_callback' => $admin_perm,
					'callback'            => function ( $request ) use ( $entity ) {
						return self::wrap( self::do_update( $entity, (int) $request['id'], (array) $request->get_json_params() ) );
					},
				),
				array(
					'methods'             => 'DELETE',
					'permission_callback' => $admin_perm,
					'callback'            => function ( $request ) use ( $entity ) {
						return self::wrap( self::do_delete( $entity, (int) $request['id'] ) );
					},
				),
			) );
		}

		// Booking-scoped "generate a client link" actions. Same underlying
		// logic the MCP tools (get_requirements_link, get_image_selection_link,
		// get_invoice_link) call — exposed here so the admin's Bookings screen
		// can trigger them directly instead of a full-page admin-post redirect.
		register_rest_route( self::NS, '/bookings/(?P<id>\d+)/requirements-link', array(
			'methods'             => 'GET',
			'permission_callback' => $admin_perm,
			'callback'            => function ( $request ) {
				return self::wrap( Infocus_ERP_Public_Forms::get_or_create_requirements_link( (int) $request['id'] ) );
			},
		) );

		register_rest_route( self::NS, '/bookings/(?P<id>\d+)/image-selection-link', array(
			'methods'             => 'GET',
			'permission_callback' => $admin_perm,
			'callback'            => function ( $request ) {
				return self::wrap( Infocus_ERP_Image_Selection_Form::get_or_create_image_link( (int) $request['id'] ) );
			},
		) );

		register_rest_route( self::NS, '/bookings/(?P<id>\d+)/invoice-link', array(
			'methods'             => 'GET',
			'permission_callback' => $admin_perm,
			'callback'            => function ( $request ) {
				return self::wrap( Infocus_ERP_Invoices::generate_invoice_for_booking( (int) $request['id'] ) );
			},
		) );
	}

	/** Turns a plain array/WP_Error result into a REST response. Used by routes; MCP calls do_* directly. */
	private static function wrap( $result ) {
		return ( $result instanceof WP_Error ) ? $result : rest_ensure_response( $result );
	}

	/* ------------------------------------------------------------------ */

	public static function do_summary( $args ) {
		return Infocus_ERP_Reports::summary( $args );
	}

	public static function do_list( $entity, $where = array() ) {
		return Infocus_ERP_CRUD::get_all( $entity, array( 'where' => $where, 'orderby' => 'id', 'order' => 'DESC' ) );
	}

	public static function do_create( $entity, $body ) {
		$data = self::sanitize_payload( $body );
		if ( empty( $data ) ) {
			return new WP_Error( 'bad_request', 'No data supplied', array( 'status' => 400 ) );
		}
		$id = Infocus_ERP_CRUD::insert( $entity, $data );
		return array( 'id' => $id, 'created' => true );
	}

	public static function do_update( $entity, $id, $body ) {
		Infocus_ERP_CRUD::update( $entity, $id, self::sanitize_payload( $body ) );
		return array( 'id' => $id, 'updated' => true );
	}

	/**
	 * do_delete( entity, id )
	 * Permanently removes a record. This is a real, irreversible delete —
	 * no soft-delete/trash. The MCP tool wrapping this requires the caller
	 * to have gotten explicit confirmation from Kartik before calling it;
	 * this method itself does not ask for confirmation, it just deletes.
	 */
	public static function do_delete( $entity, $id ) {
		$existing = Infocus_ERP_CRUD::get( $entity, $id );
		if ( ! $existing ) {
			return new WP_Error( 'not_found', 'No record found to delete.', array( 'status' => 404 ) );
		}
		Infocus_ERP_CRUD::delete( $entity, $id );
		return array( 'id' => $id, 'deleted' => true, 'entity' => $entity );
	}

	/**
	 * do_quick_expense( body )
	 * body: { description, amount, category, vendor, date, booking_id }
	 * Only "amount" is required; category is guessed from description/vendor
	 * by keyword if not one of the six exact categories.
	 */
	public static function do_quick_expense( $body ) {
		$amount = isset( $body['amount'] ) ? floatval( $body['amount'] ) : 0;
		if ( $amount <= 0 ) {
			return new WP_Error( 'bad_request', 'A positive "amount" is required.', array( 'status' => 400 ) );
		}

		$known_categories = array( 'Gear', 'Props', 'Studio Rent', 'Editor Payout', 'Marketing/Ads', 'Travel', 'Software/Subscriptions', 'Other' );
		$category         = isset( $body['category'] ) ? sanitize_text_field( $body['category'] ) : '';

		if ( ! in_array( $category, $known_categories, true ) ) {
			$category = self::guess_expense_category( ( $body['description'] ?? '' ) . ' ' . ( $body['vendor'] ?? '' ) . ' ' . $category );
		}

		$id = Infocus_ERP_CRUD::insert( 'expenses', array(
			'category'     => $category,
			'amount'       => $amount,
			'expense_date' => ! empty( $body['date'] ) ? sanitize_text_field( $body['date'] ) : current_time( 'Y-m-d' ),
			'booking_id'   => ! empty( $body['booking_id'] ) ? (int) $body['booking_id'] : null,
			'vendor'       => sanitize_text_field( $body['vendor'] ?? '' ),
			'notes'        => sanitize_textarea_field( $body['description'] ?? ( $body['notes'] ?? '' ) ),
		) );

		return array(
			'created'  => true,
			'id'       => $id,
			'category' => $category,
			'amount'   => $amount,
			'message'  => "Logged ₹$amount under \"$category\".",
		);
	}

	private static function guess_expense_category( $text ) {
		$text = strtolower( $text );
		$map  = array(
			'Marketing/Ads'          => array( 'ad', 'ads', 'advert', 'instagram', 'facebook', 'meta ', 'boost', 'promotion', 'campaign' ),
			'Travel'                 => array( 'travel', 'cab', 'uber', 'ola', 'flight', 'petrol', 'fuel', 'toll', 'taxi', 'train', 'auto' ),
			'Editor Payout'          => array( 'editor', 'editing', 'retouch', 'payout' ),
			'Studio Rent'            => array( 'rent', 'studio fee', 'lease' ),
			'Gear'                   => array( 'camera', 'lens', 'gear', 'tripod', 'light', 'flash', 'equipment', 'battery', 'memory card' ),
			'Props'                  => array( 'prop', 'gown', 'outfit', 'backdrop', 'decor' ),
			'Software/Subscriptions' => array( 'subscription', 'software', 'adobe', 'lightroom', 'photoshop', 'canva', 'saas', 'hosting', 'domain' ),
		);
		foreach ( $map as $category => $keywords ) {
			foreach ( $keywords as $kw ) {
				if ( strpos( $text, $kw ) !== false ) return $category;
			}
		}
		return 'Other';
	}

	/**
	 * do_quick_payment( body )
	 * body: { customer_name, amount, date, method, type, notes, booking_id }
	 * If booking_id is given, logs directly. Otherwise matches customer name,
	 * then finds their booking with a balance due. Ambiguous matches return
	 * needs_confirmation instead of guessing.
	 */
	public static function do_quick_payment( $body ) {
		global $wpdb;
		$amount = isset( $body['amount'] ) ? floatval( $body['amount'] ) : 0;
		if ( $amount <= 0 ) {
			return new WP_Error( 'bad_request', 'A positive "amount" is required.', array( 'status' => 400 ) );
		}

		$payment_data = array(
			'amount'       => $amount,
			'payment_date' => ! empty( $body['date'] ) ? sanitize_text_field( $body['date'] ) : current_time( 'Y-m-d' ),
			'method'       => sanitize_text_field( $body['method'] ?? '' ),
			'type'         => sanitize_text_field( $body['type'] ?? 'Advance' ),
			'notes'        => sanitize_textarea_field( $body['notes'] ?? '' ),
		);

		if ( ! empty( $body['booking_id'] ) ) {
			$payment_data['booking_id'] = (int) $body['booking_id'];
			$id = Infocus_ERP_CRUD::insert( 'payments', $payment_data );
			return array( 'created' => true, 'id' => $id, 'booking_id' => $payment_data['booking_id'] );
		}

		if ( empty( $body['customer_name'] ) ) {
			return new WP_Error( 'bad_request', 'Provide either "booking_id" or "customer_name".', array( 'status' => 400 ) );
		}

		$t         = Infocus_ERP_DB::tables();
		$name      = '%' . $wpdb->esc_like( sanitize_text_field( $body['customer_name'] ) ) . '%';
		$customers = $wpdb->get_results( $wpdb->prepare( "SELECT id, name, phone FROM {$t['customers']} WHERE name LIKE %s", $name ), ARRAY_A );

		if ( empty( $customers ) ) {
			return new WP_Error( 'not_found', 'No customer matches "' . $body['customer_name'] . '". Add them as a customer first, or check spelling.', array( 'status' => 404 ) );
		}
		if ( count( $customers ) > 1 ) {
			return array(
				'created' => false, 'needs_confirmation' => true, 'reason' => 'multiple_customers',
				'options' => $customers,
				'message' => 'More than one customer matches that name. Confirm which one, then resend with a "booking_id".',
			);
		}

		$customer = $customers[0];
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.id, b.service_type, b.session_date, b.package_price,
					COALESCE(p.paid,0) AS paid, (b.package_price - COALESCE(p.paid,0)) AS balance
			 FROM {$t['bookings']} b
			 LEFT JOIN ( SELECT booking_id, SUM(amount) AS paid FROM {$t['payments']} GROUP BY booking_id ) p ON p.booking_id = b.id
			 WHERE b.customer_id = %d AND b.status != 'Cancelled'
			 ORDER BY b.session_date DESC",
			$customer['id']
		), ARRAY_A );

		if ( empty( $bookings ) ) {
			return new WP_Error( 'not_found', $customer['name'] . ' has no bookings yet. Create a booking first.', array( 'status' => 404 ) );
		}

		$outstanding = array_values( array_filter( $bookings, function ( $b ) { return (float) $b['balance'] > 0; } ) );

		if ( count( $outstanding ) === 1 ) {
			$target = $outstanding[0];
		} elseif ( count( $outstanding ) > 1 ) {
			return array(
				'created' => false, 'needs_confirmation' => true, 'reason' => 'multiple_bookings',
				'customer' => $customer, 'options' => $outstanding,
				'message' => $customer['name'] . ' has more than one booking with a balance due. Confirm which one, then resend with a "booking_id".',
			);
		} else {
			$target = $bookings[0];
		}

		$payment_data['booking_id'] = (int) $target['id'];
		$id = Infocus_ERP_CRUD::insert( 'payments', $payment_data );

		return array(
			'created'     => true,
			'id'          => $id,
			'customer'    => $customer['name'],
			'booking_id'  => (int) $target['id'],
			'new_balance' => (float) $target['balance'] - $amount,
			'message'     => "Logged ₹$amount from {$customer['name']} against their {$target['service_type']} booking ({$target['session_date']}).",
		);
	}

	/**
	 * do_quick_booking( body )
	 * body: { customer_name, phone, service_type, session_date, session_time,
	 *         location, package_price, status, notes, advance_amount,
	 *         advance_date, advance_method, advance_type }
	 * Creates the customer if new, creates the booking, optionally logs an
	 * advance payment — all in one call.
	 */
	public static function do_quick_booking( $body ) {
		if ( empty( $body['customer_name'] ) ) {
			return new WP_Error( 'bad_request', 'A "customer_name" is required.', array( 'status' => 400 ) );
		}

		$customer_result = self::resolve_or_create_customer(
			sanitize_text_field( $body['customer_name'] ),
			sanitize_text_field( $body['phone'] ?? '' )
		);

		if ( isset( $customer_result['needs_confirmation'] ) ) {
			return $customer_result;
		}
		$customer = $customer_result;

		$known_services = array( 'Maternity', 'Newborn', 'Kids', 'Family', 'Wedding', 'Commercial', 'Corporate & Events', 'Other' );
		$service_type   = isset( $body['service_type'] ) ? sanitize_text_field( $body['service_type'] ) : '';
		if ( ! in_array( $service_type, $known_services, true ) ) {
			$service_type = self::guess_service_type( $service_type );
		}

		$booking_data = array(
			'customer_id'   => (int) $customer['id'],
			'service_type'  => $service_type,
			'session_date'  => ! empty( $body['session_date'] ) ? sanitize_text_field( $body['session_date'] ) : null,
			'session_time'  => ! empty( $body['session_time'] ) ? sanitize_text_field( $body['session_time'] ) : null,
			'location'      => sanitize_text_field( $body['location'] ?? '' ),
			'package_price' => isset( $body['package_price'] ) ? floatval( $body['package_price'] ) : 0,
			'status'        => sanitize_text_field( $body['status'] ?? 'Confirmed' ),
			'notes'         => sanitize_textarea_field( $body['notes'] ?? '' ),
		);

		$booking_id = Infocus_ERP_CRUD::insert( 'bookings', $booking_data );

		$response = array(
			'created'        => true,
			'customer'       => $customer['name'],
			'customer_new'   => ! empty( $customer_result['is_new'] ),
			'booking_id'     => $booking_id,
			'service_type'   => $service_type,
			'session_date'   => $booking_data['session_date'],
			'package_price'  => $booking_data['package_price'],
			'advance_logged' => false,
		);

		if ( ! empty( $body['advance_amount'] ) && floatval( $body['advance_amount'] ) > 0 ) {
			$advance_amount = floatval( $body['advance_amount'] );
			Infocus_ERP_CRUD::insert( 'payments', array(
				'booking_id'   => $booking_id,
				'amount'       => $advance_amount,
				'payment_date' => ! empty( $body['advance_date'] ) ? sanitize_text_field( $body['advance_date'] ) : current_time( 'Y-m-d' ),
				'method'       => sanitize_text_field( $body['advance_method'] ?? '' ),
				'type'         => sanitize_text_field( $body['advance_type'] ?? 'Advance' ),
				'notes'        => 'Logged automatically with booking creation.',
			) );
			$response['advance_logged'] = true;
			$response['advance_amount'] = $advance_amount;
			$response['balance_due']    = $booking_data['package_price'] - $advance_amount;
		}

		$response['message'] = "Booked {$customer['name']} for {$service_type}" .
			( $booking_data['session_date'] ? " on {$booking_data['session_date']}" : '' ) .
			( $response['advance_logged'] ? ", ₹{$response['advance_amount']} advance logged." : '.' );

		return $response;
	}

	/**
	 * do_mark_delivered( body )
	 * body: { booking_id OR customer_name, delivered_date, notes }
	 * Marks a job fully delivered in one call: sets the booking's own
	 * status to Delivered, and updates (or creates) its Editor Pipeline
	 * row with status Delivered + the delivered_date — both fields the
	 * Dashboard's progress bars actually key off, so this closes it out
	 * everywhere at once instead of needing two separate screen visits.
	 */
	public static function do_mark_delivered( $body ) {
		global $wpdb;
		$t = Infocus_ERP_DB::tables();

		if ( ! empty( $body['booking_id'] ) ) {
			$booking = Infocus_ERP_CRUD::get( 'bookings', (int) $body['booking_id'] );
			if ( ! $booking ) return new WP_Error( 'not_found', 'No booking with that ID.', array( 'status' => 404 ) );
		} elseif ( ! empty( $body['customer_name'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $body['customer_name'] ) ) . '%';
			$customer = $wpdb->get_row( $wpdb->prepare( "SELECT id, name FROM {$t['customers']} WHERE name LIKE %s", $like ), ARRAY_A );
			if ( ! $customer ) return new WP_Error( 'not_found', 'No customer matches "' . $body['customer_name'] . '".', array( 'status' => 404 ) );

			$bookings = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$t['bookings']} WHERE customer_id = %d AND status != 'Delivered' AND status != 'Cancelled' ORDER BY session_date DESC",
				$customer['id']
			), ARRAY_A );

			if ( empty( $bookings ) ) {
				return new WP_Error( 'not_found', $customer['name'] . ' has no bookings still awaiting delivery.', array( 'status' => 404 ) );
			}
			if ( count( $bookings ) > 1 ) {
				return array(
					'created' => false, 'needs_confirmation' => true, 'reason' => 'multiple_bookings',
					'customer' => $customer, 'options' => $bookings,
					'message'  => $customer['name'] . ' has more than one booking not yet delivered. Confirm which one, then resend with a "booking_id".',
				);
			}
			$booking = $bookings[0];
		} else {
			return new WP_Error( 'bad_request', 'Provide either "booking_id" or "customer_name".', array( 'status' => 400 ) );
		}

		$delivered_date = ! empty( $body['delivered_date'] ) ? sanitize_text_field( $body['delivered_date'] ) : current_time( 'Y-m-d' );

		Infocus_ERP_CRUD::update( 'bookings', $booking['id'], array( 'status' => 'Delivered' ) );

		$pipeline_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['pipeline']} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking['id'] ), ARRAY_A );
		$pipeline_data = array(
			'status'         => 'Delivered',
			'delivered_date' => $delivered_date,
		);
		if ( ! empty( $body['notes'] ) ) $pipeline_data['notes'] = sanitize_textarea_field( $body['notes'] );

		if ( $pipeline_row ) {
			Infocus_ERP_CRUD::update( 'pipeline', $pipeline_row['id'], $pipeline_data );
			$pipeline_id = $pipeline_row['id'];
		} else {
			$pipeline_data['booking_id'] = $booking['id'];
			$pipeline_id = Infocus_ERP_CRUD::insert( 'pipeline', $pipeline_data );
		}

		return array(
			'delivered'      => true,
			'booking_id'     => (int) $booking['id'],
			'pipeline_id'    => (int) $pipeline_id,
			'delivered_date' => $delivered_date,
			'message'        => "Marked booking #{$booking['id']} as Delivered on {$delivered_date}.",
		);
	}

	/** Shared by do_quick_payment and do_quick_booking. */
	public static function resolve_or_create_customer( $name, $phone = '' ) {
		global $wpdb;
		$t         = Infocus_ERP_DB::tables();
		$like      = '%' . $wpdb->esc_like( $name ) . '%';
		$customers = $wpdb->get_results( $wpdb->prepare( "SELECT id, name, phone FROM {$t['customers']} WHERE name LIKE %s", $like ), ARRAY_A );

		if ( count( $customers ) === 1 ) return $customers[0];

		if ( count( $customers ) > 1 ) {
			return array(
				'needs_confirmation' => true,
				'reason'             => 'multiple_customers',
				'options'            => $customers,
				'message'            => 'More than one existing customer matches "' . $name . '". Confirm which one (or that this is a new person), then resend.',
			);
		}

		$new_id = Infocus_ERP_CRUD::insert( 'customers', array( 'name' => $name, 'phone' => $phone, 'source' => 'Other' ) );
		return array( 'id' => $new_id, 'name' => $name, 'phone' => $phone, 'is_new' => true );
	}

	public static function guess_service_type( $text ) {
		$text = strtolower( $text );
		$map  = array(
			'Maternity'          => array( 'maternity', 'pregnan', 'baby bump', 'bump' ),
			'Newborn'            => array( 'newborn', 'new born', 'infant' ),
			'Kids'               => array( 'kid', 'child', 'toddler' ),
			'Family'             => array( 'family' ),
			'Wedding'            => array( 'wedding', 'shaadi', 'marriage' ),
			'Commercial'         => array( 'commercial', 'product', 'brand shoot' ),
			'Corporate & Events' => array( 'corporate', 'event', 'conference' ),
		);
		foreach ( $map as $service => $keywords ) {
			foreach ( $keywords as $kw ) {
				if ( strpos( $text, $kw ) !== false ) return $service;
			}
		}
		return 'Other';
	}

	private static function sanitize_payload( $payload ) {
		$clean = array();
		if ( ! is_array( $payload ) ) return $clean;
		foreach ( $payload as $key => $value ) {
			$key = preg_replace( '/[^a-zA-Z0-9_]/', '', $key );
			if ( $key === '' || $key === 'id' || $key === 'created_at' ) continue;
			$clean[ $key ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
		}
		return $clean;
	}
}
