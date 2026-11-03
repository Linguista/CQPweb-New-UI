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
 * 
 * @file
 * 
 * admin-home-ui.php: this file contains the code that structures the HTML of the admin control panel.
 */

/* first, process the various "actions" that the admin interface may be asked to perform */
require('../lib/admin-execute.php');
/* 
 * Note that the execute actions are zero-HTML: they call execute.php 
 * which builds an environment, then calls a function, then sets a Location header, then exits. 
 * 
 * If there is some abort, then they fall through to here, so that no action is taken and 
 * instead we just go back to this script's normal render of the interface. 
 */

require('../lib/environment.php');


/* include function library files */
require('../lib/general-lib.php');
require('../lib/sql-lib.php');
require('../lib/html-lib.php');
require('../lib/exiterror-lib.php');
require('../lib/corpus-lib.php');
require('../lib/annotation-lib.php');
require('../lib/metadata-lib.php');
require('../lib/query-lib.php');
require('../lib/scope-lib.php');
require('../lib/freqtable-lib.php');
require('../lib/cache-lib.php');
require('../lib/cqp.php');
require('../lib/db-lib.php');
require('../lib/xml-lib.php');
require('../lib/compression-lib.php');
require('../lib/upload-lib.php');
require('../lib/plugin-lib.php');
require('../lib/useracct-lib.php');
require('../lib/usercorpus-lib.php');
require('../lib/template-lib.php');
require('../lib/multivariate-lib.php');

/* and include, especially, the interface forms for this screen */
require('../lib/admin-home-forms.php');
require('../lib/admin-cachecontrol-forms.php');


/* declare global variables */
$Config = null;


cqpweb_startup_environment(
	CQPWEB_STARTUP_DONT_CONNECT_CQP | CQPWEB_STARTUP_CHECK_ADMIN_USER, 
	RUN_LOCATION_ADM
	);



/* what ui: the user interface page to be displayed on the right-hand-side. */
$what_ui = ( isset($_GET["ui"]) ? $_GET["ui"] : 'showCorpora' );



echo print_html_header('CQPweb Admin Control Panel', 
                       $Config->colour_scheme, 
                       ['cword', 'adminhome', 'corpus-name-highlight', 'attribute-embiggen', 'metadata-embiggen', 'user-quicksearch'],
                       ['../user-quicksearch']
	);

?>

<table class="layout-frame fullwidth">
	<tr>
		<td class="minwidth" valign="top">

<?php



/*
 * ===================
 * PRINT SIDE BAR MENU
 * ===================
 */

?>

<!--   a single-block <nav> which will align to the right-side heading -->

<nav>
	<a class="menuItem header">Menu</a>
</nav>

<nav>

	<?php
	
	echo print_menurow_heading('Corpora');
	echo print_menurow_index('showCorpora', 'Show corpora', '', $what_ui);
	echo print_menurow_index('showUserCorpora', 'Show user corpora', '', $what_ui);
	echo print_menurow_index('installCorpus', 'Install new corpus', '', $what_ui);
	echo print_menurow_index('uploadArea', 'View upload area', '', $what_ui);
	echo print_menurow_index('manageCorpusCategories', 'Manage corpus categories', '', $what_ui);
	
	echo print_menurow_heading('Templates');
	echo print_menurow_index('annotationTemplates', 'Annotation templates', '', $what_ui);
	echo print_menurow_index('xmlTemplates', 'XML templates', '', $what_ui);
	echo print_menurow_index('metadataTemplates', 'Metadata templates', '', $what_ui);
	echo print_menurow_index('catdescTemplates', 'Catdesc templates', '', $what_ui);
	echo print_menurow_index('visualisationTemplates', 'Visualization templates', '', $what_ui);
	
	echo print_menurow_heading('Users and privileges');
	echo print_menurow_index('userAdmin', 'Manage users', '', $what_ui);
	echo print_menurow_index('groupAdmin', 'Manage groups', '', $what_ui);
	echo print_menurow_index('groupMembership', 'Manage group membership', '', $what_ui);
	echo print_menurow_index('privilegeAdmin', 'Manage privileges', '', $what_ui);
	echo print_menurow_index('userGrants', 'Manage user grants', '', $what_ui);
	echo print_menurow_index('groupGrants', 'Manage group grants', '', $what_ui);
	
	echo print_menurow_heading('Plugins');
	echo print_menurow_index('pluginAdmin', 'Manage plugins', '', $what_ui);
