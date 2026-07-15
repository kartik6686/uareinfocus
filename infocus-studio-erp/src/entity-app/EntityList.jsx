import { createElement, Fragment, useEffect, useState, useCallback } from '@wordpress/element';
import Pill from '../shared/components/Pill';
import {
	listEntity,
	deleteEntityRecord,
	getRequirementsLink,
	getImageSelectionLink,
	getInvoiceLink,
} from '../shared/api';
import { money, initials } from '../shared/format';
import { refLabel } from '../shared/refLabel';

function pipelineTimeline( row ) {
	if ( row.status === 'Delivered' || isRealDate( row.delivered_date ) ) {
		return { label: 'Delivered', tone: 'success', percent: 100 };
	}
	if ( ! isRealDate( row.expected_date ) ) {
		return { label: 'No deadline set', tone: 'muted', percent: 0 };
	}
	const startTs = isRealDate( row.assigned_date ) ? new Date( row.assigned_date ).getTime() : new Date( row.created_at ).getTime();
	const deadlineTs = new Date( row.expected_date ).getTime();
	const now = Date.now();
	const daysElapsed = Math.max( 0, Math.floor( ( now - startTs ) / 86400000 ) );
	const daysTotal = Math.max( 1, Math.round( ( deadlineTs - startTs ) / 86400000 ) );
	const daysLeft = Math.floor( ( deadlineTs - now ) / 86400000 );
	const percent = Math.min( 100, Math.max( 0, Math.round( ( daysElapsed / daysTotal ) * 100 ) ) );
	const isOverdue = daysLeft < 0;
	return {
		label: isOverdue ? 'Overdue' : `${ daysLeft }d left`,
		tone: isOverdue ? 'danger' : percent >= 66 ? 'warning' : 'success',
		percent,
	};
}

function isRealDate( value ) {
	return !! value && ! String( value ).startsWith( '0000-00-00' );
}

