<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the admin page "News page".
 * VERSION 1.0.2
 *
 * Edits the public page news.php (the file CP_NEWS_FILE in the user pages folder)
 * in a simple text format:
 *   # October 2026          a month (or any period): a large heading
 *   ## Searching            a group within it: a smaller heading
 *   - Fixed X.              one change
 *   any other line          an ordinary paragraph; a blank line starts a new one
 * Links are written as <a href="...">text</a>; everything else is plain text.
 * The text is kept in CP_DATA_DIR/news-source.txt; each save also writes the page and keeps
 * the previous page in CP_DATA_DIR/news-history/ (the newest 50).
 * Saves through adm/cqpweb-new-ui-act.php (action "newsSave"); the text travels base64-encoded,
 * so the server's security filter does not mistake links and quotes in it for an attack.
 */

require_once(__DIR__ . '/embed-lib.php');

const CP_NEWS_SOURCE  = CP_DATA_DIR . '/news-source.txt';
const CP_NEWS_HISTORY = CP_DATA_DIR . '/news-history';
const CP_NEWS_KEEP    = 50;

/** Full path of the published page. */
function cp_news_file() : string
{
	return cp_tutorial_dir() . '/' . CP_NEWS_FILE;
}

/** The editable text: the saved source, or, the first time, the published page turned back into text. */
function cp_news_source() : string
{
	if (is_readable(CP_NEWS_SOURCE))
		return (string)file_get_contents(CP_NEWS_SOURCE);
	$html = @file_get_contents(cp_news_file());
	return false === $html ? '' : cp_news_html_to_text($html);
}

/** Turns the published page (paragraphs, h2, h3, lists) back into the text format. */
function cp_news_html_to_text(string $html) : string
{
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
	libxml_clear_errors();
	$body = $doc->getElementsByTagName('body')->item(0);
	if (!$body)
		return '';
	$inline = function (DOMNode $n) use (&$inline) : string {
		$out = '';
		foreach ($n->childNodes as $c)
		{
			if (XML_TEXT_NODE === $c->nodeType)
				$out .= $c->nodeValue;
			else if ($c instanceof DOMElement && 'a' === strtolower($c->tagName) && $c->hasAttribute('href'))
				$out .= '<a href="' . $c->getAttribute('href') . '">' . $c->textContent . '</a>';
			else
				$out .= $inline($c);
		}
		return trim(preg_replace('/\s+/u', ' ', $out));
	};
	$lines = [];
	foreach ($body->childNodes as $n)
	{
		if (!($n instanceof DOMElement))
			continue;
		switch (strtolower($n->tagName))
		{
		case 'h1':
		case 'h2':
			$lines[] = '';
			$lines[] = '# ' . $inline($n);
			break;
		case 'h3':
		case 'h4':
			$lines[] = '';
			$lines[] = '## ' . $inline($n);
			break;
		case 'ul':
		case 'ol':
			foreach ($n->getElementsByTagName('li') as $li)
				$lines[] = '- ' . $inline($li);
			break;
		default:
			$t = $inline($n);
			if ('' !== $t)
			{
				$lines[] = '';
				$lines[] = $t;
			}
		}
	}
	return trim(implode("\n", $lines)) . "\n";
}

