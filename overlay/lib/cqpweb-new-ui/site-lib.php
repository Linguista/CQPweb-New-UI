<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: site rules.
 * VERSION 1.0.2
 *
 *  - maintenance mode: while it is on, only superusers can use CQPweb; everyone else
 *    sees a maintenance page (the log-in page stays open, so a superuser can log in);
 *  - the sign-up notice shown at the top of the "Create account" page (its text is set on the
 *    Personalization page, brand-lib.php);
 *  - the password rule: a minimum length plus a lowercase letter, an uppercase letter,
 *    a digit and a special character;
 *  - the waiting page shown when all database slots are busy (instead of an error);
 *  - case-insensitive searching by default, with a Case-sensitive box and the %C flag.
 *
 * Maintenance settings live in CP_DATA_DIR/site.json and are edited on the admin page
 * "Maintenance mode" (lib/cqpweb-new-ui/site-ui.php).
 */

require_once(__DIR__ . '/appearance-lib.php');
require_once(__DIR__ . '/brand-lib.php');

const CP_SITE_FILE = CP_DATA_DIR . '/site.json';


function cp_site_settings(bool $reload = false) : array
{
	static $cache = null;
	if (null !== $cache && !$reload)
		return $cache;
	$d = is_file(CP_SITE_FILE) ? json_decode((string)file_get_contents(CP_SITE_FILE), true) : null;
	if (!is_array($d))
		$d = [];
	return $cache = [
		'maintenance'        => !empty($d['maintenance']),
		'maintenanceMessage' => (string)($d['maintenanceMessage'] ?? ''),
	];
}

function cp_site_settings_reload() : array
{
	return cp_site_settings(true);
}

