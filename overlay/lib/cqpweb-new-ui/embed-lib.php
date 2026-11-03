<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: showing embedded pages inside the site design.
 * VERSION 1.0.3
 *
 * CQPweb's "embedded pages" (admin control panel > Manage embedded pages) are whole HTML
 * files, such as the CQP syntax tutorials and the corpus documentation. CQPweb 3.3.19
 * printed only the file's path. cp_render_embed() reads the file and returns:
 *   - the file's own <style> rules, limited to the embedded page (".cp-embed ..."),
 *   - the contents of its <body>, without <script> elements,
 * wrapped in <div class="cp-embed">. Colors from the old pages are adjusted for night
 * mode in css/cqpweb-new-ui/skin.css.
 */

require_once(__DIR__ . '/paths.php');
require_once(__DIR__ . '/brand-lib.php');

function cp_render_embed(string $path) : string
{
	$html = @file_get_contents($path);
	if (false === $html)
		return '<p>This page could not be read.</p>';

	/* character set: the files are UTF-8; older ones may be Windows-1252 */
	if (!mb_check_encoding($html, 'UTF-8'))
		$html = mb_convert_encoding($html, 'UTF-8', 'Windows-1252');

	/* the file's own styles, limited to the embedded page */
	$css = '';
	if (preg_match_all('~<style\b[^>]*>(.*?)</style>~is', $html, $m))
		foreach ($m[1] as $block)
			$css .= cp_scope_css($block, '.cp-embed');

	/* body: from the first <body> to the last </body> (or the whole file) */
	$body = $html;
	if (preg_match('~<body\b[^>]*>~i', $body, $mm, PREG_OFFSET_CAPTURE))
		$body = substr($body, $mm[0][1] + strlen($mm[0][0]));
	if (false !== ($end = strripos($body, '</body>')))
		$body = substr($body, 0, $end);
	$body = preg_replace([
			'~<script\b[^>]*>.*?</script>~is',     /* no scripts from the files */
			'~<script\b[^>]*/?>~i',
			'~</?body\b[^>]*>~i',                   /* stray extra <body> tags */
			'~</?html\b[^>]*>~i',
			'~<head\b[^>]*>.*?</head>~is',
			'~<meta\b[^>]*>~i',
			'~<style\b[^>]*>.*?</style>~is',
			'~<link\b[^>]*>~i',
		], '', $body);

	/* old-style anchors <a name="x"></a> still work; give them ids too, for the sticky top bar offset */
	$body = preg_replace('~<a\s+name="([A-Za-z][\w:.-]*)"~', '<a id="$1" name="$1"', $body);

	/* web addresses written without http:// (e.g. href="www.praat.org") */
	$body = preg_replace('~href="(www\.[^"]+)"~i', 'href="https://$1"', $body);

	/* relative links and images point into the user pages folder, wherever the page is shown
	 * (inside a corpus, on page.php, on the documentation pages); links to other HTML pages
	 * of the folder open them in the site design */
	$pages_url = cp_url(CP_PAGES_URL);
	$page_view = cp_url('page.php?name=');
	$body = preg_replace_callback('~\b(href|src)="([^"]*)"~i', function ($m) use ($pages_url, $page_view) {
		$v = $m[2];
		if ('' === $v || preg_match('~^(#|/|[a-z][a-z0-9+.-]*:)~i', $v))
			return $m[0];
		if ('href' === strtolower($m[1]) && preg_match('~^([A-Za-z0-9._ -]+\.html?)(#.*)?$~', $v, $p))
			return $m[1] . '="' . $page_view . rawurlencode($p[1]) . ($p[2] ?? '') . '"';
		return $m[1] . '="' . $pages_url . '/' . $v . '"';
	}, $body);

	$out = '';
	if ('' !== trim($css))
		$out .= "<style>\n$css</style>\n";
	return $out . "<div class=\"cp-embed\">\n$body\n</div>\n";
}

/** Prefixes every selector in a block of CSS with $scope. @-rules other than @media are dropped. */
function cp_scope_css(string $css, string $scope) : string
{
	$css = preg_replace(['~<!--|-->~', '~/\*.*?\*/~s'], '', $css);
	$out = '';
	$len = strlen($css);
	$i = 0;
	while ($i < $len)
	{
		$open = strpos($css, '{', $i);
		if (false === $open)
			break;
		$sel = trim(substr($css, $i, $open - $i));
		/* find the matching closing brace */
		$depth = 1;
		$j = $open + 1;
		while ($j < $len && $depth > 0)
		{
			if ('{' === $css[$j])
				$depth++;
			elseif ('}' === $css[$j])
				$depth--;
			$j++;
		}
		$inner = substr($css, $open + 1, $j - $open - 2);
		if (0 === stripos($sel, '@media'))
			$out .= "$sel {\n" . cp_scope_css($inner, $scope) . "}\n";
		elseif ('' !== $sel && '@' !== $sel[0])
		{
			$parts = [];
			foreach (explode(',', $sel) as $p)
			{
				$p = trim($p);
				/* rules for the whole page (html, body) are only for the file seen on its own */
				if ('' === $p || preg_match('~^(html|body)\s*$~i', $p))
					continue;
				$p = preg_replace('~^(html|body)\b\s*~i', '', $p);
				$parts[] = "$scope $p";
			}
			if ($parts)
				$out .= implode(', ', $parts) . ' {' . trim($inner) . "}\n";
		}
		$i = $j;
	}
	return $out;
}


