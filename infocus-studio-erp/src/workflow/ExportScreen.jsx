import { createElement } from '@wordpress/element';
import Sidebar from '../shared/components/Sidebar';

export default function ExportScreen( { adminUrl, userName, sections, fullBackupUrl } ) {
	return (
		<div className="app">
			<Sidebar adminUrl={ adminUrl } currentUserName={ userName } active="infocus-erp-export" />
			<main className="main">
				<div className="topbar">
					<div>
						<h1 className="display">Export / Backup</h1>
					</div>
				</div>
				<p className="subhead">
					Download your data any time. These files are plain CSV — they open in Excel, Google Sheets, or any spreadsheet tool, and
					stay usable even if this website or plugin is removed.
				</p>

				<div className="stack">
					<div className="card">
						<div className="card-head">
							<h2>Full backup</h2>
						</div>
						<div className="card-body">
							<a className="btn accent" href={ fullBackupUrl }>
								Download everything (ZIP of all CSVs)
							</a>
							<p className="subhead" style={ { margin: '10px 0 0' } }>
								Tip: schedule a monthly reminder to download the full backup — it takes one click.
							</p>
						</div>
					</div>

					<div className="card">
						<div className="card-head">
							<h2>Individual sections</h2>
						</div>
						<div className="card-body export-grid">
							{ ( sections || [] ).map( ( s ) => (
								<a key={ s.key } className="btn ghost" href={ s.url }>
									{ s.label } CSV
								</a>
							) ) }
						</div>
					</div>
				</div>
			</main>
		</div>
	);
}
