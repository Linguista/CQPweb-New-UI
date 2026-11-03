<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the public Help page.
 * VERSION 1.0.4
 * Reached as /help-center.php (relative to the CQPweb root). No log-in needed.
 * Its links are set on the admin Appearance page (section "Help page").
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
require_once('../lib/cqpweb-new-ui/help-ui.php');

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP, RUN_LOCATION_MAINHOME);
cp_help_page();
cqpweb_shutdown_environment();
