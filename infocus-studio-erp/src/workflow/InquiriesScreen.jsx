import { createElement, Fragment, useEffect, useState, useCallback } from '@wordpress/element';
import Sidebar from '../shared/components/Sidebar';
import Pill from '../shared/components/Pill';
import { getInquiries, updateInquiry, approveInquiry, rejectInquiry, getInquiryWhatsappLink } from '../shared/api';
import { shortDate } from '../shared/format';
import useToast from '../shared/useToast';

const STATUS_OPTIONS = [ 'New', 'Contacted', 'Converted' ];
const QUALITY_TONE = { 'Likely genuine': 'success', 'Looks suspicious': 'danger' };

export default function InquiriesScreen( { adminUrl, userName } ) {
	const [ rows, setRows ] = useState( null );
	const [ error, setError ] = useState( null );
	const { toast, showToast } = useToast();

	const load = useCallback( () => {
		getInquiries()
			.then( setRows )
			.catch( ( err ) => setError( err.message || 'Failed to load inquiries.' ) );
	}, [] );

	useEffect( load, [ load ] );

	const handleStatusChange = ( id, status ) => {
		updateInquiry( id, { status } )
			.then( () => setRows( ( prev ) => prev.map( ( r ) => ( r.id === id ? { ...r, status } : r ) ) ) )
			.catch( ( err ) => showToast( err.message || 'Could not update status.' ) );
	};

	const handleApprove = ( id ) => {
		approveInquiry( id )
			.then( () => {
				showToast( 'Approved — added to Customers.' );
				load();
			} )
			.catch( ( err ) => showToast( err.message || 'Could not approve.' ) );
	};

	const handleReject = ( id ) => {
		if ( ! window.confirm( 'Reject and permanently delete this inquiry? This cannot be undone.' ) ) return;
		rejectInquiry( id )
			.then( () => {
				showToast( 'Rejected and deleted.' );
				setRows( ( prev ) => prev.filter( ( r ) => r.id !== id ) );
			} )
			.catch( ( err ) => showToast( err.message || 'Could not reject.' ) );
	};

	const handleWhatsapp = ( id ) => {
		getInquiryWhatsappLink( id )
			.then( ( result ) => window.open( result.link, '_blank', 'noopener,noreferrer' ) )
			.catch( ( err ) => showToast( err.message || 'Could not build WhatsApp link.' ) );
	};

	return (
		<div className="app">
			<Sidebar adminUrl={ adminUrl } currentUserName={ userName } active="infocus-erp-inquiries" />
			<main className="main">
				<div className="topbar">
					<div>
						<h1 className="display">Inquiries</h1>
					</div>
				</div>
				<p className="subhead">
					Shortcode: <code>[infocus_inquiry_form]</code>. New leads land here as <strong>Pending Review</strong> — approve to add them as a
					customer, reject to discard. Existing clients (matched by phone/email) skip straight to the normal workflow.
				</p>

				{ error && <div className="error-note">{ error }</div> }
				{ ! rows && ! error && <div className="loading">Loading inquiries…</div> }

				{ rows && (
					<div className="card">
						<div className="table-wrap">
							<table>
								<thead>
									<tr>
										<th>Lead</th>
										<th>Service</th>
										<th>Client</th>
										<th>Quality</th>
										<th>Status</th>
										<th>Received</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									{ rows.length === 0 && (
										<tr>
											<td colSpan={ 7 }>No inquiries yet.</td>
										</tr>
									) }
									{ rows.map( ( row ) => (
										<tr key={ row.id }>
											<td>
												<div style={ { fontWeight: 600 } }>{ row.name }</div>
												<div className="cell-sub">
													{ row.phone }
													{ row.email ? ` · ${ row.email }` : '' }
												</div>
											</td>
											<td>{ row.service_type || '—' }</td>
											<td>
												{ row.is_new_client ? (
													<span className="pill tone-success">
														<span className="dot"></span>New client
													</span>
												) : (
													<span className="pill tone-muted">Existing client</span>
												) }
											</td>
											<td>
												{ row.quality_flag ? (
													<span className={ `pill tone-${ QUALITY_TONE[ row.quality_flag ] || 'warning' }` }>
														<span className="dot"></span>
														{ row.quality_flag }
													</span>
												) : (
													<span className="pill tone-muted">—</span>
												) }
											</td>
											<td>
												{ row.status === 'Pending Review' ? (
													<Pill status="Pending Review" label="Pending Review" />
												) : (
													<select
														className="inline-select"
														value={ row.status }
														onChange={ ( e ) => handleStatusChange( row.id, e.target.value ) }
													>
														{ STATUS_OPTIONS.map( ( opt ) => (
															<option key={ opt } value={ opt }>
																{ opt }
															</option>
														) ) }
													</select>
												) }
											</td>
											<td className="tabular">{ shortDate( row.created_at ? row.created_at.slice( 0, 10 ) : '' ) }</td>
											<td>
												<div className="entity-actions">
													{ row.status === 'Pending Review' ? (
														<>
															<button className="btn success sm" onClick={ () => handleApprove( row.id ) }>
																Approve
															</button>
															<button className="btn danger-ghost sm" onClick={ () => handleReject( row.id ) }>
																Reject
															</button>
														</>
													) : (
														<a
															className="btn sm"
															href={ `${ adminUrl }admin.php?page=infocus-erp-bookings&action=add&prefill_customer_id=${ row.matched_customer_id || '' }&prefill_service_type=${ encodeURIComponent( row.service_type || '' ) }&prefill_notes=${ encodeURIComponent( `${ row.additional_requirements || '' } ${ row.message || '' }`.trim() ) }` }
														>
															Convert to booking
														</a>
													) }
													{ row.phone && (
														<button className="btn sm" onClick={ () => handleWhatsapp( row.id ) }>
															WhatsApp
														</button>
													) }
												</div>
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
