<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the Help page.
 * VERSION 1.0.2
 *
 *  - cp_help_page(): the public page help-center.php, linked from the bottom of every page;
 *  - cp_help_admin_card(): the "Help page" section of the admin Appearance page, where each
 *    link can be shown or hidden, changed, and new title / description / URL entries added.
 *    Saves through adm/cqpweb-new-ui-act.php (action "helpSave").
 */

require_once(__DIR__ . '/brand-lib.php');

/** A link from the Help settings, made usable from the current page. */
function cp_help_href(string $url) : string
{
	return preg_match('~^(https?://|mailto:|/)~i', $url) ? $url : cp_url($url);
}

/** The whole public Help page (called from help-center.php). */
function cp_help_page() : void
{
	global $Config;
	$h = cp_help_settings();
	echo print_html_header('Help · ' . escape_html(cp_site_name()), $Config->colour_scheme);
	echo '<main class="cp-home cp-help">', "\n";
	echo '<section class="cp-home-box"><h1 class="cp-home-h2">Help</h1>';
	if ('' !== trim(strip_tags($h['intro'])))
		echo '<div class="cp-help-intro">', $h['intro'], '</div>';
	else
		echo '<p>Guides, tutorials and manuals for searching the corpora on ', escape_html(cp_site_name()),
			' with CQPweb, and for running a CQPweb server.</p>';
	echo "</section>\n";
	$links = cp_help_links();
	if (empty($links))
		echo '<section class="cp-home-box"><p>No help links have been set up.</p></section>';
	else
	{
		echo '<ul class="cp-help-list">', "\n";
		foreach ($links as $l)
		{
			$href = escape_html(cp_help_href($l['url']));
			$keep = preg_match('~^http://~i', $l['url']) ? ' data-cp-keep-http="1"' : '';
			$ext = preg_match('~^https?://~i', $l['url']) ? ' rel="noopener"' : '';
			echo "\t<li class=\"cp-help-item\"><a class=\"cp-help-title\" href=\"$href\"$keep$ext>", escape_html($l['title']), '</a>';
			if ('' !== $l['description'])
				echo '<p class="cp-help-desc">', escape_html($l['description']), '</p>';
			echo "</li>\n";
		}
		echo "</ul>\n";
	}
	echo "</main>\n";
	echo print_html_footer(false);
}