/*
 * ===========================================
 * CQP SYNTAX TUTORIALS (corpus menu "Tutorials")
 * ===========================================
 * The files are read from CP_TUTORIAL_DIR (the folder Apache serves as /userpages), so the
 * standalone copies and the pages shown here stay the same file. Each is shown inside the corpus interface at
 * index.php?ui=<handle>, with the corpus menu on the left and the user-pages colors.
 */

const CP_TUTORIALS = [
	'cpTutorialEn' => ['file' => 'cqp-syntax-tutorial.html',    'menu' => 'CQP syntax (English)', 'title' => 'CQP syntax tutorial (English)', 'lang' => 'en'],
	'cpTutorialEs' => ['file' => 'cqp-syntax-tutorial-es.html', 'menu' => 'CQP syntax (Spanish)', 'title' => 'CQP syntax tutorial (Spanish)', 'lang' => 'es'],
];

function cp_tutorial_dir() : string
{
	return CP_TUTORIAL_DIR;
}

/** The tutorials whose files are installed: handle => info. */
function cp_tutorials() : array
{
	$out = [];
	foreach (CP_TUTORIALS as $h => $t)
		if (is_readable(cp_tutorial_dir() . '/' . $t['file']))
			$out[$h] = $t;
	return $out;
}

/** Menu rows for the corpus side bar: a "Tutorials" heading and one row per tutorial. */
function cp_tutorial_menu(string $what_ui) : string
{
	$t = cp_tutorials();
	if (empty($t))
		return '';
	$out = print_menurow_heading('Tutorials');
	foreach ($t as $h => $info)
		$out .= print_menurow_index($h, $info['menu'], '', $what_ui);
	return $out;
}

/** The tutorial page itself (inside the corpus interface). */
function cp_tutorial_page(string $handle) : void
{
	$t = cp_tutorials()[$handle] ?? null;
	if (!$t)
	{
		echo '<table class="layout-frame fullwidth"><tr><td class="layout-bg"><p>This tutorial is not installed.</p></td></tr></table>';
		return;
	}
	$other = '';
	foreach (cp_tutorials() as $h => $o)
		if ($h !== $handle)
			$other .= ' <a class="cp-navbtn" href="index.php?ui=' . $h . '">' . escape_html($o['menu']) . '</a>';
	echo '<table class="layout-frame fullwidth">'
		, '<tr><th class="layout-strong">', escape_html($t['title']), $other ? "<span class=\"cp-tut-other\">$other</span>" : '', '</th></tr>'
		, '<tr><td class="layout-fg" lang="', $t['lang'], '">', cp_render_embed(cp_tutorial_dir() . '/' . $t['file']), '</td></tr>'
		, "</table>\n";
}

/** The highlighted box with the tutorial links, for the query form when "CQP syntax" is selected. */
function cp_tutorial_callout() : string
{
	$t = cp_tutorials();
	if (empty($t))
		return '';
	$links = '';
	foreach ($t as $h => $info)
		$links .= '<a class="cp-home-cta" href="index.php?ui=' . $h . '">' . escape_html($info['title']) . '</a> ';
	return '<div class="cp-tut-callout"><p><strong>New to CQP syntax?</strong> Read a step-by-step tutorial, with examples.</p>'
		. "<p class=\"cp-tut-links\">$links</p></div>";
}


/*
 * ===========================================
 * PUBLIC CORPUS DOCUMENTATION (documentation.php?corpus=<slug>)
 * ===========================================
 * Open to everyone, no log-in, and allowed to search engines. Which file of the user pages
 * folder documents which corpus is set on the Personalization page (cp_public_docs()).
 */

/** The slug of the public documentation page for a corpus, or '' if it has none. */
function cp_public_doc_for_corpus(string $corpus) : string
{
	foreach (cp_public_docs() as $slug => $d)
		if ($d['corpus'] === $corpus)
			return (string)$slug;
	return '';
}

/** The whole public documentation page (called from the site's documentation.php). */
function cp_public_doc_page() : void
{
	$slug = (string)($_GET['corpus'] ?? '');
	$d = cp_public_docs()[$slug] ?? null;
	if (!$d)
	{
		cp_public_not_found('There is no documentation page at this address.');
		return;
	}
	$short = cp_scope_settings('corpus:' . $d['corpus'])['short'];
	$search = '';
	$info = get_all_corpora_info()[$d['corpus']] ?? null;
	if ($info && $info->visible)
		$search = '<a class="cp-home-cta" href="' . $d['corpus'] . '/">Search ' . escape_html($short) . '</a>';
	$desc = '' !== $d['description'] ? $d['description'] : "Documentation of the corpus $short.";
	cp_public_page_out("$short documentation", $desc, 'documentation.php?corpus=' . rawurlencode((string)$slug), $d['file'], $search);
}

