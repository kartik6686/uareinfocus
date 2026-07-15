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
