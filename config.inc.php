<?php


$mypage = 'focuspoint';

if($REX['REDAXO'])
{

  $I18N->appendFile(dirname(__FILE__) . '/lang/');
  $REX['ADDON']['version'][$mypage] = '0.5';
  $REX['ADDON']['author'][$mypage] = 'Oliver Kreischer, Daniel Weitenauer';
  $REX['ADDON']['name'][$mypage] = $I18N->msg('fp_title');

  $I18N->appendFile(dirname(__FILE__) . '/lang/');

  include $REX["INCLUDE_PATH"]."/addons/focuspoint/classes/class.rex_focuspoint.inc.php";

  rex_register_extension('MEDIA_ADDED', 'rex_focuspoint::set_media');
  rex_register_extension('MEDIA_UPDATED', 'rex_focuspoint::set_media');
  rex_register_extension('MEDIA_FORM_EDIT', 'rex_focuspoint::show_form_info');

  $rootPage = new rex_be_page($I18N->msg('fp_setup'), array(
      'page' => $mypage,
      'subpage' => ''
    )
  );
  $rootPage->setHref('index.php?page=focuspoint');

  // Subpages
  $REX['ADDON']['pages'][$mypage] = array(
    $rootPage
  );



}

?>
