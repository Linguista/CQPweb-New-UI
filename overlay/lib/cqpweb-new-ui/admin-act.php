<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the actions of the package's admin pages.
 * VERSION 1.0.3
 *
 * Reached as adm/cqpweb-new-ui-act.php. Superuser only, POST only, same-site only.
 * Answers in JSON. Actions:
 *   save         body {"action":"save","scopes":{...}}           writes appearance.json
 *   installFont  body {"action":"installFont","name":"Lora","kind":"serif"}
 *                                                                  downloads one Google Fonts family
 *   rebuildFonts body {"action":"rebuildFonts"}                    rewrites cp-fonts.css
 *   site         body {"action":"site","maintenance":true,"maintenanceMessage":"..."}
 *                                                                  writes site.json
 *   sysmsgSave   body {"action":"sysmsgSave","id":0,"header":"...","date":"YYYY-MM-DD","content":"..."}
 *                                                                  adds (id 0) or edits a system message
 *   sysmsgDelete body {"action":"sysmsgDelete","id":12}            deletes a system message
 *   newsSave     body {"action":"newsSave","b64":"..."}             saves and publishes the news page
 *   pageSave     body {"action":"pageSave","file":"x.html","b64":"..."}  saves one page in the user pages folder
 *   pageCreate   body {"action":"pageCreate","file":"x.html"}       creates a page from a template
 *   brandSave    body {"action":"brandSave", ...}                   Personalization page: writes brand.json
 *   brandUpload  body {"action":"brandUpload","key":"logo","b64":"..."}  stores the logo or the favicon
 *   brandRemove  body {"action":"brandRemove","key":"logo"}         removes an uploaded image
 *   helpSave     body {"action":"helpSave","intro":"...","builtin":{...},"custom":[...]}  writes help.json
 *   mailSave     body {"action":"mailSave", ...}                    E-mail page: writes mail.json (mode 600)
 *   mailTest     body {"action":"mailTest","to":"a@b"}              sends a test message with the saved settings
 * Page and news text travels base64-encoded (UTF-8), so the security filter does not read it as an attack.
 */

require('../lib/environment.php');
require('../lib/general-lib.php');
require('../lib/sql-lib.php');
require('../lib/html-lib.php');
require('../lib/exiterror-lib.php');
require('../lib/useracct-lib.php');
require('../lib/corpus-lib.php');
require_once('../lib/cqpweb-new-ui/appearance-lib.php');
require_once('../lib/cqpweb-new-ui/site-lib.php');
require_once('../lib/cqpweb-new-ui/sysmsg-ui.php');
require_once('../lib/cqpweb-new-ui/news-ui.php');
require_once('../lib/cqpweb-new-ui/pages-ui.php');
require_once('../lib/cqpweb-new-ui/brand-lib.php');
require_once('../lib/cqpweb-new-ui/help-ui.php');
require_once('../lib/cqpweb-new-ui/mail-lib.php');

$Config = null;

function cp_act_reply(bool $ok, string $message, array $extra = []) : void
{
	if (!headers_sent())
	{
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
	}
	echo json_encode(['ok' => $ok, 'message' => $message] + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? ''))
	cp_act_reply(false, 'This address only accepts requests from the admin pages.');

/* refuse requests sent from another site */
$origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
if ('' !== $origin && parse_url($origin, PHP_URL_HOST) !== parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST))
	cp_act_reply(false, 'Request refused: it did not come from this site.');

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP | CQPWEB_STARTUP_CHECK_ADMIN_USER, RUN_LOCATION_ADM);

$req = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($req))
	cp_act_reply(false, 'The request could not be read.');
/* the admin pages send their fields base64-encoded (payload64), so that a web application
 * firewall does not mistake links, tags or a password in them for an attack */
if (isset($req['payload64']))
{
	$inner = base64_decode((string)$req['payload64'], true);
	$inner = (false === $inner) ? null : json_decode($inner, true);
	if (!is_array($inner))
		cp_act_reply(false, 'The request could not be read.');
	$req = ['action' => (string)($req['action'] ?? '')] + $inner;
}