// 	echo print_menurow_index('pluginActivation', 'Activate plugins', '', $what_ui); // TODO - this is accessed via the "MAnage" UI. Do a summary view here instead.
	echo print_menurow_index('viewInstallers', 'Show installer jobs', '', $what_ui);
	
	
	echo print_menurow_heading('Frontend interface');
	echo print_menurow_index('cpPersonalize', 'Personalization', '', $what_ui); /* cqpweb-new-ui */
	echo print_menurow_index('appearance', 'Appearance', '', $what_ui); /* cqpweb-new-ui */
	echo print_menurow_index('cpSite', 'Maintenance mode', '', $what_ui); /* cqpweb-new-ui */
	echo print_menurow_index('systemMessages', 'System messages', '', $what_ui);
	echo print_menurow_index('cpNews', 'News page', '', $what_ui); /* cqpweb-new-ui */
// 	echo print_menurow_index('skins', 'Skins and colours', '', $what_ui);
	echo print_menurow_index('mappingTables', 'Mapping tables', '', $what_ui);
	echo print_menurow_index('manageEmbeds', 'Embedded pages', '', $what_ui);
	echo print_menurow_index('cpPages', 'Edit user pages', '', $what_ui); /* cqpweb-new-ui */
	
	echo print_menurow_heading('Cache control');
	echo print_menurow_index('queryCacheControl', 'Query cache', '', $what_ui);
	echo print_menurow_index('dbCacheControl', 'Datablob cache', '', $what_ui);
	echo print_menurow_index('restrictionCacheControl', 'Restriction cache', '', $what_ui);
	echo print_menurow_index('subcorpusCacheControl', 'Subcorpus file cache', '', $what_ui);
	echo print_menurow_index('freqtableCacheControl', 'Frequency table cache', '', $what_ui);
	echo print_menurow_index('tempCacheControl', 'Temporary data', '', $what_ui);
	echo print_menurow_index('fragmentCacheControl', 'Fragmentation check', '', $what_ui);
	echo print_menurow_index('cwbFileMonitor', 'CWB file monitor', '', $what_ui);
	
	echo print_menurow_heading('Backend system');
	echo print_menurow_index('cpMail', 'E-mail', '', $what_ui); /* cqpweb-new-ui */
	echo print_menurow_index('systemSettings', 'System settings', '', $what_ui);
	echo print_menurow_index('manageProcesses', 'Manage SQL processes', '', $what_ui);
	echo print_menurow_index('tableView', 'View an SQL table', '', $what_ui);
	echo print_menurow_index('sqlConfig', 'SQL configuration', '', $what_ui);
	echo print_menurow_index('phpConfig', 'PHP configuration', '', $what_ui);
	echo print_menurow_index('opcodeCache', 'PHP opcode cache', '', $what_ui);
	echo print_menurow_index('publicTables', 'Public frequency lists', '', $what_ui);
	echo print_menurow_index('systemSnapshots', 'System snapshots', '', $what_ui);
	echo print_menurow_index('systemDiagnostics', 'System diagnostics', '', $what_ui);
	
	echo print_menurow_heading('Usage Statistics');
	echo print_menurow_index('corpusStatistics', 'Corpus statistics', '', $what_ui);
	echo print_menurow_index('userStatistics', 'User statistics', '', $what_ui);
	echo print_menurow_index('queryStatistics', 'Query statistics', '', $what_ui);
	echo print_menurow_index('clearQueryHistory', 'Clear history', '', $what_ui);
	echo print_menurow_heading('Homepage'); /* cqpweb-new-ui: was "Exit" */
	
	?>

	<a id="linkExitHomepage" class="menuItem" href="../" data-tooltip="Go to the homepage">
		<?php require_once('../lib/cqpweb-new-ui/brand-lib.php'); echo escape_html(cp_site_name()); /* cqpweb-new-ui */ ?> homepage
	</a>
