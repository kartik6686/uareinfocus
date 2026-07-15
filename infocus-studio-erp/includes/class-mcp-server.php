<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * A complete MCP (Model Context Protocol) server, built directly into this
 * plugin — there is no separate "bridge" app to install or host. Claude
 * connects straight to this one URL:
 *
 *   https://yoursite.com/wp-json/infocus-erp/v1/mcp/<token>
 *
 * The token is part of the URL itself (see class-security.php), so pasting
 * that single URL into Claude's "Add custom connector" screen is the entire
 * setup — no separate login step. Every tool below just calls the same
 * do_* methods the REST API uses (class-rest-api.php), so behaviour is
 * identical whether you're using the ERP dashboard, the REST API, or Claude.
 */
class Infocus_ERP_MCP_Server {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	public static function register_route() {
		register_rest_route( Infocus_ERP_REST_API::NS, '/mcp/(?P<token>[a-zA-Z0-9]+)', array(
			'methods'             => 'POST',
			'permission_callback' => array( 'Infocus_ERP_Security', 'check_mcp_token' ),
			'callback'            => array( __CLASS__, 'handle' ),
		) );
	}

	public static function handle( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = json_decode( $request->get_body(), true );
		}
		if ( ! is_array( $body ) ) {
			return new WP_REST_Response( self::error_response( null, -32700, 'Parse error' ), 200 );
		}

		$id     = $body['id'] ?? null;
		$method = $body['method'] ?? '';
		$params = $body['params'] ?? array();

		// Notifications (no "id") get no JSON-RPC response body — just acknowledge.
		if ( $id === null && strpos( $method, 'notifications/' ) === 0 ) {
			return new WP_REST_Response( null, 202 );
		}

