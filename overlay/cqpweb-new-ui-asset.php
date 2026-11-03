<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: serves the package's own files.
 * VERSION 1.0.2
 *
 *   cqpweb-new-ui-asset.php?f=fonts/<path>   a font or cp-fonts.css from CP_FONT_ROOT
 *   cqpweb-new-ui-asset.php?f=pages/<path>   an image, PDF or stylesheet from the user pages folder
 *   cqpweb-new-ui-asset.php?f=brand/<name>   the uploaded logo, favicon or touch icon
 *
 * Only the file types listed below are served, and never anything outside those three
 * folders. With "&v=..." in the address the browser may keep the file for a year.
 * This file does not start CQPweb, so it costs almost nothing per request.
 */

require(__DIR__ . '/lib/cqpweb-new-ui/paths.php');

const CP_ASSET_TYPES = [
	'woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
	'css'   => 'text/css; charset=utf-8',
	'png'   => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
	'webp'  => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
	'pdf'   => 'application/pdf',
];

function cp_asset_fail(int $code) : void
{
	http_response_code($code);
	header('Content-Type: text/plain; charset=utf-8');
	echo 404 == $code ? "Not found.\n" : "Not allowed.\n";
	exit;
}

$f = (string)($_GET['f'] ?? '');
if (!preg_match('~^(fonts|pages|brand)/([A-Za-z0-9._/ -]{1,300})$~', $f, $m) || false !== strpos($m[2], '..'))
	cp_asset_fail(404);

$base = ['fonts' => CP_FONT_ROOT, 'pages' => CP_TUTORIAL_DIR, 'brand' => CP_DATA_DIR . '/brand'][$m[1]];
$real_base = realpath($base);
$real = realpath($base . '/' . $m[2]);
if (false === $real_base || false === $real || 0 !== strpos($real, $real_base . DIRECTORY_SEPARATOR) || !is_file($real))
	cp_asset_fail(404);

$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
if (!isset(CP_ASSET_TYPES[$ext]))
	cp_asset_fail(403);
/* stylesheets only from the font folder (cp-fonts.css and the families' own files) */
if ('css' == $ext && 'fonts' != $m[1])
	cp_asset_fail(403);

$mtime = filemtime($real);
$etag = '"' . dechex($mtime) . '-' . dechex(filesize($real)) . '"';
header('Content-Type: ' . CP_ASSET_TYPES[$ext]);
header('X-Content-Type-Options: nosniff');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);
header(isset($_GET['v']) ? 'Cache-Control: public, max-age=31536000, immutable' : 'Cache-Control: public, max-age=3600');
if ('svg' == $ext)
	header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox");
if ('fonts' == $m[1])
	header('Access-Control-Allow-Origin: *');

if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag)
{
	http_response_code(304);
	exit;
}
header('Content-Length: ' . filesize($real));
readfile($real);
