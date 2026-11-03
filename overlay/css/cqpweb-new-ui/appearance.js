/*
 * cqpweb-new-ui: day/night switch for every CQPweb page.
 * VERSION 1.0.4
 * The mode is chosen before the page draws (inline script in <head>); this file
 * only wires up the button and remembers the visitor's choice in localStorage.
 */
(function () {
	'use strict';
	function setMode(m) {
		document.documentElement.setAttribute('data-cp-mode', m);
		try { localStorage.setItem('cpMode', m); } catch (e) { /* private mode: the choice lasts for this page only */ }
		var b = document.getElementById('cpModeSwitch');
		if (b)
			b.setAttribute('aria-label', m === 'night' ? 'Switch to day mode' : 'Switch to night mode');
	}
	/* Every content table gets a sideways-scrolling wrapper, so that a long concordance or
	 * frequency table never makes the whole page wider than the screen. */
	function wrapWideTables() {
		var tables = document.querySelectorAll('table.layout-frame');
		for (var i = 0; i < tables.length; i++) {
			var t = tables[i];
			if (t.parentNode && t.parentNode.classList && t.parentNode.classList.contains('cp-scroll'))
				continue;
			if (t.querySelector(':scope > tbody > tr > td.minwidth > nav'))
				continue;
			if (t.closest('footer') || t.closest('.floatingToolTip') || t.closest('div.cp-scroll'))
				continue;
			var w = document.createElement('div');
			w.className = 'cp-scroll';
			t.parentNode.insertBefore(w, t);
			w.appendChild(t);
		}
	}
	/* On small screens the left menu is folded behind a "Menu" button. */
	function foldSidebar() {
		var cells = document.querySelectorAll('td.minwidth');
		for (var i = 0; i < cells.length; i++) {
			var td = cells[i];
			if (!td.querySelector(':scope > nav'))
				continue;
			td.classList.add('cp-sidebar');
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'cp-menu-toggle';
			b.setAttribute('aria-expanded', 'false');
			b.innerHTML = '<span>Menu</span><span aria-hidden="true">&#9776;</span>';
			b.addEventListener('click', (function (cell, btn) {
				return function () {
					var open = cell.classList.toggle('cp-open');
					btn.setAttribute('aria-expanded', open ? 'true' : 'false');
				};
			})(td, b));
			td.insertBefore(b, td.firstChild);
		}
	}
	function init() {
		wrapWideTables();
		foldSidebar();
		var b = document.getElementById('cpModeSwitch');
		if (!b)
			return;
		var cur = document.documentElement.getAttribute('data-cp-mode') || 'day';
		b.setAttribute('aria-label', cur === 'night' ? 'Switch to day mode' : 'Switch to night mode');
		b.addEventListener('click', function () {
			var now = document.documentElement.getAttribute('data-cp-mode') === 'night' ? 'day' : 'night';
			setMode(now);
		});
	}
	if (document.readyState === 'loading')
		document.addEventListener('DOMContentLoaded', init);
	else
		init();
})();
