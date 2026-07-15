import { createElement, Fragment, useEffect, useState, useCallback } from '@wordpress/element';
import Sidebar from '../shared/components/Sidebar';
import { getImageSelections, listEntity, setImageSelectionLock } from '../shared/api';
import { refLabel } from '../shared/refLabel';
import { money } from '../shared/format';
import useToast from '../shared/useToast';

function formatItems( items ) {
	if ( ! Array.isArray( items ) ) return '';
	return items
		.filter( ( i ) => i && i.image )
		.map( ( i ) => i.image + ( i.comment ? ` (${ i.comment })` : '' ) )
		.join( ', ' );
}

function buildSummary( row, bookingLabel ) {
	const data = row.selected_images || {};
	const included = data.included || [];
	const extra = data.extra || [];
	const header = `${ bookingLabel } (${ included.length } included${ row.extra_count > 0 ? `, ${ row.extra_count } extra, est. ${ money( row.estimated_extra_charge ) } additional` : '' })`;
	return `${ header }\n\nIncluded: ${ formatItems( included ) }\nExtra: ${ formatItems( extra ) }`;
}

export default function ImageSelectionsScreen( { adminUrl, userName } ) {
	const [ rows, setRows ] = useState( null );
	const [ bookingsById, setBookingsById ] = useState( {} );
	const [ customersById, setCustomersById ] = useState( {} );
	const [ error, setError ] = useState( null );
	const { toast, showToast } = useToast();

	const load = useCallback( () => {
		getImageSelections()
			.then( setRows )
			.catch( ( err ) => setError( err.message || 'Failed to load image selections.' ) );
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

	const copySummary = ( row ) => {
		const label = refLabel( 'bookings', bookingsById[ row.booking_id ], customersById );
		const summary = buildSummary( row, label );
		if ( navigator.clipboard ) navigator.clipboard.writeText( summary ).catch( () => {} );
		showToast( 'Summary copied to clipboard.' );
	};

	const toggleLock = ( row ) => {
		const next = row.locked ? 'unlocked' : 'locked';
		setImageSelectionLock( row.id, next )
			.then( () => {
				setRows( ( prev ) => prev.map( ( r ) => ( r.id === row.id ? { ...r, locked: next === 'locked' } : r ) ) );
				showToast( next === 'locked' ? 'Locked.' : 'Unlocked.' );
			} )
			.catch( ( err ) => showToast( err.message || 'Could not update lock state.' ) );
	};

	return (
		<div className="app">
			<Sidebar adminUrl={ adminUrl } currentUserName={ userName } active="infocus-erp-image-selections" />
			<main className="main">
				<div className="topbar">
					<div>
						<h1 className="display">Image Selections</h1>
					</div>
				</div>
				<p className="subhead">
					Shortcode: <code>[infocus_image_selection]</code>. Which raw images each client picked for editing, plus any extras beyond their
					package with the estimated additional charge.
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
										<th>Selections</th>
										<th>Extra charge</th>
										<th>Status</th>
										<th>Lock</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									{ rows.length === 0 && (
										<tr>
											<td colSpan={ 6 }>No image selections yet.</td>
										</tr>
									) }
									{ rows.map( ( row ) => {
										const data = row.selected_images || {};
										const included = data.included || [];
										const extra = data.extra || [];
										return (
											<tr key={ row.id }>
												<td style={ { minWidth: '200px' } }>{ refLabel( 'bookings', bookingsById[ row.booking_id ], customersById ) }</td>
												<td style={ { minWidth: '220px' } }>
													<div>
														<strong>{ included.length }</strong> included
														{ extra.length > 0 && (
															<>
																{ ' · ' }
																<strong>{ extra.length }</strong> extra
															</>
														) }
													</div>
													{ ( included.length > 0 || extra.length > 0 ) && (
														<div className="cell-sub">{ formatItems( [ ...included, ...extra ] ) }</div>
													) }
												</td>
												<td className="tabular">{ row.extra_count > 0 ? money( row.estimated_extra_charge ) : '—' }</td>
												<td>
													<span className={ `pill ${ row.submitted_at ? 'tone-success' : 'tone-warning' }` }>
														<span className="dot"></span>
														{ row.submitted_at ? 'Submitted' : 'Awaiting client' }
													</span>
												</td>
												<td>
													<span className={ `pill ${ row.locked ? 'tone-danger' : 'tone-muted' }` }>{ row.locked ? 'Locked' : 'Open' }</span>
												</td>
												<td>
													<div className="entity-actions">
														<button className="copy-btn" onClick={ () => copySummary( row ) }>
															<svg width="13" height="13" viewBox="0 0 24 24" fill="none">
																<rect x="9" y="9" width="12" height="12" rx="2" stroke="currentColor" strokeWidth="1.8" />
																<path d="M5 15V5a2 2 0 0 1 2-2h10" stroke="currentColor" strokeWidth="1.8" />
															</svg>
															Copy summary
														</button>
														{ row.submitted_at && (
															<button className="btn sm" onClick={ () => toggleLock( row ) }>
																{ row.locked ? 'Unlock' : 'Lock' }
															</button>
														) }
													</div>
												</td>
											</tr>
										);
									} ) }
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
