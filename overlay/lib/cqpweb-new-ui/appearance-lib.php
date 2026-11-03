<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the Appearance system.
 * VERSION 1.0.2
 *
 * Color palettes (12 presets, day and night), fonts, the top navigation bar
 * and the day/night switch for every CQPweb page. Settings are site-wide and
 * stored as JSON in CP_DATA_DIR; fonts are self-hosted under CP_FONT_ROOT and
 * served at CP_FONT_URL (see paths.php).
 *
 * This file is NOT part of CQPweb. CQPweb calls it from three small hooks:
 *   lib/html-lib.php   print_header_css_embed()  -> cp_appearance_head()
 *   lib/html-lib.php   print_html_header()       -> cp_navbar()
 *   lib/admin-home-ui.php  menu entry + ui=appearance -> cp_appearance_admin_ui()
 */

const CP_APPEARANCE_VERSION = '1.0.2';

require_once(__DIR__ . '/paths.php');
require_once(__DIR__ . '/brand-lib.php');

const CP_SETTINGS_FILE   = CP_DATA_DIR . '/appearance.json';
const CP_FONT_CSS_NAME   = 'cp-fonts.css';
const CP_FONT_PREFIX     = 'CP ';

const CP_FONT_ROLES = [
	'interface'   => 'Body and interface text',
	'headings'    => 'Headings and title bars',
	'menus'       => 'Left menu and top bar',
	'concordance' => 'Concordance lines',
	'data'        => 'Tables and metadata',
];

const CP_DEFAULT_FONTS = [
	'interface'   => 'Noto Sans',
	'headings'    => 'Crimson Pro',
	'menus'       => 'Noto Sans',
	'concordance' => 'Noto Sans',
	'data'        => 'Noto Sans',
];

/* "Alternate words" in concordances: the corpus's default (each visitor can override it on the concordance page) */
const CP_ALT_WORDS = ['off', 'bg', 'text', 'both'];

/* The 12 color presets: hue (degrees) and saturation (0-1). Palettes are generated from these. */
const CP_PRESETS = [
	'rose'       => ['label' => 'Rose',       'hue' => 340, 'sat' => 0.70],
	'sky'        => ['label' => 'Sky blue',   'hue' => 210, 'sat' => 0.75],
	'sunflower'  => ['label' => 'Sunflower',  'hue' =>  46, 'sat' => 0.92],
	'fern'       => ['label' => 'Fern green', 'hue' => 140, 'sat' => 0.55],
	'teal'       => ['label' => 'Teal',       'hue' => 182, 'sat' => 0.60],
	'indigo'     => ['label' => 'Indigo',     'hue' => 235, 'sat' => 0.60],
	'violet'     => ['label' => 'Violet',     'hue' => 272, 'sat' => 0.55],
	'terracotta' => ['label' => 'Terracotta', 'hue' =>  14, 'sat' => 0.65],
	'plum'       => ['label' => 'Plum',       'hue' => 305, 'sat' => 0.42],
	'olive'      => ['label' => 'Olive',      'hue' =>  72, 'sat' => 0.45],
	'sand'       => ['label' => 'Sand',       'hue' =>  33, 'sat' => 0.35],
	'slate'      => ['label' => 'Slate gray', 'hue' => 215, 'sat' => 0.14],
];

/* Every color token, with its label in the Appearance page. */
const CP_TOKENS = [
	'bg'          => 'Page background',
	'text'        => 'Body text',
	'muted'       => 'Secondary text',
	'surface'     => 'Cards and table cells',
	'surface2'    => 'Alternate cells and current menu item',
	'border'      => 'Lines and borders',
	'strong'      => 'Title bars',
	'strongText'  => 'Text on title bars',
	'contrast'    => 'Highlighted cells',
	'sidebar'     => 'Left menu',
	'sidebarText' => 'Left menu text',
	'nav'         => 'Top bar',
	'navText'     => 'Top bar text',
	'accent'      => 'Buttons and active items',
	'accentText'  => 'Text on buttons',
	'hit'         => 'Search-hit highlight',
	'hitText'     => 'Text in highlight',
	'link'        => 'Links',
];

/* Pairs shown in the Appearance page: [background, foreground, minimum ratio]. */
const CP_PAIRS = [
	['bg',       'text',        7.0],
	['surface',  'text',        7.0],
	['surface',  'muted',       4.5],
	['surface',  'link',        4.5],
	['strong',   'strongText',  7.0],
	['contrast', 'text',        7.0],
	['sidebar',  'sidebarText', 7.0],
	['nav',      'navText',     7.0],
	['accent',   'accentText',  4.5],
	['hit',      'hitText',     7.0],
];

