import { createElement } from '@wordpress/element';

export default function Card( { title, action, children } ) {
	return (
		<div className="card">
			{ title && (
				<div className="card-head">
					<h2>{ title }</h2>
					{ action }
				</div>
			) }
			<div className="card-body">{ children }</div>
		</div>
	);
}
