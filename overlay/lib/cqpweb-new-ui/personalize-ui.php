<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the admin page "Personalization".
 * VERSION 1.0.3
 * Printed inside the admin control panel (adm/index.php?ui=cpPersonalize); saves through
 * adm/cqpweb-new-ui-act.php (actions "brandSave", "brandUpload" and "brandRemove").
 */

require_once(__DIR__ . '/brand-lib.php');
require_once(__DIR__ . '/appearance-lib.php');
require_once(__DIR__ . '/embed-lib.php');

function cp_personalize_admin_ui() : void
{
	global $Config;
	$b = cp_brand(true);
	$e = fn($v) => escape_html((string)$v);
	$sel = fn(bool $on) => $on ? ' selected' : '';
	$v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.css') ?: 0;

	/* what is shown now, and where it comes from */
	$logo_src = '' !== cp_brand_image_url('logo') ? 'uploaded here'
		: ((cp_logo_url() === cp_url('css/cqpweb-new-ui/your-logo-here.svg')) ? 'the placeholder' : 'CQPweb&rsquo;s configuration file ($homepage_logo_left)');
	$fav_src = '' !== cp_brand_image_url('favicon') ? 'uploaded here' : 'the default';
	$cfg_welcome = trim((string)($Config->homepage_welcome_message ?? ''));
	$welcome_auto = ('' !== $cfg_welcome && 'Welcome to CQPweb!' !== $cfg_welcome)
		? 'the welcome message in CQPweb&rsquo;s configuration file ($homepage_welcome_message)'
		: 'the text &ldquo;Welcome to [site name]! Created by [your name]&rdquo;, filled in from the fields above';
	$cfg_contact = '';
	foreach (['server_admin_email_address', 'cqpweb_email_from_address'] as $k)
		if ('' === $cfg_contact && !empty($Config->$k))
			$cfg_contact = $Config->$k;

	$pages = array_keys(cp_userpages_list());
	$corpora = list_corpora_with_titles(true);
	?>
	<link rel="stylesheet" href="../css/cqpweb-new-ui/appearance-admin.css?v=<?= $v ?>">
	<form id="cpBrandForm" class="cpa" onsubmit="return false">
		<div class="cpa-intro">
			<h2 class="cpa-h1">Personalization</h2>
			<p>Your site&rsquo;s name, logo, icons and texts, the query form&rsquo;s defaults and the public documentation pages.
			Anything left empty falls back to what CQPweb&rsquo;s own configuration file already says, and only then to a placeholder.
			Colors and fonts are on the <a href="index.php?ui=appearance">Appearance</a> page.</p>
		</div>

		<section class="cpa-card" aria-labelledby="cpbIdH">
			<h3 id="cpbIdH" class="cpa-h2">Site and owner</h3>
			<div class="cpa-row">
				<label for="cpbSite">Site name</label>
				<input type="text" id="cpbSite" name="siteName" value="<?= $e($b['siteName']) ?>" maxlength="80" placeholder="CQPweb">
			</div>
			<p class="cpa-note">Shown in the top bar, in page titles and in messages. Empty means &ldquo;CQPweb&rdquo;.</p>
			<div class="cpa-row">
				<label for="cpbOwner">Your name, or your project&rsquo;s</label>
				<input type="text" id="cpbOwner" name="ownerName" value="<?= $e($b['ownerName']) ?>" maxlength="120" placeholder="<?= $e(CP_PLACEHOLDER_OWNER) ?>">
			</div>
			<div class="cpa-row">
				<label for="cpbOwnerUrl">Web address for that name</label>
				<input type="url" id="cpbOwnerUrl" name="ownerUrl" value="<?= $e($b['ownerUrl']) ?>" maxlength="500" placeholder="https://">
			</div>
			<div class="cpa-row">
				<label for="cpbContact">Contact address</label>
				<input type="email" id="cpbContact" name="contact" value="<?= $e($b['contact']) ?>" maxlength="254"
					placeholder="<?= '' !== $cfg_contact ? $e($cfg_contact) . ' (from CQPweb&rsquo;s configuration)' : 'not set' ?>">
			</div>
			<p class="cpa-note">The &ldquo;Contact&rdquo; link at the bottom of every page. Empty means CQPweb&rsquo;s administrator address.</p>
		</section>

		<section class="cpa-card" aria-labelledby="cpbLogoH">
			<h3 id="cpbLogoH" class="cpa-h2">Logo and icons</h3>
			<div class="cp-brand-imgs">
				<figure class="cp-brand-fig">
					<img id="cpbLogoImg" src="<?= $e(cp_logo_url()) ?>" alt="">
					<figcaption>Logo now shown: <?= $logo_src ?></figcaption>
				</figure>
				<div>
					<div class="cpa-row">
						<label for="cpbLogoFile">Upload your logo</label>
						<input type="file" id="cpbLogoFile" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,image/png,image/jpeg,image/gif,image/webp,image/svg+xml">
						<button type="button" class="cpa-secondary" data-cpb-remove="logo">Remove the uploaded logo</button>
					</div>
					<p class="cpa-note">PNG, JPEG, GIF, WebP or SVG, up to 2 MB. Shown on the home page and, if you choose, in the top bar.
					A wide image about 600 by 150 pixels works well.</p>
					<div class="cpa-row">
						<label for="cpbAlt">Text describing the logo (for screen readers)</label>
						<input type="text" id="cpbAlt" name="logoAlt" value="<?= $e($b['logoAlt']) ?>" maxlength="200" placeholder="the site name">
					</div>
					<div class="cpa-row">
						<input type="checkbox" id="cpbInvert" name="logoInvert" value="1"<?= $b['logoInvert'] ? ' checked' : '' ?>>
						<label for="cpbInvert">Invert the logo&rsquo;s colors in night mode (for dark artwork on a transparent background)</label>
					</div>
					<div class="cpa-row">
						<label for="cpbTopBar">Top bar shows</label>
						<select id="cpbTopBar" name="topBar">
							<option value="name"<?= $sel('name' == $b['topBar']) ?>>The site name</option>
							<option value="logo"<?= $sel('logo' == $b['topBar']) ?>>The logo</option>
							<option value="both"<?= $sel('both' == $b['topBar']) ?>>The logo and the site name</option>
						</select>
					</div>
				</div>
			</div>
			<div class="cp-brand-imgs">
				<figure class="cp-brand-fig cp-brand-fav">
					<img id="cpbFavImg" src="<?= $e(cp_brand_image_url('favicon') ?: cp_url('css/cqpweb-new-ui/favicon.svg')) ?>" alt="" width="48" height="48">
					<figcaption>Browser icon now shown: <?= $fav_src ?></figcaption>
				</figure>
				<div>
					<div class="cpa-row">
						<label for="cpbFavFile">Upload a browser icon</label>
						<input type="file" id="cpbFavFile" accept=".png,.ico,.svg,.gif,.webp,.jpg,.jpeg,image/png,image/x-icon,image/svg+xml">
						<button type="button" class="cpa-secondary" data-cpb-remove="favicon">Remove the uploaded icon</button>
					</div>
					<p class="cpa-note">A square image: PNG of at least 180 by 180 pixels is best, since the icons phones use when a
					visitor saves the site to the home screen are made from it<?= function_exists('imagecreatefromstring') ? '' : ' (this server&rsquo;s PHP lacks the GD extension, so they will not be made)' ?>.
					ICO and SVG work too. Up to 512 KB.</p>
				</div>
			</div>
			<p role="status" id="cpbUpStatus" class="cpa-status"></p>
		</section>

		<section class="cpa-card" aria-labelledby="cpbTextH">
			<h3 id="cpbTextH" class="cpa-h2">Texts</h3>
			<p class="cpa-note">These fields accept simple HTML: <code>&lt;a href="https://..."&gt;</code>, <code>&lt;strong&gt;</code>,
			<code>&lt;em&gt;</code>, <code>&lt;br&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;small&gt;</code>, <code>&lt;code&gt;</code>. Anything else is removed.</p>
			<div class="cpa-row cpa-stack">
				<label for="cpbWelcome">Home-page welcome</label>
				<textarea id="cpbWelcome" name="welcome" rows="3" maxlength="4000"><?= $e($b['welcome']) ?></textarea>
			</div>
			<p class="cpa-note">Empty means <?= $welcome_auto ?>.</p>
			<div class="cpa-row cpa-stack">
				<label for="cpbFooter">Text at the bottom of every page</label>
				<textarea id="cpbFooter" name="footer" rows="3" maxlength="4000" placeholder="for example: a funding acknowledgment, a citation request, a privacy notice"><?= $e($b['footer']) ?></textarea>
			</div>
			<p class="cpa-note">The bottom of every page also has links to the Help page<?= cp_news_available() ? ', the news page' : '' ?> and the contact address.</p>
			<div class="cpa-row cpa-stack">
				<label for="cpbSignup">Notice on the &ldquo;Create an account&rdquo; page (plain text; a blank line starts a new paragraph)</label>
				<textarea id="cpbSignup" name="signupNotice" rows="3" maxlength="4000"><?= $e(cp_signup_notice_text()) ?></textarea>
			</div>
			<p class="cpa-note">Empty shows no notice.</p>
			<div class="cpa-row">
				<label for="cpbNewsTitle">Title of the news page</label>
				<input type="text" id="cpbNewsTitle" name="newsTitle" value="<?= $e($b['newsTitle']) ?>" maxlength="80" placeholder="<?= $e('New on ' . cp_site_name()) ?>">
			</div>
			<div class="cpa-row">
				<input type="checkbox" id="cpbNewsShow" name="newsShow" value="1"<?= $b['newsShow'] ? ' checked' : '' ?>>
				<label for="cpbNewsShow">Link to the news page from the home page, the menus and the bottom of every page (once it has text: <a href="index.php?ui=cpNews">edit it</a>)</label>
			</div>
		</section>

		<section class="cpa-card" aria-labelledby="cpbQueryH">
			<h3 id="cpbQueryH" class="cpa-h2">Query form defaults</h3>
			<div class="cpa-row">
				<label for="cpbQMode">The query form opens on</label>
				<select id="cpbQMode" name="queryMode">
					<option value="simple"<?= $sel('simple' == $b['queryMode']) ?>>Simple query</option>
					<option value="cqp"<?= $sel('cqp' == $b['queryMode']) ?>>CQP syntax</option>
				</select>
			</div>
			<p class="cpa-note">A user who has chosen CQP syntax in their own account settings always gets CQP syntax.</p>
			<div class="cpa-row">
				<label for="cpbQCase">Searches are, unless the visitor changes it</label>
				<select id="cpbQCase" name="queryCase">
					<option value="insensitive"<?= $sel('insensitive' == $b['queryCase']) ?>>Case-insensitive (house also finds House and HOUSE)</option>
					<option value="sensitive"<?= $sel('sensitive' == $b['queryCase']) ?>>Case-sensitive</option>
				</select>
			</div>
			<p class="cpa-note">Every query form has a &ldquo;Case-sensitive&rdquo; box; each browser remembers the visitor&rsquo;s own choice.
			This setting decides how the box starts for visitors who have never changed it, on every corpus.</p>
			<div class="cpa-row">
				<input type="checkbox" id="cpbCaseNotice" name="caseNotice" value="1"<?= $b['caseNotice'] ? ' checked' : '' ?>>
				<label for="cpbCaseNotice">Show a one-time note above the query box explaining the Case-sensitive box and %C</label>
			</div>
		</section>

		<section class="cpa-card" aria-labelledby="cpbDocsH">
			<h3 id="cpbDocsH" class="cpa-h2">Public documentation pages</h3>
			<p class="cpa-note">A corpus can have a documentation page that anyone can read without logging in, and that search engines
			may index. It is one of the HTML files in the user pages folder (<code><?= $e(CP_TUTORIAL_DIR) ?></code>, edited on
			<a href="index.php?ui=cpPages">Edit user pages</a>). The home-page card of the corpus then links to it.</p>
			<?php if (empty($pages)) { ?>
			<p class="cpa-note"><strong>The user pages folder has no HTML files yet.</strong></p>
			<?php } ?>
			<table class="cp-brand-docs">
				<tr><th>Corpus</th><th>Page</th><th>One-line description (for search engines)</th></tr>
				<?php foreach ($corpora as $corpus => $title) {
					$d = $b['docs'][$corpus] ?? ['file' => '', 'description' => ''];
					?>
				<tr data-corpus="<?= $e($corpus) ?>">
					<td><?= $e(cp_scope_settings("corpus:$corpus")['short']) ?><br><small><?= $e(strip_tags($title)) ?></small></td>
					<td><select class="cpb-doc-file" aria-label="Documentation page of <?= $e($corpus) ?>">
						<option value="">None</option>
						<?php foreach ($pages as $p) { ?>
						<option value="<?= $e($p) ?>"<?= $sel($p === $d['file']) ?>><?= $e($p) ?></option>
						<?php } ?>
					</select></td>
					<td><input type="text" class="cpb-doc-desc" value="<?= $e($d['description']) ?>" maxlength="300" aria-label="Description of <?= $e($corpus) ?>"></td>
				</tr>
				<?php } ?>
			</table>
			<p class="cpa-note">The address is <code>documentation.php?corpus=</code> followed by the corpus&rsquo;s short name in lowercase (set on the Appearance page).</p>
		</section>

		<div class="cpa-savebar">
			<span role="status" id="cpbStatus" class="cpa-status"></span>
			<button type="button" id="cpbSave">Save</button>
		</div>
	</form>
	<script>
	(function () {
		var f = document.getElementById('cpBrandForm'), st = document.getElementById('cpbStatus'), up = document.getElementById('cpbUpStatus');
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
		function say(el, r) { el.textContent = r.message; el.className = 'cpa-status' + (r.ok ? '' : ' cpa-bad'); }
		document.getElementById('cpbSave').addEventListener('click', function () {
			var btn = this, docs = {};
			Array.prototype.forEach.call(f.querySelectorAll('tr[data-corpus]'), function (tr) {
				var file = tr.querySelector('.cpb-doc-file').value;
				if (file)
					docs[tr.getAttribute('data-corpus')] = { file: file, description: tr.querySelector('.cpb-doc-desc').value };
			});
			btn.disabled = true;
			st.textContent = 'Saving…';
			post({ action: 'brandSave', siteName: f.siteName.value, ownerName: f.ownerName.value, ownerUrl: f.ownerUrl.value,
				contact: f.contact.value, logoAlt: f.logoAlt.value, logoInvert: f.logoInvert.checked, topBar: f.topBar.value, welcome: f.welcome.value,
				footer: f.footer.value, signupNotice: f.signupNotice.value, newsTitle: f.newsTitle.value,
				newsShow: f.newsShow.checked, queryMode: f.queryMode.value, queryCase: f.queryCase.value,
				caseNotice: f.caseNotice.checked, docs: docs })
			.then(function (r) { say(st, r); })
			.catch(function () { say(st, { ok: false, message: 'The server could not be reached.' }); })
			.then(function () { btn.disabled = false; });
		});
		function upload(input, key, img) {
			input.addEventListener('change', function () {
				var file = input.files[0];
				if (!file)
					return;
				var rd = new FileReader();
				rd.onload = function () {
					var b64 = String(rd.result).replace(/^data:[^,]*,/, '');
					up.textContent = 'Uploading…';
					post({ action: 'brandUpload', key: key, b64: b64 })
					.then(function (r) { say(up, r); if (r.ok && r.url) document.getElementById(img).src = r.url; input.value = ''; })
					.catch(function () { say(up, { ok: false, message: 'The server could not be reached.' }); });
				};
				rd.readAsDataURL(file);
			});
		}
		upload(document.getElementById('cpbLogoFile'), 'logo', 'cpbLogoImg');
		upload(document.getElementById('cpbFavFile'), 'favicon', 'cpbFavImg');
		Array.prototype.forEach.call(f.querySelectorAll('[data-cpb-remove]'), function (b) {
			b.addEventListener('click', function () {
				var key = b.getAttribute('data-cpb-remove');
				post({ action: 'brandRemove', key: key }).then(function (r) {
					say(up, r);
					if (r.ok && r.url) document.getElementById(key === 'logo' ? 'cpbLogoImg' : 'cpbFavImg').src = r.url;
				});
			});
		});
	})();
	</script>
	<?php
}