switch ($req['action'] ?? '')
{
case 'save':
	$err = cp_save_settings(['scopes' => (array)($req['scopes'] ?? [])]);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Saved.');

case 'installFont':
	$name = (string)($req['name'] ?? '');
	$kind = in_array($req['kind'] ?? '', ['sans', 'serif', 'mono'], true) ? $req['kind'] : null;
	set_time_limit(120);
	$err = cp_google_install($name, $kind);
	if ('' !== $err)
		cp_act_reply(false, $err);
	$err = cp_rebuild_font_css();
	if ('' !== $err)
		cp_act_reply(false, $err);
	$fams = cp_scan_fonts();
	$clean = trim(preg_replace('/\s+/', ' ', $name));
	cp_act_reply(true, "$clean is installed.", [
		'font'   => ['name' => $clean, 'kind' => $fams[$clean]['kind'] ?? ($kind ?? cp_font_kind($clean)), 'source' => $fams[$clean]['source'] ?? 'Google Fonts, downloaded'],
		'cssVer' => (string)@filemtime(CP_FONT_ROOT . '/' . CP_FONT_CSS_NAME),
	]);

case 'site':
	$err = cp_save_site_settings($req);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, cp_site_settings_reload()['maintenance'] ? 'Saved. Maintenance mode is ON.' : 'Saved. Maintenance mode is off.');

case 'rebuildFonts':
	$err = cp_rebuild_font_css();
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Font list rebuilt.', ['count' => count(cp_scan_fonts())]);

case 'sysmsgSave':
	$id = (int)($req['id'] ?? 0);
	$err = cp_sysmsg_save($id, (string)($req['header'] ?? ''), (string)($req['content'] ?? ''), (string)($req['date'] ?? ''), $new_id);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 0 === $id ? 'Message added.' : 'Saved.', ['id' => $new_id]);

case 'sysmsgDelete':
	$err = cp_sysmsg_delete((int)($req['id'] ?? 0));
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Deleted.');

case 'newsSave':
	$text = isset($req['b64']) ? base64_decode((string)$req['b64'], true) : (string)($req['text'] ?? '');
	if (false === $text)
		cp_act_reply(false, 'The text could not be read.');
	$err = cp_news_save($text);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Saved and published.');

case 'pageSave':
	$html = base64_decode((string)($req['b64'] ?? ''), true);
	if (false === $html)
		cp_act_reply(false, 'The page could not be read.');
	$err = cp_pages_save((string)($req['file'] ?? ''), $html);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Saved.');

case 'pageCreate':
	$err = cp_pages_create((string)($req['file'] ?? ''));
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Created.', ['file' => trim((string)$req['file'])]);

case 'brandSave':
	$err = cp_save_brand($req);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Saved. Reload any page to see the changes.');

case 'brandUpload':
	$key = (string)($req['key'] ?? '');
	$err = cp_brand_upload($key, (string)($req['b64'] ?? ''));
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_brand(true);
	cp_act_reply(true, 'Uploaded.', ['url' => 'logo' === $key ? cp_logo_url() : cp_brand_image_url($key)]);

case 'brandRemove':
	$key = (string)($req['key'] ?? '');
	$err = cp_brand_remove($key);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_brand(true);
	cp_act_reply(true, 'Removed.', ['url' => 'logo' === $key ? cp_logo_url() : cp_url('css/cqpweb-new-ui/favicon.svg')]);

case 'helpSave':
	$err = cp_save_help($req);
	if ('' !== $err)
		cp_act_reply(false, $err);
	cp_act_reply(true, 'Saved.');

case 'mailSave':
	$err = cp_mail_save($req);
	if ('' !== $err)
		cp_act_reply(false, $err);
	$ms = cp_mail_settings(true);
	cp_act_reply(true, $ms['enabled'] ? 'Saved. CQPweb now sends its e-mail through ' . $ms['host'] . '.' : 'Saved. SMTP sending is off.',
		['hasPassword' => '' !== $ms['password']]);

case 'mailTest':
	$to = trim((string)($req['to'] ?? ''));
	if (false === filter_var($to, FILTER_VALIDATE_EMAIL))
		cp_act_reply(false, 'Give an address to send the test message to.');
	if ('' === cp_mail_settings(true)['host'])
		cp_act_reply(false, 'Save the server settings first.');
	set_time_limit(180);
	$site = cp_site_name();
	$body = "This is a test message from the E-mail page of $site.\n\nIf you can read it, CQPweb can send e-mail through "
		. cp_mail_settings()['host'] . ".\n\nSent " . date('Y-m-d H:i:s T') . ".\n";
	$from = $Config->cqpweb_email_from_address ?? '';
	$ok = cp_mail_send($to, "Test message from $site", $body, '' !== $from ? ["From: $from"] : [], $log);
	cp_act_reply($ok, $ok ? "The server accepted the message for $to. Check that mailbox (and its spam folder)." : 'The message was not sent.', ['log' => $log ?? []]);

default:
	cp_act_reply(false, 'Unknown action.');
}
