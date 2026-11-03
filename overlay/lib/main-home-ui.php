<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @file
 * 
 * This is the script that renders the CQPweb "main menu" / "entrance page" / whate'er-you-ma-call-it.
 */

/* Very first thing: Let's work in a subdirectory so that we can use the same subdirectory references! */
chdir('exe');


require('../lib/environment.php');

require('../lib/sql-lib.php');
require('../lib/general-lib.php');
require('../lib/html-lib.php');
require('../lib/useracct-lib.php');
require('../lib/corpus-lib.php');
require('../lib/metadata-lib.php');
require('../lib/exiterror-lib.php');

$User = $Config = null;

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP, RUN_LOCATION_MAINHOME);

/* cqpweb-new-ui: the home page is drawn by lib/cqpweb-new-ui/home-ui.php; the original code below is not reached. */
require_once('../lib/cqpweb-new-ui/home-ui.php');
cp_home_page();
cqpweb_shutdown_environment();
exit;

if ($Config->homepage_use_corpus_categories)
{
	/* get a list of categories */
	$categories = list_corpus_categories();
	
	/* how many categories? if only one, it is either uncategorised or a single assigned cat: ergo don't use cats */
	$n_key_items = count($categories);
	if ($n_key_items < 2)
		$Config->homepage_use_corpus_categories = false;
}
else
{
	/* empty string: to make the loop cycle once */
	$categories = array(0=>'');
}


/* devise the HTML for the header-bar logos. */
$logo_divs = '';
foreach ( array('left', 'right') as $side)
{
	$addresses = 'homepage_logo_'.$side;
	if (empty($Config->$addresses))
		continue;
	if (false !== strpos($Config->$addresses, "\t"))
		list ($img_url, $link_url) = explode("\t", $Config->$addresses, 2);
	else
	{
		$img_url = $Config->$addresses;
		$link_url = false;
	}
	$logo_divs .= "<div style=\"float: $side;\">" .
		($link_url ? "<a href=\"$link_url\">" : '') .
		"<img src=\"$img_url\" height=\"80\"  border=\"0\" >" .
		($link_url ? '</a>' : '') .
		'</div>      ';
}





echo print_html_header('CQPweb Main Page', $Config->colour_scheme);

?>


<table class="layout-frame fullwidth">

	<tr>
		<th class="layout-strong" colspan="3">
			<?php echo $logo_divs, $Config->homepage_welcome_message; ?>
		</th>
	</tr>
	
