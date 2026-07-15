import { createElement, useEffect, useState, useCallback } from '@wordpress/element';
import Sidebar from '../shared/components/Sidebar';
import { getInvoices, listEntity, createCustomInvoice } from '../shared/api';
import { refLabel } from '../shared/refLabel';
import { money, shortDate } from '../shared/format';
import useToast from '../shared/useToast';

const EMPTY_ITEM = { description: '', amount: '' };

export default function InvoicesScreen( { adminUrl, userName } ) {
	const [ invoices, setInvoices ] = useState( null );
	const [ customers, setCustomers ] = useState( [] );
	const [ error, setError ] = useState( null );
	const { toast, copyLink, showToast } = useToast();

	const [ customerId, setCustomerId ] = useState( '' );
	const [ items, setItems ] = useState( [ { ...EMPTY_ITEM } ] );
	const [ advancePaid, setAdvancePaid ] = useState( '0' );
	const [ notes, setNotes ] = useState( '' );
	const [ saving, setSaving ] = useState( false );

	const load = useCallback( () => {
		getInvoices()
			.then( setInvoices )
			.catch( ( err ) => setError( err.message || 'Failed to load invoices.' ) );
		listEntity( 'customers' ).then( setCustomers ).catch( () => {} );
	}, [] );

	useEffect( load, [ load ] );

	const customersById = ( () => {
		const map = {};
		customers.forEach( ( c ) => ( map[ c.id ] = c ) );
		return map;
	} )();

	const updateItem = ( i, field, value ) => {
		setItems( ( prev ) => prev.map( ( item, idx ) => ( idx === i ? { ...item, [ field ]: value } : item ) ) );
	};
	const removeItem = ( i ) => {
		setItems( ( prev ) => ( prev.length > 1 ? prev.filter( ( _, idx ) => idx !== i ) : prev ) );
	};
	const addItem = () => setItems( ( prev ) => [ ...prev, { ...EMPTY_ITEM } ] );

	const handleSubmit = ( e ) => {
		e.preventDefault();
		if ( ! customerId ) {
			showToast( 'Please choose a customer.' );
			return;
		}
		setSaving( true );
		createCustomInvoice( {
			customer_id: Number( customerId ),
			line_items: items
				.filter( ( it ) => it.description || it.amount )
				.map( ( it ) => ( { description: it.description, amount: Number( it.amount ) || 0 } ) ),
			advance_paid: Number( advancePaid ) || 0,
			notes,
		} )
			.then( ( result ) => {
				showToast( `Invoice ${ result.invoice_number } created.` );
				setCustomerId( '' );
				setItems( [ { ...EMPTY_ITEM } ] );
				setAdvancePaid( '0' );
				setNotes( '' );
				load();
			} )
			.catch( ( err ) => showToast( err.message || 'Could not create invoice.' ) )
			.finally( () => setSaving( false ) );
	};

	return (
		<div className="app">
			<Sidebar adminUrl={ adminUrl } currentUserName={ userName } active="infocus-erp-invoices" />
			<main className="main">
				<div className="topbar">
					<div>
						<h1 className="display">Invoices</h1>
					</div>
				</div>
				<p className="subhead">
					Shortcode: <code>[infocus_invoice]</code>. Booking invoices auto-generate from the Bookings screen. Use this form for a one-off
					custom invoice instead.
				</p>

				{ error && <div className="error-note">{ error }</div> }

				<div className="grid-2" style={ { gridTemplateColumns: '0.85fr 1.15fr' } }>
					<div className="form-card">
						<h2>New custom invoice</h2>
						<form onSubmit={ handleSubmit }>
							<div className="field">
								<label htmlFor="inv-customer">
									Customer <span className="req">*</span>
								</label>
								<select id="inv-customer" value={ customerId } onChange={ ( e ) => setCustomerId( e.target.value ) }>
									<option value="">— Select —</option>
									{ customers.map( ( c ) => (
										<option key={ c.id } value={ c.id }>
											{ c.name }
										</option>
									) ) }
								</select>
							</div>

							<div className="field">
								<label>Line items</label>
								{ items.map( ( item, i ) => (
									<div className="line-item" key={ i }>
										<input
											type="text"
											placeholder="Description"
											value={ item.description }
											onChange={ ( e ) => updateItem( i, 'description', e.target.value ) }
										/>
										<input
											type="text"
											placeholder="Amount (₹)"
											value={ item.amount }
											onChange={ ( e ) => updateItem( i, 'amount', e.target.value ) }
										/>
										<button type="button" className="icon-btn" title="Remove" onClick={ () => removeItem( i ) }>
											<svg width="13" height="13" viewBox="0 0 24 24" fill="none">
												<path d="M6 6l12 12M18 6L6 18" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
											</svg>
										</button>
									</div>
								) ) }
								<button type="button" className="add-line" onClick={ addItem }>
									+ Add line item
								</button>
							</div>

							<div className="field">
								<label htmlFor="inv-advance">Advance already paid (₹)</label>
								<input id="inv-advance" type="text" value={ advancePaid } onChange={ ( e ) => setAdvancePaid( e.target.value ) } />
							</div>

							<div className="field">
								<label htmlFor="inv-notes">Notes</label>
								<textarea id="inv-notes" rows={ 2 } value={ notes } onChange={ ( e ) => setNotes( e.target.value ) } />
							</div>

							<button type="submit" className="btn accent" disabled={ saving }>
								{ saving ? 'Creating…' : 'Create invoice' }
							</button>
						</form>
					</div>

					<div className="card" style={ { marginBottom: 0 } }>
						<div className="table-wrap">
							<table>
								<thead>
									<tr>
										<th>Invoice</th>
										<th>Customer</th>
										<th>Date</th>
										<th className="num">Subtotal</th>
										<th className="num">Balance</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									{ invoices && invoices.length === 0 && (
										<tr>
											<td colSpan={ 6 }>No invoices yet.</td>
										</tr>
									) }
									{ ! invoices && (
										<tr>
											<td colSpan={ 6 }>Loading…</td>
										</tr>
									) }
									{ invoices &&
										invoices.map( ( inv ) => (
											<tr key={ inv.id }>
												<td style={ { fontWeight: 600 } }>{ inv.invoice_number }</td>
												<td>{ refLabel( 'customers', customersById[ inv.customer_id ] ) }</td>
												<td className="tabular">{ shortDate( inv.invoice_date ) }</td>
												<td className="num tabular">{ money( inv.subtotal ) }</td>
												<td className="num tabular">{ money( inv.balance_due ) }</td>
												<td>
													<button
														className="copy-btn"
														onClick={ () => copyLink( inv.link || `${ adminUrl }?page=infocus-erp-invoices`, 'Invoice link' ) }
													>
														<svg width="13" height="13" viewBox="0 0 24 24" fill="none">
															<rect x="9" y="9" width="12" height="12" rx="2" stroke="currentColor" strokeWidth="1.8" />
															<path d="M5 15V5a2 2 0 0 1 2-2h10" stroke="currentColor" strokeWidth="1.8" />
														</svg>
													</button>
												</td>
											</tr>
										) ) }
								</tbody>
							</table>
						</div>
					</div>
				</div>

				{ toast && <div className="toast">{ toast }</div> }
			</main>
		</div>
	);
}
