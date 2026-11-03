<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: personalization (site name, logo, icons, texts,
 * query defaults, public documentation pages) and the Help page links.
 * VERSION 1.0.2
 *
 * Settings live in CP_DATA_DIR/brand.json and CP_DATA_DIR/help.json; uploaded images in
 * CP_DATA_DIR/brand/ (served by cqpweb-new-ui-asset.php). Edited on the admin pages
 * "Personalization" (lib/cqpweb-new-ui/personalize-ui.php) and "Appearance" (Help page links).
 *
 * Wherever a value has not been set, what CQPweb is already configured with is used:
 * the home-page logo and welcome message of CQPweb's configuration file, its administrator
 * address. Only when CQPweb has nothing either does a placeholder appear.
 */

require_once(__DIR__ . '/paths.php');

const CP_BRAND_FILE = CP_DATA_DIR . '/brand.json';
const CP_HELP_FILE  = CP_DATA_DIR . '/help.json';
const CP_BRAND_DIR  = CP_DATA_DIR . '/brand';

const CP_PLACEHOLDER_SITE  = '[YOUR SITE NAME HERE]';
const CP_PLACEHOLDER_OWNER = '[YOUR NAME HERE]';

/* the images that can be uploaded: key => [label, largest size in bytes] */
const CP_BRAND_IMAGES = [
	'logo'    => ['Logo', 2097152],
	'favicon' => ['Browser icon (favicon)', 524288],
];
const CP_IMAGE_TYPES = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon'];

const CP_BRAND_DEFAULTS = [
	'siteName'      => '',
	'ownerName'     => '',
	'ownerUrl'      => '',
	'welcome'       => '',
	'logoAlt'       => '',
	'logoInvert'    => false,       /* invert the logo's colors in night mode */
	'topBar'        => 'name',      /* name | logo | both */
	'footer'        => '',
	'contact'       => '',
	'signupNotice'  => null,        /* null: the default notice */
	'newsTitle'     => '',
	'newsShow'      => true,
	'queryMode'     => 'simple',    /* simple | cqp */
	'queryCase'     => 'insensitive',  /* insensitive | sensitive */
	'caseNotice'    => true,
	'docs'          => [],          /* corpus handle => ['file' => ..., 'description' => ...] */
	'images'        => [],          /* key => file name in CP_BRAND_DIR */
];


/*
 * ========
 * SETTINGS
 * ========
 */

function cp_brand(bool $reload = false) : array
{
	static $cache = null;
	if (null !== $cache && !$reload)
		return $cache;
	$d = is_file(CP_BRAND_FILE) ? json_decode((string)file_get_contents(CP_BRAND_FILE), true) : null;
	if (!is_array($d))
		$d = [];
	$out = CP_BRAND_DEFAULTS;
	foreach ($out as $k => $v)
		if (array_key_exists($k, $d))
			$out[$k] = $d[$k];
	return $cache = $out;
}

/** Writes a JSON file atomically. Returns '' or an error message. */
function cp_write_json(string $file, array $data, int $mode = 0664) : string
{
	if (!is_dir(dirname($file)) && !@mkdir(dirname($file), 02775, true))
		return 'Cannot create ' . dirname($file);
	$tmp = $file . '.tmp' . getmypid();
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
	if (false === @file_put_contents($tmp, $json))
		return "Cannot write $file";
	@chmod($tmp, $mode);
	if (!@rename($tmp, $file))
	{
		@unlink($tmp);
		return "Cannot write $file";
	}
	return '';
}

function cp_nfc(string $s) : string
{
	$n = class_exists('Normalizer') ? Normalizer::normalize($s, Normalizer::FORM_C) : $s;
	return false === $n ? $s : $n;
}

/** One line of plain text: tags removed, spaces collapsed, at most $max characters. */
function cp_clean_line($s, int $max) : string
{
	$s = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$s)));
	return cp_nfc(mb_substr($s, 0, $max));
}