<!-- 	<tr> -->
	<?php
	
	$corpus_info = get_all_corpora_info();


	if ($User->logged_in)
	{
		/* personalised welcome message */
		if (empty($User->realname) || $User->realname == 'unknown person')
			$personalise = '';
		else
			$personalise = ', ' . escape_html($User->realname);

		$result = do_sql_query("select corpus from query_history where user='{$User->username}' order by date_of_query desc");
		
		$recent_corpora = array();
		
		while (count($recent_corpora) < 6 && ($o = mysqli_fetch_row($result)))
			if (!in_array($o[0], $recent_corpora))
				$recent_corpora[] = $o[0];
		?>
		
		<tr>
			<td colspan="3" class="layout-fg">
			
				<p style="text-align:center; font-size:1.2rem">
					Welcome back to the CQPweb server<?php echo $personalise; ?>.<br>You are logged in to the system.
				</p>

				<p>&nbsp;</p>
				
				<table class="basicbox" style="width:75%; margin:auto;">
					<tr>
						<th width="50%" class="basicbox">Recently-used corpora</th>
						<th width="50%" class="basicbox">Quick links</th>
					</tr>
					<tr>
						<td class="basicbox algC">
							<table style="margin:auto;">
								<tr>
									<?php
									echo "\n";
									$count = 0;
									foreach($recent_corpora as $rc)
										if (isset($corpus_info[$rc]))
											echo "\t\t\t\t\t\t\t\t<td class=\"layout-bg hollow\"><a class=\"control-box\" href=\"{$corpus_info[$rc]->corpus}/\">"
												, escape_html($corpus_info[$rc]->title)
												, "</a></td>\n"
 												, (0 == (++$count % 2) ? "\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t<tr>\n" : '')
												;
									if(!$count)
										echo "\t\t\t\t\t\t\t\t<td class=\"layout-bg\" colspan=\"2\"><p>(no recent corpora)</p></td>\n";
									else if ($count > 4)
										while ($count++ < 6)
											echo "\t\t\t\t\t\t\t\t<td class=\"layout-bg hollow\"></td>\n";
									else if ($count > 2)
										while ($count++ < 4)
											echo "\t\t\t\t\t\t\t\t<td class=\"layout-bg hollow\"></td>\n";
									?>
								</tr>
							</table>
							<!-- <ul style="margin:auto">
// 								echo "\n";
// 								foreach($recent_corpora as $rc)
// 									if (isset($corpus_info[$rc]))
// 										echo "\t\t\t\t\t\t\t\t<li><a href=\"{$corpus_info[$rc]->corpus}/\">"
// 											, escape_html($corpus_info[$rc]->title)
// 											, "</a></li>\n"
// 											;
								
							</ul> -->
						</td>
						<td class="basicbox algC">
							<table style="width: 100%; margin: auto">
								<tr>
									<td class="layout-bg hollow" width="50%"><a class="control-box" href="usr/index.php?ui=corpusAccess">Your corpus access privileges</a></td>
									<td class="layout-bg hollow" width="50%">
										<?php 
										if ($User->is_admin()) 
											echo "<a class=\"control-box\" href=\"adm\">Admin control panel</a>\n";
										else
											echo "<a class=\"control-box\" href=\"adm\">Help! system</a>\n";
										?>
									</td>
								</tr>
								<tr>
									<td class="layout-bg hollow" width="50%"><a class="control-box" href="usr/index.php?ui=userDetails">Your user account details</a></td>
									<td class="layout-bg hollow" width="50%"><a class="control-box" href="usr/useracct-act.php?userAction=userLogout">Log out of CQPweb</a></td>
								</tr>
							</table>
							<!--<ul>
								<li><a href="usr/index.php?ui=corpusAccess">Your corpus access privileges</a></li>
								<?php //if ($User->is_admin()) echo "<li><a href=\"adm\">Admin control panel</a></li>\n"; ?>
								<li><a href="usr/index.php?ui=userDetails">Your user account details</a></li>
								<li><a href="usr/useracct-act.php?userAction=userLogout">Log out of CQPweb</a></li>
							</ul>-->
						</td>
					</tr>
				</table>
			</td>
		</tr>
		
		<?php
	}
	else
	{
		// TODO SW's idea of having a side menu same as in /usr when logged out is a pretty good one. 
		?>
		
		<tr>
			<td colspan="3" class="layout-fg">
				<?php echo print_login_form(), "\n"; ?>
			</td>
		</tr>
		<tr>
			<td colspan="3" class="layout-fg">
				<p class="algC">
					<a class="control-inline" href="usr/?ui=create">[Create account]</a>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<a class="control-inline" href="usr/">[Full account options]</a>
				</p>
			</td>
		</tr>
		
		<?php
	}
	
	if (!empty($corpus_info))
	{
		
		?>
		
		<tr>
			<th class="layout-strong" colspan="3">
				Corpora available on this server
				<?php 
				if ($User->logged_in)
					echo '(<a class="control-inline" href="usr/index.php?ui=corpusAccess">click here to view your own corpus access privileges</a>)';
				echo "\n";
				?>
			</th>
		</tr>
		
		<?php
	}

	foreach ($categories as $id => $cat)
	{
		/* get a filtered, ordered list of corpora */
		$sql = "select corpus from corpus_info where owner IS NULL and visible = 1 "
			. ($Config->homepage_use_corpus_categories ? "and corpus_cat = $id" : '') 
			. " order by corpus asc";
	
		$result = do_sql_query($sql);
		
		/* don't print a table for empty categories */
		if (0 == mysqli_num_rows($result))
			continue;
		
	
		if ($Config->homepage_use_corpus_categories)
			echo "\t\t<tr><th class=\"layout-strong\" colspan=\"3\">$cat</th></tr>\n\n";
		
		
		
		$i = 0;
		$celltype = 'layout-fg';
		
		while ($o = mysqli_fetch_row($result)) 
		{
			list($c) = $o;
			
			if ($i == 0)
				echo "\t\t<tr>";
	
			echo "
				<td class=\"$celltype hollow\" width=\"33%\">
					<p>
						<a class=\"control-box\" href=\"{$c}/\">", escape_html($corpus_info[$c]->title), "</a>
					</p>
				</td>\n";
			
			$celltype = ($celltype=='layout-bg'?'layout-fg':'layout-bg');
			
			if ($i == 2)
			{
				echo "\t\t</tr>\n";
				$i = 0;
			}
			else
				$i++;
		}
		
		if ($i == 1)
		{
			echo "\t\t\t<td class=\"$celltype algC\" width=\"33%\">&nbsp;</td>\n";
			$i++;
			$celltype = ($celltype=='layout-bg'?'layout-fg':'layout-bg');
		}
		if ($i == 2)
			echo "\t\t\t<td class=\"$celltype algC\" width=\"33%\">&nbsp;</td>\n\t\t</tr>\n";
	}
	
	?>


</table>

<a id="mainhome:messages"></a>

<?php

display_system_messages();

// TODO. Build in helplinks above instead of a bottom-row helplink?
echo print_html_footer(false);

cqpweb_shutdown_environment();


/* END OF SCRIPT */