/* Google Fonts families offered by "Install the recommended fonts". */
const CP_RECOMMENDED_FONTS = [
	/* sans serif */
	'Inter' => 'sans', 'Noto Sans' => 'sans', 'Source Sans 3' => 'sans', 'Open Sans' => 'sans', 'Lato' => 'sans',
	'Roboto' => 'sans', 'Work Sans' => 'sans', 'Fira Sans' => 'sans', 'IBM Plex Sans' => 'sans',
	'Atkinson Hyperlegible' => 'sans', 'Lexend' => 'sans', 'Nunito Sans' => 'sans', 'PT Sans' => 'sans',
	'Public Sans' => 'sans', 'Manrope' => 'sans', 'Figtree' => 'sans', 'DM Sans' => 'sans',
	'Libre Franklin' => 'sans', 'Barlow' => 'sans', 'Overpass' => 'sans', 'Raleway' => 'sans',
	'Montserrat' => 'sans', 'Mulish' => 'sans', 'Karla' => 'sans', 'Rubik' => 'sans', 'Archivo' => 'sans',
	'Red Hat Text' => 'sans', 'Golos Text' => 'sans', 'Titillium Web' => 'sans', 'Exo 2' => 'sans',
	/* serif */
	'Crimson Pro' => 'serif', 'Lusitana' => 'serif', 'Charis SIL' => 'serif', 'EB Garamond' => 'serif',
	'Source Serif 4' => 'serif', 'Literata' => 'serif', 'Merriweather' => 'serif', 'Lora' => 'serif',
	'PT Serif' => 'serif', 'Libre Baskerville' => 'serif', 'Gentium Book Plus' => 'serif', 'Alegreya' => 'serif',
	'Vollkorn' => 'serif', 'Newsreader' => 'serif', 'Fraunces' => 'serif', 'Spectral' => 'serif',
	'Cormorant Garamond' => 'serif', 'Playfair Display' => 'serif', 'IBM Plex Serif' => 'serif',
	'Noto Serif' => 'serif', 'Libre Caslon Text' => 'serif', 'Piazzolla' => 'serif', 'Bitter' => 'serif',
	'Domine' => 'serif', 'Zilla Slab' => 'serif', 'Roboto Slab' => 'serif',
	/* monospace */
	'IBM Plex Mono' => 'mono', 'JetBrains Mono' => 'mono', 'Source Code Pro' => 'mono', 'Fira Code' => 'mono',
	'Roboto Mono' => 'mono', 'Noto Sans Mono' => 'mono', 'Overpass Mono' => 'mono', 'Space Mono' => 'mono',
	'Inconsolata' => 'mono', 'Courier Prime' => 'mono', 'DM Mono' => 'mono', 'Red Hat Mono' => 'mono',
];


/*
 * ======
 * COLOR
 * ======
 */

function cp_hsl_to_hex(float $h, float $s, float $l) : string
{
	$h = fmod(fmod($h, 360) + 360, 360) / 360;
	$s = max(0, min(1, $s));
	$l = max(0, min(1, $l));
	$f = function (int $n) use ($h, $s, $l) : string {
		$k = fmod($n + $h * 12, 12);
		$a = $s * min($l, 1 - $l);
		$c = $l - $a * max(-1, min($k - 3, 9 - $k, 1));
		return str_pad(dechex((int)round($c * 255)), 2, '0', STR_PAD_LEFT);
	};
	return '#' . $f(0) . $f(8) . $f(4);
}

function cp_hex_to_rgb(string $hex) : array
{
	$n = hexdec(ltrim($hex, '#'));
	return [($n >> 16) & 255, ($n >> 8) & 255, $n & 255];
}

function cp_hex_to_hsl(string $hex) : array
{
	[$r, $g, $b] = array_map(fn($v) => $v / 255, cp_hex_to_rgb($hex));
	$max = max($r, $g, $b);
	$min = min($r, $g, $b);
	$l = ($max + $min) / 2;
	$h = $s = 0.0;
	if ($max != $min)
	{
		$d = $max - $min;
		$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
		if ($max == $r)
			$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
		else if ($max == $g)
			$h = ($b - $r) / $d + 2;
		else
			$h = ($r - $g) / $d + 4;
		$h *= 60;
	}
	return [$h, $s, $l];
}