		switch ( $method ) {
			case 'initialize':
				return new WP_REST_Response( self::success_response( $id, array(
					'protocolVersion' => $params['protocolVersion'] ?? '2025-06-18',
					'capabilities'    => array( 'tools' => new stdClass() ),
					'serverInfo'      => array( 'name' => 'Infocus Studio ERP', 'version' => INFOCUS_ERP_VERSION ),
				) ), 200 );

			case 'ping':
				return new WP_REST_Response( self::success_response( $id, new stdClass() ), 200 );

			case 'tools/list':
				return new WP_REST_Response( self::success_response( $id, array( 'tools' => self::tool_definitions() ) ), 200 );

			case 'tools/call':
				return new WP_REST_Response( self::success_response( $id, self::call_tool( $params['name'] ?? '', $params['arguments'] ?? array() ) ), 200 );

			default:
				return new WP_REST_Response( self::error_response( $id, -32601, 'Method not found: ' . $method ), 200 );
		}
	}

	private static function success_response( $id, $result ) {
		return array( 'jsonrpc' => '2.0', 'id' => $id, 'result' => $result );
	}

	private static function error_response( $id, $code, $message ) {
		return array( 'jsonrpc' => '2.0', 'id' => $id, 'error' => array( 'code' => $code, 'message' => $message ) );
	}

	/* ------------------------------------------------------------------ */

	private static function tool_definitions() {
		$entity_enum = array( 'customers', 'bookings', 'payments', 'employees', 'pipeline', 'expenses', 'inquiries', 'packages', 'invoices' );

		return array(
			array(
				'name'        => 'get_studio_summary',
				'description' => 'Get the full dashboard picture: revenue collected, total expenses, net profit, outstanding balances, expenses by category, editing pipeline status, overdue deliveries, and upcoming bookings.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'from' => array( 'type' => 'string', 'description' => 'Start date YYYY-MM-DD (optional)' ),
						'to'   => array( 'type' => 'string', 'description' => 'End date YYYY-MM-DD (optional)' ),
					),
				),
			),
			array(
				'name'        => 'list_records',
				'description' => 'List raw records from any section of the studio ERP: customers, bookings, payments, employees, editor pipeline, expenses, or public inquiries.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'entity' => array( 'type' => 'string', 'enum' => $entity_enum ),
						'status' => array( 'type' => 'string', 'description' => 'Optional exact status filter, e.g. "Confirmed" or "Overdue".' ),
					),
					'required'   => array( 'entity' ),
				),
			),
			array(
				'name'        => 'log_expense',
				'description' => 'Log a business expense from a plain description. The category (Gear, Props, Studio Rent, Editor Payout, Marketing/Ads, Travel, Software/Subscriptions, Other) is guessed automatically from the description unless given explicitly.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'amount'      => array( 'type' => 'number' ),
						'description' => array( 'type' => 'string', 'description' => 'What the expense was for, e.g. "instagram ads" or "cab to the shoot".' ),
						'category'    => array( 'type' => 'string', 'description' => 'Optional exact category if known.' ),
						'vendor'      => array( 'type' => 'string' ),
						'date'        => array( 'type' => 'string', 'description' => 'YYYY-MM-DD, defaults to today.' ),
					),
					'required'   => array( 'amount' ),
				),
			),
			array(
				'name'        => 'log_payment',
				'description' => 'Log a payment received from a client, by name. The right booking (the one with a balance due) is found automatically. If the name or the booking is ambiguous, the response asks for confirmation instead of guessing — surface that question to the user rather than picking one.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'customer_name' => array( 'type' => 'string' ),
						'amount'        => array( 'type' => 'number' ),
						'date'          => array( 'type' => 'string' ),
						'method'        => array( 'type' => 'string', 'enum' => array( 'Cash', 'UPI', 'Bank Transfer', 'Card', 'Other' ) ),
						'type'          => array( 'type' => 'string', 'enum' => array( 'Advance', 'Balance', 'Full', 'Refund' ) ),
						'booking_id'    => array( 'type' => 'integer', 'description' => 'Use only if you already know the exact booking.' ),
					),
					'required'   => array( 'amount' ),
				),
			),
			array(
				'name'        => 'create_booking',
				'description' => 'Create a new booking in one call — creates the customer too if they are new, and can log an advance payment against the new booking in the same call.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'customer_name'   => array( 'type' => 'string' ),
						'phone'           => array( 'type' => 'string' ),
						'service_type'    => array( 'type' => 'string', 'description' => 'Free text is fine, e.g. "maternity photoshoot" — matched automatically.' ),
						'session_date'    => array( 'type' => 'string', 'description' => 'YYYY-MM-DD. Resolve relative dates like "next Saturday" yourself before calling this.' ),
						'session_time'    => array( 'type' => 'string' ),
						'location'        => array( 'type' => 'string' ),
						'package_price'   => array( 'type' => 'number' ),
						'status'          => array( 'type' => 'string', 'enum' => array( 'Inquiry', 'Confirmed', 'Shot', 'Editing', 'Delivered', 'Cancelled' ) ),
						'notes'           => array( 'type' => 'string' ),
						'advance_amount'  => array( 'type' => 'number' ),
						'advance_date'    => array( 'type' => 'string' ),
						'advance_method'  => array( 'type' => 'string' ),
					),
					'required'   => array( 'customer_name' ),
				),
			),
			array(
				'name'        => 'get_shoot_requirements',
				'description' => 'Get the shoot-day requirements a client submitted for a specific booking — preferred lighting/time, theme, outfit ideas, location, reference links, and any other requests.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array( 'booking_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'booking_id' ),
				),
			),
			array(
				'name'        => 'get_image_selection',
				'description' => 'Get which raw image numbers a client selected for editing on a specific booking, plus how many were extra beyond their package and the estimated additional charge (₹1000 per 2 extra images).',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array( 'booking_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'booking_id' ),
				),
			),
			array(
				'name'        => 'approve_inquiry',
				'description' => 'Approve a "Pending Review" inquiry from the public form — this creates the customer record and moves it into the normal follow-up workflow. Only use this after the person (Kartik) has confirmed they want to approve it — never approve on your own judgment alone.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array( 'inquiry_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'inquiry_id' ),
				),
			),
			array(
				'name'        => 'reject_inquiry',
				'description' => 'Reject a "Pending Review" inquiry — no customer record is created. Only use this after explicit confirmation from Kartik.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array( 'inquiry_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'inquiry_id' ),
				),
			),
			array(
				'name'        => 'get_requirements_link',
				'description' => 'Get (creating if needed) the private shoot-requirements link for a booking, ready to send to the client.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array( 'booking_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'booking_id' ),
				),
			),
			array(
				'name'        => 'get_image_selection_link',
				'description' => 'Get (creating if needed) the private image-selection link for a booking, ready to send to the client.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array( 'booking_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'booking_id' ),
				),
			),
			array(
				'name'        => 'get_invoice_link',
				'description' => 'Get (creating if needed) the invoice for a booking — auto-pulls package price, advance paid, and any extra-image charge — and return its private link, ready to send to the client.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array( 'booking_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'booking_id' ),
				),
			),
			array(
				'name'        => 'get_whatsapp_link',
				'description' => 'Build a WhatsApp click-to-chat link with a message you write, pre-filled and addressed to a specific phone number. Compose the message text yourself based on context (e.g. a warm welcome, a package brochure link from list_records on "packages", or an invoice/requirements/image-selection link from the other tools) — this tool only turns your finished text into a clickable link. It does not send anything; Kartik still has to open the link and press send in WhatsApp himself.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'phone'   => array( 'type' => 'string', 'description' => 'Phone number, any reasonable format — will be normalized.' ),
						'message' => array( 'type' => 'string', 'description' => 'The full message text to pre-fill, written by you.' ),
					),
					'required'   => array( 'phone', 'message' ),
				),
			),
			array(
				'name'        => 'confirm_booking',
				'description' => 'Turns a "Pending Review" booking request (submitted through the [infocus_book_session] calendar on the website) into a real Confirmed booking, occupying that date and slot. IMPORTANT: only call this after Kartik has explicitly told you to confirm that specific request — never on your own judgment, even if the slot looks free. If the request came in against a slot that turns out to already be taken by the time you confirm it, say so and ask what he wants to do rather than double-booking silently.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'inquiry_id'     => array( 'type' => 'integer', 'description' => 'The inquiry ID from list_records on "inquiries".' ),
						'session_time'   => array( 'type' => 'string', 'description' => 'Optional override, HH:MM:SS. If omitted, it\'s worked out from the logged request (Morning = 09:00:00, Post-Lunch = 14:00:00).' ),
						'package_price'  => array( 'type' => 'number', 'description' => 'Optional — set the package price now if known.' ),
					),
					'required'   => array( 'inquiry_id' ),
				),
			),
			array(
				'name'        => 'mark_delivered',
				'description' => 'Marks a booking as fully delivered in one call — sets the booking\'s status to Delivered AND updates its Editor Pipeline entry (status + delivered date) at the same time, so the Dashboard\'s progress bars clear immediately instead of needing two separate edits. Use when told something like "mark [client]\'s booking as delivered today, no balance left" — resolve the date yourself (e.g. "today" → today\'s date) before calling.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'booking_id'     => array( 'type' => 'integer', 'description' => 'Use if known — otherwise provide customer_name.' ),
						'customer_name'  => array( 'type' => 'string', 'description' => 'Used to find the booking if booking_id isn\'t known. If more than one undelivered booking matches, you\'ll be asked to confirm which one instead of guessing.' ),
						'delivered_date' => array( 'type' => 'string', 'description' => 'YYYY-MM-DD, defaults to today.' ),
						'notes'          => array( 'type' => 'string' ),
					),
				),
			),
			array(
				'name'        => 'create_record',
				'description' => 'Creates a new record on any entity — customers, bookings, payments, employees, pipeline entries, expenses, inquiries, packages, or invoices. Prefer the dedicated tools where one exists (create_booking for bookings, log_expense for expenses, log_payment for payments) since they handle matching/linking automatically — use create_record for everything else, most commonly adding a new package to the Packages list. Only send the fields that actually apply; unknown fields are ignored.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'entity' => array( 'type' => 'string', 'enum' => $entity_enum ),
						'fields' => array(
							'type'        => 'object',
							'description' => 'Key/value pairs for the new record, e.g. {"name": "Golden Hour", "category": "Maternity", "price": 24000, "included_edits": 20, "is_popular": true, "description": "3 dress changes, 3 hr session, 5 setups..."}.',
						),
					),
					'required'   => array( 'entity', 'fields' ),
				),
			),
			array(
				'name'        => 'update_record',
				'description' => 'Updates one or more fields on a single existing record — customers, bookings, payments, employees, pipeline entries, expenses, inquiries, packages, or invoices. Use this for things like correcting an inquiry\'s preferred_date after a WhatsApp negotiation, fixing a typo in a customer\'s phone number, or adjusting a booking\'s session_time. Only send the fields that are actually changing. IMPORTANT: confirm with Kartik what should change before calling this if there\'s any ambiguity about which record or which value — don\'t guess.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'entity' => array( 'type' => 'string', 'enum' => $entity_enum ),
						'id'     => array( 'type' => 'integer' ),
						'fields' => array(
							'type'        => 'object',
							'description' => 'Key/value pairs of exactly the columns to change, e.g. {"preferred_date": "2026-08-14"} or {"session_time": "14:00:00", "notes": "moved from morning per client request"}.',
						),
					),
					'required'   => array( 'entity', 'id', 'fields' ),
				),
			),
			array(
				'name'        => 'delete_record',
				'description' => 'Permanently deletes a single record from any part of the ERP — customers, bookings, payments, employees, pipeline entries, expenses, inquiries, packages, or invoices. This is irreversible, there is no undo. IMPORTANT: only call this after Kartik has explicitly confirmed he wants that specific record deleted (e.g. after you\'ve shown him the record and he\'s said "yes, delete it") — never as a guess or on your own judgment. A very common, safe use: deleting a fake/spam/bot inquiry he has identified.',
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'entity' => array( 'type' => 'string', 'enum' => $entity_enum ),
						'id'     => array( 'type' => 'integer' ),
					),
					'required'   => array( 'entity', 'id' ),
				),
			),
		);
	}

	private static function call_tool( $name, $args ) {
		$args = is_array( $args ) ? $args : array();

		switch ( $name ) {
			case 'get_studio_summary':
				$result = Infocus_ERP_REST_API::do_summary( array( 'from' => $args['from'] ?? null, 'to' => $args['to'] ?? null ) );
				break;

			case 'list_records':
				$entity = sanitize_key( $args['entity'] ?? '' );
				$where  = array();
				if ( ! empty( $args['status'] ) ) $where['status'] = sanitize_text_field( $args['status'] );
				$result = Infocus_ERP_REST_API::do_list( $entity, $where );
				break;

			case 'log_expense':
				$result = Infocus_ERP_REST_API::do_quick_expense( $args );
				break;

			case 'log_payment':
				$result = Infocus_ERP_REST_API::do_quick_payment( $args );
				break;

			case 'create_booking':
				$result = Infocus_ERP_REST_API::do_quick_booking( $args );
				break;

			case 'get_shoot_requirements':
				$result = self::get_shoot_requirements( (int) ( $args['booking_id'] ?? 0 ) );
				break;

			case 'get_image_selection':
				$result = self::get_image_selection( (int) ( $args['booking_id'] ?? 0 ) );
				break;

			case 'approve_inquiry':
				$result = Infocus_ERP_Public_Forms::approve_inquiry( (int) ( $args['inquiry_id'] ?? 0 ) );
				break;

			case 'reject_inquiry':
				$result = Infocus_ERP_Public_Forms::reject_inquiry( (int) ( $args['inquiry_id'] ?? 0 ) );
				break;

			case 'get_requirements_link':
				$result = Infocus_ERP_Public_Forms::get_or_create_requirements_link( (int) ( $args['booking_id'] ?? 0 ) );
				break;

			case 'get_image_selection_link':
				$result = Infocus_ERP_Image_Selection_Form::get_or_create_image_link( (int) ( $args['booking_id'] ?? 0 ) );
				break;

			case 'get_invoice_link':
				$result = Infocus_ERP_Invoices::generate_invoice_for_booking( (int) ( $args['booking_id'] ?? 0 ) );
				break;

			case 'get_whatsapp_link':
				$link   = Infocus_ERP_Public_Forms::build_whatsapp_link( $args['phone'] ?? '', $args['message'] ?? '' );
				$result = array( 'link' => $link );
				break;

			case 'confirm_booking':
				if ( empty( $args['inquiry_id'] ) ) {
					return array( 'isError' => true, 'content' => array( array( 'type' => 'text', 'text' => 'An "inquiry_id" is required.' ) ) );
				}
				$result = Infocus_ERP_Booking_Calendar::confirm_booking_request(
					(int) $args['inquiry_id'],
					$args['session_time'] ?? null,
					$args['package_price'] ?? null
				);
				break;

			case 'mark_delivered':
				$result = Infocus_ERP_REST_API::do_mark_delivered( $args );
				break;

			case 'create_record':
				$entity = sanitize_key( $args['entity'] ?? '' );
				$fields = is_array( $args['fields'] ?? null ) ? $args['fields'] : array();
				if ( ! $entity || empty( $fields ) ) {
					return array( 'isError' => true, 'content' => array( array( 'type' => 'text', 'text' => '"entity" and a non-empty "fields" object are both required.' ) ) );
				}
				$result = Infocus_ERP_REST_API::do_create( $entity, $fields );
				break;

			case 'update_record':
				$entity = sanitize_key( $args['entity'] ?? '' );
				$id     = (int) ( $args['id'] ?? 0 );
				$fields = is_array( $args['fields'] ?? null ) ? $args['fields'] : array();
				if ( ! $entity || ! $id || empty( $fields ) ) {
					return array( 'isError' => true, 'content' => array( array( 'type' => 'text', 'text' => '"entity", "id", and a non-empty "fields" object are all required.' ) ) );
				}
				$result = Infocus_ERP_REST_API::do_update( $entity, $id, $fields );
				break;

			case 'delete_record':
				$entity = sanitize_key( $args['entity'] ?? '' );
				$id     = (int) ( $args['id'] ?? 0 );
				if ( ! $entity || ! $id ) {
					return array( 'isError' => true, 'content' => array( array( 'type' => 'text', 'text' => 'Both "entity" and "id" are required.' ) ) );
				}
				$result = Infocus_ERP_REST_API::do_delete( $entity, $id );
				break;

			default:
				return array( 'isError' => true, 'content' => array( array( 'type' => 'text', 'text' => 'Unknown tool: ' . $name ) ) );
		}

		if ( $result instanceof WP_Error ) {
			return array( 'isError' => true, 'content' => array( array( 'type' => 'text', 'text' => $result->get_error_message() ) ) );
		}

		return array( 'content' => array( array( 'type' => 'text', 'text' => wp_json_encode( $result ) ) ) );
	}

	private static function get_shoot_requirements( $booking_id ) {
		if ( ! $booking_id ) {
			return new WP_Error( 'bad_request', 'A "booking_id" is required.' );
		}
		$rows = Infocus_ERP_CRUD::get_all( 'shoot_requirements', array( 'where' => array( 'booking_id' => $booking_id ) ) );
		foreach ( $rows as &$row ) {
			$row['reference_links'] = ! empty( $row['reference_links'] ) ? json_decode( $row['reference_links'], true ) : array();
		}
		return $rows;
	}

	private static function get_image_selection( $booking_id ) {
		if ( ! $booking_id ) {
			return new WP_Error( 'bad_request', 'A "booking_id" is required.' );
		}
		$rows = Infocus_ERP_CRUD::get_all( 'image_selections', array( 'where' => array( 'booking_id' => $booking_id ) ) );
		foreach ( $rows as &$row ) {
			$row['selected_images'] = ! empty( $row['selected_images'] ) ? json_decode( $row['selected_images'], true ) : array();
		}
		return $rows;
	}
}
