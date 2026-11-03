<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: where the package keeps its files.
 * VERSION 1.0.3
 *
 * Every other file of the package loads this one first. The installer writes
 * local-config.php beside it with the folders chosen at installation; any constant
 * defined there wins over the defaults below.
 *
 *   CP_DATA_DIR      settings (JSON), uploaded logo and icons, page and news history.
 *                    Must be writable by the web server. Not served to the web.
 *   CP_FONT_ROOT     self-hosted fonts and the generated cp-fonts.css.
 *   CP_FONT_URL      the address of CP_FONT_ROOT. Either an absolute path that the web
 *                    server maps to CP_FONT_ROOT (for example "/fonts"), or, by default,
 *                    the package's own file server, relative to the CQPweb root.
 *   CP_TUTORIAL_DIR  the user pages folder: tutorials, documentation pages, the news page.
 *   CP_PAGES_URL     the address of CP_TUTORIAL_DIR, as for CP_FONT_URL.
 */

const CP_VERSION = '1.0.3';

if (is_file(__DIR__ . '/local-config.php'))
	require_once(__DIR__ . '/local-config.php');

if (!defined('CP_DATA_DIR'))
	define('CP_DATA_DIR', '/usr/local/share/cqpweb/cqpweb-new-ui');
if (!defined('CP_FONT_ROOT'))
	define('CP_FONT_ROOT', CP_DATA_DIR . '/fonts');
if (!defined('CP_FONT_URL'))
	define('CP_FONT_URL', 'cqpweb-new-ui-asset.php?f=fonts');
if (!defined('CP_TUTORIAL_DIR'))
	define('CP_TUTORIAL_DIR', CP_DATA_DIR . '/pages');
if (!defined('CP_PAGES_URL'))
	define('CP_PAGES_URL', 'cqpweb-new-ui-asset.php?f=pages');

/**
 * A package address made usable from the current page: absolute paths and full URLs are
 * returned unchanged; addresses relative to the CQPweb root get the page's URL prefix.
 */
function cp_url(string $root_relative) : string
{
	global $Config;
	if (preg_match('~^(/|[a-z][a-z0-9+.-]*:)~i', $root_relative))
		return $root_relative;
	$ready = function_exists('get_url_prefix') && isset($Config) && is_object($Config) && isset($Config->run_location);
	return ($ready ? get_url_prefix() : '') . $root_relative;
}
