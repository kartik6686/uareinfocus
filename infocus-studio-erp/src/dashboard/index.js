import { createElement, createRoot } from '@wordpress/element';
import Dashboard from './Dashboard';
import '../shared/tokens.css';
import '../shared/layout.css';

document.addEventListener( 'DOMContentLoaded', function () {
	const el = document.getElementById( 'infocus-erp-app' );
	if ( ! el ) return;

	const config = window.infocusErpAdmin || {};
	const root = createRoot( el );
	root.render(
		<Dashboard adminUrl={ config.adminUrl || '/wp-admin/' } currentUserName={ config.currentUserName || 'Admin' } />
	);
} );
