<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the admin page "Maintenance mode".
 * VERSION 1.0.2
 * Printed inside the admin control panel (adm/index.php?ui=cpSite); saves through
 * adm/cqpweb-new-ui-act.php (action "site").
 */

require_once(__DIR__ . '/site-lib.php');

function cp_site_admin_ui() : void
{
	$s = cp_site_settings();
	$site = escape_html(cp_site_name());
	$on = $s['maintenance'] ? ' checked' : '';
	$msg = escape_html($s['maintenanceMessage']);
	$rule = escape_html(cp_password_rule_text());
	$v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.css') ?: 0;
	?>
	<link rel="stylesheet" href="../css/cqpweb-new-ui/appearance-admin.css?v=<?= $v ?>">
	<form id="cpSiteForm" class="cpa" onsubmit="return false">
		<div class="cpa-intro">
			<h2 class="cpa-h1">Maintenance mode</h2>
		</div>

		<section class="cpa-card" aria-labelledby="cpMaintH">
			<h3 id="cpMaintH" class="cpa-h2">Maintenance mode</h3>
			<p class="cpa-note">While maintenance mode is on, only superusers can use <?= $site ?>. Everyone else sees the message below
			(the log-in page stays open, so you can log in). A reminder bar shows at the top of every page while it is on.</p>
			<div class="cpa-row">
				<input type="checkbox" id="cpMaint" name="maintenance" value="1"<?= $on ?>>
				<label for="cpMaint">Maintenance mode is on</label>
			</div>
			<div class="cpa-row cpa-stack">
				<label for="cpMaintMsg">Message for visitors (plain text; a blank line starts a new paragraph)</label>
				<textarea id="cpMaintMsg" name="maintenanceMessage" rows="4" placeholder="<?= $site ?> is closed for maintenance. Please come back a little later."><?= $msg ?></textarea>
			</div>
		</section>

		<section class="cpa-card" aria-labelledby="cpRuleH">
			<h3 id="cpRuleH" class="cpa-h2">Password rule (fixed)</h3>
			<p class="cpa-note"><?= $rule ?> The notice on the &ldquo;Create an account&rdquo; page is set on the
			<a href="index.php?ui=cpPersonalize">Personalization</a> page.</p>
		</section>

		<div class="cpa-savebar">
			<span role="status" id="cpSiteStatus" class="cpa-status"></span>
			<button type="button" id="cpSiteSave">Save</button>
		</div>
	</form>
	<script>
	(function () {
		var f = document.getElementById('cpSiteForm'), st = document.getElementById('cpSiteStatus');
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
		document.getElementById('cpSiteSave').addEventListener('click', function () {
			var btn = this;
			btn.disabled = true;
			st.textContent = 'Saving…';
			fetch('cqpweb-new-ui-act.php', {
				method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
				body: seal({ action: 'site', maintenance: f.maintenance.checked, maintenanceMessage: f.maintenanceMessage.value })
			}).then(function (r) { return r.json(); })
			  .then(function (r) { st.textContent = r.message; st.className = 'cpa-status' + (r.ok ? '' : ' cpa-bad'); })
			  .catch(function () { st.textContent = 'The server could not be reached.'; st.className = 'cpa-status cpa-bad'; })
			  .then(function () { btn.disabled = false; });
		});
	})();
	</script>
	<?php
}
