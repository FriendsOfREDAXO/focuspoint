<?php

/**
 * Focuspoint Addon
 */

require $REX['INCLUDE_PATH'] . '/layout/top.php';

$page    = rex_request('page', 'string');
$subpage = rex_request('subpage', 'string');
$func    = rex_request('func', 'string');

rex_title($I18N->msg('fp_title'), $REX['ADDON']['pages']['focuspoint']);

echo '<div class="rex-addon-output-v2">';

if (!in_array($subpage, array('help'))) {
    $subpage = 'help';
}

require $REX['INCLUDE_PATH'] . '/addons/focuspoint/pages/' . $subpage . '.inc.php';

echo '</div>';

require $REX['INCLUDE_PATH'] . '/layout/bottom.php';
