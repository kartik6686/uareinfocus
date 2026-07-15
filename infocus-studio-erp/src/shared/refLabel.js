// Mirrors Infocus_ERP_Admin_Pages::ref_label() on the PHP side: given a row
// from a referenced entity (e.g. a booking referenced by a payment), build
// the same human-readable label the old admin screens showed.
export function refLabel( refEntity, row, customersById ) {
	if ( ! row ) return '—';
	if ( refEntity === 'bookings' ) {
		const c = customersById ? customersById[ row.customer_id ] : null;
		const parts = [ c ? c.name : 'Unknown client', c ? c.phone : '', row.service_type, row.session_date ];
		return parts.filter( Boolean ).join( ' — ' );
	}
	return row.name || `#${ row.id }`;
}