/** An http or https address, or ''. */
function cp_clean_url($s) : string
{
	$s = trim((string)$s);
	if ('' === $s)
		return '';
	if (!preg_match('~^https?://[^\s<>"]+$~i', $s) || false === filter_var($s, FILTER_VALIDATE_URL))
		return '';
	return mb_substr($s, 0, 500);
}

/** A link target for the Help page: http(s), mailto, an absolute path, or a path relative to the CQPweb root. */
function cp_clean_link($s) : string
{
	$s = trim((string)$s);
	if ('' === $s || preg_match('/[\s<>"]/', $s) || mb_strlen($s) > 500)
		return '';
	if (preg_match('~^(https?://|mailto:)~i', $s))
		return $s;
	if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $s))
		return '';          /* javascript:, data: and the like */
	return $s;
}

/**
 * Limited HTML for the texts an administrator writes (welcome, footer, notices): the tags
 * a, strong, b, em, i, br, p, span, small and code; links only to http, https, mailto
 * and paths; no other attributes. Everything else is escaped.
 */
function cp_safe_html(string $html, int $max = 4000) : string
{
	$html = cp_nfc(mb_substr($html, 0, $max));
	/* scripts and style sheets go entirely, contents included */
	$html = preg_replace('~<(script|style)\b[^>]*>.*?</\1\s*>~is', '', $html);
	$allowed = ['a', 'strong', 'b', 'em', 'i', 'br', 'p', 'span', 'small', 'code'];
	$parts = preg_split('~(<[^>]*>)~', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
	$out = '';
	$open = [];
	foreach ($parts as $i => $p)
	{
		if (0 == $i % 2)
		{
			$out .= htmlspecialchars(html_entity_decode($p, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
			continue;
		}
		if (!preg_match('~^<\s*(/?)\s*([a-z0-9]+)([^>]*)>$~i', $p, $m) || !in_array(strtolower($m[2]), $allowed, true))
			continue;      /* a tag that is not allowed is dropped */
		$tag = strtolower($m[2]);
		if ('/' === $m[1])
		{
			if ('br' !== $tag && false !== ($k = array_search($tag, array_reverse($open, true), true)))
			{
				/* close everything opened after it, then it */
				while (count($open) > $k)
					$out .= '</' . array_pop($open) . '>';
			}
			continue;
		}
		if ('br' === $tag)
		{
			$out .= '<br>';
			continue;
		}
		$attr = '';
		if ('a' === $tag && preg_match('~\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~i', $m[3], $h))
		{
			$raw = ('' !== ($h[1] ?? '')) ? $h[1] : (('' !== ($h[2] ?? '')) ? $h[2] : ($h[3] ?? ''));
			$href = cp_clean_link(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			if ('' !== $href)
				$attr = ' href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
		}
		$out .= "<$tag$attr>";
		$open[] = $tag;
	}
	while ($open)
		$out .= '</' . array_pop($open) . '>';
	return $out;
}

/** Validates and saves the Personalization settings sent by the admin page. Returns '' or an error. */
function cp_save_brand(array $in) : string
{
	$old = cp_brand();
	$b = $old;
	$b['siteName']  = cp_clean_line($in['siteName'] ?? '', 80);
	$b['ownerName'] = cp_clean_line($in['ownerName'] ?? '', 120);
	$b['ownerUrl']  = cp_clean_url($in['ownerUrl'] ?? '');
	$b['welcome']   = cp_safe_html((string)($in['welcome'] ?? ''), 4000);
	$b['logoAlt']   = cp_clean_line($in['logoAlt'] ?? '', 200);
	$b['logoInvert'] = !empty($in['logoInvert']);
	$b['topBar']    = in_array($in['topBar'] ?? '', ['name', 'logo', 'both'], true) ? $in['topBar'] : 'name';
	$b['footer']    = cp_safe_html((string)($in['footer'] ?? ''), 4000);
	$contact = trim((string)($in['contact'] ?? ''));
	if ('' !== $contact && false === filter_var($contact, FILTER_VALIDATE_EMAIL))
		return 'The contact address is not a valid e-mail address.';
	$b['contact']   = $contact;
	$b['signupNotice'] = cp_nfc(mb_substr(trim((string)($in['signupNotice'] ?? '')), 0, 4000));
	$b['newsTitle'] = cp_clean_line($in['newsTitle'] ?? '', 80);
	$b['newsShow']  = !empty($in['newsShow']);
	$b['queryMode'] = in_array($in['queryMode'] ?? '', ['simple', 'cqp'], true) ? $in['queryMode'] : 'simple';
	$b['queryCase'] = in_array($in['queryCase'] ?? '', ['insensitive', 'sensitive'], true) ? $in['queryCase'] : 'insensitive';
	$b['caseNotice'] = !empty($in['caseNotice']);
	$docs = [];
	foreach ((array)($in['docs'] ?? []) as $corpus => $d)
	{
		if (!preg_match('/^[a-z0-9_]{1,64}$/', (string)$corpus) || !is_array($d))
			continue;
		$file = basename((string)($d['file'] ?? ''));
		if ('' === $file || !preg_match('/^[A-Za-z0-9._ -]+\.html?$/', $file))
			continue;
		$docs[$corpus] = ['file' => $file, 'description' => cp_clean_line($d['description'] ?? '', 300)];
	}
	$b['docs'] = $docs;
	return cp_write_json(CP_BRAND_FILE, $b);
}


/*
 * ======
 * IMAGES
 * ======
 */

/** Detects an image type from its bytes: one of the CP_IMAGE_TYPES keys, or ''. */
function cp_image_type(string $bytes) : string
{
	if (0 === strncmp($bytes, "\x89PNG\r\n\x1a\n", 8))
		return 'png';
	if (0 === strncmp($bytes, "\xff\xd8\xff", 3))
		return 'jpg';
	if (0 === strncmp($bytes, 'GIF87a', 6) || 0 === strncmp($bytes, 'GIF89a', 6))
		return 'gif';
	if (0 === strncmp($bytes, 'RIFF', 4) && 'WEBP' === substr($bytes, 8, 4))
		return 'webp';
	if (0 === strncmp($bytes, "\x00\x00\x01\x00", 4))
		return 'ico';
	$head = ltrim(substr($bytes, 0, 2048), "\xEF\xBB\xBF \t\r\n");
	if (preg_match('~^(<\?xml[^>]*>\s*)?(<!--.*?-->\s*)*(<!DOCTYPE svg[^>]*>\s*)?<svg[\s>]~is', $head))
		return 'svg';
	return '';
}

/** SVG files may not carry scripts, event handlers, foreign objects or outside references. */
function cp_svg_is_safe(string $svg) : bool
{
	return !preg_match('~<script|<foreignObject|\son[a-z]+\s*=|javascript:|<!ENTITY|xlink:href\s*=\s*["\'](?!#)|\shref\s*=\s*["\'](?!#)~i', $svg);
}

/**
 * Stores an uploaded image (sent base64-encoded). The favicon also gives the two touch
 * icons (180 and 152 pixels) when PHP's GD extension is there and the image is not SVG or ICO.
 * Returns '' or an error message.
 */
function cp_brand_upload(string $key, string $b64) : string
{
	if (!isset(CP_BRAND_IMAGES[$key]))
		return 'Unknown image.';
	$bytes = base64_decode($b64, true);
	if (false === $bytes || '' === $bytes)
		return 'The file could not be read.';
	[$label, $max] = CP_BRAND_IMAGES[$key];
	if (strlen($bytes) > $max)
		return "$label: the file is larger than " . round($max / 1048576, 1) . ' MB.';
	$type = cp_image_type($bytes);
	if ('' === $type)
		return "$label: only PNG, JPEG, GIF, WebP, SVG and ICO files can be used.";
	if ('ico' === $type && 'favicon' !== $key)
		return "$label: an ICO file can only be used as the browser icon.";
	if ('svg' === $type && !cp_svg_is_safe($bytes))
		return "$label: this SVG file contains scripts or outside references, which are not allowed.";
	if (!is_dir(CP_BRAND_DIR) && !@mkdir(CP_BRAND_DIR, 02775, true))
		return 'Cannot create ' . CP_BRAND_DIR;

	$b = cp_brand();
	$name = "$key." . $type;
	$tmp = CP_BRAND_DIR . "/$name.tmp" . getmypid();
	if (false === @file_put_contents($tmp, $bytes) || !@rename($tmp, CP_BRAND_DIR . "/$name"))
		return 'Cannot write ' . CP_BRAND_DIR . "/$name";
	/* an older upload of another type */
	if (!empty($b['images'][$key]) && $b['images'][$key] !== $name)
		@unlink(CP_BRAND_DIR . '/' . basename($b['images'][$key]));
	$b['images'][$key] = $name;

	if ('favicon' === $key)
	{
		foreach ([180, 152] as $px)
		{
			@unlink(CP_BRAND_DIR . "/touch-$px.png");
			unset($b['images']["touch$px"]);
		}
		if (in_array($type, ['png', 'jpg', 'gif', 'webp'], true) && function_exists('imagecreatefromstring'))
		{
			$src = @imagecreatefromstring($bytes);
			if ($src)
			{
				foreach ([180, 152] as $px)
				{
					$dst = imagecreatetruecolor($px, $px);
					imagealphablending($dst, false);
					imagesavealpha($dst, true);
					imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
					imagecopyresampled($dst, $src, 0, 0, 0, 0, $px, $px, imagesx($src), imagesy($src));
					if (@imagepng($dst, CP_BRAND_DIR . "/touch-$px.png"))
						$b['images']["touch$px"] = "touch-$px.png";
					imagedestroy($dst);
				}
				imagedestroy($src);
			}
		}
	}
	return cp_write_json(CP_BRAND_FILE, $b);
}

/** Removes an uploaded image (and, for the favicon, its touch icons). Returns '' or an error. */
function cp_brand_remove(string $key) : string
{
	if (!isset(CP_BRAND_IMAGES[$key]))
		return 'Unknown image.';
	$b = cp_brand();
	$gone = [$key];
	if ('favicon' === $key)
		array_push($gone, 'touch180', 'touch152');
	foreach ($gone as $k)
		if (!empty($b['images'][$k]))
		{
			@unlink(CP_BRAND_DIR . '/' . basename($b['images'][$k]));
			unset($b['images'][$k]);
		}
	return cp_write_json(CP_BRAND_FILE, $b);
}

/** The address of an uploaded image, with a version tag; '' if there is none. */
function cp_brand_image_url(string $key) : string
{
	$file = cp_brand()['images'][$key] ?? '';
	if ('' === $file || !is_file(CP_BRAND_DIR . '/' . $file))
		return '';
	return cp_url('cqpweb-new-ui-asset.php?f=brand/' . rawurlencode($file) . '&v=' . filemtime(CP_BRAND_DIR . '/' . $file));
}


/*
 * ==================================================
 * WHAT THE PAGES SHOW (settings, else CQPweb's own, else placeholders)
 * ==================================================
 */

/** The site's name: the one set on the Personalization page, else "CQPweb". */
function cp_site_name() : string
{
	$n = cp_brand()['siteName'];
	return '' !== $n ? $n : 'CQPweb';
}

/** The logo: uploaded, else CQPweb's own home-page logo (not the CWB logo), else the placeholder. */
function cp_logo_url() : string
{
	global $Config;
	if ('' !== ($u = cp_brand_image_url('logo')))
		return $u;
	foreach (['homepage_logo_left', 'homepage_logo_right'] as $key)
	{
		if (empty($Config->$key))
			continue;
		[$img] = explode("\t", (string)$Config->$key, 2);
		if ('' !== trim($img) && !preg_match('~c?wb-logo|ocwb~i', $img))
			return cp_url(trim($img));
	}
	return cp_url('css/cqpweb-new-ui/your-logo-here.svg');
}

/** The class for the logo's <img>: inverted in night mode when the Personalization page says so. */
function cp_logo_class() : string
{
	return cp_brand()['logoInvert'] ? ' cp-logo-invert' : '';
}

function cp_logo_alt() : string
{
	$a = cp_brand()['logoAlt'];
	return '' !== $a ? $a : cp_site_name();
}

/** The home-page welcome text (HTML): the one set here, else CQPweb's own unless it is the stock one, else the template. */
function cp_welcome_html() : string
{
	global $Config;
	$b = cp_brand();
	if ('' !== trim(strip_tags($b['welcome'])))
		return $b['welcome'];
	$cfg = trim((string)($Config->homepage_welcome_message ?? ''));
	if ('' !== $cfg && 'Welcome to CQPweb!' !== $cfg)
		return $cfg;
	$site = escape_html('' !== $b['siteName'] ? $b['siteName'] : CP_PLACEHOLDER_SITE);
	$owner = escape_html('' !== $b['ownerName'] ? $b['ownerName'] : CP_PLACEHOLDER_OWNER);
	if ('' !== $b['ownerUrl'])
		$owner = '<a href="' . escape_html($b['ownerUrl']) . '">' . $owner . '</a>';
	return "<p class=\"cp-welcome-big\">Welcome to $site!</p><p>Created by $owner</p>";
}

/** The contact address: the one set here, else CQPweb's administrator address, else its sender address. */
function cp_contact_address() : string
{
	global $Config;
	$c = cp_brand()['contact'];
	if ('' !== $c)
		return $c;
	foreach (['server_admin_email_address', 'cqpweb_email_from_address'] as $k)
		if (!empty($Config->$k) && filter_var($Config->$k, FILTER_VALIDATE_EMAIL))
			return $Config->$k;
	return '';
}

function cp_news_title() : string
{
	$t = cp_brand()['newsTitle'];
	return '' !== $t ? $t : 'New on ' . cp_site_name();
}

function cp_signup_notice_text() : string
{
	$n = cp_brand()['signupNotice'];
	return null === $n ? 'Create an account on ' . cp_site_name() . ' to use our corpus resources!' : (string)$n;
}

/** The <head> lines for the browser and touch icons. */
function cp_icon_links() : string
{
	$fav = cp_brand_image_url('favicon');
	if ('' === $fav)
	{
		/* CQPweb's own favicon.ico, if the server has one; else the package's */
		$fav = is_file(__DIR__ . '/../../favicon.ico') ? cp_url('favicon.ico') : cp_url('css/cqpweb-new-ui/favicon.svg');
	}
	$out = "\t<link rel=\"icon\" href=\"" . escape_html($fav) . "\">\n";
	$t180 = cp_brand_image_url('touch180');
	if ('' !== $t180)
	{
		$t152 = cp_brand_image_url('touch152');
		$out .= "\t<link rel=\"apple-touch-icon\" href=\"" . escape_html($t180) . "\">\n";
		if ('' !== $t152)
			$out .= "\t<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"" . escape_html($t152) . "\">\n";
	}
	return $out;
}

/** The text and links at the bottom of every page. */
function cp_footer_html() : string
{
	$b = cp_brand();
	$links = '<a href="' . cp_url('help-center.php') . '">Help</a>';
	if ($b['newsShow'] && cp_news_available())
		$links .= ' <span aria-hidden="true">&middot;</span> <a href="' . cp_url('news.php') . '">' . escape_html(cp_news_title()) . '</a>';
	$c = cp_contact_address();
	if ('' !== $c)
		$links .= ' <span aria-hidden="true">&middot;</span> <a href="mailto:' . escape_html($c) . '">Contact</a>';
	$text = '' !== trim(strip_tags($b['footer'])) ? '<div class="cp-footer-text">' . $b['footer'] . '</div>' : '';
	return "\n<footer class=\"cp-footer\">$text<nav class=\"cp-footer-links\" aria-label=\"Help and contact\">$links</nav></footer>\n";
}

function cp_news_available() : bool
{
	return is_readable(CP_TUTORIAL_DIR . '/' . CP_NEWS_FILE);
}

/* the news page: /news.php shows this file from the user pages folder (local-config.php may name another) */
if (!defined('CP_NEWS_FILE'))
	define('CP_NEWS_FILE', 'news.html');


/*
 * ==========
 * HELP PAGE
 * ==========
 */

/* The links the Help page offers from the start. The URL of each can be changed, and each can be hidden. */
const CP_HELP_BUILTIN = [
	'cqpManual'   => ['title' => 'CQP Interface and Query Language Manual',
	                  'description' => 'The complete guide to the CQP query language, by Stephanie Evert and the CWB Development Team (the authors of CQP). PDF.',
	                  'url' => 'https://cwb.sourceforge.io/files/CQP_Manual.pdf'],
	'tutorialEs'  => ['title' => 'CQP syntax tutorial (Spanish)',
	                  'description' => 'A step-by-step introduction to CQP syntax, with examples, in Spanish.',
	                  'url' => 'page.php?name=cqp-syntax-tutorial-es.html'],
	'tutorialEn'  => ['title' => 'CQP syntax tutorial (English)',
	                  'description' => 'A step-by-step introduction to CQP syntax, with examples, in English.',
	                  'url' => 'page.php?name=cqp-syntax-tutorial.html'],
	'videos'      => ['title' => 'Video tutorials',
	                  'description' => 'Videos on CQPweb, CQP and the Corpus Workbench, on the CWB project\'s YouTube channel.',
	                  'url' => 'https://www.youtube.com/@corpusworkbench'],
	'adminManual' => ['title' => 'CQPweb System Administrator\'s Manual',
	                  'description' => 'How to set up, configure and run CQPweb and install corpora. The copy that came with this server\'s CQPweb. PDF.',
	                  'url' => 'doc/CQPwebAdminManual.pdf'],
	'install'     => ['title' => 'Downloading and installing the Corpus Workbench and CQPweb',
	                  'description' => 'The CWB project\'s download and installation page.',
	                  'url' => 'https://cwb.sourceforge.io/install.php'],
	'mailingList' => ['title' => 'CWB mailing list archive',
	                  'description' => 'Questions and answers from CQP and CQPweb users and developers, going back many years.',
	                  'url' => 'http://liste.sslmit.unibo.it/pipermail/cwb/'],
];

/** The Help page settings, with every default filled in. */
function cp_help_settings(bool $reload = false) : array
{
	static $cache = null;
	if (null !== $cache && !$reload)
		return $cache;
	$d = is_file(CP_HELP_FILE) ? json_decode((string)file_get_contents(CP_HELP_FILE), true) : null;
	if (!is_array($d))
		$d = [];
	$out = ['intro' => (string)($d['intro'] ?? ''), 'builtin' => [], 'custom' => []];
	foreach (CP_HELP_BUILTIN as $id => $def)
	{
		$s = (array)($d['builtin'][$id] ?? []);
		$out['builtin'][$id] = [
			'show'        => array_key_exists('show', $s) ? (bool)$s['show'] : true,
			'title'       => (isset($s['title']) && '' !== $s['title']) ? (string)$s['title'] : $def['title'],
			'description' => (isset($s['description']) && '' !== $s['description']) ? (string)$s['description'] : $def['description'],
			'url'         => (isset($s['url']) && '' !== $s['url']) ? (string)$s['url'] : $def['url'],
		];
	}
	foreach ((array)($d['custom'] ?? []) as $c)
		if (is_array($c) && !empty($c['url']))
			$out['custom'][] = ['show' => !array_key_exists('show', $c) || (bool)$c['show'], 'title' => (string)($c['title'] ?? ''),
			                    'description' => (string)($c['description'] ?? ''), 'url' => (string)$c['url']];
	return $cache = $out;
}

function cp_save_help(array $in) : string
{
	$out = ['intro' => cp_safe_html((string)($in['intro'] ?? ''), 2000), 'builtin' => [], 'custom' => []];
	$now = cp_help_settings(true);
	foreach (CP_HELP_BUILTIN as $id => $def)
	{
		/* an entry the request does not mention keeps its present settings */
		$s = isset($in['builtin'][$id]) ? (array)$in['builtin'][$id] : $now['builtin'][$id];
		$row = ['show' => !empty($s['show'])];
		$t = cp_clean_line($s['title'] ?? '', 200);
		$d = cp_clean_line($s['description'] ?? '', 600);
		$u = cp_clean_link($s['url'] ?? '');
		if ('' !== $t && $t !== $def['title'])
			$row['title'] = $t;
		if ('' !== $d && $d !== $def['description'])
			$row['description'] = $d;
		if ('' !== $u && $u !== $def['url'])
			$row['url'] = $u;
		$out['builtin'][$id] = $row;
	}
	foreach ((array)($in['custom'] ?? []) as $i => $c)
	{
		if (!is_array($c))
			continue;
		$u = cp_clean_link($c['url'] ?? '');
		$t = cp_clean_line($c['title'] ?? '', 200);
		$d = cp_clean_line($c['description'] ?? '', 600);
		if ('' === $u && '' === $t && '' === $d)
			continue;          /* an empty row */
		if ('' === $u)
			return 'Added link ' . ($i + 1) . ': the URL is missing or not allowed (use http://, https://, mailto: or a path).';
		if ('' === $t)
			return 'Added link ' . ($i + 1) . ': the title is missing.';
		$out['custom'][] = ['show' => !empty($c['show']), 'title' => $t, 'description' => $d, 'url' => $u];
		if (count($out['custom']) >= 50)
			break;
	}
	return cp_write_json(CP_HELP_FILE, $out);
}

/** The links to show on the Help page, in order: [title, description, url]. */
function cp_help_links() : array
{
	$h = cp_help_settings();
	$out = [];
	foreach ($h['builtin'] as $id => $s)
	{
		if (!$s['show'])
			continue;
		/* the two tutorials only when their files are installed */
		if (in_array($id, ['tutorialEs', 'tutorialEn'], true) && $s['url'] === CP_HELP_BUILTIN[$id]['url']
			&& !is_readable(CP_TUTORIAL_DIR . '/' . ('tutorialEs' === $id ? 'cqp-syntax-tutorial-es.html' : 'cqp-syntax-tutorial.html')))
			continue;
		$out[] = $s;
	}
	foreach ($h['custom'] as $c)
		if ($c['show'])
			$out[] = $c;
	return $out;
}


/*
 * ===================================
 * PUBLIC CORPUS DOCUMENTATION PAGES
 * ===================================
 * Set on the Personalization page: for a corpus, a file from the user pages folder and a
 * one-line description. Shown at documentation.php?corpus=<slug>, where the slug is the
 * corpus's short name in lowercase (Appearance page), or else its handle.
 */

/** slug => ['corpus', 'file', 'description'] for every corpus whose page is set and readable. */
function cp_public_docs() : array
{
	$out = [];
	foreach (cp_brand()['docs'] as $corpus => $d)
	{
		if (!is_readable(CP_TUTORIAL_DIR . '/' . $d['file']))
			continue;
		$short = function_exists('cp_scope_settings') ? cp_scope_settings("corpus:$corpus")['short'] : '';
		$slug = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '-', $short));
		if ('' === trim($slug, '-') || isset($out[$slug]))
			$slug = $corpus;
		$out[$slug] = ['corpus' => $corpus, 'file' => $d['file'], 'description' => $d['description']];
	}
	return $out;
}
