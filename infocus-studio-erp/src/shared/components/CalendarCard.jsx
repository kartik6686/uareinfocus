import { createElement } from '@wordpress/element';
import Card from './Card';

const MONTH_NAMES = [
	'January', 'February', 'March', 'April', 'May', 'June',
	'July', 'August', 'September', 'October', 'November', 'December',
];

export default function CalendarCard( { year, month, byDay, onPrev, onNext, children } ) {
	const daysInMonth = new Date( year, month, 0 ).getDate();
	const firstWeekday = new Date( year, month - 1, 1 ).getDay();

	const now = new Date();
	const isCurrentMonth = now.getFullYear() === year && now.getMonth() + 1 === month;
	const todayDate = now.getDate();

	const cells = [];
	for ( let i = 0; i < firstWeekday; i++ ) {
		cells.push( <div className="cal-day blank" key={ `blank-${ i }` } /> );
	}
	for ( let d = 1; d <= daysInMonth; d++ ) {
		const hasBooking = !! ( byDay && byDay[ d ] && byDay[ d ].length );
		const isToday = isCurrentMonth && d === todayDate;
		const classes = [ 'cal-day' ];
		if ( hasBooking ) classes.push( 'has-booking' );
		if ( isToday ) classes.push( 'today' );
		cells.push(
			<div
				className={ classes.join( ' ' ) }
				key={ d }
				title={ hasBooking ? byDay[ d ].map( ( b ) => `${ b.name } (${ b.service })` ).join( ', ' ) : undefined }
			>
				{ d }
			</div>
		);
	}

	const nav = (
		<div className="cal-nav">
			<button className="icon-btn" aria-label="Previous month" onClick={ onPrev }>
				‹
			</button>
			<button className="icon-btn" aria-label="Next month" onClick={ onNext }>
				›
			</button>
		</div>
	);

	return (
		<Card title={ `${ MONTH_NAMES[ month - 1 ] } ${ year }` } action={ nav }>
			<div className="cal-grid">
				{ [ 'S', 'M', 'T', 'W', 'T', 'F', 'S' ].map( ( d, i ) => (
					<div className="cal-dow" key={ i }>
						{ d }
					</div>
				) ) }
				{ cells }
			</div>
			<div className="cal-legend">
				<span>
					<span className="dot" style={ { background: 'var(--progress)' } } /> Booking
				</span>
				<span>
					<span className="dot" style={ { background: 'var(--accent-fill)' } } /> Today
				</span>
			</div>
			{ children }
		</Card>
	);
}
