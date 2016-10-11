<?php

$file = rex_file::get(rex_path::addon('focuspoint','license.md'));
$Parsedown = new Parsedown();
$content =  $Parsedown->text($file);


$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', $this->i18n('licence'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
