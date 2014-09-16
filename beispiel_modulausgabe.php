<?php

    if ("REX_FILE[1]" != "") { // Bild

      $bild               = OOMedia::getMediaByName('REX_FILE[1]');
      $bildTitle          = $bild->getTitle();

      $focuspoint_data    = $bild->getValue('med_focuspoint_data');
      $focuspoint_css     = $bild->getValue('med_focuspoint_css');

      $bildDateiName      = $bild->getFileName();
      $bildBreite         = $bild->getWidth();
      $bildHoehe          = $bild->getHeight();

      $image              = rex_image_manager::getImageCache('REX_FILE[1]', "contentimage_REX_VALUE[8]");

      if ($focuspoint_data != '') {

        // echo $focuspoint_data.' data-focus-w="'.$bildBreite.'" data-focus-h="'.$bildHoehe.'"<br/>';
        // echo $focuspoint_css.'<br/>';

        $bildcode = '
        <div class="focuspoint"
          '.$focuspoint_data.'
          data-focus-w="'.$bildBreite.'"
          data-focus-h="'.$bildHoehe.'"
        >
             <img src="./files/'.$bildDateiName.'" alt="" />
        </div>


        ';

      } else {

        // Bildangaben ohne gesetzten Focuspoint

      }

  echo $bildcode;

?>
