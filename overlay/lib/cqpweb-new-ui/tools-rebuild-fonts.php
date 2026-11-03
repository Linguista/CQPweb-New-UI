<?php
/*
 * cqpweb-new-ui: rebuilds cp-fonts.css in the font folder (CP_FONT_ROOT) from the fonts there.
 * VERSION 1.0.2
 * Usage, on the server, as the web server's user (www-data on Debian and Ubuntu, apache on Fedora):
 *   sudo -u www-data php /var/www/html/cqpweb/lib/cqpweb-new-ui/tools-rebuild-fonts.php
 */
require_once(__DIR__ . '/appearance-lib.php');
$err = cp_rebuild_font_css();
if ('' !== $err) { fwrite(STDERR, "$err\n"); exit(1); }
echo 'cp-fonts.css rebuilt: ', count(cp_scan_fonts()), " font families.\n";