/** Plain text with <a href="...">text</a> links kept; everything else escaped. */
function cp_news_inline(string $t) : string
{
	$h = htmlspecialchars($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	return preg_replace('~&lt;a\s+href=&quot;((?:https?://|/|#)[^"<>\s]*?)&quot;\s*&gt;(.*?)&lt;/a&gt;~u', '<a href="$1">$2</a>', $h);
}

/** Turns the text format into the page's HTML. */
function cp_news_text_to_html(string $text) : string
{
	$out = [];
	$in_list = false;
	$para = [];
	$flush = function () use (&$out, &$para, &$in_list) {
		if ($para)
		{
			$out[] = '<p>' . implode(' ', $para) . '</p>';
			$para = [];
		}
		if ($in_list)
		{
			$out[] = '</ul>';
			$in_list = false;
		}
	};
	foreach (preg_split('/\R/u', $text) as $raw)
	{
		$line = trim($raw);
		if ('' === $line)
		{
			$flush();
			continue;
		}
		if (preg_match('/^##\s+(.+)$/u', $line, $m))
		{
			$flush();
			$out[] = '';
			$out[] = '<h3>' . cp_news_inline($m[1]) . '</h3>';
		}
		else if (preg_match('/^#\s+(.+)$/u', $line, $m))
		{
			$flush();
			$out[] = '';
			$out[] = '<h2>' . cp_news_inline($m[1]) . '</h2>';
		}
		else if (preg_match('/^[-*]\s+(.+)$/u', $line, $m))
		{
			if ($para)
			{
				$out[] = '<p>' . implode(' ', $para) . '</p>';
				$para = [];
			}
			if (!$in_list)
			{
				$out[] = '<ul>';
				$in_list = true;
			}
			$out[] = "\t<li>" . cp_news_inline($m[1]) . '</li>';
		}
		else
		{
			if ($in_list)
			{
				$out[] = '</ul>';
				$in_list = false;
			}
			$para[] = cp_news_inline($line);
		}
	}
	$flush();
	return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n\t<meta charset=\"utf-8\">\n\t<title>" . escape_html(cp_news_title()) . "</title>\n</head>\n<body>\n\n"
		. trim(implode("\n", $out)) . "\n\n</body>\n</html>\n";
}

/** Saves the text and publishes the page. Returns '' on success, else the reason. */
function cp_news_save(string $text) : string
{
	global $User;
	if (!$User->is_admin())
		return 'Only administrators can change this page.';
	$text = str_replace("\r\n", "\n", Normalizer::normalize($text, Normalizer::FORM_C) ?: $text);
	$text = trim($text) . "\n";
	if (strlen($text) > 500000)
		return 'The text is too long.';
	if ('' === trim($text))
		return 'The text is empty; nothing was saved.';
	$html = cp_news_text_to_html($text);
	$file = cp_news_file();

	if (!is_dir(CP_NEWS_HISTORY) && !@mkdir(CP_NEWS_HISTORY, 02775, true))
		return 'Cannot create ' . CP_NEWS_HISTORY;
	if (is_readable($file))
	{
		if (!@copy($file, CP_NEWS_HISTORY . '/news-' . date('Ymd-His') . '.html'))
			return 'Cannot keep a copy of the current page in ' . CP_NEWS_HISTORY . '; nothing was saved.';
		$old = glob(CP_NEWS_HISTORY . '/news-*.html') ?: [];
		sort($old);
		while (count($old) > CP_NEWS_KEEP)
			@unlink(array_shift($old));
	}
	if (false === @file_put_contents($file, $html, LOCK_EX))
		return 'Cannot write ' . $file . ' (the web server needs write permission on it).';
	if (false === @file_put_contents(CP_NEWS_SOURCE, $text, LOCK_EX))
		return 'The page was published, but its text could not be kept in ' . CP_NEWS_SOURCE . '.';
	return '';
}

/** The admin page. */
function cp_news_admin_ui() : void
{
	$v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance-admin.css') ?: 0;
	$text = escape_html(cp_news_source());
	$file = cp_news_file();
	$writable = is_file($file) ? is_writable($file) : is_writable(dirname($file));
	?>
	<link rel="stylesheet" href="../css/cqpweb-new-ui/appearance-admin.css?v=<?= $v ?>">
	<div class="cpa" id="cpNewsAdmin">
		<div class="cpa-intro">
			<h2 class="cpa-h1">News page: <?= escape_html(cp_news_title()) ?></h2>
			<p class="cpa-note">This is the public page <a href="../news.php" target="_blank">news.php</a>, linked from the home page, from every menu
			and from the bottom of every page (its title and those links are set on the <a href="index.php?ui=cpPersonalize">Personalization</a> page).
			Until something is saved here, the page does not exist and nothing links to it.
			Write it in this simple format:</p>
			<pre class="cp-news-help"># October 2026            a period: large heading
## Searching              a group: smaller heading
- Fixed X.                one change
Any other line            an ordinary paragraph (a blank line starts a new one)</pre>
			<p class="cpa-note">Links: <code>&lt;a href="https://www.example.org/"&gt;text to click&lt;/a&gt;</code>
			(start addresses on this site with a slash). Put the newest period first. Each save keeps the previous version of the page on the server.</p>
			<?php if (!$writable) echo '<p class="cpa-note cpa-bad">The web server cannot write the page file or its folder, so saving will fail. See the package README, &ldquo;Folders and permissions&rdquo;.</p>'; ?>
		</div>
		<section class="cpa-card">
			<textarea id="cpNewsText" class="cp-news-text" rows="30" spellcheck="true"><?= $text ?></textarea>
			<div class="cpa-row">
				<button type="button" id="cpNewsSave">Save and publish</button>
				<a class="cpa-secondary cp-news-view" href="../news.php" target="_blank">View the page</a>
				<span role="status" id="cpNewsStatus" class="cpa-status"></span>
			</div>
		</section>
	</div>
	<script>
	(function () {
		var st = document.getElementById('cpNewsStatus');
		function b64(s) {
			var bytes = new TextEncoder().encode(s), bin = '';
			for (var i = 0; i < bytes.length; i += 0x8000)
				bin += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
			return btoa(bin);
		}
		document.getElementById('cpNewsSave').addEventListener('click', function () {
			var btn = this;
			btn.disabled = true;
			st.textContent = 'Saving…';
			st.className = 'cpa-status';
			fetch('cqpweb-new-ui-act.php', {
				method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'newsSave', b64: b64(document.getElementById('cpNewsText').value) })
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
