<?php

$mypage = 'focuspoint';
$REX['ADDON']['version'][$mypage] = '0.1';
$REX['ADDON']['author'][$mypage] = 'Oliver Kreischer';

if($REX['REDAXO'])
{
  include $REX["INCLUDE_PATH"]."/addons/focuspoint/classes/class.rex_focuspoint.inc.php";

  rex_register_extension('MEDIA_ADDED', 'rex_focuspoint::set_media');
  rex_register_extension('MEDIA_UPDATED', 'rex_focuspoint::set_media');
  rex_register_extension('MEDIA_FORM_EDIT', 'rex_focuspoint::show_form_info');

}

?>