function cp_save_site_settings(array $new) : string
{
	$clean = [
		'maintenance'        => !empty($new['maintenance']),
		'maintenanceMessage' => cp_nfc(mb_substr(trim((string)($new['maintenanceMessage'] ?? '')), 0, 2000)),
	];
	if (!is_dir(CP_DATA_DIR) && !@mkdir(CP_DATA_DIR, 02775, true))
		return 'Cannot create ' . CP_DATA_DIR;
	$tmp = CP_SITE_FILE . '.tmp' . getmypid();
	if (false === @file_put_contents($tmp, json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n") || !@rename($tmp, CP_SITE_FILE))
		return 'Cannot write ' . CP_SITE_FILE;
	return '';
}

/** Plain text with blank-line paragraphs -> HTML paragraphs (text is escaped; URLs become links). */
function cp_text_to_html(string $text) : string
{
	$out = '';
	foreach (preg_split('/\R\s*\R/', trim($text)) as $para)
	{
		if ('' === trim($para))
			continue;
		$h = nl2br(escape_html(trim($para)), false);
		$h = preg_replace('~(https?://[^\s<]+[^\s<.,;:!?)\]])~', '<a href="$1">$1</a>', $h);
		$out .= "<p>$h</p>\n";
	}
	return $out;
}


/*
 * ================
 * MAINTENANCE MODE
 * ================
 */

/** Called at the end of cqpweb_startup_environment(): stops everyone but superusers while maintenance mode is on. */
function cp_maintenance_gate() : void
{
	global $Config, $User;

	if ('cli' == PHP_SAPI || !cp_site_settings()['maintenance'])
		return;
	if (isset($User) && $User->logged_in && $User->is_admin())
		return;

	/* the log-in page and the log-in / log-out actions stay open, so that a superuser can get in */
	$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
	if (RUN_LOCATION_USR == ($Config->run_location ?? 0))
	{
		$ui = $_GET['ui'] ?? 'login';
		$action = $_GET['userAction'] ?? '';
		if (('index.php' == $script && in_array($ui, ['login', 'welcome', ''], true))
			|| ('useracct-act.php' == $script && in_array($action, ['userLogin', 'userLogout'], true)))
			return;
	}

	if ($Config->Api ?? false)
	{
		cqpweb_shutdown_environment();
		exit;
	}

	http_response_code(503);
	header('Retry-After: 3600');
	$msg = cp_site_settings()['maintenanceMessage'];
	$site = escape_html(cp_site_name());
	$msg = '' === $msg ? "<p>$site is closed for maintenance. Please come back a little later.</p>" : cp_text_to_html($msg);
	$prefix = get_url_prefix();
	echo print_html_header("$site: closed for maintenance", $Config->colour_scheme ?? '~blue');
	echo <<<END_HTML
	<main class="cp-home">
		<section class="cp-home-box cp-maint">
			<h1 class="cp-home-h2">Closed for maintenance</h1>
			$msg
			<p class="cp-home-note">Administrators can still <a href="{$prefix}usr/?ui=login">log in</a>.</p>
		</section>
	</main>
	</body>
</html>
END_HTML;
	cqpweb_shutdown_environment();
	exit;
}

/** A banner for superusers while maintenance mode is on (printed under the top bar). */
function cp_maintenance_banner() : string
{
	global $User;
	if (!cp_site_settings()['maintenance'] || !(isset($User) && $User->logged_in && $User->is_admin()))
		return '';
	$prefix = get_url_prefix();
	return "<div class=\"cp-banner\" role=\"status\">Maintenance mode is on: only superusers can use the site. "
		. "<a href=\"{$prefix}adm/index.php?ui=cpSite\">Turn it off</a></div>\n";
}


/*
 * ========
 * ACCOUNTS
 * ========
 */

/** '' if the password meets the rule, else the reason it does not. */
function cp_password_rule_error(string $pw) : string
{
	global $Config;
	$min = max(12, (int)($Config->password_minimum_length ?? 12));
	$missing = [];
	if (mb_strlen($pw) < $min)
		$missing[] = "at least $min characters";
	if (!preg_match('/\p{Ll}/u', $pw))
		$missing[] = 'a lowercase letter';
	if (!preg_match('/\p{Lu}/u', $pw))
		$missing[] = 'an uppercase letter';
	if (!preg_match('/\p{Nd}/u', $pw))
		$missing[] = 'a digit';
	if (!preg_match('/[^\p{L}\p{Nd}\s]/u', $pw))
		$missing[] = 'a special character (a punctuation mark or symbol)';
	if (empty($missing))
		return '';
	return 'Your password needs ' . implode(', ', $missing) . '.';
}

function cp_password_rule_text() : string
{
	global $Config;
	$min = max(12, (int)($Config->password_minimum_length ?? 12));
	return "Your password must be at least $min characters long and contain a lowercase letter, an uppercase letter, "
		. 'a digit and a special character (a punctuation mark or symbol). Spaces are allowed.';
}

/** The sign-up notice as a table row for the "Create account" form ('' if there is none). */
function cp_signup_notice_row() : string
{
	$n = cp_signup_notice_text();
	if ('' === trim($n))
		return '';
	return "<tr><td class=\"layout-contrast cp-signup-notice\" colspan=\"2\">\n" . cp_text_to_html($n) . "</td></tr>\n";
}


/*
 * ===========================================
 * WAITING PAGE WHEN ALL DATABASE SLOTS ARE BUSY
 * ===========================================
 */

/** Replaces CQPweb's "Too many database processes!" error with a page that retries by itself. */
function cp_queue_page(string $process_type) : void
{
	global $Config, $Corpus;

	if ($Config->Api ?? false)
		return;      /* API clients get CQPweb's normal error */

	$limit = (int)$Config->sql_process_limit[$process_type];
	$what = escape_html($Config->sql_process_name[$process_type] ?? $process_type);
	$busy = (int)get_sql_value("select count(*) from system_processes where process_type = '" . escape_sql($process_type) . "'");
	$prefix = get_url_prefix();
	$back = (isset($Corpus) && !empty($Corpus->name)) ? "{$prefix}{$Corpus->name}/" : ($prefix ?: './');

	if (!headers_sent())
		header('Cache-Control: no-store');
	echo print_html_header('Waiting for a free slot', $Config->colour_scheme ?? '~blue');
	echo <<<END_HTML
	<main class="cp-home">
		<section class="cp-home-box cp-queue" aria-labelledby="cpQueueH">
			<h1 id="cpQueueH" class="cp-home-h2">Your request is waiting for a free slot</h1>
			<p>The server is already building $busy of the $limit $what databases it can build at once for other searches.
			Your request will start by itself as soon as one of them finishes.</p>
			<p class="cp-queue-status" aria-live="polite">Checking again in <span id="cpQueueSecs">5</span> seconds.
			Waiting for <span id="cpQueueTotal">0</span> seconds so far.</p>
			<div class="cp-home-links">
				<a class="cp-home-cta" id="cpQueueCancel" href="$back">Cancel</a>
				<a class="cp-home-link" href="javascript:location.reload()">Check now</a>
			</div>
			<noscript><p>This page needs JavaScript to check again by itself; press Reload in your browser to try again.</p></noscript>
		</section>
	</main>
	<script>
	(function () {
		var key = 'cpQueueStart:' + location.href, start;
		try { start = parseInt(sessionStorage.getItem(key), 10); } catch (e) {}
		if (!start) { start = Date.now(); try { sessionStorage.setItem(key, String(start)); } catch (e) {} }
		var left = 5;
		document.getElementById('cpQueueCancel').addEventListener('click', function (e) {
			try { sessionStorage.removeItem(key); } catch (x) {}
			if (history.length > 1) { e.preventDefault(); history.back(); }
		});
		setInterval(function () {
			left--;
			document.getElementById('cpQueueSecs').textContent = Math.max(left, 0);
			document.getElementById('cpQueueTotal').textContent = Math.round((Date.now() - start) / 1000);
			if (left <= 0) location.reload();
		}, 1000);
	})();
	</script>
	</body>
</html>
END_HTML;
	cqpweb_shutdown_environment();
	exit;
}


/*
 * ======================================
 * CASE-INSENSITIVE SEARCHING BY DEFAULT
 * ======================================
 */

/**
 * Applies the Case-sensitive box of the query forms. Only requests sent from a form that has the box
 * (cpCaseUI=1) are touched, so links CQPweb builds itself (collocates, frequency lists, history) keep
 * their exact meaning.
 *  - Simple queries: the box chooses between sq_case and sq_nocase.
 *  - CQP syntax with the box off: every string gets %c, except strings marked %C, which stay
 *    case-sensitive (%C is removed before the query reaches CQP).
 *  - CQP syntax with the box on: only %C is removed.
 */
function cp_apply_case_setting(string &$qmode, string &$qdata) : void
{
	/* bare words typed in CQP syntax mode become [word="..."] tokens (see cp_cqp_bareword) */
	if ('cqp' == $qmode && null !== ($converted = cp_cqp_bareword($qdata)))
	{
		$GLOBALS['cp_query_notice'] = ['from' => preg_replace('/\s+/u', ' ', trim($qdata)), 'to' => $converted];
		$qdata = $converted;
	}
	if (empty($_GET['cpCaseUI']))
		return;
	$sensitive = !empty($_GET['cpCase']);
	if ('sq_case' == $qmode || 'sq_nocase' == $qmode)
		$qmode = $sensitive ? 'sq_case' : 'sq_nocase';
	else if ('cqp' == $qmode)
		$qdata = cp_cqp_fold_case($qdata, !$sensitive);
}

/**
 * If a CQP-syntax query is nothing but bare words ("perro", "mi casa"), which CQP would
 * otherwise reject as an undefined corpus or named query, returns the same words as
 * [word="..."] tokens; otherwise null. Only letters, digits, combining marks, hyphens and
 * full stops count as a bare word, so any real CQP query is left alone.
 */
function cp_cqp_bareword(string $q) : ?string
{
	$q = trim($q);
	if ('' === $q)
		return null;
	$tokens = [];
	foreach (preg_split('/\s+/u', $q) as $w)
	{
		if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{M}\p{N}.\-]*$/u', $w))
			return null;
		$tokens[] = '[word="' . str_replace('.', '\\.', $w) . '"]';
	}
	return implode(' ', $tokens);
}

/** The note shown above the results when cp_cqp_bareword changed the query; '' otherwise. */
function cp_query_notice() : string
{
	if (empty($GLOBALS['cp_query_notice']))
		return '';
	$from = escape_html($GLOBALS['cp_query_notice']['from']);
	$to = escape_html($GLOBALS['cp_query_notice']['to']);
	return "<div class=\"cp-query-notice\" role=\"status\"><p>I have assumed you wanted to search for "
		. "<span class=\"cp-case-q\">$to</span>, and so I changed your query from "
		. "<span class=\"cp-case-q\">$from</span> to that. In the future, remember that searches with "
		. "CQP query syntax require this bracketed <span class=\"cp-case-q\">argument=\"value\"</span> syntax.</p></div>\n";
}

/** Adds %c to every quoted string of a CQP query (unless it carries %C or already %c); removes %C. */
function cp_cqp_fold_case(string $q, bool $fold) : string
{
	$out = '';
	$n = strlen($q);
	$i = 0;
	while ($i < $n)
	{
		$ch = $q[$i];
		if ('"' !== $ch && "'" !== $ch)
		{
			$out .= $ch;
			$i++;
			continue;
		}
		/* a quoted string: copy it whole, honoring backslash escapes */
		$j = $i + 1;
		while ($j < $n && $q[$j] !== $ch)
			$j += ('\\' === $q[$j]) ? 2 : 1;
		if ($j >= $n)
		{
			/* unterminated string: copy the rest unchanged and let CQP report the error */
			$out .= substr($q, $i);
			break;
		}
		$out .= substr($q, $i, $j - $i + 1);
		$i = $j + 1;
		/* its flags, if any */
		$flags = '';
		if ($i < $n && '%' === $q[$i] && preg_match('/\G%([A-Za-z]+)/', $q, $m, 0, $i))
		{
			$flags = $m[1];
			$i += strlen($m[0]);
		}
		$keep_case = false !== strpos($flags, 'C');
		$flags = str_replace('C', '', $flags);
		if ($fold && !$keep_case && false === strpos($flags, 'c'))
			$flags .= 'c';
		if ('' !== $flags)
			$out .= '%' . $flags;
	}
	return $out;
}
