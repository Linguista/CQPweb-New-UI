<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the admin page "Edit user pages".
 * VERSION 1.0.2
 *
 * Lists the HTML files in the user pages folder (CP_TUTORIAL_DIR): the tutorials, the corpus
 * documentation, the tagset pages and any other embedded page. Each one can be opened and edited
 * as HTML. Saving keeps the previous version in CP_DATA_DIR/page-history/ (the newest 20 per file)
 * and keeps the file's own line endings. The text travels base64-encoded, so the server's security
 * filter does not mistake page HTML for an attack. Saves through adm/cqpweb-new-ui-act.php
 * (action "pageSave"). The news page has its own editor (news-ui.php) and is not listed here.
 */

require_once(__DIR__ . '/embed-lib.php');

const CP_PAGE_HISTORY = CP_DATA_DIR . '/page-history';
const CP_PAGE_KEEP    = 20;

/** The editable files: name => full path. Only .html/.htm files directly in the user pages folder. */
function cp_pages_list() : array
{
	$out = [];
	foreach (glob(cp_tutorial_dir() . '/*.{html,htm}', GLOB_BRACE) ?: [] as $path)
	{
		$name = basename($path);
		if (CP_NEWS_FILE === $name || !is_file($path))
			continue;
		$out[$name] = $path;
	}
	ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
	return $out;
}

/** The full path of an editable file, or '' when the name is not one of them. */
function cp_pages_path(string $name) : string
{
	return cp_pages_list()[$name] ?? '';
}

/** Saves one page. Returns '' on success, else the reason. */
function cp_pages_save(string $name, string $html) : string
{
	global $User;
	if (!$User->is_admin())
		return 'Only administrators can change these pages.';
	$path = cp_pages_path($name);
	if ('' === $path)
		return 'That page does not exist in the user pages folder.';
	if (!is_writable($path))
		return "The web server cannot write $name.";
	if ('' === trim($html))
		return 'The page is empty; nothing was saved.';
	if (strlen($html) > 8000000)
		return 'The page is too large.';
	if (!mb_check_encoding($html, 'UTF-8'))
		return 'The text is not valid UTF-8; nothing was saved.';
	$html = Normalizer::normalize($html, Normalizer::FORM_C) ?: $html;

	$old = (string)@file_get_contents($path);
	/* keep the file's own line endings (several pages use Windows line endings) */
	$html = str_replace("\r\n", "\n", $html);
	if (false !== strpos($old, "\r\n"))
		$html = str_replace("\n", "\r\n", $html);
	if ($html === $old)
		return '';

	if (!is_dir(CP_PAGE_HISTORY) && !@mkdir(CP_PAGE_HISTORY, 02775, true))
		return 'Cannot create ' . CP_PAGE_HISTORY . '; nothing was saved.';
	$stem = preg_replace('/\.html?$/i', '', $name);
	if (!@copy($path, CP_PAGE_HISTORY . "/$stem-" . date('Ymd-His') . '.html'))
		return 'Cannot keep a copy of the current page in ' . CP_PAGE_HISTORY . '; nothing was saved.';
	$copies = glob(CP_PAGE_HISTORY . '/' . glob_escape_cp($stem) . '-*.html') ?: [];
	sort($copies);
	while (count($copies) > CP_PAGE_KEEP)
		@unlink(array_shift($copies));

	if (false === @file_put_contents($path, $html, LOCK_EX))
		return "Cannot write $name.";
	return '';
}

/** Creates a new page from a short template. Returns '' or the reason it could not. */
function cp_pages_create(string $name) : string
{
	global $User;
	if (!$User->is_admin())
		return 'Only administrators can create pages.';
	$name = trim($name);
	if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,75}\.html$/', $name))
		return 'Use a file name of letters, digits, hyphens, underscores and dots, ending in .html.';
	if (CP_NEWS_FILE === $name)
		return 'That name is kept for the news page.';
	$dir = cp_tutorial_dir();
	if (!is_dir($dir) && !@mkdir($dir, 02775, true))
		return "Cannot create the folder $dir.";
	$path = "$dir/$name";
	if (file_exists($path))
		return 'A page with that name already exists.';
	$title = escape_html(ucfirst(str_replace(['-', '_'], ' ', preg_replace('/\.html$/', '', $name))));
	$html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n\t<meta charset=\"utf-8\">\n\t<title>$title</title>\n</head>\n<body>\n\n"
		. "<h1>$title</h1>\n\n<p>Write the page here.</p>\n\n</body>\n</html>\n";
	if (false === @file_put_contents($path, $html, LOCK_EX))
		return "Cannot write $path.";
	@chmod($path, 0664);
	return '';
}

/** glob() special characters escaped. */
function glob_escape_cp(string $s) : string
{
	return preg_replace('/([*?\[\]])/', '[$1]', $s);
}

