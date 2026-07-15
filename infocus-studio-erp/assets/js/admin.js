// Infocus Studio ERP — admin JS
// Deliberately dependency-free to keep the plugin lightweight.
document.addEventListener( 'DOMContentLoaded', function () {
	// Confirm before any delete action (belt-and-braces alongside the inline onsubmit).
	document.querySelectorAll( '.button-link-delete' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			if ( ! confirm( 'Delete this record? This cannot be undone.' ) ) {
				e.preventDefault();
			}
		} );
	} );
} );
