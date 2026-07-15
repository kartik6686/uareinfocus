import { createElement, Fragment, useEffect, useState, useCallback } from '@wordpress/element';
import Sidebar from '../shared/components/Sidebar';
import KpiTile from '../shared/components/KpiTile';
import CalendarCard from '../shared/components/CalendarCard';
import AttentionList from '../shared/components/AttentionList';
import Pill from '../shared/components/Pill';
import { getSummary, getCalendarMonth, getInquiries } from '../shared/api';
import { money, shortDate, initials } from '../shared/format';

const today = new Date();

export default function Dashboard( { adminUrl, currentUserName } ) {
	const [ summary, setSummary ] = useState( null );
	const [ byDay, setByDay ] = useState( {} );
	const [ inquiries, setInquiries ] = useState( [] );
	const [ year, setYear ] = useState( today.getFullYear() );
	const [ month, setMonth ] = useState( today.getMonth() + 1 );
	const [ error, setError ] = useState( null );
	const [ loading, setLoading ] = useState( true );

	const loadCore = useCallback( () => {
		setLoading( true );
		Promise.all( [
			getSummary(),
			getInquiries( { status: 'Pending Review' } ),
		] )
			.then( ( [ summaryData, inquiryData ] ) => {
				setSummary( summaryData );
				setInquiries( inquiryData );
				setError( null );
			} )
			.catch( ( err ) => setError( err.message || 'Something went wrong loading the dashboard.' ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const loadMonth = useCallback( ( y, m ) => {
		getCalendarMonth( y, m )
			.then( setByDay )
			.catch( () => setByDay( {} ) );
	}, [] );

	useEffect( () => {
		loadCore();
	}, [ loadCore ] );

	useEffect( () => {
		loadMonth( year, month );
	}, [ year, month, loadMonth ] );

	const goPrevMonth = () => {
		if ( month === 1 ) {
			setYear( year - 1 );
			setMonth( 12 );
		} else {
			setMonth( month - 1 );
		}
	};
	const goNextMonth = () => {
		if ( month === 12 ) {
			setYear( year + 1 );
			setMonth( 1 );
		} else {
			setMonth( month + 1 );
		}
	};

	const dateLabel = today.toLocaleDateString( 'en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' } );

	return (
		<div className="app">
			<Sidebar adminUrl={ adminUrl } currentUserName={ currentUserName } />
			<main className="main">
				<div className="topbar">
					<div>
						<h1 className="display">Studio dashboard</h1>
						<div className="date">{ dateLabel }</div>
					</div>
					<div className="actions">
						<a className="btn" href={ `${ adminUrl }admin.php?page=infocus-erp-bookings&action=add` }>
							+ New booking
						</a>
						<a className="btn accent" href={ `${ adminUrl }admin.php?page=infocus-erp-invoices` }>
							+ New invoice
						</a>
					</div>
				</div>

				{ loading && ! summary && <div className="loading">Loading studio data…</div> }
				{ error && <div className="error-note">{ error }</div> }

				{ summary && (
					<>
						<div className="kpi-row">
							<KpiTile
								label="Revenue collected"
								value={ money( summary.total_revenue_collected ) }
								tone="success"
								delta="Total payments received"
							/>
							<KpiTile
								label="Total expenses"
								value={ money( summary.total_expenses ) }
								tone="warning"
								delta="All logged expenses"
							/>
							<KpiTile
								label="Net profit"
								value={ money( summary.net_profit ) }
								tone={ summary.net_profit >= 0 ? 'success' : 'danger' }
								delta={ summary.net_profit >= 0 ? 'Positive this period' : 'Running at a loss' }
							/>
							<KpiTile
								label="Outstanding"
								value={ money( summary.total_outstanding ) }
								tone={ summary.total_outstanding > 0 ? 'danger' : 'success' }
								delta={
									summary.unpaid_bookings && summary.unpaid_bookings.length
										? `${ summary.unpaid_bookings.length } client${ summary.unpaid_bookings.length === 1 ? '' : 's' } owe balance`
										: 'All bookings settled'
								}
							/>
						</div>

						<div className="grid-2">
							<CalendarCard year={ year } month={ month } byDay={ byDay } onPrev={ goPrevMonth } onNext={ goNextMonth }>
								<div style={ { marginTop: '20px', paddingTop: '16px', borderTop: '1px solid var(--border)' } }>
									<h2 style={ { fontSize: '14px', fontWeight: 700, color: 'var(--ink-2)', margin: '0 0 10px' } }>
										Upcoming bookings
									</h2>
									{ summary.upcoming_bookings && summary.upcoming_bookings.length ? (
										<div className="rows">
											{ summary.upcoming_bookings.slice( 0, 6 ).map( ( b ) => (
												<div className="row-item" key={ b.id }>
													<div className="row-init">{ initials( b.customer_name ) }</div>
													<div className="row-name">{ b.customer_name || 'Unknown client' }</div>
													<div className="row-date tabular">{ shortDate( b.session_date ) }</div>
													<Pill status={ b.status } />
												</div>
											) ) }
										</div>
									) : (
										<div className="empty-note">No upcoming bookings yet.</div>
									) }
								</div>
							</CalendarCard>

							<div className="stack">
								<AttentionList
									title="Who still owes money"
									action={
										<a className="link" href={ `${ adminUrl }admin.php?page=infocus-erp-bookings` }>
											View all
										</a>
									}
									empty="Everyone is paid up."
									items={ ( summary.unpaid_bookings || [] ).slice( 0, 6 ).map( ( u ) => ( {
										name: u.customer_name || 'Unknown client',
										sub: `Booking #${ u.id }`,
										right: <div className="attn-amt tabular">{ money( u.balance ) }</div>,
									} ) ) }
								/>

								<AttentionList
									title="Editing deadlines"
									action={
										<a className="link" href={ `${ adminUrl }admin.php?page=infocus-erp-pipeline` }>
											Pipeline
										</a>
									}
									empty="Nothing in progress yet."
									items={ ( summary.editing_deadlines || [] ).slice( 0, 6 ).map( ( d ) => ( {
										name: `${ d.customer_name || 'Unknown client' } · ${ d.service_type || '' }`,
										sub: d.is_overdue ? `${ Math.abs( d.days_left ) } days overdue` : `${ d.days_left } days left`,
										right: <Pill status={ d.is_overdue ? 'Overdue' : 'Editing' } label={ d.is_overdue ? 'Overdue' : 'On track' } />,
									} ) ) }
								/>

								<AttentionList
									title="New inquiries"
									action={
										<a className="link" href={ `${ adminUrl }admin.php?page=infocus-erp-inquiries` }>
											Review
										</a>
									}
									empty="No inquiries waiting on review."
									items={ ( inquiries || [] ).slice( 0, 6 ).map( ( inq ) => ( {
										name: inq.name,
										sub: `${ inq.service_type || 'General' }${ inq.preferred_date ? ` · wants ${ shortDate( inq.preferred_date ) }` : '' }`,
										right: <Pill status="Pending Review" label="Pending" />,
									} ) ) }
								/>
							</div>
						</div>
					</>
				) }
			</main>
		</div>
	);
}