/** The admin page: the list, or one page in the editor. */
function cp_pages_admin_ui() : void
{
	$v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.css') ?: 0;
	$titles = [];
	$r = do_sql_query("select `id`, `title`, `file_path` from `embedded_pages`");
	while ($o = mysqli_fetch_object($r))
		$titles[basename((string)$o->file_path)][] = ['id' => (int)$o->id, 'title' => (string)$o->title];
	$name = (string)($_GET['file'] ?? '');
	?>
	<link rel="stylesheet" href="../css/cqpweb-new-ui/appearance-admin.css?v=<?= $v ?>">
	<div class="cpa" id="cpPagesAdmin">
	<?php
	if ('' === $name)
	{
		?>
		<div class="cpa-intro">
			<h2 class="cpa-h1">Edit user pages</h2>
			<p class="cpa-note">The pages in <code><?= escape_html(cp_tutorial_dir()) ?></code>: the tutorials, the corpus documentation,
			the tagset pages and the other embedded pages. Click a page to edit its HTML. Every save keeps the previous version on the server.
			The news page is edited on <a href="index.php?ui=cpNews">News page</a>.</p>
		</div>
		<section class="cpa-card">
			<div class="cpa-row">
				<label for="cpNewPage">New page: file name</label>
				<input type="text" id="cpNewPage" maxlength="80" placeholder="for example mycorpus-documentation.html">
				<button type="button" id="cpNewPageBtn">Create</button>
				<span role="status" id="cpNewPageStatus" class="cpa-status"></span>
			</div>
			<p class="cpa-note">Letters, digits, hyphens, underscores and dots, ending in <code>.html</code>. The new page starts with a short template.
			Pictures and PDFs for the pages are copied into the folder by hand; refer to them by their file name alone.</p>
		</section>
		<script>
		document.getElementById('cpNewPageBtn').addEventListener('click', function () {
			var name = document.getElementById('cpNewPage').value.trim(), st = document.getElementById('cpNewPageStatus');
			fetch('cqpweb-new-ui-act.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'pageCreate', file: name }) })
			.then(function (r) { return r.json(); })
			.then(function (r) {
				if (r.ok) { location.href = 'index.php?ui=cpPages&file=' + encodeURIComponent(r.file); return; }
				st.textContent = r.message; st.className = 'cpa-status cpa-bad';
			});
		});
		</script>
		<section class="cpa-card">
			<table class="cp-pages-list">
				<tr><th>File</th><th>Embedded page</th><th>Size</th><th>Last changed</th></tr>
				<?php
				foreach (cp_pages_list() as $file => $path)
				{
					$t = [];
					foreach ($titles[$file] ?? [] as $e)
						$t[] = escape_html($e['title']) . ' <span class="cp-muted">(id ' . $e['id'] . ')</span>';
					$writable = is_writable($path) ? '' : ' <span class="cpa-bad">(the web server cannot write it)</span>';
					echo '<tr><td><a href="index.php?ui=cpPages&amp;file=', rawurlencode($file), '">', escape_html($file), '</a>', $writable, '</td>'
						, '<td>', $t ? implode('<br>', $t) : '<span class="cp-muted">not an embedded page</span>', '</td>'
						, '<td class="cp-num">', number_format((int)ceil(filesize($path) / 1024)), ' KB</td>'
						, '<td>', date('Y-m-d H:i', filemtime($path)), "</td></tr>\n";
				}
				?>
			</table>
		</section>
		<?php
	}
	else
	{
		$path = cp_pages_path($name);
		if ('' === $path)
		{
			echo '<p class="cpa-note cpa-bad">There is no page called ', escape_html($name), ' in the user pages folder. <a href="index.php?ui=cpPages">Back to the list</a>.</p></div>';
			return;
		}
		$html = (string)file_get_contents($path);
		$note = '';
		if (!mb_check_encoding($html, 'UTF-8'))
		{
			$html = mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
			$note = '<p class="cpa-note cpa-bad">This file was not in UTF-8; it is shown converted from Windows-1252 and will be saved as UTF-8.</p>';
		}
		$html = str_replace("\r\n", "\n", $html);
		$file_url = '../page.php?name=' . rawurlencode($name);
		?>
		<div class="cpa-intro">
			<h2 class="cpa-h1">Edit <?= escape_html($name) ?></h2>
			<p class="cpa-note"><a href="index.php?ui=cpPages">Back to the list</a>. This is the page's HTML; change the text between the tags.
			Every save keeps the previous version on the server.</p>
			<?= $note ?>
			<?php if (!is_writable($path)) echo '<p class="cpa-note cpa-bad">The web server cannot write this file, so saving will fail.</p>'; ?>
		</div>
		<section class="cpa-card">
			<textarea id="cpPageText" class="cp-page-text" rows="34" spellcheck="false" data-file="<?= escape_html($name) ?>"><?= escape_html($html) ?></textarea>
			<div class="cpa-row">
				<button type="button" id="cpPageSave">Save</button>
				<a class="cpa-secondary cp-news-view" href="<?= $file_url ?>" target="_blank">View the page</a>
				<span role="status" id="cpPageStatus" class="cpa-status"></span>
			</div>
		</section>
		<script>
		(function () {
			var ta = document.getElementById('cpPageText'), st = document.getElementById('cpPageStatus');
			function b64(s) {
				var bytes = new TextEncoder().encode(s), bin = '';
				for (var i = 0; i < bytes.length; i += 0x8000)
					bin += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
				return btoa(bin);
			}
			document.getElementById('cpPageSave').addEventListener('click', function () {
				var btn = this;
				btn.disabled = true;
				st.textContent = 'Saving…';
				st.className = 'cpa-status';
				fetch('cqpweb-new-ui-act.php', {
					method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ action: 'pageSave', file: ta.getAttribute('data-file'), b64: b64(ta.value) })
				}).then(function (r) { return r.json(); })
				  .catch(function () { return { ok: false, message: 'The server could not be reached.' }; })
				  .then(function (r) {
					btn.disabled = false;
					st.textContent = r.message;
					st.className = 'cpa-status' + (r.ok ? '' : ' cpa-bad');
				});
			});
		})();
		</script>
		<?php
	}
	?>
	</div>
	<?php
}
