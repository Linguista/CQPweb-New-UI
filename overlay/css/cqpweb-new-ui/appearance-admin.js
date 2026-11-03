/*
 * cqpweb-new-ui: the admin Appearance page (adm/index.php?ui=appearance).
 * VERSION 1.0.3
 */
(function () {
	'use strict';

	var root = document.getElementById('cpAppearance');
	if (!root)
		return;
	var D = JSON.parse(root.getAttribute('data-cp'));

	/* ---------- state ---------- */
	var state = {
		scopes: {},          /* id -> {preset, custom:{day:{},night:{}}, fonts:{}, short} */
		current: D.scopes[0].id,
		mode: 'day',
		dirty: false,
		fonts: D.fonts.slice()
	};
	D.scopes.forEach(function (s) {
		state.scopes[s.id] = JSON.parse(JSON.stringify(s.settings));
		if (Array.isArray(state.scopes[s.id].custom.day)) state.scopes[s.id].custom.day = {};
		if (Array.isArray(state.scopes[s.id].custom.night)) state.scopes[s.id].custom.night = {};
	});

	function $(id) { return document.getElementById(id); }
	function el(tag, attrs, kids) {
		var e = document.createElement(tag);
		if (attrs)
			Object.keys(attrs).forEach(function (k) {
				if (k === 'text') e.textContent = attrs[k];
				else if (k === 'style') e.setAttribute('style', attrs[k]);
				else if (k.slice(0, 2) === 'on') e.addEventListener(k.slice(2), attrs[k]);
				else e.setAttribute(k, attrs[k]);
			});
		(kids || []).forEach(function (c) { if (c) e.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
		return e;
	}
	function scopeInfo(id) { return D.scopes.filter(function (s) { return s.id === id; })[0]; }
	function cur() { return state.scopes[state.current]; }

	/* ---------- color maths (same rules as appearance-lib.php) ---------- */
	function hexToRgb(h) { var n = parseInt(h.slice(1), 16); return [(n >> 16) & 255, (n >> 8) & 255, n & 255]; }
	function lum(h) {
		var c = hexToRgb(h).map(function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
		return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
	}
	function ratio(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }
	function hexToHsl(h) {
		var rgb = hexToRgb(h).map(function (v) { return v / 255; }), r = rgb[0], g = rgb[1], b = rgb[2];
		var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, hh = 0, s = 0;
		if (max !== min) {
			var d = max - min;
			s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
			if (max === r) hh = (g - b) / d + (g < b ? 6 : 0); else if (max === g) hh = (b - r) / d + 2; else hh = (r - g) / d + 4;
			hh *= 60;
		}
		return [hh, s, l];
	}
	function hslToHex(h, s, l) {
		h = ((h % 360) + 360) % 360 / 360;
		function f(n) {
			var k = (n + h * 12) % 12, a = s * Math.min(l, 1 - l);
			var c = l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1));
			return ('0' + Math.round(c * 255).toString(16)).slice(-2);
		}
		return '#' + f(0) + f(8) + f(4);
	}
	function ensure(fg, bg, target) {
		if (ratio(fg, bg) >= target) return fg;
		var hsl = hexToHsl(fg), darker = lum(bg) > 0.18, l = hsl[2];
		for (var i = 0; i < 100; i++) {
			l = darker ? l - 0.01 : l + 0.01;
			if (l <= 0 || l >= 1) break;
			var c = hslToHex(hsl[0], hsl[1], l);
			if (ratio(c, bg) >= target) return c;
		}
		return darker ? '#000000' : '#ffffff';
	}
	function palette(scopeId, mode) {
		var s = state.scopes[scopeId];
		var p = Object.assign({}, D.presets[s.preset][mode]);
		Object.keys(s.custom[mode] || {}).forEach(function (k) { if (k.indexOf(':') < 0) p[k] = s.custom[mode][k]; });
		return p;
	}
	function partnerOf(token) {
		for (var i = 0; i < D.pairs.length; i++)
			if (D.pairs[i][0] === token && ['text', 'muted', 'link'].indexOf(D.pairs[i][1]) < 0)
				return D.pairs[i];
		return null;
	}

	/* ---------- dirty / status ---------- */
	function setStatus(msg, bad) {
		var s = $('cpaStatus');
		s.textContent = msg;
		s.className = 'cpa-status' + (bad ? ' cpa-bad' : '');
	}
	function markDirty() {
		state.dirty = true;
		setStatus('Unsaved changes.');
	}
	window.addEventListener('beforeunload', function (e) {
		if (state.dirty) { e.preventDefault(); e.returnValue = ''; }
	});

	/* ---------- scopes ---------- */
	function renderScopes() {
		var box = $('cpaScopes');
		box.innerHTML = '';
		var lastGroup = null;
		D.scopes.forEach(function (s) {
			if (s.group !== lastGroup) {
				box.appendChild(el('span', { 'class': 'cpa-chipgroup', text: s.group }));
				lastGroup = s.group;
			}
			var p = palette(s.id, 'day');
			var on = s.id === state.current;
			box.appendChild(el('button', {
				type: 'button', 'class': 'cpa-chip' + (on ? ' cpa-on' : ''), 'aria-pressed': on ? 'true' : 'false',
				title: s.title || s.label,
				onclick: function () { state.current = s.id; renderAll(); }
			}, [el('span', { 'class': 'cpa-dot', style: 'background:' + p.sidebar + ';border-color:' + p.strong, 'aria-hidden': 'true' }),
				s.id.indexOf('corpus:') === 0 ? (state.scopes[s.id].short || s.label) : s.label]));
		});
		var info = scopeInfo(state.current);
		var row = $('cpaShortRow'), cardRow = $('cpaCardRow');
		if (state.current.indexOf('corpus:') === 0) {
			row.hidden = false;
			$('cpaShort').value = cur().short || info.defaultShort;
			cardRow.hidden = false;
			$('cpaAltRow').hidden = false;
			$('cpaAlt').value = cur().altWords || 'off';
			$('cpaCard').value = cur().card || '';
			$('cpaCard').placeholder = info.title || '';
		} else {
			row.hidden = true;
			cardRow.hidden = true;
			$('cpaAltRow').hidden = true;
		}
	}
	$('cpaAlt').addEventListener('change', function () {
		cur().altWords = this.value;
		markDirty();
	});
	$('cpaCard').addEventListener('input', function () {
		cur().card = this.value;
		markDirty();
	});
	$('cpaShort').addEventListener('input', function () {
		cur().short = this.value;
		markDirty();
		renderScopes();
		renderPreview();
	});

	/* ---------- preset dropdown ---------- */
	function renderPresets() {
		var sel = $('cpaPreset');
		if (!sel.options.length)
			Object.keys(D.presets).forEach(function (id) {
				sel.appendChild(el('option', { value: id, text: D.presets[id].label }));
			});
		sel.value = cur().preset;
		var sw = $('cpaPresetSwatches');
		sw.innerHTML = '';
		var p = D.presets[cur().preset][state.mode];
		['nav', 'sidebar', 'strong', 'surface2', 'accent', 'hit', 'link'].forEach(function (k) {
			sw.appendChild(el('span', { style: 'background:' + p[k], title: D.tokens[k] }));
		});
	}
	$('cpaPreset').addEventListener('change', function () {
		var c = cur();
		var hadCustom = Object.keys(c.custom.day).length + Object.keys(c.custom.night).length;
		c.preset = this.value;
		c.custom = { day: {}, night: {} };
		markDirty();
		if (hadCustom) setStatus('Unsaved changes. The new scheme replaced this page type\'s individual color changes.');
		renderAll();
	});

	/* ---------- day / night editing ---------- */
	Array.prototype.forEach.call(root.querySelectorAll('.cpa-segbtn'), function (b) {
		b.addEventListener('click', function () {
			state.mode = b.getAttribute('data-mode');
			Array.prototype.forEach.call(root.querySelectorAll('.cpa-segbtn'), function (x) {
				x.setAttribute('aria-pressed', x === b ? 'true' : 'false');
			});
			renderAll();
		});
	});

	/* ---------- color tokens ---------- */
	function renderTokens() {
		var box = $('cpaTokens');
		box.innerHTML = '';
		var c = cur(), p = palette(state.current, state.mode), base = D.presets[c.preset][state.mode];
		Object.keys(D.tokens).forEach(function (k) {
			var custom = Object.prototype.hasOwnProperty.call(c.custom[state.mode], k);
			var id = 'cpaTok-' + k;
			var input = el('input', { type: 'color', id: id, value: p[k] });
			input.addEventListener('input', function () {
				c.custom[state.mode][k] = this.value;
				var pr = partnerOf(k);
				if (pr && !Object.prototype.hasOwnProperty.call(c.custom[state.mode], pr[1] + ':manual')) {
					var fg = palette(state.current, state.mode)[pr[1]];
					var fixed = ensure(fg, this.value, pr[2]);
					if (fixed !== fg) c.custom[state.mode][pr[1]] = fixed;
				}
				markDirty();
				renderPairs(); renderPreview(); renderScopes(); updateHexes();
			});
			input.addEventListener('change', function () {
				/* a text color set by hand is never auto-adjusted again */
				if (['text', 'muted', 'strongText', 'sidebarText', 'navText', 'accentText', 'hitText', 'link'].indexOf(k) >= 0)
					c.custom[state.mode][k + ':manual'] = true;
				renderTokens();
			});
			var row = el('div', { 'class': 'cpa-tok' + (custom ? ' cpa-custom' : '') }, [
				el('label', { 'for': id, text: D.tokens[k] }),
				input,
				el('code', { 'class': 'cpa-hex', 'data-tok': k, text: p[k] }),
				custom ? el('button', {
					type: 'button', 'class': 'cpa-mini',
					onclick: function () {
						delete c.custom[state.mode][k];
						delete c.custom[state.mode][k + ':manual'];
						markDirty(); renderAll();
					}
				}, ['Use scheme']) : el('span', { 'class': 'cpa-from', text: 'from scheme' })
			]);
			box.appendChild(row);
		});
	}
	function updateHexes() {
		var p = palette(state.current, state.mode);
		Array.prototype.forEach.call(root.querySelectorAll('.cpa-hex'), function (h) {
			h.textContent = p[h.getAttribute('data-tok')];
		});
	}
	function renderPairs() {
		var box = $('cpaPairs');
		box.innerHTML = '';
		var p = palette(state.current, state.mode);
		D.pairs.forEach(function (pr) {
			var r = ratio(p[pr[0]], p[pr[1]]);
			var grade = r >= 7 ? 'AAA' : (r >= 4.5 ? 'AA' : 'Too low');
			var ok = r >= pr[2];
			box.appendChild(el('div', { 'class': 'cpa-pair' + (ok ? '' : ' cpa-fail') }, [
				el('span', { 'class': 'cpa-sample', style: 'background:' + p[pr[0]] + ';color:' + p[pr[1]], 'aria-hidden': 'true', text: 'Aa' }),
				el('span', { 'class': 'cpa-pairlabel', text: D.tokens[pr[1]] + ' on ' + D.tokens[pr[0]].toLowerCase() }),
				el('span', { 'class': 'cpa-ratio', text: r.toFixed(1) + ' : 1' }),
				el('span', { 'class': 'cpa-grade', text: ok ? grade : 'Too low (needs ' + pr[2] + ')' })
			]));
		});
	}

	/* ---------- fonts ---------- */
	function stack(name) {
		var f = state.fonts.filter(function (x) { return x.name === name; })[0];
		var kind = f ? f.kind : 'sans';
		var fb = kind === 'mono' ? 'ui-monospace, monospace' : (kind === 'serif' ? 'Georgia, serif' : 'system-ui, sans-serif');
		return '"' + D.fontPrefix + name + '", ' + fb;
	}
	function renderFontRoles() {
		var box = $('cpaFontRoles');
		box.innerHTML = '';
		var c = cur();
		Object.keys(D.roles).forEach(function (role) {
			var id = 'cpaFont-' + role;
			var sel = el('select', { id: id });
			var known = state.fonts.some(function (f) { return f.name === c.fonts[role]; });
			[['sans', 'Sans serif'], ['serif', 'Serif'], ['mono', 'Monospace']].forEach(function (g) {
				var og = el('optgroup', { label: g[1] });
				state.fonts.filter(function (f) { return f.kind === g[0]; }).forEach(function (f) {
					og.appendChild(el('option', { value: f.name, text: f.name, style: 'font-family:' + stack(f.name) }));
				});
				if (og.children.length) sel.appendChild(og);
			});
			if (!known)
				sel.appendChild(el('option', { value: c.fonts[role], text: c.fonts[role] + ' (not installed)' }));
			sel.value = c.fonts[role];
			var sample = el('span', { 'class': 'cpa-fontsample', style: 'font-family:' + stack(c.fonts[role]), text: 'Aa Gg Ññ Éé Øø 0123 ʃ ɾ' });
			sel.addEventListener('change', function () {
				c.fonts[role] = this.value;
				sample.setAttribute('style', 'font-family:' + stack(this.value));
				markDirty(); renderPreview(); renderInstalled();
			});
			box.appendChild(el('div', { 'class': 'cpa-fontrow' }, [
				el('div', {}, [el('label', { 'for': id, text: D.roles[role] }), sample]), sel
			]));
		});
	}

	/* ---------- preview ---------- */
	function renderPreview() {
		var c = cur(), p = palette(state.current, state.mode), info = scopeInfo(state.current);
		var box = $('cpaPreview');
		var vars = '';
		Object.keys(p).forEach(function (k) { vars += '--p-' + k + ':' + p[k] + ';'; });
		Object.keys(D.roles).forEach(function (r) { vars += '--p-font-' + r + ':' + stack(c.fonts[r]) + ';'; });
		box.setAttribute('style', vars);
		var name = state.current.indexOf('corpus:') === 0 ? (c.short || info.defaultShort) : info.label;
		$('cpaPrevCaption').textContent = info.label + ', ' + (state.mode === 'day' ? 'day' : 'night') + ' palette, with the fonts chosen above.';
		box.innerHTML = '';
		box.appendChild(el('div', { 'class': 'cpp-top' }, [
			el('span', { 'class': 'cpp-brand', text: D.siteName }),
			el('span', { 'class': 'cpp-btn', text: 'Homepage' }),
			state.current.indexOf('corpus:') === 0 ? el('span', { 'class': 'cpp-btn cpp-back', text: '← Back to ' + name }) : null
		]));
		var body = el('div', { 'class': 'cpp-body' });
		body.appendChild(el('div', { 'class': 'cpp-side' }, [
			el('div', { 'class': 'cpp-sidetitle', text: 'Menu' }),
			el('div', { 'class': 'cpp-sidehead', text: 'Corpus queries' }),
			el('div', { 'class': 'cpp-item', text: 'Standard query' }),
			el('div', { 'class': 'cpp-item cpp-cur', text: 'Word lookup' }),
			el('div', { 'class': 'cpp-item', text: 'Frequency lists' })
		]));
		var lines = [['in the end we stayed at the', 'house', 'of my grandmother because it rained'],
			['they bought a', 'house', 'near the river last year'],
			['did you know she left the', 'house', 'at eighteen?']];
		var conc = el('div', { 'class': 'cpp-conc' });
		lines.forEach(function (l) {
			conc.appendChild(el('div', { 'class': 'cpp-line' }, [
				el('span', { 'class': 'cpp-left', text: l[0] }), el('mark', { text: l[1] }), el('span', { text: l[2] })]));
		});
		body.appendChild(el('div', { 'class': 'cpp-main' }, [
			el('div', { 'class': 'cpp-bar', text: 'Your query “house” returned 244,599 matches' }),
			el('div', { 'class': 'cpp-cell' }, ['Results for ', el('a', { href: '#cpaPrevH', text: '[word="house"]' }),
				el('span', { 'class': 'cpp-muted', text: ' · 0.03 seconds' })]),
			el('div', { 'class': 'cpp-cell cpp-contrast', text: 'A highlighted cell' }),
			conc,
			el('div', { 'class': 'cpp-actions' }, [el('span', { 'class': 'cpp-accent', text: 'Start query' }),
				el('span', { 'class': 'cpp-data', text: '896,242,344 tokens' })])
		]));
		box.appendChild(body);
	}

	/* ---------- installed fonts and downloads ---------- */
	function renderInstalled() {
		var ul = $('cpaInstalled');
		ul.innerHTML = '';
		$('cpaInstalledH').textContent = 'Installed fonts (' + state.fonts.length + ')';
		var c = cur();
		state.fonts.forEach(function (f) {
			var used = Object.keys(D.roles).filter(function (r) { return c.fonts[r] === f.name; }).map(function (r) { return D.roles[r]; });
			ul.appendChild(el('li', {}, [
				el('span', { 'class': 'cpa-fontname', style: 'font-family:' + stack(f.name), text: f.name }),
				el('span', { 'class': 'cpa-fontmeta', text: ({ sans: 'Sans serif', serif: 'Serif', mono: 'Monospace' })[f.kind] + ' · ' + f.source }),
				used.length ? el('span', { 'class': 'cpa-fontused', text: 'Used here for: ' + used.join(', ') }) : null
			]));
		});
		var missing = D.recommended.filter(function (r) { return !state.fonts.some(function (f) { return f.name === r.name; }); });
		var b = $('cpaGfRecommended');
		b.textContent = missing.length ? 'Install the recommended fonts (' + missing.length + ' not yet here)' : 'All recommended fonts are installed';
		b.disabled = !missing.length;
	}
	function reloadFontCss(ver) {
		var links = document.querySelectorAll('link[rel="stylesheet"][href*="cp-fonts.css"]');
		Array.prototype.forEach.call(links, function (l) {
			l.href = /[?&]v=\d*/.test(l.href) ? l.href.replace(/([?&])v=\d*/, '$1v=' + ver) : l.href + (l.href.indexOf('?') < 0 ? '?' : '&') + 'v=' + ver;
		});
		if (!links.length)
			document.head.appendChild(el('link', { rel: 'stylesheet', href: D.fontCss + ver }));
	}
	function post(body) {
		return fetch(D.actUrl, {
			method: 'POST', credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(body)
		}).then(function (r) {
			return r.text().then(function (t) {
				try { return JSON.parse(t); }
				catch (e) { return { ok: false, message: 'The server answered with an error page (HTTP ' + r.status + ').' }; }
			});
		}).catch(function () { return { ok: false, message: 'The server could not be reached.' }; });
	}
	function logLine(text, bad) {
		var log = $('cpaGfLog');
		log.hidden = false;
		var li = el('li', { 'class': bad ? 'cpa-bad' : '', text: text });
		log.appendChild(li);
		li.scrollIntoView({ block: 'nearest' });
		return li;
	}
	function installOne(name, kind) {
		var li = logLine(name + ': downloading…');
		return post({ action: 'installFont', name: name, kind: kind || '' }).then(function (r) {
			li.textContent = name + ': ' + r.message;
			if (!r.ok) { li.className = 'cpa-bad'; return false; }
			if (!state.fonts.some(function (f) { return f.name === r.font.name; }))
				state.fonts.push(r.font);
			state.fonts.sort(function (a, b) { return a.name.localeCompare(b.name); });
			reloadFontCss(r.cssVer);
			return true;
		});
	}
	$('cpaGfAdd').addEventListener('click', function () {
		var name = $('cpaGfName').value.trim();
		if (!name) { setStatus('Type a family name first.', true); return; }
		var btn = this;
		btn.disabled = true;
		installOne(name, $('cpaGfKind').value).then(function (ok) {
			btn.disabled = false;
			if (ok) { $('cpaGfName').value = ''; renderFontRoles(); renderInstalled(); }
		});
	});
	$('cpaGfRecommended').addEventListener('click', function () {
		var btn = this;
		var missing = D.recommended.filter(function (r) { return !state.fonts.some(function (f) { return f.name === r.name; }); });
		btn.disabled = true;
		var done = 0, failed = 0;
		logLine('Installing ' + missing.length + ' families, one at a time.');
		(function next(i) {
			if (i >= missing.length) {
				logLine('Finished: ' + done + ' installed, ' + failed + ' failed.', failed > 0);
				renderFontRoles(); renderInstalled();
				return;
			}
			installOne(missing[i].name, missing[i].kind).then(function (ok) {
				if (ok) done++; else failed++;
				next(i + 1);
			});
		})(0);
	});

	/* ---------- save / reset ---------- */
	function cleanForSave() {
		var out = {};
		Object.keys(state.scopes).forEach(function (id) {
			var s = state.scopes[id], c = { preset: s.preset, custom: { day: {}, night: {} }, fonts: s.fonts };
			['day', 'night'].forEach(function (m) {
				Object.keys(s.custom[m]).forEach(function (k) {
					if (k.indexOf(':manual') < 0) c.custom[m][k] = s.custom[m][k];
				});
			});
			if (id.indexOf('corpus:') === 0) { c.short = s.short || ''; c.card = s.card || ''; c.altWords = s.altWords || 'off'; }
			out[id] = c;
		});
		return out;
	}
	$('cpaSave').addEventListener('click', function () {
		var btn = this;
		btn.disabled = true;
		setStatus('Saving…');
		post({ action: 'save', scopes: cleanForSave() }).then(function (r) {
			btn.disabled = false;
			if (r.ok) { state.dirty = false; setStatus('Saved. Reload any open CQPweb page to see the changes.'); }
			else setStatus(r.message, true);
		});
	});
	$('cpaRevert').addEventListener('click', function () {
		var info = scopeInfo(state.current);
		var s = state.scopes[state.current];
		s.preset = info.defaultPreset;
		s.custom = { day: {}, night: {} };
		s.fonts = Object.assign({}, D.defaultFonts);
		if (info.defaultShort) s.short = info.defaultShort;
		markDirty();
		setStatus('Unsaved changes: ' + (s.short || info.label) + ' was reset to its defaults. Save to keep this.');
		renderAll();
	});

	/* ---------- Google Fonts picker: a searchable list, each name in its own font ---------- */
	(function gfPicker() {
		var input = $('cpaGfName'), list = $('cpaGfList'), cat = $('cpaGfCat'), kindSel = $('cpaGfKind');
		if (!input || !list)
			return;
		var families = null, shown = [], active = -1, loaded = {};
		var KIND = { sans: 'Sans serif', serif: 'Serif', mono: 'Monospace', display: 'Display', hand: 'Handwriting' };
		var observer = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) { loadPreview(e.target); observer.unobserve(e.target); }
			});
		}, { root: list, rootMargin: '120px' }) : null;

		function loadPreview(li) {
			var name = li.getAttribute('data-name'), w = li.getAttribute('data-w');
			if (!loaded[name]) {
				loaded[name] = true;
				var fam = encodeURIComponent(name).replace(/%20/g, '+') + (w === '400' ? '' : ':wght@' + w);
				document.head.appendChild(el('link', { rel: 'stylesheet',
					href: 'https://fonts.googleapis.com/css2?family=' + fam + '&text=' + encodeURIComponent(name) + '&display=swap' }));
			}
			li.querySelector('.cpa-gfname').style.fontFamily = '"' + name + '", ' + (li.getAttribute('data-kind') === 'serif' ? 'serif' : 'sans-serif');
		}
		function installed(name) {
			return state.fonts.some(function (f) { return f.name === name; });
		}
		function render() {
			var q = input.value.trim().toLowerCase(), c = cat.value;
			shown = families.filter(function (f) {
				return (!c || f[1] === c) && (!q || f[0].toLowerCase().indexOf(q) !== -1);
			});
			list.textContent = '';
			active = -1;
			if (!shown.length) {
				list.appendChild(el('li', { 'class': 'cpa-gfnone', text: 'No family matches.' }));
			}
			shown.forEach(function (f, i) {
				var have = installed(f[0]);
				var li = el('li', { role: 'option', id: 'cpaGfOpt' + i, 'data-name': f[0], 'data-kind': f[1], 'data-w': String(f[2]) }, [
					el('span', { 'class': 'cpa-gfname', text: f[0] }),
					el('span', { 'class': 'cpa-gfkind', text: KIND[f[1]] + (have ? ' · on this server' : '') })
				]);
				if (have) li.className = 'cpa-gfhave';
				li.addEventListener('mousedown', function (e) { e.preventDefault(); choose(i); });
				list.appendChild(li);
				if (observer) observer.observe(li); else if (i < 60) loadPreview(li);
			});
			open(true);
		}
		function open(on) {
			list.hidden = !on;
			input.setAttribute('aria-expanded', on ? 'true' : 'false');
		}
		function choose(i) {
			var f = shown[i];
			if (!f) return;
			input.value = f[0];
			if (kindSel) kindSel.value = ({ sans: 'sans', serif: 'serif', mono: 'mono' })[f[1]] || '';
			open(false);
		}
		function highlight(i) {
			var items = list.querySelectorAll('li[role="option"]');
			if (!items.length) return;
			active = Math.max(0, Math.min(items.length - 1, i));
			Array.prototype.forEach.call(items, function (li, k) { li.classList.toggle('cpa-gfactive', k === active); });
			items[active].scrollIntoView({ block: 'nearest' });
			input.setAttribute('aria-activedescendant', items[active].id);
		}
		function ensure(then) {
			if (families) { then(); return; }
			fetch(root.getAttribute('data-gf-list'), { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (d) { families = d; then(); })
				.catch(function () { setStatus('The list of Google Fonts families could not be loaded; type a name instead.', true); });
		}
		input.addEventListener('focus', function () { ensure(render); });
		input.addEventListener('input', function () { ensure(render); });
		cat.addEventListener('change', function () { ensure(render); input.focus(); });
		input.addEventListener('blur', function () { setTimeout(function () { open(false); }, 150); });
		input.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) ensure(render); else highlight(active + 1); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(active - 1); }
			else if (e.key === 'Enter' && !list.hidden && active >= 0) { e.preventDefault(); choose(active); }
			else if (e.key === 'Escape') { open(false); }
		});
	})();

	function renderAll() {
		renderScopes(); renderPresets(); renderTokens(); renderPairs(); renderFontRoles(); renderPreview(); renderInstalled();
	}
	renderAll();
})();
