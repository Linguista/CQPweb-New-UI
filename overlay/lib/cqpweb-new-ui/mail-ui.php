<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the admin page "E-mail".
 * VERSION 1.0.2
 * Printed inside the admin control panel (adm/index.php?ui=cpMail); saves and sends the
 * test message through adm/cqpweb-new-ui-act.php (actions "mailSave" and "mailTest").
 */

require_once(__DIR__ . '/mail-lib.php');
require_once(__DIR__ . '/brand-lib.php');

function cp_mail_admin_ui() : void
{
	global $Config, $User;
	$s = cp_mail_settings(true);
	$e = fn($v) => escape_html((string)$v);
	$cfg_from = (string)($Config->cqpweb_email_from_address ?? '');
	$from_now = $s['fromAddress'] ?: $cfg_from;
	$dns = '' !== $from_now ? cp_mail_dns_report($from_now) : [];
	$readable = is_file(CP_MAIL_FILE) ? (is_readable(CP_MAIL_FILE) ? '' : 'The settings file exists but the web server cannot read it.') : '';
	$perms = is_file(CP_MAIL_FILE) ? substr(sprintf('%o', fileperms(CP_MAIL_FILE)), -3) : '';
	/* the administrator's own address, unless it is the stand-in CQPweb's setup script gives superusers */
	$test_to = (!empty($User->email) && filter_var($User->email, FILTER_VALIDATE_EMAIL) && !preg_match('/@nowhere\.net$/i', $User->email)) ? $User->email : '';
	$v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.css') ?: 0;
	$sec = $s['security'];
	?>
	<link rel="stylesheet" href="../css/cqpweb-new-ui/appearance-admin.css?v=<?= $v ?>">
	<form id="cpMailForm" class="cpa" onsubmit="return false" autocomplete="off">
		<div class="cpa-intro">
			<h2 class="cpa-h1">E-mail</h2>
			<p>CQPweb sends e-mail to confirm new accounts, to reset passwords and to remind users of their username.
			Out of the box it hands every message to the server's own mail system, and on most servers today that mail
			never arrives: hosting companies block the port it needs, and receiving providers discard mail sent straight
			from a server that is not a recognized mail sender. Here, CQPweb is set to log in to a real outgoing mail
			server (an SMTP relay) and send through it. Nothing else needs to be installed on this server.</p>
			<p class="cpa-note">Status: <strong><?= cp_mail_smtp_on() ? 'SMTP sending is ON.' : 'SMTP sending is off: CQPweb uses PHP&rsquo;s mail(), as it does without this package.' ?></strong>
			<?= $readable ? '<br><strong>' . $e($readable) . '</strong>' : '' ?>
			<?= ('' !== $perms && '600' !== $perms) ? '<br>The settings file ' . $e(CP_MAIL_FILE) . ' has permissions ' . $e($perms) . '; it should be 600, so that only the web server can read the password. Saving again sets it.' : '' ?></p>
			<p class="cpa-note">New to this? Read <a href="#cpMailGuide">the step-by-step guide</a> below first.</p>
		</div>

		<section class="cpa-card" aria-labelledby="cpMailSrvH">
			<h3 id="cpMailSrvH" class="cpa-h2">Outgoing mail server (SMTP)</h3>
			<div class="cpa-row">
				<input type="checkbox" id="cpmEnabled" name="enabled" value="1"<?= $s['enabled'] ? ' checked' : '' ?>>
				<label for="cpmEnabled">Send CQPweb&rsquo;s e-mail through this server</label>
			</div>
			<div class="cpa-row">
				<label for="cpmHost">Server name</label>
				<input type="text" id="cpmHost" name="host" value="<?= $e($s['host']) ?>" placeholder="for example mail.smtp2go.com" maxlength="253">
			</div>
			<div class="cpa-row">
				<label for="cpmPort">Port</label>
				<input type="number" id="cpmPort" name="port" value="<?= (int)$s['port'] ?>" min="1" max="65535">
				<span class="cpa-note">587 with STARTTLS is the usual choice; 465 with TLS from the start is the other common one.</span>
			</div>
			<div class="cpa-row">
				<label for="cpmSec">Encryption</label>
				<select id="cpmSec" name="security">
					<option value="starttls"<?= 'starttls' == $sec ? ' selected' : '' ?>>STARTTLS (port 587)</option>
					<option value="tls"<?= 'tls' == $sec ? ' selected' : '' ?>>TLS from the start (port 465)</option>
					<option value="none"<?= 'none' == $sec ? ' selected' : '' ?>>None (only for a server on this machine or your own network)</option>
				</select>
			</div>
			<div class="cpa-row">
				<input type="checkbox" id="cpmAuth" name="auth" value="1"<?= $s['auth'] ? ' checked' : '' ?>>
				<label for="cpmAuth">The server needs a user name and password (almost always)</label>
			</div>
			<div class="cpa-row">
				<label for="cpmUser">User name</label>
				<input type="text" id="cpmUser" name="username" value="<?= $e($s['username']) ?>" maxlength="200" autocomplete="off">
			</div>
			<div class="cpa-row">
				<label for="cpmPass">Password</label>
				<input type="password" id="cpmPass" name="password" value="" maxlength="500" autocomplete="new-password"
					placeholder="<?= '' !== $s['password'] ? 'A password is saved; type here only to replace it' : 'Not set' ?>">
				<?php if ('' !== $s['password']) { ?>
				<input type="checkbox" id="cpmClear" name="clearPassword" value="1"><label for="cpmClear">Remove the saved password</label>
				<?php } ?>
			</div>
			<p class="cpa-note">The password is kept in <code><?= $e(CP_MAIL_FILE) ?></code>, which only the web server can read.
			It is never sent back to this page.</p>
		</section>

		<section class="cpa-card" aria-labelledby="cpMailFromH">
			<h3 id="cpMailFromH" class="cpa-h2">Sender</h3>
			<div class="cpa-row">
				<label for="cpmFrom">Sender address</label>
				<input type="email" id="cpmFrom" name="fromAddress" value="<?= $e($s['fromAddress']) ?>" maxlength="254"
					placeholder="<?= '' !== $cfg_from ? $e($cfg_from) . ' (from CQPweb&rsquo;s configuration)' : 'for example corpus@your-domain.org' ?>">
			</div>
			<p class="cpa-note">Empty means the address in CQPweb&rsquo;s configuration file (<code>$cqpweb_email_from_address</code>)<?= '' !== $cfg_from ? ', now ' . $e($cfg_from) : ', which is not set' ?>.
			It must be an address on a domain you have verified with your mail relay (step 3 of the guide).</p>
			<div class="cpa-row">
				<label for="cpmName">Sender name</label>
				<input type="text" id="cpmName" name="fromName" value="<?= $e($s['fromName']) ?>" maxlength="120" placeholder="<?= $e(cp_site_name()) ?>">
			</div>
			<div class="cpa-row">
				<label for="cpmReply">Replies go to</label>
				<input type="email" id="cpmReply" name="replyTo" value="<?= $e($s['replyTo']) ?>" maxlength="254" placeholder="the sender address">
			</div>
			<div class="cpa-row">
				<label for="cpmTimeout">Give up after (seconds)</label>
				<input type="number" id="cpmTimeout" name="timeout" value="<?= (int)$s['timeout'] ?>" min="5" max="120">
			</div>
		</section>

		<div class="cpa-savebar">
			<span role="status" id="cpMailStatus" class="cpa-status"></span>
			<button type="button" id="cpMailSave">Save</button>
		</div>

		<section class="cpa-card" aria-labelledby="cpMailTestH">
			<h3 id="cpMailTestH" class="cpa-h2">Send a test message</h3>
			<p class="cpa-note">Uses the settings as saved (save first). The conversation with the server is shown below, with the
			user name and password hidden, so that any problem can be seen.</p>
			<div class="cpa-row">
				<label for="cpmTestTo">Send to</label>
				<input type="email" id="cpmTestTo" value="<?= $e($test_to) ?>" maxlength="254" placeholder="an address where you can read mail">
				<button type="button" id="cpMailTest">Send a test message</button>
			</div>
			<pre id="cpMailLog" class="cp-mail-log" hidden></pre>
		</section>

		<?php if ($dns) { ?>
		<section class="cpa-card" aria-labelledby="cpMailDnsH">
			<h3 id="cpMailDnsH" class="cpa-h2">Your domain&rsquo;s mail records</h3>
			<p class="cpa-note">As this server sees them now, for the sender address <?= $e($from_now) ?>. Steps 3 and 4 of the guide say what they should contain.</p>
			<dl class="cp-mail-dns">
			<?php foreach ($dns as $label => $lines) { ?>
				<dt><?= $e($label) ?></dt>
				<?php foreach ($lines as $l) { ?><dd><code><?= $e($l) ?></code></dd><?php } ?>
			<?php } ?>
			</dl>
		</section>
		<?php } ?>

		<section class="cpa-card cp-mail-guide" id="cpMailGuide" aria-labelledby="cpMailGuideH">
			<h3 id="cpMailGuideH" class="cpa-h2">Step by step: making CQPweb&rsquo;s e-mail arrive</h3>

			<h4 class="cpa-h3">Why CQPweb&rsquo;s mail does not arrive without this</h4>
			<p>CQPweb calls PHP&rsquo;s <code>mail()</code>. On Linux, <code>mail()</code> cannot talk to a mail server; it only hands
			the message to a local program called <code>sendmail</code>. On a typical server, either nothing is there to take it,
			or a full mail server (Postfix, Exim) tries to deliver it directly to the recipient&rsquo;s provider on port 25.
			Many hosting companies block outgoing port 25 on new servers (Vultr, for example, does so by default), and the big
			mail providers reject or hide mail from server addresses that have no sending reputation and no proof that the domain
			allowed them to send. The result is mail that is &ldquo;sent&rdquo; but never seen. The cure is to send through an SMTP relay,
			a service whose job is delivering mail, logging in to it with a user name and password, exactly as a mail program on
			a desktop computer does.</p>

			<h4 class="cpa-h3">1. Choose the sender address</h4>
			<p>Use an address on a domain you control, for example <code>corpus@your-domain.org</code>. Do not use a Gmail,
			Outlook or Yahoo address as the sender: those providers publish rules (DMARC) telling everyone to reject mail sent
			in their name from other servers. The address does not need its own mailbox if you forward it (step 6).</p>

			<h4 class="cpa-h3">2. Get an account with an SMTP relay</h4>
			<p>Any relay works: commercial ones such as SMTP2GO, Brevo, Mailgun, Postmark or Amazon SES (most have a free
			allowance far above what a corpus site needs), or your university&rsquo;s or company&rsquo;s own outgoing server, if
			its IT department allows it. Each gives you a server name, a port, and a user name and password for SMTP.</p>

			<h4 class="cpa-h3">3. Verify your domain with the relay</h4>
			<p>The relay asks you to add a few DNS records to your domain, at the company where you registered it (or wherever
			its DNS is managed). They prove that the domain allows the relay to send for it, and they sign each message (DKIM).
			Copy them exactly as the relay shows them. For example, SMTP2GO asks for three CNAME records on its
			&ldquo;Verified Senders&rdquo; page: one pointing at <code>return.smtp2go.net</code> (bounces and SPF), one at
			<code>dkim.smtp2go.net</code> (the DKIM signature) and one at <code>track.smtp2go.net</code> (link tracking);
			with SMTP2GO your SPF record does not need to change. Other relays may instead ask you to add something like
			<code>include:relay.example</code> to your SPF record: a domain may have only one record starting with
			<code>v=spf1</code>, so add the include to the existing one rather than creating a second. DNS changes can take
			from minutes to a few hours to be seen everywhere; then press the relay&rsquo;s Verify button.</p>

			<h4 class="cpa-h3">4. Add a DMARC record</h4>
			<p>Large providers increasingly expect one. Add a TXT record named <code>_dmarc</code> on your domain with, to begin
			with, <code>v=DMARC1; p=none; rua=mailto:you@your-domain.org</code>. <code>p=none</code> only asks for reports and
			never causes mail to be rejected; once the reports show your mail passing, it can be raised to
			<code>p=quarantine</code>.</p>

			<h4 class="cpa-h3">5. Fill in this page</h4>
			<p>Server name, port and encryption as the relay gives them (port 587 with STARTTLS, or port 465 with TLS from the
			start), the SMTP user name and password, and the sender address from step 1. Tick &ldquo;Send CQPweb&rsquo;s e-mail
			through this server&rdquo; and save. If the hosting company also blocks 587 and 465, ask the relay for another port:
			many offer 2525.</p>

			<h4 class="cpa-h3">6. Make sure replies reach you</h4>
			<p>Users will sometimes reply to these messages. If the sender address has no mailbox, set up forwarding for it
			at your domain registrar or DNS provider (often called &ldquo;e-mail forwarding&rdquo;), or put an address you read in
			&ldquo;Replies go to&rdquo;.</p>

			<h4 class="cpa-h3">7. Test</h4>
			<p>Send a test message from the box above to an address at a large provider (Gmail, for example). If it arrives,
			open it and look at its full headers (&ldquo;Show original&rdquo; in Gmail): <code>spf=pass</code>,
			<code>dkim=pass</code> and <code>dmarc=pass</code> mean everything is in place. Then create a test account on
			your site, to see the real confirmation message arrive.</p>

			<h4 class="cpa-h3">If it does not work</h4>
			<table class="cp-mail-trouble">
				<tr><th>What the test shows</th><th>What it usually means</th></tr>
				<tr><td>Could not connect</td><td>Wrong server name or port, or the hosting company blocks that port. Try the relay&rsquo;s other ports.</td></tr>
				<tr><td>The server does not offer STARTTLS on this port</td><td>The port expects TLS from the start (465): change the encryption setting.</td></tr>
				<tr><td>Log-in failed</td><td>Wrong SMTP user name or password. Relays usually have SMTP users separate from the log-in to their website.</td></tr>
				<tr><td>The server refused &ldquo;MAIL FROM&rdquo;</td><td>The sender address is not on a domain or address verified with the relay (step 3).</td></tr>
				<tr><td>Sent, but it lands in spam or never arrives</td><td>The DNS records of steps 3 and 4 are missing or not yet visible; the relay&rsquo;s activity log shows whether the receiving provider accepted it.</td></tr>
				<tr><td>The encrypted connection could not be set up</td><td>The server name does not match the relay&rsquo;s certificate: use exactly the name the relay gives.</td></tr>
			</table>
			<p class="cpa-note">This package changes nothing in the server&rsquo;s own mail system: any mail software already
			installed keeps working for other programs, and CQPweb goes back to it as soon as SMTP sending is switched off here.</p>
		</section>
	</form>
	<script>
	(function () {
		var f = document.getElementById('cpMailForm'), st = document.getElementById('cpMailStatus');
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
		function post(body) {
			return fetch('cqpweb-new-ui-act.php', { method: 'POST', credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' }, body: seal(body) })
				.then(function (r) { return r.json(); });
		}
		document.getElementById('cpMailSave').addEventListener('click', function () {
			var btn = this;
			btn.disabled = true;
			st.textContent = 'Saving…';
			st.className = 'cpa-status';
			post({ action: 'mailSave', enabled: f.enabled.checked, host: f.host.value, port: f.port.value,
				security: f.security.value, auth: f.auth.checked, username: f.username.value,
				password: f.password.value, clearPassword: f.clearPassword ? f.clearPassword.checked : false,
				fromAddress: f.fromAddress.value, fromName: f.fromName.value, replyTo: f.replyTo.value, timeout: f.timeout.value })
			.then(function (r) {
				st.textContent = r.message;
				st.className = 'cpa-status' + (r.ok ? '' : ' cpa-bad');
				if (r.ok) { f.password.value = ''; if (r.hasPassword) f.password.placeholder = 'A password is saved; type here only to replace it'; }
			})
			.catch(function () { st.textContent = 'The server could not be reached.'; st.className = 'cpa-status cpa-bad'; })
			.then(function () { btn.disabled = false; });
		});
		document.getElementById('cpMailTest').addEventListener('click', function () {
			var btn = this, out = document.getElementById('cpMailLog');
			btn.disabled = true;
			out.hidden = false;
			out.textContent = 'Sending…';
			post({ action: 'mailTest', to: document.getElementById('cpmTestTo').value })
			.then(function (r) { out.textContent = r.message + '\n\n' + (r.log || []).join('\n'); })
			.catch(function () { out.textContent = 'The server could not be reached.'; })
			.then(function () { btn.disabled = false; });
		});
	})();
	</script>
	<?php
}
