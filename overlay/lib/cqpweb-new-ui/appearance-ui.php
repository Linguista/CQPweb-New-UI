<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the admin Appearance page.
 * VERSION 1.0.2
 *
 * Printed inside the admin control panel (adm/index.php?ui=appearance).
 * All editing happens in the browser (css/cqpweb-new-ui/appearance-admin.js);
 * the page saves through adm/cqpweb-new-ui-act.php.
 */

require_once(__DIR__ . '/appearance-lib.php');
require_once(__DIR__ . '/help-ui.php');

function cp_appearance_admin_ui() : void
{
	/* make sure the font stylesheet exists, so the menus can show every font */
	if (!is_file(CP_FONT_ROOT . '/' . CP_FONT_CSS_NAME))
		cp_rebuild_font_css();

	$scopes = [
		['id' => 'home',  'label' => 'Home page',                   'group' => 'Site'],
		['id' => 'user',  'label' => 'User pages and tutorials',    'group' => 'Site'],
		['id' => 'admin', 'label' => 'Admin pages',                 'group' => 'Site'],
	];
	foreach (list_corpora_with_titles(true) as $corpus => $title)
		$scopes[] = ['id' => "corpus:$corpus", 'label' => cp_scope_settings("corpus:$corpus")['short'], 'title' => strip_tags($title), 'group' => 'Corpora'];
	foreach ($scopes as &$sc)
	{
		$sc['settings'] = cp_scope_settings($sc['id']);
		$sc['defaultPreset'] = cp_default_preset($sc['id']);
		if (0 === strpos($sc['id'], 'corpus:'))
			$sc['defaultShort'] = cp_default_short_name(substr($sc['id'], 7));
	}
	unset($sc);

	$presets = [];
	foreach (CP_PRESETS as $id => $p)
		$presets[$id] = ['label' => $p['label'], 'day' => cp_palette($id, 'day'), 'night' => cp_palette($id, 'night')];

	$fonts = [];
	foreach (cp_scan_fonts() as $name => $f)
		$fonts[] = ['name' => $name, 'kind' => $f['kind'], 'source' => $f['source']];

	$recommended = [];
	foreach (CP_RECOMMENDED_FONTS as $name => $kind)
		$recommended[] = ['name' => $name, 'kind' => $kind];

	$data = [
		'version'     => CP_APPEARANCE_VERSION,
		'actUrl'      => 'cqpweb-new-ui-act.php',
		'presets'     => $presets,
		'tokens'      => CP_TOKENS,
		'pairs'       => CP_PAIRS,
		'roles'       => CP_FONT_ROLES,
		'defaultFonts'=> CP_DEFAULT_FONTS,
		'scopes'      => $scopes,
		'fonts'       => $fonts,
		'recommended' => $recommended,
		'fontPrefix'  => CP_FONT_PREFIX,
		'fontRoot'    => CP_FONT_ROOT,
		'fontCss'     => cp_url(CP_FONT_URL . '/' . CP_FONT_CSS_NAME) . (false === strpos(CP_FONT_URL, '?') ? '?' : '&') . 'v=',
		'siteName'    => cp_site_name(),
	];
	$json = htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
	$v1 = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.js') ?: 0;
	$v3 = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/google-fonts.json') ?: 0;
	$v2 = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.css') ?: 0;

	?>
	<link rel="stylesheet" href="../css/cqpweb-new-ui/appearance-admin.css?v=<?= $v2 ?>">
	<div id="cpAppearance" data-gf-list="../css/cqpweb-new-ui/google-fonts.json?v=<?= $v3 ?>" class="cpa" data-cp="<?= $json ?>">

		<div class="cpa-intro">
			<h2 class="cpa-h1">Appearance</h2>
			<p>Colors and fonts for the home page, the user pages (with the tutorials), the admin pages and each corpus.
			Every page inside a corpus (queries, concordances, collocations, frequency lists) uses that corpus's settings.
			Visitors switch between day and night mode with the button at the top right of every page.</p>
		</div>

		<section class="cpa-card" aria-labelledby="cpaScopeH">
			<h3 id="cpaScopeH" class="cpa-h2">What to style</h3>
			<div class="cpa-chips" id="cpaScopes" role="group" aria-label="Pages to style"></div>
			<div class="cpa-row" id="cpaShortRow" hidden>
				<label for="cpaShort">Short name of this corpus (used in the "Back to" button)</label>
				<input type="text" id="cpaShort" maxlength="40">
			</div>
			<div class="cpa-row cpa-stack" id="cpaCardRow" hidden>
				<label for="cpaCard">Description on this corpus's home-page card (leave empty to show the corpus title)</label>
				<textarea id="cpaCard" rows="2" maxlength="400"></textarea>
			</div>
			<div class="cpa-row" id="cpaAltRow" hidden>
				<label for="cpaAlt">Alternate words in this corpus's concordances, by default (visitors can change it above each concordance)</label>
				<select id="cpaAlt">
					<option value="off">Off</option>
					<option value="bg">Background shading</option>
					<option value="text">Text color</option>
					<option value="both">Background shading and text color</option>
				</select>
			</div>
		</section>

		<div class="cpa-grid">
			<section class="cpa-card" aria-labelledby="cpaColH">
				<h3 id="cpaColH" class="cpa-h2">Colors</h3>
				<div class="cpa-row">
					<label for="cpaPreset">Color scheme</label>
					<select id="cpaPreset"></select>
					<div class="cpa-swatches" id="cpaPresetSwatches" aria-hidden="true"></div>
				</div>
				<div class="cpa-row">
					<span class="cpa-label">Editing</span>
					<div class="cpa-seg" role="group" aria-label="Palette to edit">
						<button type="button" class="cpa-segbtn" data-mode="day" aria-pressed="true">Day palette</button>
						<button type="button" class="cpa-segbtn" data-mode="night" aria-pressed="false">Night palette</button>
					</div>
				</div>
				<p class="cpa-note">Each color starts from the scheme. Change one to override it; "Use scheme" takes it back.
				When you change a background, its text color is adjusted for legibility unless you have set that text color yourself.</p>
				<div id="cpaTokens" class="cpa-tokens"></div>
				<h4 class="cpa-h3">Contrast check</h4>
				<div id="cpaPairs" class="cpa-pairs"></div>
			</section>

			<div class="cpa-col">
				<section class="cpa-card" aria-labelledby="cpaFontH">
					<h3 id="cpaFontH" class="cpa-h2">Fonts</h3>
					<div id="cpaFontRoles"></div>
				</section>
				<section class="cpa-card" aria-labelledby="cpaPrevH">
					<h3 id="cpaPrevH" class="cpa-h2">Preview</h3>
					<p class="cpa-note" id="cpaPrevCaption"></p>
					<div id="cpaPreview" class="cpa-preview"></div>
				</section>
			</div>
		</div>

		<section class="cpa-card" aria-labelledby="cpaGfH">
			<h3 id="cpaGfH" class="cpa-h2">Fonts on this server</h3>
			<p class="cpa-note">Fonts are downloaded once into <code><?= escape_html(CP_FONT_ROOT) ?></code> and served from this server itself,
			so visitors' browsers never contact Google. Pick a family from the list (type to narrow it down); each name is shown in its own font.
			Only your browser, on this page, fetches those small previews from Google.</p>
			<div class="cpa-row cpa-addfont">
				<label for="cpaGfName">Google Fonts family</label>
				<select id="cpaGfCat" aria-label="Show only">
					<option value="">All kinds</option>
					<option value="sans">Sans serif</option>
					<option value="serif">Serif</option>
					<option value="mono">Monospace</option>
					<option value="display">Display</option>
					<option value="hand">Handwriting</option>
				</select>
				<div class="cpa-gfpick">
					<input type="text" id="cpaGfName" placeholder="Search Google Fonts families" maxlength="60" autocomplete="off"
						role="combobox" aria-expanded="false" aria-controls="cpaGfList" aria-autocomplete="list">
					<ul id="cpaGfList" class="cpa-gflist" role="listbox" hidden></ul>
				</div>
				<select id="cpaGfKind" aria-label="Kind of font">
					<option value="">Kind: guess</option>
					<option value="sans">Sans serif</option>
					<option value="serif">Serif</option>
					<option value="mono">Monospace</option>
				</select>
				<button type="button" id="cpaGfAdd">Download and add</button>
				<button type="button" id="cpaGfRecommended" class="cpa-secondary"></button>
			</div>
			<ol id="cpaGfLog" class="cpa-log" aria-live="polite" hidden></ol>
			<h4 class="cpa-h3" id="cpaInstalledH"></h4>
			<ul id="cpaInstalled" class="cpa-installed"></ul>
		</section>

		<div class="cpa-savebar">
			<span role="status" id="cpaStatus" class="cpa-status"></span>
			<button type="button" id="cpaRevert" class="cpa-secondary">Reset this page type to its defaults</button>
			<button type="button" id="cpaSave">Save all changes</button>
		</div>
		<?= cp_help_admin_card() ?>
	</div>
	<script defer src="../css/cqpweb-new-ui/appearance-admin.js?v=<?= $v1 ?>"></script>
	<?php
}
