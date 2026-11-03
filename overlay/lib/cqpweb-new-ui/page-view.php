<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: public view of the pages in the user pages folder.
 * VERSION 1.0.4
 * Reached as page.php?name=cqp-syntax-tutorial.html. No log-in needed. Shows the file inside the
 * site design, with the user-pages colors, like the tutorials inside the corpora.
 */
define('CP_SCOPE_OVERRIDE', 'user');
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
cp_userpage_view();
cqpweb_shutdown_environment();
