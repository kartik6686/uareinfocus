/* Infocus Studio ERP — shared behavior for the public-facing forms.
 * Two things were previously duplicated per form and are consolidated here:
 *   - a month-grid calendar renderer (booking calendar's slot picker and
 *     the inquiry form's date popover each had their own copy)
 *   - a repeatable add/remove-row helper (shoot requirements' reference
 *     links and image selection's extra-image rows each had their own copy)
 * Each shortcode's own inline script still owns its specific behavior
 * (availability fetching, live estimate calculation, popover open/close) —
 * only the genuinely identical mechanics live here.
 */
window.InfocusPublic = (function () {
	var MONTHS = [
		'January', 'February', 'March', 'April', 'May', 'June',
		'July', 'August', 'September', 'October', 'November', 'December',
	];

	function pad( n ) {
		return n < 10 ? '0' + n : '' + n;
	}

	function dateKey( y, m, d ) {
		return y + '-' + pad( m + 1 ) + '-' + pad( d );
	}

	/**
	 * Renders a month grid of day buttons into opts.gridEl.
	 * opts: {
	 *   year, month, gridEl, monthLabelEl, prevBtn (optional),
	 *   dayClass: base class string for each day button (default 'ifc-cal-day'),
	 *   onDayClick: function(year, month, day, buttonEl),
	 *   decorate: function(buttonEl, year, month, day, isPast) — add extra classes/state,
	 * }
	 */
	function renderMonth( opts ) {
		var year = opts.year;
		var month = opts.month;
		var today = new Date();
		var dayClass = opts.dayClass || 'ifc-cal-day';

		if ( opts.monthLabelEl ) {
			opts.monthLabelEl.textContent = MONTHS[ month ] + ' ' + year;
		}
		opts.gridEl.innerHTML = '';

		var firstDay = new Date( year, month, 1 ).getDay();
		var daysInMonth = new Date( year, month + 1, 0 ).getDate();
		var isCurrentMonth = year === today.getFullYear() && month === today.getMonth();

		for ( var i = 0; i < firstDay; i++ ) {
			var empty = document.createElement( 'button' );
			empty.type = 'button';
			empty.className = dayClass + ' is-empty';
			empty.disabled = true;
			opts.gridEl.appendChild( empty );
		}

		for ( var d = 1; d <= daysInMonth; d++ ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = dayClass;
			btn.textContent = d;
			var isPast = isCurrentMonth && d < today.getDate();

			if ( isPast ) {
				btn.disabled = true;
			} else if ( opts.onDayClick ) {
				btn.addEventListener( 'click', ( function ( yy, mm, dd, el ) {
					return function () {
						opts.onDayClick( yy, mm, dd, el );
					};
				} )( year, month, d, btn ) );
			}

			if ( opts.decorate ) opts.decorate( btn, year, month, d, isPast );
			opts.gridEl.appendChild( btn );
		}

		if ( opts.prevBtn ) opts.prevBtn.disabled = isCurrentMonth;
	}

	/**
	 * Wires an "add row" button to append rows, and any .ifc-remove-btn
	 * inside the list (including rows already present at page load) to
	 * remove its own row.
	 * opts: {
	 *   listEl, addBtn (optional — omit for a list with no add button),
	 *   rowHtml: function() => HTML string for one new row,
	 *   onAdd: function(rowEl) — called for every row, new or pre-existing,
	 *   onRemove: function(rowEl),
	 * }
	 */
	function repeatableRows( opts ) {
		function wireRow( rowEl ) {
			var removeBtn = rowEl.querySelector( '.ifc-remove-btn' );
			if ( removeBtn ) {
				removeBtn.addEventListener( 'click', function () {
					rowEl.remove();
					if ( opts.onRemove ) opts.onRemove( rowEl );
				} );
			}
			if ( opts.onAdd ) opts.onAdd( rowEl );
		}

		if ( opts.addBtn ) {
			opts.addBtn.addEventListener( 'click', function () {
				var wrap = document.createElement( 'div' );
				wrap.innerHTML = opts.rowHtml();
				var rowEl = wrap.firstElementChild;
				opts.listEl.appendChild( rowEl );
				wireRow( rowEl );
			} );
		}

		Array.prototype.forEach.call( opts.listEl.children, wireRow );
	}

	function initToast() {
		var t = document.getElementById( 'infocus-toast' );
		if ( ! t ) return;
		setTimeout( function () {
			t.style.transition = 'opacity 0.4s';
			t.style.opacity = '0';
			setTimeout( function () {
				t.remove();
			}, 400 );
		}, 5000 );
		if ( window.history && window.history.replaceState ) {
			var url = new URL( window.location.href );
			url.searchParams.delete( 'infocus_thankyou' );
			window.history.replaceState( {}, document.title, url.toString() );
		}
	}

	document.addEventListener( 'DOMContentLoaded', initToast );

	return {
		MONTHS: MONTHS,
		pad: pad,
		dateKey: dateKey,
		renderMonth: renderMonth,
		repeatableRows: repeatableRows,
	};
} )();
