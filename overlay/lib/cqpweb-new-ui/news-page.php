<?php
/*
 * cqpweb-new-ui, an addition to CQPweb 3.3: the public news page.
 * VERSION 1.0.2
 * Reached as news.php (relative to the CQPweb root). No log-in needed. The text is the file
 * CP_NEWS_FILE in the user pages folder, edited on the admin page "News page".
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
cp_news_page();
cqpweb_shutdown_environment();
