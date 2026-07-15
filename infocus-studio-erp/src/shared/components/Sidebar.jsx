import { createElement } from '@wordpress/element';

const GROUPS = [
	{
		label: 'Studio',
		items: [
			{ slug: 'infocus-erp-bookings', label: 'Bookings' },
			{ slug: 'infocus-erp-customers', label: 'Customers' },
			{ slug: 'infocus-erp-payments', label: 'Payments' },
			{ slug: 'infocus-erp-employees', label: 'Employees' },
			{ slug: 'infocus-erp-pipeline', label: 'Editor Pipeline' },
			{ slug: 'infocus-erp-expenses', label: 'Expenses' },
			{ slug: 'infocus-erp-packages', label: 'Packages' },
		],
	},
	{
		label: 'Client-facing',
		items: [
			{ slug: 'infocus-erp-inquiries', label: 'Inquiries' },
			{ slug: 'infocus-erp-requirements', label: 'Shoot Requirements' },
			{ slug: 'infocus-erp-image-selections', label: 'Image Selections' },
			{ slug: 'infocus-erp-invoices', label: 'Invoices' },
		],
	},
	{
		label: 'System',
		items: [
			{ slug: 'infocus-erp-export', label: 'Export / Backup' },
			{ slug: 'infocus-erp-settings', label: 'Settings' },
		],
	},
];

export default function Sidebar( { adminUrl, currentUserName } ) {
	const initial = ( currentUserName || '?' ).trim().charAt( 0 ).toUpperCase();
	return (
		<nav className="rail" aria-label="Primary">
			<div className="brand">
				<div className="brand-mark" aria-hidden="true">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none">
						<path d="M4 8.5C4 7.67 4.67 7 5.5 7H8l1.2-1.6c.28-.38.73-.6 1.2-.6h3.2c.47 0 .92.22 1.2.6L16 7h2.5c.83 0 1.5.67 1.5 1.5v9c0 .83-.67 1.5-1.5 1.5h-13C4.67 19 4 18.33 4 17.5v-9Z" stroke="currentColor" strokeWidth="1.6" />
						<circle cx="12" cy="13" r="3.1" stroke="currentColor" strokeWidth="1.6" />
					</svg>
				</div>
				<div>
					<div className="brand-name">Infocus Studio</div>
					<div className="brand-sub">Studio ERP</div>
				</div>
			</div>

			<div className="nav-label">Overview</div>
			<a className="nav-item active" href={ `${ adminUrl }admin.php?page=infocus-erp` }>
				Dashboard
			</a>

			{ GROUPS.map( ( group ) => (
				<div key={ group.label }>
					<div className="nav-label">{ group.label }</div>
					{ group.items.map( ( item ) => (
						<a key={ item.slug } className="nav-item" href={ `${ adminUrl }admin.php?page=${ item.slug }` }>
							{ item.label }
						</a>
					) ) }
				</div>
			) ) }

			<div className="rail-foot">
				<div className="avatar">{ initial }</div>
				<div>{ currentUserName } · Admin</div>
			</div>
		</nav>
	);
}
