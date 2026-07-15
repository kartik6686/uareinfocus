const inr = new Intl.NumberFormat( 'en-IN', { maximumFractionDigits: 0 } );

export function money( amount ) {
	return `₹${ inr.format( Math.round( Number( amount ) || 0 ) ) }`;
}

export function shortDate( isoDate ) {
	if ( ! isoDate ) return '';
	const d = new Date( `${ isoDate }T00:00:00` );
	if ( Number.isNaN( d.getTime() ) ) return isoDate;
	return d.toLocaleDateString( 'en-IN', { day: '2-digit', month: 'short', year: 'numeric' } );
}

export function initials( name ) {
	if ( ! name ) return '?';
	return name
		.trim()
		.split( /\s+/ )
		.slice( 0, 2 )
		.map( ( p ) => p.charAt( 0 ).toUpperCase() )
		.join( '' );
}
