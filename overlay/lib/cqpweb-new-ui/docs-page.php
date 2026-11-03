<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: public corpus documentation pages.
 * VERSION 1.0.3
 * Reached as documentation.php?corpus=<slug> (relative to the CQPweb root). No log-in needed.
 * Which page documents which corpus is set on the Personalization page.
 */
chdir(__DIR__ . '/../../exe');
require('../lib/environment.php');
require('../lib/sql-lib.php');
require('../lib/general-lib.php');
require('../lib/html-lib.php');
require('../lib/useracct-lib.php');
require('../lib/corpus-lib.php');
require('../lib/metadata-lib.php');
require('../lib/exiterror-lib.php');
require_once('../lib/cqpweb-new-ui/appearance-lib.php');
require_once('../lib/cqpweb-new-ui/embed-lib.php');

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP, RUN_LOCATION_MAINHOME);
cp_public_doc_page();
cqpweb_shutdown_environment();