function cp_luminance(string $hex) : float
{
	$c = array_map(function ($v) {
		$v /= 255;
		return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
	}, cp_hex_to_rgb($hex));
	return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function cp_contrast(string $a, string $b) : float
{
	$x = cp_luminance($a);
	$y = cp_luminance($b);
	return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

/** Moves the foreground's lightness away from the background until the ratio is reached. */
function cp_ensure_contrast(string $fg, string $bg, float $target) : string
{
	if (cp_contrast($fg, $bg) >= $target)
		return $fg;
	[$h, $s, $l] = cp_hex_to_hsl($fg);
	$darker = cp_luminance($bg) > 0.18;
	for ($i = 0; $i < 100; $i++)
	{
		$l = $darker ? $l - 0.01 : $l + 0.01;
		if ($l <= 0 || $l >= 1)
			break;
		$c = cp_hsl_to_hex($h, $s, $l);
		if (cp_contrast($c, $bg) >= $target)
			return $c;
	}
	return $darker ? '#000000' : '#ffffff';
}

/** Generates the full token set for a preset in one mode ('day' or 'night'). */
function cp_palette(string $preset, string $mode) : array
{
	$p = CP_PRESETS[$preset] ?? CP_PRESETS['sky'];
	$h = $p['hue'];
	$s = $p['sat'];
	$H = fn(float $sat, float $light, ?float $hue = null) => cp_hsl_to_hex($hue ?? $h, $sat, $light);

	if ('night' == $mode)
	{
		$t = [
			'bg'          => $H($s * 0.30, 0.065),
			'surface'     => $H($s * 0.24, 0.115),
			'surface2'    => $H($s * 0.26, 0.165),
			'border'      => $H($s * 0.20, 0.27),
			'strong'      => $H($s * 0.40, 0.23),
			'contrast'    => $H(0.45, 0.19, 42),
			'sidebar'     => $H($s * 0.35, 0.045),
			'nav'         => $H($s * 0.35, 0.035),
			'accent'      => $H(min(1, $s * 0.85), 0.72),
			'hit'         => $H(0.75, 0.30, 44),
			'text'        => $H($s * 0.12, 0.95),
			'muted'       => $H($s * 0.10, 0.76),
			'strongText'  => $H($s * 0.20, 0.96),
			'sidebarText' => $H($s * 0.18, 0.92),
			'navText'     => $H($s * 0.18, 0.95),
			'accentText'  => $H($s * 0.50, 0.08),
			'hitText'     => $H(0.90, 0.95, 48),
			'link'        => $H(min(1, $s * 0.80), 0.78),
		];
	}
	else
	{
		$t = [
			'bg'          => $H($s * 0.55, 0.960),
			'surface'     => $H($s * 0.60, 0.990),
			'surface2'    => $H($s * 0.75, 0.920),
			'border'      => $H($s * 0.35, 0.830),
			'strong'      => $H(min(1, $s * 0.90), 0.840),
			'contrast'    => $H(0.90, 0.880, 46),
			'sidebar'     => $H($s * 0.55, 0.215),
			'nav'         => $H($s * 0.55, 0.170),
			'accent'      => $H(min(1, $s * 0.85), 0.330),
			'hit'         => $H(0.95, 0.700, 48),
			'text'        => $H($s * 0.25, 0.120),
			'muted'       => $H($s * 0.15, 0.360),
			'strongText'  => $H($s * 0.40, 0.140),
			'sidebarText' => $H($s * 0.30, 0.930),
			'navText'     => $H($s * 0.30, 0.960),
			'accentText'  => '#ffffff',
			'hitText'     => $H(0.80, 0.120, 40),
			'link'        => $H(min(1, $s * 0.80), 0.320),
		];
		/* yellow hues stay legible only with darker accents and links; their left menu is light
		 * (a dark yellow is brown, which would not read as a yellow scheme) */
		if ($h >= 35 && $h <= 80)
		{
			if ($s >= 0.8)
			{
				$t['sidebar']     = $H(min(1, $s), 0.700);
				$t['sidebarText'] = $H($s * 0.60, 0.120, $h - 8);
				$t['strong']      = $H(min(1, $s), 0.760);
			}
			$t['accent'] = $H(min(1, $s * 0.85), 0.26);
			$t['link']   = $H(min(1, $s * 0.80), 0.25);
			$t['hit']    = $H(0.85, 0.72, 200);
			$t['hitText']= $H(0.80, 0.12, 210);
		}
	}
	return cp_fix_palette($t);
}

/** Enforces the minimum ratios of CP_PAIRS on a palette (foregrounds move, backgrounds stay). */
function cp_fix_palette(array $t) : array
{
	foreach (CP_PAIRS as [$bg, $fg, $min])
		$t[$fg] = cp_ensure_contrast($t[$fg], $t[$bg], $min);
	/* links must also read on the page background */
	$t['link'] = cp_ensure_contrast($t['link'], $t['bg'], 4.5);
	$t['muted'] = cp_ensure_contrast($t['muted'], $t['bg'], 4.5);
	return $t;
}


/*
 * ========
 * SETTINGS
 * ========
 */

/** Default preset for a scope: rose for the admin pages, sky blue everywhere else. */
function cp_default_preset(string $scope) : string
{
	return 'admin' == $scope ? 'rose' : 'sky';
}

/** Default short name for a corpus: the part of its handle before the first underscore, upper-cased. */
function cp_default_short_name(string $corpus) : string
{
	return strtoupper(explode('_', $corpus)[0]);
}

function cp_load_settings() : array
{
	static $cache = null;
	if (null !== $cache)
		return $cache;
	$data = null;
	if (is_file(CP_SETTINGS_FILE))
		$data = json_decode((string)file_get_contents(CP_SETTINGS_FILE), true);
	if (!is_array($data))
		$data = [];
	$data['scopes'] = $data['scopes'] ?? [];
	return $cache = $data;
}

/** Settings for one scope, with every default filled in. */
function cp_scope_settings(string $scope) : array
{
	$all = cp_load_settings();
	$s = $all['scopes'][$scope] ?? [];
	$preset = $s['preset'] ?? cp_default_preset($scope);
	if (!isset(CP_PRESETS[$preset]))
		$preset = cp_default_preset($scope);
	$out = [
		'preset' => $preset,
		'custom' => ['day' => [], 'night' => []],
		'fonts'  => CP_DEFAULT_FONTS,
	];
	foreach (['day', 'night'] as $m)
		foreach ((array)($s['custom'][$m] ?? []) as $k => $v)
			if (isset(CP_TOKENS[$k]) && preg_match('/^#[0-9a-f]{6}$/i', $v))
				$out['custom'][$m][$k] = strtolower($v);
	foreach (CP_FONT_ROLES as $r => $label)
		if (!empty($s['fonts'][$r]) && is_string($s['fonts'][$r]))
			$out['fonts'][$r] = $s['fonts'][$r];
	if (0 === strpos($scope, 'corpus:'))
	{
		$out['short'] = (isset($s['short']) && '' !== trim($s['short'])) ? trim($s['short']) : cp_default_short_name(substr($scope, 7));
		$out['card'] = (isset($s['card']) && is_string($s['card'])) ? trim($s['card']) : '';
		$out['altWords'] = in_array($s['altWords'] ?? '', CP_ALT_WORDS, true) ? $s['altWords'] : 'off';
	}
	return $out;
}

/** The finished palette for a scope and mode: the preset, then any custom colors. */
function cp_scope_palette(string $scope, string $mode) : array
{
	$s = cp_scope_settings($scope);
	return array_merge(cp_palette($s['preset'], $mode), $s['custom'][$mode]);
}

/** Validates and writes a complete settings array. Returns '' on success, else an error message. */
function cp_save_settings(array $new) : string
{
	$clean = ['version' => 1, 'scopes' => []];
	foreach ((array)($new['scopes'] ?? []) as $scope => $s)
	{
		if (!preg_match('/^(home|user|admin|corpus:[a-z0-9_]{1,64})$/', $scope))
			continue;
		$c = [];
		if (isset($s['preset']) && isset(CP_PRESETS[$s['preset']]))
			$c['preset'] = $s['preset'];
		foreach (['day', 'night'] as $m)
			foreach ((array)($s['custom'][$m] ?? []) as $k => $v)
				if (isset(CP_TOKENS[$k]) && is_string($v) && preg_match('/^#[0-9a-f]{6}$/i', $v))
					$c['custom'][$m][$k] = strtolower($v);
		foreach (CP_FONT_ROLES as $r => $label)
			if (isset($s['fonts'][$r]) && is_string($s['fonts'][$r]) && preg_match('/^[A-Za-z0-9 \-]{1,80}$/', $s['fonts'][$r]))
				$c['fonts'][$r] = $s['fonts'][$r];
		if (0 === strpos($scope, 'corpus:') && isset($s['short']) && is_string($s['short']))
		{
			$short = trim(strip_tags($s['short']));
			if ('' !== $short && mb_strlen($short) <= 40)
				$c['short'] = Normalizer::normalize($short, Normalizer::FORM_C);
		}
		if (0 === strpos($scope, 'corpus:') && in_array($s['altWords'] ?? '', CP_ALT_WORDS, true))
			$c['altWords'] = $s['altWords'];
		if (0 === strpos($scope, 'corpus:') && isset($s['card']) && is_string($s['card']))
		{
			/* the description on the home-page card; empty means "use the corpus title" */
			$card = trim(preg_replace('/\s+/u', ' ', strip_tags($s['card'])));
			if ('' !== $card && mb_strlen($card) <= 400)
				$c['card'] = Normalizer::normalize($card, Normalizer::FORM_C);
		}
		$clean['scopes'][$scope] = $c;
	}
	if (!is_dir(CP_DATA_DIR) && !@mkdir(CP_DATA_DIR, 02775, true))
		return 'Cannot create ' . CP_DATA_DIR;
	$json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$tmp = CP_SETTINGS_FILE . '.tmp' . getmypid();
	if (false === @file_put_contents($tmp, $json . "\n") || !@rename($tmp, CP_SETTINGS_FILE))
		return 'Cannot write ' . CP_SETTINGS_FILE;
	return '';
}


/*
 * =====
 * FONTS
 * =====
 */

const CP_WEIGHTS = [
	'Thin' => 100, 'Hairline' => 100, 'ExtraLight' => 200, 'UltraLight' => 200, 'Light' => 300,
	'Regular' => 400, 'Book' => 400, 'Medium' => 500, 'SemiBold' => 600, 'DemiBold' => 600,
	'Bold' => 700, 'ExtraBold' => 800, 'UltraBold' => 800, 'Black' => 900, 'Heavy' => 900,
];

/** "BarlowSemiCondensed" -> "Barlow Semi Condensed"; "EBGaramond" -> "EB Garamond"; "SourceSans3" -> "Source Sans 3". */
function cp_split_family_name(string $s) : string
{
	$s = preg_replace('/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])|(?<=[A-Za-z])(?=[0-9])/', ' ', $s);
	return trim(preg_replace('/\s+/', ' ', $s));
}

/** Guesses sans / serif / mono from a family name. */
function cp_font_kind(string $name) : string
{
	if (isset(CP_RECOMMENDED_FONTS[$name]))
		return CP_RECOMMENDED_FONTS[$name];
	if (preg_match('/Mono|Code|Courier|Consol/i', $name))
		return 'mono';
	if (preg_match('/Serif|Garamond|Literata|Merriweather|Vollkorn|Gelasio|Caslon|Bitter|Gentium|Lusitana|Crimson|Charis|Andada|Alegreya(?! Sans)|Lora|Baskerville|Spectral|Newsreader|Fraunces|Playfair|Domine|Slab|Piazzolla|Cormorant|Linux Libertine/i', $name))
		return 'serif';
	return 'sans';
}

/** Families written only for non-Latin scripts are not offered. */
function cp_font_is_offered(string $name) : bool
{
	return !preg_match('/Arabic|Naskh|Devanagari|Hebrew|Bengali|Tamil|Thai|CJK|Japanese|Korean/i', $name);
}

/** Parses a static font file name into [family, weight, style]. Null if not a font. */
function cp_parse_font_file(string $file) : ?array
{
	if (!preg_match('/^(.+)\.(woff2|ttf|otf|woff)$/i', $file, $m))
		return null;
	$stem = $m[1];
	$variable = false;
	if (preg_match('/^(.+?)[-_](Italic[-_])?Variable(Font)?(_.*)?$/i', $stem, $v))
	{
		$variable = true;
		$stem = $v[1] . (empty($v[2]) ? '' : '-Italic');
	}
	$italic = false;
	$weight = 400;
	if (preg_match('/^(.+)-([A-Za-z]+)$/', $stem, $p))
	{
		$tail = $p[2];
		$known = false;
		if (preg_match('/^(.*?)Italic$/', $tail, $it))
		{
			$italic = true;
			$tail = $it[1];
			$known = true;
		}
		if ('' === $tail)
			$known = true;
		else if (isset(CP_WEIGHTS[$tail]))
		{
			$weight = CP_WEIGHTS[$tail];
			$known = true;
		}
		if ($known)
			$stem = $p[1];
	}
	return [
		'family' => cp_split_family_name($stem),
		'weight' => $variable ? '100 900' : (string)$weight,
		'style'  => $italic ? 'italic' : 'normal',
		'format' => strtolower($m[2]),
	];
}

/**
 * Scans the font folders. Returns [family name => ['kind', 'source', 'css']].
 * library/ and <folder>/<family>/ hold static files; google/<slug>/ holds a ready
 * family.css (written by cp_google_install()) and meta.json.
 */
function cp_scan_fonts() : array
{
	$fam = [];
	$faces = [];   /* family => "weight|style" => [format => url] */

	/* library/ holds font files directly; any other folder (other than google/) holds one
	 * folder per family, with font files added by hand */
	$dirs = [];
	if (is_dir(CP_FONT_ROOT . '/library'))
		$dirs[CP_FONT_ROOT . '/library'] = 'Font library';
	foreach ((array)glob(CP_FONT_ROOT . '/*', GLOB_ONLYDIR) as $top)
		if (!in_array(basename($top), ['library', 'google'], true))
			foreach ((array)glob($top . '/*', GLOB_ONLYDIR) as $d)
				$dirs[$d] = 'Added by hand';

	foreach ($dirs as $dir => $source)
	{
		foreach ((array)scandir($dir) as $f)
		{
			if (!($p = cp_parse_font_file($f)))
				continue;
			$name = $p['family'];
			if ('Charis' == $name)
				$name = 'Charis SIL';
			$url = CP_FONT_URL . substr($dir, strlen(CP_FONT_ROOT)) . '/' . rawurlencode($f);
			$faces[$name][$p['weight'] . '|' . $p['style']][$p['format']] = $url;
			if (!isset($fam[$name]))
				$fam[$name] = ['kind' => cp_font_kind($name), 'source' => $source, 'css' => ''];
		}
	}

	$fmt = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'];
	foreach ($faces as $name => $list)
	{
		$css = '';
		foreach ($list as $key => $urls)
		{
			[$w, $st] = explode('|', $key);
			$src = [];
			foreach ($fmt as $ext => $format)
				if (isset($urls[$ext]))
					$src[] = "url(\"{$urls[$ext]}\") format(\"$format\")";
			$css .= "@font-face{font-family:\"" . CP_FONT_PREFIX . "$name\";src:" . implode(',', $src)
				. ";font-weight:$w;font-style:$st;font-display:swap}\n";
		}
		$fam[$name]['css'] = $css;
	}

	/* Google Fonts downloads override static copies of the same family (they carry unicode ranges). */
	foreach ((array)glob(CP_FONT_ROOT . '/google/*/meta.json') as $mf)
	{
		$meta = json_decode((string)file_get_contents($mf), true);
		$cssf = dirname($mf) . '/family.css';
		if (!is_array($meta) || empty($meta['name']) || !is_file($cssf))
			continue;
		$fam[$meta['name']] = [
			'kind'   => $meta['kind'] ?? cp_font_kind($meta['name']),
			'source' => 'Google Fonts, downloaded',
			/* the addresses in family.css follow CP_FONT_URL, wherever the files were first installed */
			'css'    => preg_replace('~url\("[^"]*?/google/~', 'url("' . CP_FONT_URL . '/google/', (string)file_get_contents($cssf)),
		];
	}

	$fam = array_filter($fam, fn($k) => cp_font_is_offered($k), ARRAY_FILTER_USE_KEY);
	ksort($fam, SORT_NATURAL | SORT_FLAG_CASE);
	return $fam;
}

/** Rewrites CP_FONT_ROOT/cp-fonts.css from the font folders. Returns '' or an error. */
function cp_rebuild_font_css() : string
{
	$css = "/* cqpweb-new-ui: every installed font. Written by the Appearance page; do not edit. */\n";
	foreach (cp_scan_fonts() as $f)
		$css .= $f['css'];
	$target = CP_FONT_ROOT . '/' . CP_FONT_CSS_NAME;
	$tmp = $target . '.tmp' . getmypid();
	if (false === @file_put_contents($tmp, $css) || !@rename($tmp, $target))
		return "Cannot write $target";
	return '';
}

function cp_font_stack(string $name) : string
{
	static $fams = null;
	$kind = cp_font_kind($name);
	$fallback = ['mono' => 'ui-monospace, "Courier New", monospace', 'serif' => 'Georgia, "Times New Roman", serif', 'sans' => 'system-ui, "Segoe UI", Arial, sans-serif'][$kind];
	$name = str_replace(['"', '\\'], '', $name);
	return '"' . CP_FONT_PREFIX . $name . '", ' . $fallback;
}

/**
 * Downloads one Google Fonts family into CP_FONT_ROOT/google/<slug>/.
 * Only the Latin and Latin Extended subsets are kept. Returns '' or an error message.
 */
function cp_google_install(string $name, ?string $kind = null) : string
{
	$name = trim(preg_replace('/\s+/', ' ', $name));
	if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ]{0,60}$/', $name))
		return 'That is not a valid family name.';
	$slug = strtolower(str_replace(' ', '-', $name));
	$dir = CP_FONT_ROOT . '/google/' . $slug;
	if (is_file("$dir/family.css"))
		return '';

	$ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
	$ctx = stream_context_create(['http' => ['header' => "User-Agent: $ua\r\n", 'timeout' => 20, 'ignore_errors' => true]]);
	$fam = str_replace(' ', '+', $name);
	/* Variable families first (the widest weight range Google accepts), then the usual static styles. */
	$specs = [];
	foreach ([[100, 900], [200, 900], [300, 900], [100, 800], [200, 800], [300, 800], [400, 900], [400, 800], [300, 700], [400, 700], [500, 900]] as [$lo, $hi])
	{
		$specs[] = ":ital,wght@0,$lo..$hi;1,$lo..$hi";
		$specs[] = ":wght@$lo..$hi";
	}
	array_push($specs, ':ital,wght@0,400;0,700;1,400;1,700', ':ital@0;1', ':wght@400;700', '');
	$css = null;
	foreach ($specs as $spec)
	{
		$body = @file_get_contents("https://fonts.googleapis.com/css2?family=$fam$spec&display=swap", false, $ctx);
		$status = $http_response_header[0] ?? '';
		if (false !== $body && false !== strpos($status, ' 200') && false !== strpos($body, '@font-face'))
		{
			$css = $body;
			break;
		}
	}
	if (null === $css)
		return "Google Fonts has no family called \"$name\" (check the spelling and capitals).";

	if (!preg_match_all('~/\*\s*([a-z\-]+)\s*\*/\s*(@font-face\s*\{[^}]*\})~', $css, $blocks, PREG_SET_ORDER))
		preg_match_all('~()(@font-face\s*\{[^}]*\})~', $css, $blocks, PREG_SET_ORDER);

	if (!is_dir($dir) && !@mkdir($dir, 02775, true))
		return "Cannot create $dir";

	$out = '';
	$n = 0;
	foreach ($blocks as $b)
	{
		$subset = $b[1];
		if ('' !== $subset && !in_array($subset, ['latin', 'latin-ext'], true))
			continue;
		$block = $b[2];
		if (!preg_match('~url\((https://fonts\.gstatic\.com/[^)\s]+)\)~', $block, $u))
			continue;
		preg_match('/font-style:\s*(\w+)/', $block, $st);
		preg_match('/font-weight:\s*([0-9 ]+);/', $block, $wt);
		$file = sprintf('%s-%s-%s-%s.woff2', $slug, $st[1] ?? 'normal', str_replace(' ', '_', trim($wt[1] ?? '400')), $subset ?: 'all');
		$data = @file_get_contents($u[1], false, $ctx);
		if (false === $data || strlen($data) < 100)
			return "Download failed for one of the files of $name.";
		if (false === @file_put_contents("$dir/$file", $data))
			return "Cannot write $dir/$file";
		$block = str_replace($u[0], 'url("' . CP_FONT_URL . "/google/$slug/$file" . '")', $block);
		$block = preg_replace("/font-family:\s*'[^']*'/", 'font-family:"' . CP_FONT_PREFIX . $name . '"', $block);
		$out .= preg_replace('/\s+/', ' ', $block) . "\n";
		$n++;
	}
	if (0 == $n)
		return "Google Fonts sent no Latin files for $name.";
	file_put_contents("$dir/family.css", $out);
	file_put_contents("$dir/meta.json", json_encode(['name' => $name, 'kind' => $kind ?? cp_font_kind($name), 'files' => $n, 'installed' => date('c')]));
	return '';
}


/*
 * ================
 * PAGE INTEGRATION
 * ================
 */

/** Which settings scope the current request belongs to. */
function cp_current_scope() : string
{
	global $Config, $Corpus;
	/* a page that says which palette it uses (page.php: the user pages) */
	if (defined('CP_SCOPE_OVERRIDE'))
		return CP_SCOPE_OVERRIDE;
	/* embedded pages (tutorials, documentation) always use the user-pages palette */
	if (in_array($_GET['ui'] ?? '', ['embed', 'cpTutorialEn', 'cpTutorialEs'], true) && in_array($Config->run_location ?? 0, [RUN_LOCATION_CORPUS, RUN_LOCATION_USR]))
		return 'user';
	switch ($Config->run_location ?? 0)
	{
	case RUN_LOCATION_ADM:
		return 'admin';
	case RUN_LOCATION_USR:
		return 'user';
	case RUN_LOCATION_CORPUS:
		if (isset($Corpus) && !empty($Corpus->name) && preg_match('/^[a-z0-9_]+$/', $Corpus->name))
			return 'corpus:' . $Corpus->name;
		return 'home';
	default:
		return 'home';
	}
}

function cp_css_vars(array $palette) : string
{
	$out = '';
	foreach ($palette as $k => $v)
		$out .= '--cp-' . strtolower(preg_replace('/([A-Z])/', '-$1', $k)) . ":$v;";
	return $out;
}

/** The <head> additions for every CQPweb page: palettes, fonts, skin, day/night switch. */
function cp_appearance_head() : string
{
	$scope = cp_current_scope();
	$s = cp_scope_settings($scope);
	$day = cp_css_vars(cp_scope_palette($scope, 'day'));
	$night = cp_css_vars(cp_scope_palette($scope, 'night'));
	$fonts = '';
	foreach (CP_FONT_ROLES as $r => $label)
		$fonts .= "--cp-font-$r:" . cp_font_stack($s['fonts'][$r]) . ';';

	$prefix = get_url_prefix();
	$b = cp_brand();
	$case_default = 'sensitive' === $b['queryCase'] ? '1' : '0';
	$case_notice = $b['caseNotice'] ? '1' : '0';
	$icons = cp_icon_links();
	$skin_v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/skin.css') ?: 0;
	$js_v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/appearance.js') ?: 0;
	$site_v = @filemtime(__DIR__ . '/../../css/cqpweb-new-ui/site.js') ?: 0;
	$font_v = @filemtime(CP_FONT_ROOT . '/' . CP_FONT_CSS_NAME) ?: 0;
	$font_href = cp_url(CP_FONT_URL . '/' . CP_FONT_CSS_NAME) . (false === strpos(CP_FONT_URL, '?') ? '?' : '&') . "v=$font_v";
	$font_link = $font_v ? "\t<link rel=\"stylesheet\" href=\"$font_href\">\n" : '';
	$alt = $s['altWords'] ?? 'off';

	return <<<END_HTML

	<!-- cqpweb-new-ui appearance ($scope) -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>(function(){var m=null;try{m=localStorage.getItem('cpMode');}catch(e){}if(m!=='day'&&m!=='night'){m=(window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)?'night':'day';}document.documentElement.setAttribute('data-cp-mode',m);})();</script>
	<style>:root{{$day}{$fonts}}html[data-cp-mode="night"]{{$night}}</style>
	<link rel="stylesheet" href="{$prefix}css/cqpweb-new-ui/skin.css?v=$skin_v">
$font_link$icons	<meta name="cp-alt-words" content="$alt">
	<meta name="cp-root" content="$prefix">
	<meta name="cp-case-default" content="$case_default">
	<meta name="cp-case-notice" content="$case_notice">
	<script defer src="{$prefix}css/cqpweb-new-ui/appearance.js?v=$js_v"></script>
	<script defer src="{$prefix}css/cqpweb-new-ui/site.js?v=$site_v"></script>

END_HTML;
}

/** The top bar: home, back to the corpus, help, account, admin, day/night switch. */
function cp_navbar() : string
{
	global $Config, $Corpus, $User;

	$prefix = get_url_prefix();
	$home = ('' === $prefix) ? './' : $prefix;
	$loc = $Config->run_location ?? 0;

	$site = escape_html(cp_site_name());
	$style = cp_brand()['topBar'];
	$brand = '';
	if ('name' !== $style)
		$brand .= '<img class="cp-brand-logo' . cp_logo_class() . '" src="' . escape_html(cp_logo_url()) . '" alt="' . ('logo' === $style ? escape_html(cp_logo_alt()) : '') . '">';
	if ('logo' !== $style)
		$brand .= "<span class=\"cp-brand-name\">$site</span>";
	$left = "<a class=\"cp-brand\" href=\"$home\" aria-label=\"$site home page\">$brand</a>";
	$left .= "<a class=\"cp-navbtn\" href=\"$home\">Homepage</a>";

	if (RUN_LOCATION_CORPUS == $loc && isset($Corpus) && !empty($Corpus->name))
	{
		$short = escape_html(cp_scope_settings('corpus:' . $Corpus->name)['short']);
		$left .= "<a class=\"cp-navbtn cp-navbtn-corpus\" href=\"{$prefix}{$Corpus->name}/\"><span aria-hidden=\"true\">&larr;</span> Back to $short</a>";
	}

	$right = '';
	$logged_in = isset($User) && $User->logged_in;
	if ($logged_in && method_exists($User, 'is_admin') && $User->is_admin())
		$right .= "<a class=\"cp-navlink\" href=\"{$prefix}adm/\">Admin</a>";
	if ($logged_in)
		$right .= "<a class=\"cp-navlink\" href=\"{$prefix}usr/\">Your account</a>";
	else
		$right .= "<a class=\"cp-navlink\" href=\"{$prefix}usr/\">Log in or sign up</a>";
	$right .= '<button type="button" class="cp-mode" id="cpModeSwitch" aria-label="Switch between day and night mode" title="Day / night">'
		. '<svg class="cp-icon-moon" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>'
		. '<svg class="cp-icon-sun" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>'
		. '</button>';

	require_once(__DIR__ . '/site-lib.php');
	return "\n<header class=\"cp-topbar\"><div class=\"cp-topbar-left\">$left</div><div class=\"cp-topbar-right\">$right</div></header>\n"
		. cp_maintenance_banner() . cp_query_notice();
}
