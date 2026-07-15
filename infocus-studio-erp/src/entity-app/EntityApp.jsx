import { createElement } from '@wordpress/element';
import Sidebar from '../shared/components/Sidebar';
import EntityList from './EntityList';
import EntityForm from './EntityForm';

export default function EntityApp( { config } ) {
	const { entity, label, singular, view, editId, prefill, adminUrl, userName } = config;

	return (
		<div className="app">
			<Sidebar adminUrl={ adminUrl } currentUserName={ userName } active={ `infocus-erp-${ entity }` } />
			<main className="main">
				<div className="topbar">
					<div>
						<h1 className="display">{ label }</h1>
					</div>
					{ view === 'list' && (
						<div className="actions">
							<a className="btn accent" href={ `${ adminUrl }admin.php?page=infocus-erp-${ entity }&action=add` }>
								+ Add { singular.toLowerCase() }
							</a>
						</div>
					) }
				</div>

				{ view === 'list' ? (
					<EntityList config={ config } adminUrl={ adminUrl } />
				) : (
					<EntityForm config={ config } editId={ editId } prefill={ prefill } adminUrl={ adminUrl } />
				) }
			</main>
		</div>
	);
}
