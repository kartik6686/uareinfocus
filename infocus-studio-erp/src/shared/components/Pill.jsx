import { createElement } from '@wordpress/element';

// Every status string used across bookings/pipeline/inquiries maps to one of
// four tones, kept separate from the gold UI accent so "attention" colors
// never compete with the brand accent.
const STATUS_TONE = {
	Confirmed: 'success',
	Delivered: 'success',
	Paid: 'success',
	Full: 'success',
	New: 'success',
	Inquiry: 'warning',
	'Pending Review': 'warning',
	'Not Started': 'warning',
	Cancelled: 'danger',
	Overdue: 'danger',
	Refund: 'danger',
	Shot: 'progress',
	Editing: 'progress',
	'In Progress': 'progress',
	Revision: 'progress',
};

export default function Pill( { status, label } ) {
	const tone = STATUS_TONE[ status ] || 'warning';
	return (
		<span className={ `pill tone-${ tone }` }>
			<span className="dot" />
			{ label || status }
		</span>
	);
}