export default function EntityList( { config, adminUrl } ) {
	const { entity, singular, columns, fields } = config;
	const [ rows, setRows ] = useState( null );
	const [ refData, setRefData ] = useState( {} );
	const [ error, setError ] = useState( null );
	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( 'All' );
	const [ openMenuId, setOpenMenuId ] = useState( null );
	const [ toast, setToast ] = useState( null );

	const load = useCallback( () => {
		listEntity( entity )
			.then( setRows )
			.catch( ( err ) => setError( err.message || 'Failed to load records.' ) );

		const refEntities = new Set();
		Object.values( fields ).forEach( ( def ) => {
			if ( def.type === 'ref' ) refEntities.add( def.ref );
		} );
		if ( refEntities.has( 'bookings' ) ) refEntities.add( 'customers' );

		refEntities.forEach( ( refEntity ) => {
			listEntity( refEntity )
				.then( ( list ) => {
					const byId = {};
					list.forEach( ( r ) => ( byId[ r.id ] = r ) );
					setRefData( ( prev ) => ( { ...prev, [ refEntity ]: byId } ) );
				} )
				.catch( () => {} );
		} );
	}, [ entity, fields ] );

	useEffect( () => {
		load();
		const params = new URLSearchParams( window.location.search );
		if ( params.get( 'saved' ) ) {
			setToast( 'Saved.' );
			params.delete( 'saved' );
			const qs = params.toString();
			window.history.replaceState( {}, '', window.location.pathname + ( qs ? `?${ qs }` : '' ) );
			window.setTimeout( () => setToast( null ), 2500 );
		}
	}, [ load ] );

	const statusOptions = fields.status && fields.status.type === 'select' ? fields.status.options : null;

	const filteredRows = ( rows || [] ).filter( ( row ) => {
		if ( statusOptions && statusFilter !== 'All' && row.status !== statusFilter ) return false;
		if ( ! search.trim() ) return true;
		const needle = search.trim().toLowerCase();
		return Object.keys( columns ).some( ( col ) => {
			const def = fields[ col ];
			let text = row[ col ];
			if ( def && def.type === 'ref' ) {
				text = refLabel( def.ref, refData[ def.ref ] && refData[ def.ref ][ row[ col ] ], refData.customers );
			}
			return String( text ?? '' ).toLowerCase().includes( needle );
		} );
	} );

	const handleDelete = ( id ) => {
		if ( ! window.confirm( `Delete this ${ singular.toLowerCase() }? This cannot be undone.` ) ) return;
		deleteEntityRecord( entity, id )
			.then( () => {
				setRows( ( prev ) => prev.filter( ( r ) => r.id !== id ) );
				setToast( 'Deleted.' );
				window.setTimeout( () => setToast( null ), 2000 );
			} )
			.catch( ( err ) => setToast( err.message || 'Delete failed.' ) );
		setOpenMenuId( null );
	};

	const copyLink = ( promise, label ) => {
		promise
			.then( ( result ) => {
				const link = result && result.link;
				if ( link && navigator.clipboard ) {
					navigator.clipboard.writeText( link ).catch( () => {} );
				}
				setToast( link ? `${ label } copied to clipboard.` : `${ label } ready: ${ JSON.stringify( result ) }` );
			} )
			.catch( ( err ) => setToast( err.message || `Couldn't create ${ label.toLowerCase() }.` ) )
			.finally( () => {
				setOpenMenuId( null );
				window.setTimeout( () => setToast( null ), 3000 );
			} );
	};

	if ( error ) return <div className="error-note">{ error }</div>;
	if ( ! rows ) return <div className="loading">Loading { singular.toLowerCase() }s…</div>;

	return (
		<>
			<div className="toolbar">
				<div className="search">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none">
						<circle cx="11" cy="11" r="7" stroke="currentColor" strokeWidth="2" />
						<path d="M21 21l-4.3-4.3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
					</svg>
					<input
						type="text"
						placeholder={ `Search ${ singular.toLowerCase() }s…` }
						value={ search }
						onChange={ ( e ) => setSearch( e.target.value ) }
					/>
				</div>
				{ statusOptions && (
					<div className="filters">
						<button className={ `chip ${ statusFilter === 'All' ? 'on' : '' }` } onClick={ () => setStatusFilter( 'All' ) }>
							All
						</button>
						{ statusOptions.map( ( opt ) => (
							<button key={ opt } className={ `chip ${ statusFilter === opt ? 'on' : '' }` } onClick={ () => setStatusFilter( opt ) }>
								{ opt }
							</button>
						) ) }
					</div>
				) }
			</div>

			<div className="card">
				<div className="table-wrap">
					<table>
						<thead>
							<tr>
								{ Object.entries( columns ).map( ( [ col, label ] ) => (
									<th key={ col } className={ fields[ col ] && fields[ col ].type === 'number' ? 'num' : '' }>
										{ label }
									</th>
								) ) }
								{ entity === 'pipeline' && <th>Timeline</th> }
								<th></th>
							</tr>
						</thead>
						<tbody>
							{ filteredRows.length === 0 && (
								<tr>
									<td colSpan={ Object.keys( columns ).length + ( entity === 'pipeline' ? 2 : 1 ) }>No records yet.</td>
								</tr>
							) }
							{ filteredRows.map( ( row ) => (
								<tr key={ row.id }>
									{ Object.keys( columns ).map( ( col, i ) => {
										const def = fields[ col ];
										const value = row[ col ];
										if ( col === 'id' ) {
											return <td key={ col }>#{ value }</td>;
										}
										if ( def && def.type === 'ref' ) {
											const refRow = refData[ def.ref ] && refData[ def.ref ][ value ];
											const label = refLabel( def.ref, refRow, refData.customers );
											if ( i === 0 ) {
												return (
													<td key={ col }>
														<div className="cust-cell">
															<div className="row-init">{ initials( label ) }</div>
															<div>
																<div className="row-name">{ label }</div>
																<div className="attn-sub">#{ row.id }</div>
															</div>
														</div>
													</td>
												);
											}
											return <td key={ col }>{ label }</td>;
										}
										if ( col === 'status' ) {
											return (
												<td key={ col }>
													<Pill status={ value } />
												</td>
											);
										}
										if ( def && def.type === 'number' ) {
											const isMoney = ( columns[ col ] || '' ).includes( '₹' );
											return (
												<td key={ col } className="num tabular">
													{ isMoney ? money( value ) : value }
												</td>
											);
										}
										return <td key={ col }>{ value || '—' }</td>;
									} ) }
									{ entity === 'pipeline' &&
										( () => {
											const t = pipelineTimeline( row );
											return (
												<td>
													<div style={ { fontSize: '11px', fontWeight: 600, marginBottom: '3px', color: t.tone === 'muted' ? 'var(--muted)' : `var(--${ t.tone })` } }>
														{ t.label }
													</div>
													<div style={ { height: '5px', width: '100px', background: 'var(--surface-3)', borderRadius: '4px', overflow: 'hidden' } }>
														<div style={ { height: '5px', width: `${ t.percent }%`, background: t.tone === 'muted' ? 'var(--border)' : `var(--${ t.tone })` } } />
													</div>
												</td>
											);
										} )() }
									<td>
										<div className="entity-actions">
											<a
												className="icon-btn"
												title="Edit"
												href={ `${ adminUrl }admin.php?page=infocus-erp-${ entity }&edit=${ row.id }` }
											>
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none">
													<path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" stroke="currentColor" strokeWidth="1.6" />
												</svg>
											</a>
											<button className="icon-btn" title="More" onClick={ () => setOpenMenuId( openMenuId === row.id ? null : row.id ) }>
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none">
													<circle cx="5" cy="12" r="1.4" fill="currentColor" />
													<circle cx="12" cy="12" r="1.4" fill="currentColor" />
													<circle cx="19" cy="12" r="1.4" fill="currentColor" />
												</svg>
											</button>
											{ openMenuId === row.id && (
												<div className="menu">
													{ entity === 'bookings' && (
														<>
															<button onClick={ () => copyLink( getRequirementsLink( row.id ), 'Requirements link' ) }>
																Copy requirements link
															</button>
															<button onClick={ () => copyLink( getImageSelectionLink( row.id ), 'Image selection link' ) }>
																Copy image selection link
															</button>
															<button onClick={ () => copyLink( getInvoiceLink( row.id ), 'Invoice link' ) }>Generate invoice</button>
															<div className="menu-divider" />
														</>
													) }
													<button className="danger" onClick={ () => handleDelete( row.id ) }>
														Delete
													</button>
												</div>
											) }
										</div>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			</div>

			{ toast && <div className="toast">{ toast }</div> }
		</>
	);
}
