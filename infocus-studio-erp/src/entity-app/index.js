import { createElement, createRoot } from '@wordpress/element';
import EntityApp from './EntityApp';
import '../shared/tokens.css';
import '../shared/layout.css';

document.addEventListener( 'DOMContentLoaded', function () {
	const el = document.getElementById( 'infocus-erp-app' );
	if ( ! el ) return;

	const config = window.infocusErpEntity;
	if ( ! config ) return;

	const root = createRoot( el );
	root.render( <EntityApp config={ config } /> );
} );
