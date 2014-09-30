<?php

/**
 * Focuspoint Addon
 */

require $REX['INCLUDE_PATH'] . '/layout/top.php';

$page    = rex_request('page', 'string');
$subpage = rex_request('subpage', 'string');
$func    = rex_request('func', 'string');
$oid     = rex_request('oid', 'int');

rex_title($I18N->msg('fp_title'), $REX['ADDON']['pages']['focuspoint']);

echo "\n  <div class=\"rex-addon-output-v2\">\n  ";

if (!in_array($subpage, array('setup'))) {
    $subpage = 'setup';
}

require $REX['INCLUDE_PATH'] . '/addons/focuspoint/pages/' . $subpage . '.inc.php';

echo "\n  </div>";

require $REX['INCLUDE_PATH'] . '/layout/bottom.php';
