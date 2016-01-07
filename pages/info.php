<?php

$fragment = new rex_fragment();
$content = '<p>

<h3>Focuspoint</h3>
<p>Weitere Infos zum Focuspoint gibt es hier: <a href="https://github.com/jonom/jquery-focuspoint" target="_blank">https://github.com/jonom/jquery-focuspoint</a></p>

  <p>Folgendes im Template einfügen:</p>
';

$content .= rex_string::highlight(rex_file::get(rex_path::addon('focuspoint','pages/info_template.inc')));

$content .= '
Bei der Installation wurde ein Effekt beim Image Manager AddOn hinzugefügt. Sollte dieser fehlen, bitte ein reinstall durchführen

Diese Ausgabe dient als Beispiel für ein Modul:

';
$content .= rex_string::highlight(rex_file::get(rex_path::addon('focuspoint','pages/info_modul.inc')));


$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', $this->i18n('info'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

