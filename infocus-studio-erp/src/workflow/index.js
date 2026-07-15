import { createElement, createRoot } from '@wordpress/element';
import InquiriesScreen from './InquiriesScreen';
import RequirementsScreen from './RequirementsScreen';
import ImageSelectionsScreen from './ImageSelectionsScreen';
import InvoicesScreen from './InvoicesScreen';
import '../shared/tokens.css';
import '../shared/layout.css';

const SCREENS = {
	inquiries: InquiriesScreen,
	requirements: RequirementsScreen,
	'image-selections': ImageSelectionsScreen,
	invoices: InvoicesScreen,
};

document.addEventListener( 'DOMContentLoaded', function () {
	const el = document.getElementById( 'infocus-erp-app' );
	if ( ! el ) return;

	const Screen = SCREENS[ el.dataset.screen ];
	if ( ! Screen ) return;

	const config = window.infocusErpWorkflow || {};
	const root = createRoot( el );
	root.render( <Screen adminUrl={ config.adminUrl || '/wp-admin/' } userName={ config.userName || 'Admin' } /> );
} );
