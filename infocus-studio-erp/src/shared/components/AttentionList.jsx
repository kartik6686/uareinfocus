import { createElement } from '@wordpress/element';
import Card from './Card';

export default function AttentionList( { title, action, items, empty } ) {
	return (
		<Card title={ title } action={ action }>
			{ items && items.length ? (
				<div className="stack">
					{ items.map( ( item, i ) => (
						<div className="attn-item" key={ i }>
							<div>
								<div className="attn-name">{ item.name }</div>
								{ item.sub && <div className="attn-sub">{ item.sub }</div> }
							</div>
							{ item.right }
						</div>
					) ) }
				</div>
			) : (
				<div className="empty-note">{ empty }</div>
			) }
		</Card>
	);
}
