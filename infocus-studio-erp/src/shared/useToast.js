import { useState, useCallback } from '@wordpress/element';

export default function useToast() {
	const [ toast, setToast ] = useState( null );

	const showToast = useCallback( ( message, ms = 2500 ) => {
		setToast( message );
		window.setTimeout( () => setToast( null ), ms );
	}, [] );

	const copyLink = useCallback(
		( link, label ) => {
			if ( link && navigator.clipboard ) {
				navigator.clipboard.writeText( link ).catch( () => {} );
			}
			showToast( link ? `${ label } copied to clipboard.` : `${ label } ready.` );
		},
		[ showToast ]
	);

	return { toast, showToast, copyLink };
}