/** The "Help page" section of the Appearance admin page. */
function cp_help_admin_card() : string
{
	$h = cp_help_settings(true);
	$e = fn($v) => escape_html((string)$v);
	$rows = '';
	foreach ($h['builtin'] as $id => $s)
	{
		$def = CP_HELP_BUILTIN[$id];
		$rows .= '<tr class="cph-row" data-builtin="' . $e($id) . '">'
			. '<td><input type="checkbox" class="cph-show" aria-label="Show this link"' . ($s['show'] ? ' checked' : '') . '></td>'
			. '<td><input type="text" class="cph-title" maxlength="200" value="' . $e($s['title']) . '" placeholder="' . $e($def['title']) . '" aria-label="Title"></td>'
			. '<td><input type="text" class="cph-desc" maxlength="600" value="' . $e($s['description']) . '" placeholder="' . $e($def['description']) . '" aria-label="Description"></td>'
			. '<td><input type="text" class="cph-url" maxlength="500" value="' . $e($s['url']) . '" placeholder="' . $e($def['url']) . '" aria-label="URL"></td>'
			. '<td><button type="button" class="cpa-secondary cph-reset" data-title="' . $e($def['title']) . '" data-desc="' . $e($def['description']) . '" data-url="' . $e($def['url']) . '">Default</button></td>'
			. "</tr>\n";
	}
	foreach ($h['custom'] as $c)
		$rows .= cp_help_custom_row($c);
	$intro = $e($h['intro']);
	$blank = htmlspecialchars(cp_help_custom_row(['show' => true, 'title' => '', 'description' => '', 'url' => '']), ENT_QUOTES);
	return <<<END_HTML

		<section class="cpa-card" id="cpHelpCard" aria-labelledby="cpHelpH" data-blank-row="$blank">
			<h3 id="cpHelpH" class="cpa-h2">Help page</h3>
			<p class="cpa-note">The public <a href="../help-center.php" target="_blank">Help page</a>, linked from the bottom of every page.
			Untick a link to hide it; change its title, description or address, or press Default to go back to the original.
			Addresses can be full web addresses (https://...), <code>mailto:</code> addresses, or paths on this site
			(for example <code>doc/CQPwebAdminManual.pdf</code>). The two tutorials are listed only while their files are in the user pages folder.</p>
			<div class="cpa-row cpa-stack">
				<label for="cphIntro">Text at the top of the Help page (simple HTML; empty for the standard sentence)</label>
				<textarea id="cphIntro" rows="2" maxlength="2000">$intro</textarea>
			</div>
			<table class="cp-help-admin">
				<thead><tr><th>Show</th><th>Title</th><th>Description</th><th>URL</th><th></th></tr></thead>
				<tbody id="cphRows">
$rows				</tbody>
			</table>
			<div class="cpa-row">
				<button type="button" class="cpa-secondary" id="cphAdd">Add a link</button>
				<span role="status" id="cphStatus" class="cpa-status"></span>
				<button type="button" id="cphSave">Save the Help page</button>
			</div>
		</section>
		<script>
		(function () {
			var card = document.getElementById('cpHelpCard'), body = document.getElementById('cphRows'), st = document.getElementById('cphStatus');
			/* the request travels base64-encoded, so a web application firewall (such as ModSecurity
			 * with the OWASP rules) does not mistake links, tags or a password in it for an attack */
			function seal(body) {
				var a = body.action, c = {};
				Object.keys(body).forEach(function (k) { if (k !== 'action') c[k] = body[k]; });
				var bytes = new TextEncoder().encode(JSON.stringify(c)), bin = '';
				for (var i = 0; i < bytes.length; i += 0x8000)
					bin += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
				return JSON.stringify({ action: a, payload64: btoa(bin) });
			}
			document.getElementById('cphAdd').addEventListener('click', function () {
				body.insertAdjacentHTML('beforeend', card.getAttribute('data-blank-row'));
				body.lastElementChild.querySelector('.cph-title').focus();
			});
			body.addEventListener('click', function (ev) {
				var b = ev.target.closest('button');
				if (!b) return;
				var tr = b.closest('tr');
				if (b.classList.contains('cph-reset')) {
					tr.querySelector('.cph-title').value = b.getAttribute('data-title');
					tr.querySelector('.cph-desc').value = b.getAttribute('data-desc');
					tr.querySelector('.cph-url').value = b.getAttribute('data-url');
				} else if (b.classList.contains('cph-del')) {
					tr.remove();
				} else if (b.classList.contains('cph-up') && tr.previousElementSibling && !tr.previousElementSibling.hasAttribute('data-builtin')) {
					tr.parentNode.insertBefore(tr, tr.previousElementSibling);
				}
			});
			document.getElementById('cphSave').addEventListener('click', function () {
				var btn = this, builtin = {}, custom = [];
				Array.prototype.forEach.call(body.querySelectorAll('tr.cph-row'), function (tr) {
					var row = { show: tr.querySelector('.cph-show').checked, title: tr.querySelector('.cph-title').value,
						description: tr.querySelector('.cph-desc').value, url: tr.querySelector('.cph-url').value };
					if (tr.hasAttribute('data-builtin')) builtin[tr.getAttribute('data-builtin')] = row; else custom.push(row);
				});
				btn.disabled = true;
				st.textContent = 'Saving…';
				fetch('cqpweb-new-ui-act.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
					body: seal({ action: 'helpSave', intro: document.getElementById('cphIntro').value, builtin: builtin, custom: custom }) })
				.then(function (r) { return r.json(); })
				.then(function (r) { st.textContent = r.message; st.className = 'cpa-status' + (r.ok ? '' : ' cpa-bad'); })
				.catch(function () { st.textContent = 'The server could not be reached.'; st.className = 'cpa-status cpa-bad'; })
				.then(function () { btn.disabled = false; });
			});
		})();
		</script>

END_HTML;
}

function cp_help_custom_row(array $c) : string
{
	$e = fn($v) => escape_html((string)$v);
	return '<tr class="cph-row">'
		. '<td><input type="checkbox" class="cph-show" aria-label="Show this link"' . ($c['show'] ? ' checked' : '') . '></td>'
		. '<td><input type="text" class="cph-title" maxlength="200" value="' . $e($c['title']) . '" placeholder="Title" aria-label="Title"></td>'
		. '<td><input type="text" class="cph-desc" maxlength="600" value="' . $e($c['description']) . '" placeholder="Description" aria-label="Description"></td>'
		. '<td><input type="text" class="cph-url" maxlength="500" value="' . $e($c['url']) . '" placeholder="https://" aria-label="URL"></td>'
		. '<td class="nowrap"><button type="button" class="cpa-secondary cph-up" aria-label="Move up">Up</button> '
		. '<button type="button" class="cpa-secondary cph-del">Remove</button></td>'
		. "</tr>\n";
}
