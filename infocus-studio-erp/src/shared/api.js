import apiFetch from '@wordpress/api-fetch';

const NS = '/infocus-erp/v1';

export function getSummary( { from, to } = {} ) {
	const query = new URLSearchParams();
	if ( from ) query.set( 'from', from );
	if ( to ) query.set( 'to', to );
	const qs = query.toString();
	return apiFetch( { path: `${ NS }/summary${ qs ? `?${ qs }` : '' }` } );
}

export function getCalendarMonth( year, month ) {
	return apiFetch( { path: `${ NS }/calendar-month?year=${ year }&month=${ month }` } );
}

export function getInquiries( { status } = {} ) {
	const qs = status ? `?status=${ encodeURIComponent( status ) }` : '';
	return apiFetch( { path: `${ NS }/inquiries${ qs }` } );
}

export function listEntity( entity ) {
	return apiFetch( { path: `${ NS }/${ entity }` } );
}

export function getEntityRecord( entity, id ) {
	return apiFetch( { path: `${ NS }/${ entity }/${ id }` } );
}

export function createEntityRecord( entity, fields ) {
	return apiFetch( { path: `${ NS }/${ entity }`, method: 'POST', data: fields } );
}

export function updateEntityRecord( entity, id, fields ) {
	return apiFetch( { path: `${ NS }/${ entity }/${ id }`, method: 'PUT', data: fields } );
}

export function deleteEntityRecord( entity, id ) {
	return apiFetch( { path: `${ NS }/${ entity }/${ id }`, method: 'DELETE' } );
}

export function getRequirementsLink( bookingId ) {
	return apiFetch( { path: `${ NS }/bookings/${ bookingId }/requirements-link` } );
}

export function getImageSelectionLink( bookingId ) {
	return apiFetch( { path: `${ NS }/bookings/${ bookingId }/image-selection-link` } );
}

export function getInvoiceLink( bookingId ) {
	return apiFetch( { path: `${ NS }/bookings/${ bookingId }/invoice-link` } );
}

export function updateInquiry( id, fields ) {
	return apiFetch( { path: `${ NS }/inquiries/${ id }`, method: 'PUT', data: fields } );
}

export function approveInquiry( id ) {
	return apiFetch( { path: `${ NS }/inquiries/${ id }/approve`, method: 'POST' } );
}

export function rejectInquiry( id ) {
	return apiFetch( { path: `${ NS }/inquiries/${ id }/reject`, method: 'POST' } );
}

export function getInquiryWhatsappLink( id ) {
	return apiFetch( { path: `${ NS }/inquiries/${ id }/whatsapp-link` } );
}

export function getShootRequirements() {
	return apiFetch( { path: `${ NS }/shoot-requirements` } );
}

export function getImageSelections() {
	return apiFetch( { path: `${ NS }/image-selections` } );
}

export function setImageSelectionLock( id, lockOverride ) {
	return apiFetch( { path: `${ NS }/image-selections/${ id }`, method: 'PUT', data: { lock_override: lockOverride } } );
}

export function getInvoices() {
	return apiFetch( { path: `${ NS }/invoices` } );
}

export function createCustomInvoice( payload ) {
	return apiFetch( { path: `${ NS }/invoices/custom`, method: 'POST', data: payload } );
}
