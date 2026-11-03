/*
 * cqpweb-new-ui: Case-sensitive box and Match strategy row on the query forms, the live password checklist,
 * https links, American spelling in the text of CQPweb's own pages, deleting system messages
 * with their [x], and alternating word colors in concordances.
 * VERSION 1.0.2
 */
(function () {
	'use strict';

	function meta(name) {
		var m = document.querySelector('meta[name="' + name + '"]');
		return m ? m.getAttribute('content') : null;
	}
	/* the CQPweb root, relative to this page ("", "../") */
	var ROOT = meta('cp-root') || '';

	/* ---------- Case-sensitive box (remembered in this browser) ---------- */
	function caseBox(form) {
		var sel = form.querySelector('select[name="qmode"]');
		if (!sel || form.querySelector('input[name="cpCaseUI"]'))
			return;
		/* the site's default (Personalization page) unless this browser has chosen before; the corpus's own default is ignored */
		var stored = null;
		try { stored = localStorage.getItem('cpCaseSensitive'); } catch (e) {}
		var on = stored === '1' || (stored === null && meta('cp-case-default') === '1');

		/* start from the mode the server chose (Simple query unless the account prefers CQP syntax),
		 * not from whatever the browser restored after a reload or a Back */
		sel.setAttribute('autocomplete', 'off');
		Array.prototype.forEach.call(sel.options, function (o) {
			if (o.defaultSelected)
				sel.value = o.value;
		});

		/* one "Simple query" entry: the box decides the case, so CQPweb's two simple modes are merged */
		var optNo = sel.querySelector('option[value="sq_nocase"]');
		var optCase = sel.querySelector('option[value="sq_case"]');
		if (optNo && optCase) {
			if (sel.value === 'sq_case')
				sel.value = 'sq_nocase';
			optCase.remove();
			optNo.textContent = 'Simple query';
			sel.insertBefore(optNo, sel.firstChild);
		}

		var flag = document.createElement('input');
		flag.type = 'hidden';
		flag.name = 'cpCaseUI';
		flag.value = '1';
		form.appendChild(flag);

		var label = document.createElement('label');
		label.className = 'cp-case';
		var box = document.createElement('input');
		box.type = 'checkbox';
		box.name = 'cpCase';
		box.value = '1';
		box.checked = on;
		label.appendChild(box);
		label.appendChild(document.createTextNode('Case-sensitive'));
		sel.parentNode.insertBefore(label, sel.nextSibling);

		var hint = document.createElement('span');
		hint.className = 'cp-case-hint';
		hint.textContent = 'Searches ignore capital letters unless this box is ticked. In CQP syntax, add %C after a word '
			+ '(for example [word="London"%C]) to keep just that word case-sensitive.';
		label.parentNode.insertBefore(hint, label.nextSibling);

		box.addEventListener('change', function () {
			try { localStorage.setItem('cpCaseSensitive', box.checked ? '1' : '0'); } catch (e) {}
		});

		if (meta('cp-case-notice') !== '0')
			caseNotice(form, on);
	}

	/* ---------- one-time notice about the new case-insensitive default ---------- */
	function caseNotice(form, sensitive) {
		var seen = null;
		try { seen = localStorage.getItem('cpCaseNoticeSeen'); } catch (e) {}
		if (seen === '1' || document.querySelector('.cp-case-notice'))
			return;
		var box = document.createElement('div');
		box.className = 'cp-case-notice';
		box.setAttribute('role', 'note');
		box.innerHTML = sensitive
			? '<p><strong>Searches here distinguish capital letters.</strong> '
				+ '<span class="cp-case-q">house</span> does not find <span class="cp-case-q">House</span>. '
				+ 'To ignore capitals, untick <strong>Case-sensitive</strong>; in CQP syntax you can also add '
				+ '<span class="cp-case-q">%c</span> after a single word, as in <span class="cp-case-q">[word="house"%c]</span>.</p>'
			: '<p><strong>Searches here ignore capital letters.</strong> '
				+ '<span class="cp-case-q">house</span> also finds <span class="cp-case-q">House</span> and '
				+ '<span class="cp-case-q">HOUSE</span>, in Simple query and in CQP syntax. '
				+ 'To make a search case sensitive, tick <strong>Case-sensitive</strong>; in CQP syntax you can also add '
				+ '<span class="cp-case-q">%C</span> (that\'s a capital C) after a single word, as in <span class="cp-case-q">[word="London"%C]</span>.</p>';
		var ok = document.createElement('button');
		ok.type = 'button';
		ok.textContent = 'Got it';
		ok.addEventListener('click', function () {
			try { localStorage.setItem('cpCaseNoticeSeen', '1'); } catch (e) {}
			box.remove();
		});
		box.appendChild(ok);
		form.insertBefore(box, form.firstChild);
	}

	/* ---------- password checklist on the sign-up and change-password forms ---------- */
	function pwRules(form) {
		var pw = form.querySelector('#newPassword');
		if (!pw)
			return;
		var meta = document.getElementById('CQPwebGlobalApplicationData');
		var min = Math.max(12, parseInt((meta && meta.getAttribute('data-pwd-min-length')) || '12', 10) || 12);
		var rules = [
			['At least ' + min + ' characters', function (v) { return Array.from(v).length >= min; }],
			['A lowercase letter', function (v) { return /\p{Ll}/u.test(v); }],
			['An uppercase letter', function (v) { return /\p{Lu}/u.test(v); }],
			['A digit', function (v) { return /\p{Nd}/u.test(v); }],
			['A special character (punctuation mark or symbol)', function (v) { return /[^\p{L}\p{Nd}\s]/u.test(v); }]
		];
		var ul = document.createElement('ul');
		ul.className = 'cp-pwrule';
		ul.setAttribute('aria-live', 'polite');
		var items = rules.map(function (r) {
			var li = document.createElement('li');
			li.textContent = r[0];
			ul.appendChild(li);
			return li;
		});
		var holder = pw.closest('td') || pw.parentNode;
		holder.appendChild(ul);
		function update() {
			rules.forEach(function (r, i) { items[i].classList.toggle('cp-ok', r[1](pw.value)); });
		}
		pw.addEventListener('input', update);
		update();

		/* CQPweb's own check (jsc/useracct-create.js) has a bug that lets every password through;
		 * this one runs first and stops the form if the rule is not met. */
		form.addEventListener('submit', function (e) {
			var bad = rules.filter(function (r) { return !r[1](pw.value); }).map(function (r) { return r[0].toLowerCase(); });
			var check = form.querySelector('#newPasswordCheck');
			var msg = '';
			if (bad.length)
				msg = 'Your password still needs: ' + bad.join(', ') + '.';
			else if (check && check.value !== pw.value)
				msg = 'The two passwords you typed are not the same.';
			if (msg) {
				e.preventDefault();
				e.stopImmediatePropagation();
				var out = form.querySelector('.cp-pwerror');
				if (!out) {
					out = document.createElement('p');
					out.className = 'cp-pwerror cp-banner';
					out.setAttribute('role', 'alert');
					holder.appendChild(out);
				}
				out.textContent = msg;
				pw.focus();
			}
		}, true);
	}

	/* ---------- tutorial box: above the query box while "CQP syntax" is selected ---------- */
	function tutorialBox() {
		var box = document.querySelector('.cp-tut-callout');
		var ta = document.querySelector('textarea[name="qdata"]');
		var sel = document.querySelector('select[name="qmode"]');
		if (!box || !ta || !sel)
			return;
		ta.parentNode.insertBefore(box, ta);
		function show() { box.hidden = sel.value !== 'cqp'; }
		sel.addEventListener('change', show);
		show();
	}

	/* ---------- Match strategy: grayed out while "CQP syntax" is selected (CQPweb ignores it there) ---------- */
	function strategyRow(form) {
		var sel = form.querySelector('select[name="qmode"]');
		var strat = form.querySelector('select[name="qstrategy"]');
		if (!sel || !strat)
			return;
		var row = strat.closest('tr');
		var why = document.createElement('span');
		why.className = 'cp-off-why';
		why.textContent = 'Not used in CQP syntax. There, start the query with (?longest), (?shortest) or (?traditional) instead.';
		strat.parentNode.appendChild(why);
		function update() {
			var off = sel.value === 'cqp';
			strat.disabled = off;
			why.hidden = !off;
			if (row)
				row.classList.toggle('cp-off', off);
		}
		sel.addEventListener('change', update);
		update();
	}

	/* ---------- every link to another site goes over https (CQPweb's own pages still carry some http:// links) ---------- */
	function httpsLinks() {
		Array.prototype.forEach.call(document.querySelectorAll('a[href^="http://"]'), function (a) {
			var h = a.getAttribute('href');
			if (/^http:\/\/(localhost|127\.|\[::1\])/i.test(h) || a.hasAttribute('data-cp-keep-http'))
				return;
			a.setAttribute('href', h.replace(/^http:\/\/cwb\.sourceforge\.net/i, 'https://cwb.sourceforge.io').replace(/^http:/i, 'https:'));
		});
	}

	/* ---------- American spelling in CQPweb's own interface text ----------
	 * CQPweb writes British English ("Analyse corpus", "colour scheme"). This changes the words in the
	 * page's text, tooltips, placeholders and button labels. Corpus data (concordance lines, query
	 * boxes, code) and the documentation pages are never touched. */
	var US_WORDS = {
		colour: 'color', colours: 'colors', coloured: 'colored', colourful: 'colorful', colouring: 'coloring',
		grey: 'gray', greyed: 'grayed', greys: 'grays',
		analyse: 'analyze', analysed: 'analyzed', analysing: 'analyzing',
		behaviour: 'behavior', behaviours: 'behaviors', favour: 'favor', favourite: 'favorite', favourites: 'favorites',
		honour: 'honor', centre: 'center', centred: 'centered', centres: 'centers', metre: 'meter', metres: 'meters',
		licence: 'license', catalogue: 'catalog', catalogues: 'catalogs', programme: 'program', programmes: 'programs',
		cancelled: 'canceled', cancelling: 'canceling', labelled: 'labeled', labelling: 'labeling',
		modelled: 'modeled', modelling: 'modeling', travelled: 'traveled', enrolment: 'enrollment',
		whilst: 'while', amongst: 'among', judgement: 'judgment', acknowledgement: 'acknowledgment'
	};
	var US_STEMS = 'categor|visual|personal|standard|normal|recogn|organ|reorgan|optim|custom|special|final|initial|random|summar|'
		+ 'priorit|minim|maxim|util|author|apolog|real|emphas|character|capital|local|synchron|memor|stabil|token|sanit|serial|lemmat|'
		+ 'marginal|general|central|penal|famil|harmon|critic|prior|item|digit|subcorpor|vocal|equal|neutral|ideal|symbol|summar';
	var US_RE = new RegExp('\\b(' + Object.keys(US_WORDS).join('|') + '|(?:non-)?(?:' + US_STEMS + ')is(?:e|ed|es|ing|ation|ations|er|ers))\\b', 'gi');
	function usWord(w) {
		var low = w.toLowerCase();
		var r = US_WORDS[low] || low.replace(/is(e|ed|es|ing|ation|ations|er|ers)$/, 'iz$1');
		if (w === w.toUpperCase() && w !== low)
			return r.toUpperCase();
		if (w.charAt(0) !== low.charAt(0))
			return r.charAt(0).toUpperCase() + r.slice(1);
		return r;
	}
	function usText(t) {
		return t.replace(US_RE, usWord);
	}
	var US_SKIP = 'script, style, textarea, code, pre, .cp-embed, td.kwic, td.lineview, td.parallel-line, td.parallel-kwic, '
		+ '.concordtable, [contenteditable], .cp-case-q, .cp-query-notice, .cpa-gflist';
	function americanSpelling() {
		var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
			acceptNode: function (n) {
				if (!/[a-z]/i.test(n.nodeValue))
					return NodeFilter.FILTER_SKIP;
				var p = n.parentElement;
				return p && p.closest(US_SKIP) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
			}
		});
		var n, todo = [];
		while ((n = walker.nextNode()))
			todo.push(n);
		todo.forEach(function (node) {
			var v = usText(node.nodeValue);
			if (v !== node.nodeValue)
				node.nodeValue = v;
		});
		Array.prototype.forEach.call(document.querySelectorAll('[data-tooltip], [title], [placeholder], input[type="submit"], input[type="button"], input[type="reset"]'), function (el) {
			if (el.closest(US_SKIP))
				return;
			['data-tooltip', 'title', 'placeholder'].forEach(function (a) {
				var v = el.getAttribute(a);
				if (v && usText(v) !== v)
					el.setAttribute(a, usText(v));
			});
			if (/^(submit|button|reset)$/.test(el.type || '') && el.value && usText(el.value) !== el.value)
				el.value = usText(el.value);
		});
		if (document.title && usText(document.title) !== document.title)
			document.title = usText(document.title);
	}

	/* ---------- system messages: [x] deletes the message in place ----------
	 * Two clicks: the first asks for confirmation, the second deletes through the admin action
	 * file. Without JavaScript the link opens the message on the admin page instead. */
	function sysmsgDelete() {
		Array.prototype.forEach.call(document.querySelectorAll('a[data-cp-sysmsg-del]'), function (a) {
			a.addEventListener('click', function (e) {
				e.preventDefault();
				if (!a.classList.contains('cp-armed')) {
					a.classList.add('cp-armed');
					a.textContent = '[click again to delete]';
					setTimeout(function () { if (a.isConnected) { a.classList.remove('cp-armed'); a.textContent = '[x]'; } }, 5000);
					return;
				}
				a.textContent = '[deleting…]';
				fetch(ROOT + 'adm/cqpweb-new-ui-act.php', {
					method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ action: 'sysmsgDelete', id: parseInt(a.getAttribute('data-cp-sysmsg-del'), 10) })
				}).then(function (r) { return r.json(); })
				  .catch(function () { return { ok: false, message: 'The server could not be reached.' }; })
				  .then(function (r) {
					if (!r.ok) {
						a.textContent = '[x]';
						a.classList.remove('cp-armed');
						a.setAttribute('data-tooltip', r.message);
						a.title = r.message;
						return;
					}
					/* the message is two rows; remove them and one gap row next to them */
					var row = a.closest('tr');
					var next = row && row.nextElementSibling;
					var gap = next && next.nextElementSibling && next.nextElementSibling.classList.contains('cp-msg-gap') ? next.nextElementSibling
						: (row && row.previousElementSibling && row.previousElementSibling.classList.contains('cp-msg-gap') ? row.previousElementSibling : null);
					var table = row && row.closest('table');
					[row, next, gap].forEach(function (x) { if (x) x.remove(); });
					if (table && !table.querySelector('[data-cp-sysmsg-del]'))
						table.remove();
				});
			});
		});
	}

	/* ---------- concordances: alternating word colors ----------
	 * Every second word can be set off along each line: with a light background, a different
	 * text color, or both. With tags shown below the words (glossbox tables), the word and its tag
	 * are shaded together as one box. The corpus's default comes from the Appearance page (meta
	 * cp-alt-words); a menu above the concordance lets each visitor choose otherwise, and the
	 * browser remembers that choice until "Corpus default" is picked again. The search hit keeps
	 * its own highlight. Only classes are added, so copying text is not affected. */
	var ALT_NAMES = { off: 'Off', bg: 'Background shading', text: 'Text color', both: 'Background shading and text color' };
	function altTokens() {
		var frames = document.querySelectorAll('table.glossbox-frame');
		var first = document.querySelector('td.kwic, td.lineview, td.parallel-line') || frames[0];
		if (!first)
			return;
		var meta = document.querySelector('meta[name="cp-alt-words"]');
		var def = meta && ALT_NAMES[meta.getAttribute('content')] ? meta.getAttribute('content') : 'off';
		var own = '';
		try {
			var stored = localStorage.getItem('cpAltTokens');
			own = stored === '1' ? 'bg' : (ALT_NAMES[stored] ? stored : '');
		} catch (e) {}

		if (frames.length) {
			/* tags below the words: count columns along each concordance line, across its tables */
			var lines = [];
			Array.prototype.forEach.call(frames, function (f) {
				var line = f.parentElement ? f.parentElement.closest('tr') : null;
				var entry = lines.filter(function (l) { return l.tr === line; })[0];
				if (!entry) { entry = { tr: line, frames: [] }; lines.push(entry); }
				entry.frames.push(f);
			});
			lines.forEach(function (l) {
				var n = 0;
				l.frames.forEach(function (f) {
					var rows = f.rows;
					if (!rows.length) return;
					var top = rows[0].cells, bottom = rows.length > 1 ? rows[1].cells : [];
					for (var k = 0; k < top.length; k++, n++) {
						if (!(n % 2) || top[k].classList.contains('node')) continue;
						top[k].classList.add('cp-col-alt');
						if (bottom[k]) bottom[k].classList.add('cp-col-alt');
					}
				});
			});
		} else {
			Array.prototype.forEach.call(document.querySelectorAll('tr'), function (tr) {
				if (!tr.querySelector('td.kwic, td.lineview, td.parallel-line'))
					return;
				Array.prototype.forEach.call(tr.querySelectorAll('span.token'), function (t, i) {
					if (i % 2 && !t.closest('.node')) t.classList.add('cp-tok-alt');
				});
			});
		}
		function apply(m) {
			var root = document.documentElement;
			['bg', 'text', 'both'].forEach(function (k) { root.classList.toggle('cp-alt-' + k, m === k); });
		}
		apply(own || def);
		var table = first.closest('table.concordtable') || (first.closest('table.glossbox-frame') || first).parentElement.closest('table') || first.closest('table');
		if (!table || !table.parentNode)
			return;
		var label = document.createElement('label');
		label.className = 'cp-alt-toggle';
		label.appendChild(document.createTextNode('Alternate words: '));
		var sel = document.createElement('select');
		[['', 'Corpus default (' + ALT_NAMES[def].toLowerCase() + ')'], ['off', 'Off'], ['bg', 'Background shading'], ['text', 'Text color'], ['both', 'Background shading and text color']].forEach(function (o) {
			var opt = document.createElement('option');
			opt.value = o[0];
			opt.textContent = o[1];
			sel.appendChild(opt);
		});
		sel.value = own;
		label.appendChild(sel);
		sel.addEventListener('change', function () {
			apply(sel.value || def);
			try {
				if (sel.value) localStorage.setItem('cpAltTokens', sel.value);
				else localStorage.removeItem('cpAltTokens');
			} catch (e) {}
		});
		table.parentNode.insertBefore(label, table);
	}

	/* ---------- the summary line above a concordance ----------
	 * CQPweb writes: Your query “Q” returned N matches in M different texts (in W words [T texts];
	 * frequency: F instances per million words), ordered randomly
	 * This shows the query in code type without the quotation marks, and writes the rest as
	 * (in W words / T texts). Frequency: F instances per million words, with the frequency in bold
	 * and its number in code type. */
	function summaryLine() {
		Array.prototype.forEach.call(document.querySelectorAll('th.layout-strong'), function (th) {
			var h = th.innerHTML;
			if (h.indexOf('instances per million words') < 0 && h.indexOf('\u201c') < 0)
				return;
			var out = h.replace(/(query )\u201c([\s\S]*?)\u201d/, '$1<code class="cp-q">$2</code>')
				.replace(/\(in ([\d,]+) words \[([\d,]+) ([\s\S]*?)\]; frequency: ([\d.,]+) instances per million words\)/,
					'(in $1 words / $2 $3). Frequency: <strong><code class="cp-freq">$4</code> instances per million words</strong>');
			if (out !== h)
				th.innerHTML = out;
		});
	}

	function init() {
		sysmsgDelete();
		summaryLine();
		altTokens();
		americanSpelling();
		httpsLinks();
		tutorialBox();
		Array.prototype.forEach.call(document.querySelectorAll('form'), function (f) {
			if (f.querySelector('[name="qdata"]')) {
				caseBox(f);
				strategyRow(f);
			}
			pwRules(f);
		});
	}
	if (document.readyState === 'loading')
		document.addEventListener('DOMContentLoaded', init);
	else
		init();
})();
