import { createElement, useEffect, useState, useCallback } from '@wordpress/element';
import Sidebar from '../shared/components/Sidebar';
import { getShootRequirements, listEntity } from '../shared/api';
import { refLabel } from '../shared/refLabel';
import useToast from '../shared/useToast';

export default function RequirementsScreen( { adminUrl, userName } ) {
	const [ rows, setRows ] = useState( null );
	const [ bookingsById, setBookingsById ] = useState( {} );
	const [ customersById, setCustomersById ] = useState( {} );
	const [ error, setError ] = useState( null );
	const { toast, copyLink } = useToast();

	const load = useCallback( () => {
		getShootRequirements()
			.then( setRows )
			.catch( ( err ) => setError( err.message || 'Failed to load shoot requirements.' ) );
		listEntity( 'bookings' ).then( ( list ) => {
			const byId = {};
			list.forEach( ( b ) => ( byId[ b.id ] = b ) );
			setBookingsById( byId );
		} );
		listEntity( 'customers' ).then( ( list ) => {
			const byId = {};
			list.forEach( ( c ) => ( byId[ c.id ] = c ) );
			setCustomersById( byId );
		} );
	}, [] );

	useEffect( load, [ load ] );

	return (
		<div className="app">
			<Sidebar adminUrl={ adminUrl } currentUserName={ userName } active="infocus-erp-requirements" />
			<main className="main">
				<div className="topbar">
					<div>
						<h1 className="display">Shoot Requirements</h1>
					</div>
				</div>
				<p className="subhead">
					Shortcode: <code>[infocus_shoot_requirements]</code>. Links are generated from a booking's row menu on the Bookings screen. Each
					link is private to one booking.
				</p>

				{ error && <div className="error-note">{ error }</div> }
				{ ! rows && ! error && <div className="loading">Loading…</div> }

				{ rows && (
					<div className="card">
						<div className="table-wrap">
							<table>
								<thead>
									<tr>
										<th>Booking</th>
										<th>Preferences</th>
										<th>Reference links</th>
										<th>Notes</th>
										<th>Status</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									{ rows.length === 0 && (
										<tr>
											<td colSpan={ 6 }>No requirements links generated yet.</td>
										</tr>
									) }
									{ rows.map( ( row ) => (
										<tr key={ row.id }>
											<td style={ { minWidth: '200px' } }>{ refLabel( 'bookings', bookingsById[ row.booking_id ], customersById ) }</td>
											<td style={ { minWidth: '200px' } }>
												<div>
													<strong>Theme:</strong> { row.theme || '—' }
												</div>
												<div>
													<strong>Time:</strong> { row.preferred_time || '—' }
												</div>
												<div>
													<strong>Outfit:</strong> { row.outfit || '—' }
												</div>
												<div>
													<strong>Location:</strong> { row.location_preference || '—' }
												</div>
											</td>
											<td>
												{ row.reference_links && row.reference_links.length ? (
													row.reference_links
														.filter( Boolean )
														.map( ( link, i ) => (
															<div key={ i }>
																<a href={ link } target="_blank" rel="noopener noreferrer">
																	Link { i + 1 }
																</a>
															</div>
														) )
												) : (
													'—'
												) }
											</td>
											<td>{ row.additional_notes || '—' }</td>
											<td>
												<span className={ `pill ${ row.submitted_at ? 'tone-success' : 'tone-warning' }` }>
													<span className="dot"></span>
													{ row.submitted_at ? 'Submitted' : 'Awaiting client' }
												</span>
											</td>
											<td>
												<button className="copy-btn" onClick={ () => copyLink( row.link, 'Requirements link' ) }>
													<svg width="13" height="13" viewBox="0 0 24 24" fill="none">
														<rect x="9" y="9" width="12" height="12" rx="2" stroke="currentColor" strokeWidth="1.8" />
														<path d="M5 15V5a2 2 0 0 1 2-2h10" stroke="currentColor" strokeWidth="1.8" />
													</svg>
													Copy link
												</button>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					</div>
				) }

				{ toast && <div className="toast">{ toast }</div> }
			</main>
		</div>
	);
}