</nav>

		</td>
		<td class="fullwidth" valign="top">
		
<table class="layout-frame fullwidth">
	<tr>
		<th class="layout-strong">
			CQPweb Admin Control Panel
		</th>
	</tr>
</table>

<?php




/* 
 * ==================
 * PRINT MAIN CONTENT
 * ==================
 */

switch($what_ui)
{
case 'showCorpora':
	do_adm_ui_showcorpora_system_admhome();
	break;
	
case 'showUserCorpora':
	do_adm_ui_showcorpora_user_admhome();
	break;
	
case 'installCorpus':
	do_adm_ui_installcorpus_unindexed();
	break;

case 'installCorpusIndexed':
	do_adm_ui_installcorpus_indexed();
	break;

case 'installCorpusDone':
	do_adm_ui_installcorpusdone();
	break;
	
case 'deleteCorpus':
	/* note - this never has a menu entry -- it must be triggered from showCorpora */
	do_adm_ui_deletecorpus();
	break;

case 'manageCorpusCategories':
	do_adm_ui_corpuscategories();
	break;
	
case 'annotationTemplates':
	do_adm_ui_annotationtemplates();
	break;
	
case 'xmlTemplates':
	do_adm_ui_xmltemplates();
	break;
	
case 'metadataTemplates':
	do_adm_ui_metadatatemplates();
	break;
	
case 'catdescTemplates':
	do_adm_ui_catdesctemplates();
	break;
	
case 'visualisationTemplates':
	do_adm_ui_visualisationtemplates();
	break;
		
case 'uploadArea':
	do_adm_ui_uploadarea();
	break;
	
case 'userAdmin':
	do_adm_ui_useradmin();
	break;

case 'userUnverified':
	do_adm_ui_usersearch_unverified();
	break;
	
case 'userSearch':
	do_adm_ui_usersearch_searchterm();
	break;

case 'userView':
	do_adm_ui_userview();
	break;
	
case 'userUploads':
	/* note - this never has a menu entry -- it must be triggered from one of the other user-admin pages */
	do_adm_ui_useruploads();
	break;

case 'userFilePreview':
	/* note - this never has a menu entry -- it must be triggered from one of the other user-admin pages */
	do_adm_ui_userfilepreview();
	break;
	
case 'userDelete':
	/* note - this never has a menu entry -- it must be triggered from one of the other user-admin pages */
	do_adm_ui_userdelete();
	break;
	
case 'groupAdmin':
	do_adm_ui_groupadmin();
	break;

case 'groupMembership':
	do_adm_ui_groupmembership();
	break;

case 'privilegeAdmin':
	do_adm_ui_privilegeadmin();
	break;

case 'editPrivilege':
	do_adm_ui_editprivilege();
	break;

case 'userGrants':
	do_adm_ui_usergrants();
	break;

case 'groupGrants':
	do_adm_ui_groupgrants();
	break;

case 'pluginAdmin':
	do_adm_ui_pluginadmin();
	break;

case 'pluginActivation':
	do_adm_ui_pluginactivation();
	break;

case 'viewInstallers':
	do_adm_ui_viewinstallers();
	break;

case 'cpSite':
	require_once('../lib/cqpweb-new-ui/site-ui.php');
	cp_site_admin_ui();
	break;

case 'cpPersonalize':
	/* cqpweb-new-ui: site name, logo, icons, texts, query defaults, public documentation (lib/cqpweb-new-ui/personalize-ui.php) */
	require_once('../lib/cqpweb-new-ui/personalize-ui.php');
	cp_personalize_admin_ui();
	break;

case 'cpMail':
	/* cqpweb-new-ui: sending e-mail through an SMTP server, with a step-by-step guide (lib/cqpweb-new-ui/mail-ui.php) */
	require_once('../lib/cqpweb-new-ui/mail-ui.php');
	cp_mail_admin_ui();
	break;

case 'appearance':
	/* cqpweb-new-ui: colours, fonts and day/night mode (lib/cqpweb-new-ui/appearance-ui.php) */
	require_once('../lib/cqpweb-new-ui/appearance-ui.php');
	cp_appearance_admin_ui();
	break;
case 'cpPages':
	/* cqpweb-new-ui: edit the HTML pages in the user pages folder (lib/cqpweb-new-ui/pages-ui.php) */
	require_once('../lib/cqpweb-new-ui/pages-ui.php');
	cp_pages_admin_ui();
	break;

case 'cpNews':
	/* cqpweb-new-ui: edit the public news page (lib/cqpweb-new-ui/news-ui.php) */
	require_once('../lib/cqpweb-new-ui/news-ui.php');
	cp_news_admin_ui();
	break;

case 'systemMessages':
	/* cqpweb-new-ui: system messages can be edited as well as added and deleted (lib/cqpweb-new-ui/sysmsg-ui.php) */
	require_once('../lib/cqpweb-new-ui/sysmsg-ui.php');
	cp_sysmsg_admin_ui();
	break;

// case 'skins':
// 	do_adm_ui_skins();
// 	break;

case 'mappingTables':
	do_adm_ui_mappingtables();
	break;

case 'manageEmbeds':
	do_adm_ui_embeddedpages();
	break;

case 'queryCacheControl':
	do_adm_ui_querycachecontrol();
	break;
	
case 'dbCacheControl':
	do_adm_ui_dbcachecontrol();
	break;
	
case 'restrictionCacheControl':
	do_adm_ui_restrictioncachecontrol();
	break;
	
case 'subcorpusCacheControl':
	do_adm_ui_subcorpuscachecontrol();
	break;
	
case 'freqtableCacheControl':
	do_adm_ui_freqtablecachecontrol();
	break;
	
case 'tempCacheControl':
	do_adm_ui_tempcachecontrol();
	break;
	
case 'fragmentCacheControl':
	do_adm_ui_innodbfragmentation();
	break;
	
case 'cwbFileMonitor':
	do_adm_ui_cwbfilemonitor();
	break;
	
case 'systemSettings':
	do_adm_ui_systemsettings();
	break;
	
case 'manageProcesses':
	do_adm_ui_manageprocesses();
	break;
	
case 'tableView':
	do_adm_ui_tableview();
	break;
	
case 'sqlConfig':
	do_adm_ui_sqlconfig();
	break;
	
case 'phpConfig':
	do_adm_ui_phpconfig();
	break;

case 'opcodeCache':
	do_adm_ui_opcodecache();
	break;

case 'publicTables':
	do_adm_ui_publictables();
	break;

case 'systemSnapshots':
	do_adm_ui_systemsnapshots();
	break;

case 'systemDiagnostics':
	do_adm_ui_systemdiagnostics();
	break;

case 'corpusStatistics':
	do_adm_ui_statistic_corpus();
	break;

case 'userStatistics':
	do_adm_ui_statistic_user();
	break;

case 'queryStatistics':
	do_adm_ui_statistic_query();
	break;

case 'clearQueryHistory':
	do_adm_ui_historyclear();
	break;

/* special option for printing a message shown via GET */
case 'showMessage':
	do_ui_message();
	break;

default:
	?>
	
	<p class="spacer">&nbsp;</p>
	<p class="errormessage">
		Sorry, but that is not a valid menu option.
	</p>
	<p class="spacer">&nbsp;</p>
	
	<?php
	break;
}




/* finish off the page */

?>
		</td>
	</tr>
</table>
<?php

echo print_html_footer();

cqpweb_shutdown_environment();

/* ------------- * 
 * END OF SCRIPT * 
 * ------------- */

