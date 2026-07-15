import { createElement, useEffect, useState, useCallback } from '@wordpress/element';
import { getEntityRecord, createEntityRecord, updateEntityRecord, listEntity } from '../shared/api';
import { refLabel } from '../shared/refLabel';

function emptyValues( fields, prefill ) {
	const values = {};
	Object.keys( fields ).forEach( ( key ) => {
		values[ key ] = prefill && prefill[ key ] !== undefined ? prefill[ key ] : '';
	} );
	return values;
}

export default function EntityForm( { config, editId, prefill, adminUrl } ) {
	const { entity, singular, fields } = config;
	const isEditing = editId > 0;

	const [ values, setValues ] = useState( () => emptyValues( fields, prefill ) );
	const [ refOptions, setRefOptions ] = useState( {} );
	const [ loading, setLoading ] = useState( isEditing );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		const refEntities = new Set();
		Object.values( fields ).forEach( ( def ) => {
			if ( def.type === 'ref' ) refEntities.add( def.ref );
		} );
		if ( refEntities.has( 'bookings' ) ) refEntities.add( 'customers' );
		refEntities.forEach( ( refEntity ) => {
			listEntity( refEntity )
				.then( ( list ) => setRefOptions( ( prev ) => ( { ...prev, [ refEntity ]: list } ) ) )
				.catch( () => {} );
		} );
	}, [ fields ] );

	useEffect( () => {
		if ( ! isEditing ) return;
		getEntityRecord( entity, editId )
			.then( ( record ) => setValues( emptyValues( fields, record ) ) )
			.catch( ( err ) => setError( err.message || 'Could not load this record.' ) )
			.finally( () => setLoading( false ) );
	}, [ entity, editId, isEditing, fields ] );

	const customersById = ( () => {
		const map = {};
		( refOptions.customers || [] ).forEach( ( c ) => ( map[ c.id ] = c ) );
		return map;
	} )();

	const setField = useCallback( ( field, value ) => {
		setValues( ( prev ) => ( { ...prev, [ field ]: value } ) );
	}, [] );

	const handlePackageChange = ( packageId ) => {
		setField( 'package_id', packageId );
		if ( entity !== 'bookings' || ! packageId ) return;
		const pkg = ( refOptions.packages || [] ).find( ( p ) => String( p.id ) === String( packageId ) );
		if ( pkg ) {
			setField( 'package_price', pkg.price );
			setField( 'included_edits', pkg.included_edits );
		}
	};

	const handleSubmit = ( e ) => {
		e.preventDefault();
		setSaving( true );
		setError( null );

		const payload = {};
		Object.entries( fields ).forEach( ( [ field, def ] ) => {
			const raw = values[ field ];
			if ( def.type === 'number' || def.type === 'ref' ) {
				payload[ field ] = raw === '' || raw === null ? '' : Number( raw );
			} else {
				payload[ field ] = raw ?? '';
			}
		} );

		const request = isEditing ? updateEntityRecord( entity, editId, payload ) : createEntityRecord( entity, payload );
		request
			.then( () => {
				window.location.href = `${ adminUrl }admin.php?page=infocus-erp-${ entity }&saved=1`;
			} )
			.catch( ( err ) => {
				setError( err.message || 'Could not save this record.' );
				setSaving( false );
			} );
	};

	if ( loading ) return <div className="loading">Loading…</div>;

	return (
		<div className="form-card">
			{ error && <div className="error-note" style={ { padding: '0 0 14px', textAlign: 'left' } }>{ error }</div> }
			<form onSubmit={ handleSubmit }>
				{ Object.entries( fields ).map( ( [ field, def ] ) => (
					<div className="field" key={ field }>
						<label htmlFor={ `f-${ field }` }>
							{ def.label }
							{ def.required && <span className="req"> *</span> }
						</label>

						{ def.type === 'select' && (
							<select id={ `f-${ field }` } value={ values[ field ] || '' } onChange={ ( e ) => setField( field, e.target.value ) }>
								<option value="">— Select —</option>
								{ def.options.map( ( opt ) => (
									<option key={ opt } value={ opt }>
										{ opt }
									</option>
								) ) }
							</select>
						) }

						{ def.type === 'ref' && (
							<select
								id={ `f-${ field }` }
								value={ values[ field ] || '' }
								onChange={ ( e ) => ( def.ref === 'packages' ? handlePackageChange( e.target.value ) : setField( field, e.target.value ) ) }
							>
								<option value="">— Select —</option>
								{ ( refOptions[ def.ref ] || [] ).map( ( row ) => (
									<option key={ row.id } value={ row.id }>
										{ refLabel( def.ref, row, customersById ) }
									</option>
								) ) }
							</select>
						) }

						{ def.type === 'textarea' && (
							<textarea id={ `f-${ field }` } value={ values[ field ] || '' } onChange={ ( e ) => setField( field, e.target.value ) } />
						) }

						{ ( def.type === 'text' || def.type === 'number' || def.type === 'date' || def.type === 'time' ) && (
							<input
								id={ `f-${ field }` }
								type={ def.type }
								step={ def.type === 'number' ? '0.01' : undefined }
								value={ values[ field ] ?? '' }
								onChange={ ( e ) => setField( field, e.target.value ) }
							/>
						) }
					</div>
				) ) }

				<div className="form-actions">
					<button type="submit" className="btn accent" disabled={ saving }>
						{ saving ? 'Saving…' : isEditing ? 'Update' : 'Save' }
					</button>
					<a className="btn ghost" href={ `${ adminUrl }admin.php?page=infocus-erp-${ entity }` }>
						Cancel
					</a>
				</div>
			</form>
		</div>
	);
}