/** The whole news page (called from the site's news.php). */
function cp_news_page() : void
{
	if (!cp_news_available())
	{
		cp_public_not_found('The news page is not available at the moment.');
		return;
	}
	cp_public_page_out(cp_news_title(), 'What has changed on ' . cp_site_name() . ': new features, fixes and updates.', 'news.php', CP_NEWS_FILE, '');
}

/** The site's own address for a page, for search engines: CQPweb's $cqpweb_root_url if set, else this request's. */
function cp_canonical_url(string $path) : string
{
	global $Config;
	if (!empty($Config->cqpweb_root_url))
		return rtrim($Config->cqpweb_root_url, '/') . '/' . $path;
	$https = !empty($_SERVER['HTTPS']) && 'off' !== strtolower((string)$_SERVER['HTTPS']);
	$host = preg_replace('/[^A-Za-z0-9.:\[\]-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
	$dir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
	return ($https ? 'https://' : 'http://') . $host . $dir . '/' . $path;
}

/** A public page made of one file from the user pages folder, inside the site design. */
function cp_public_page_out(string $title, string $description, string $path, string $file, string $title_extra) : void
{
	global $Config;
	$desc = escape_html($description);
	$canon = escape_html(cp_canonical_url($path));
	echo str_replace('</head>', "\t<meta name=\"description\" content=\"$desc\">\n\t<link rel=\"canonical\" href=\"$canon\">\n</head>",
		print_html_header(escape_html($title) . ' · ' . escape_html(cp_site_name()), $Config->colour_scheme));
	echo '<table class="layout-frame fullwidth cp-doc-page">'
		, '<tr><th class="layout-strong">', escape_html($title), $title_extra ? "<span class=\"cp-tut-other\">$title_extra</span>" : '', '</th></tr>'
		, '<tr><td class="layout-fg">', cp_render_embed(cp_tutorial_dir() . '/' . $file), '</td></tr>'
		, "</table>\n";
	echo print_html_footer(false);
}

/** A 404 page in the site design. */
function cp_public_not_found(string $what) : void
{
	global $Config;
	http_response_code(404);
	echo print_html_header('Page not found · ' . escape_html(cp_site_name()), $Config->colour_scheme);
	echo '<main class="cp-home"><section class="cp-home-box"><h1 class="cp-home-h2">Page not found</h1>'
		, '<p>', escape_html($what), ' <a href="', cp_url(''), './">Go to the homepage</a>.</p></section></main>';
	echo print_html_footer(false);
}


/*
 * Public view of any page in the user pages folder, in the site design: page.php?name=file.html.
 */

/** The HTML files in the user pages folder that can be shown: name => full path. */
function cp_userpages_list() : array
{
	$out = [];
	foreach (glob(cp_tutorial_dir() . '/*.{html,htm}', GLOB_BRACE) ?: [] as $path)
		if (is_file($path))
			$out[basename($path)] = $path;
	return $out;
}

/** The page's own <title>, without a leading "<site name> - ", or '' when it has none. */
function cp_userpage_title(string $path) : string
{
	$html = (string)@file_get_contents($path, false, null, 0, 65536);
	if (!mb_check_encoding($html, 'UTF-8'))
		$html = mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
	if (!preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m))
		return '';
	$t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
	return trim(preg_replace('/^' . preg_quote(cp_site_name(), '/') . '\s*[-:\x{2013}\x{2014}|]\s*/iu', '', $t));
}

/** The whole public page (called from the site's page.php). */
function cp_userpage_view() : void
{
	$name = basename((string)($_GET['name'] ?? ''));
	$list = cp_userpages_list();
	if (!isset($list[$name]))
	{
		cp_public_not_found('There is no such page.');
		return;
	}
	if (CP_NEWS_FILE === $name)
	{
		header('Location: news.php', true, 301);
		return;
	}
	foreach (cp_public_docs() as $slug => $d)
		if ($d['file'] === $name)
		{
			header('Location: documentation.php?corpus=' . rawurlencode((string)$slug), true, 301);
			return;
		}
	$title = cp_userpage_title($list[$name]);
	$extra = '';
	foreach (CP_TUTORIALS as $h => $t)
		if ($t['file'] === $name)
		{
			$title = $t['title'];
			foreach (CP_TUTORIALS as $h2 => $o)
				if ($h2 !== $h && isset($list[$o['file']]))
					$extra .= ' <a class="cp-navbtn" href="page.php?name=' . rawurlencode($o['file']) . '">' . escape_html($o['menu']) . '</a>';
		}
	if ('' === $title)
		$title = preg_replace('/\.html?$/i', '', $name);
	cp_public_page_out($title, $title . ' (' . cp_site_name() . ')', 'page.php?name=' . rawurlencode($name), $name, $extra);
}
