import { createElement } from '@wordpress/element';

export default function KpiTile( { label, value, delta, tone = 'accent' } ) {
	return (
		<div className="kpi" style={ { '--stripe': `var(--${ tone })` } }>
			<div className="kpi-label">{ label }</div>
			<div className="kpi-value tabular">{ value }</div>
			{ delta && <div className={ `kpi-delta ${ tone === 'danger' ? 'bad' : tone === 'success' ? 'good' : '' }` }>{ delta }</div> }
		</div>
	);
}
