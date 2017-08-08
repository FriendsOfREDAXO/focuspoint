<?php

/**
 * focuspoint Addon.
 *
 * @author FriendsOfREDAXO
 *
 * @var rex_addon
 */
$fragment = new rex_fragment();
$content = '
<p>Folgendes im Template einfügen:</p>
';

$content .= rex_string::highlight(rex_file::get(rex_path::addon('focuspoint', 'pages/info_template.inc')));

$content .= '
<p>Bei der Installation wurde ein Effekt beim Media Manager AddOn hinzugefügt. Sollte dieser fehlen, bitte ein reinstall durchführen</p>
<p>Diese Ausgabe dient als Beispiel für ein Modul:</p>

';
$content .= rex_string::highlight(rex_file::get(rex_path::addon('focuspoint', 'pages/info_modul.inc')));

$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', $this->i18n('help'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
