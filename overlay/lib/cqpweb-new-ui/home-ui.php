<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the home page.
 * VERSION 1.0.2
 *
 * Replaces the body of CQPweb's main page (lib/main-home-ui.php calls cp_home_page()
 * right after the environment starts). Shows the logo and welcome text, the log-in box
 * or the visitor's quick links, and one card per visible corpus, colored with that
 * corpus's own Appearance palette. CQPweb's system messages and footer stay as they are.
 */

require_once(__DIR__ . '/appearance-lib.php');
require_once(__DIR__ . '/embed-lib.php');

function cp_home_page() : void
{
	global $Config, $User;

	$corpus_info = get_all_corpora_info();
	$logged_in = $User->logged_in;

	/* most recently used corpora first, as CQPweb's own home page does */
	$recent = [];
	if ($logged_in)
	{
		$u = escape_sql($User->username);
		$result = do_sql_query("select corpus from query_history where user='$u' order by date_of_query desc limit 200");
		while (count($recent) < 6 && ($o = mysqli_fetch_row($result)))
			if (!in_array($o[0], $recent) && isset($corpus_info[$o[0]]))
				$recent[] = $o[0];
	}

	echo print_html_header(escape_html(cp_site_name()), $Config->colour_scheme);

	/* ---------- hero: the logo and welcome of the Personalization page, else CQPweb's own (brand-lib.php) ---------- */
	$logo = '<img class="cp-home-logo' . cp_logo_class() . '" src="' . escape_html(cp_logo_url()) . '" alt="' . escape_html(cp_logo_alt()) . '">';
	$welcome = cp_welcome_html();
	?>
	<main class="cp-home">
		<section class="cp-hero">
			<?= $logo ?>
			<div class="cp-hero-text"><?= $welcome ?></div>
		</section>

	<?php
	/* ---------- account box ---------- */
	if ($logged_in)
	{
		$name = (empty($User->realname) || 'unknown person' == $User->realname) ? escape_html($User->username) : escape_html($User->realname);
		$admin = $User->is_admin() ? '<a class="cp-home-link" href="adm/">Admin control panel</a>' : '';
		echo <<<END_HTML
		<section class="cp-home-box cp-home-account" aria-label="Your account">
			<p class="cp-home-greeting">Welcome back, $name.</p>
			<div class="cp-home-links">
				<a class="cp-home-link" href="usr/index.php?ui=userDetails">Your account details</a>
				<a class="cp-home-link" href="usr/index.php?ui=corpusAccess">Your corpus access</a>
				$admin
				<a class="cp-home-link cp-home-quiet" href="usr/useracct-act.php?userAction=userLogout">Log out</a>
			</div>
		</section>

END_HTML;
	}
	else
	{
		$signup = $Config->allow_account_self_registration
			? '<a class="cp-home-cta" href="usr/?ui=create">Create an account</a>'
			: '';
		echo '<section class="cp-home-box cp-home-login" aria-labelledby="cpLoginH">'
			, '<h2 id="cpLoginH" class="cp-home-h2">Log in</h2>'
			, '<p class="cp-home-note">You need a free account to search the corpora.</p>'
			, print_login_form()
			, '<div class="cp-home-links">', $signup
			, '<a class="cp-home-link" href="usr/?ui=lostPassword">Forgotten your password?</a>'
			, '<a class="cp-home-link cp-home-quiet" href="usr/">All account options</a></div>'
			, "</section>\n";
	}

	/* ---------- corpus cards ---------- */
	$visible = [];
	foreach ($corpus_info as $c => $info)
		if (null === $info->owner && $info->visible)
			$visible[$c] = $info;
	if (!empty($recent))
		uksort($visible, function ($a, $b) use ($recent) {
			$ia = array_search($a, $recent);
			$ib = array_search($b, $recent);
			if (false === $ia && false === $ib)
				return strcmp($a, $b);
			if (false === $ia)
				return 1;
			if (false === $ib)
				return -1;
			return $ia <=> $ib;
		});

	if (!empty($visible))
	{
		echo "\t\t<section aria-labelledby=\"cpCorporaH\">\n\t\t\t<h2 id=\"cpCorporaH\" class=\"cp-home-h2\">Corpora</h2>\n\t\t\t<div class=\"cp-cards\">\n";
		foreach ($visible as $c => $info)
		{
			$scope = "corpus:$c";
			$short = escape_html(cp_scope_settings($scope)['short']);
			$d = cp_scope_palette($scope, 'day');
			$n = cp_scope_palette($scope, 'night');
			$vars = "--cd-band:{$d['sidebar']};--cd-band-text:{$d['sidebarText']};--cn-band:{$n['strong']};--cn-band-text:{$n['strongText']};"
				. "--cd-btn:{$d['accent']};--cd-btn-text:{$d['accentText']};--cn-btn:{$n['accent']};--cn-btn-text:{$n['accentText']}";
			$card_text = cp_scope_settings($scope)['card'] ?? '';
			$title = escape_html('' !== $card_text ? $card_text : $info->title);
			$facts = [];
			if ($info->size_tokens)
				$facts[] = number_format((float)$info->size_tokens) . ' words';
			if ($info->size_texts)
				$facts[] = number_format((float)$info->size_texts) . ' texts';
			$facts = implode(' &middot; ', $facts);
			$tag = in_array($c, $recent) ? '<span class="cp-card-tag">Recently used</span>' : '';
			/* documentation: the public page if there is one, otherwise the corpus's own manual link */
			$doc = '';
			if ('' !== ($slug = cp_public_doc_for_corpus($c)))
				$doc = '<a class="cp-card-doc" href="documentation.php?corpus=' . rawurlencode($slug) . '">Documentation</a>';
			elseif (!empty($info->external_url))
				$doc = '<a class="cp-card-doc" href="' . escape_html(preg_match('~^(https?:)?//|^/~', $info->external_url) ? $info->external_url : "$c/{$info->external_url}") . '">Documentation</a>';
			echo <<<END_HTML
				<article class="cp-card" style="$vars">
					<a class="cp-card-band" href="$c/"><span class="cp-card-short">$short</span>$tag</a>
					<div class="cp-card-body">
						<p class="cp-card-title">$title</p>
						<p class="cp-card-facts">$facts</p>
						<div class="cp-card-actions"><a class="cp-card-open" href="$c/">Search $short</a>$doc</div>
					</div>
				</article>

END_HTML;
		}
		echo "\t\t\t</div>\n\t\t</section>\n";
	}
	/* ---------- what has changed ---------- */
	require_once(__DIR__ . '/embed-lib.php');
	if (cp_brand()['newsShow'] && cp_news_available())
		echo "\t\t<section class=\"cp-home-box cp-home-news\" aria-label=\"" . escape_html(cp_news_title()) . "\">\n"
			, "\t\t\t<h2 class=\"cp-home-h2\">" . escape_html(cp_news_title()) . "</h2>\n"
			, "\t\t\t<p class=\"cp-home-note\">New features, fixes and updates.</p>\n"
			, "\t\t\t<div class=\"cp-home-links\"><a class=\"cp-home-link\" href=\"news.php\">See what has changed</a></div>\n"
			, "\t\t</section>\n";
	echo "\t\t<p class=\"cp-credit\"><img src=\"css/cqpweb-new-ui/cwb-logo.svg\" alt=\"\" width=\"64\" height=\"30\"> Powered by <a href=\"https://cwb.sourceforge.io/cqpweb.php\">CQPweb</a> and the <a href=\"https://cwb.sourceforge.io/\">IMS Open Corpus Workbench</a></p>\n";
	echo "\t</main>\n";
	?>
	<a id="mainhome:messages"></a>
	<?php
	display_system_messages();
	echo print_html_footer(false);
}
