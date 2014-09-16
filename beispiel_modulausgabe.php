<?php

    if ('REX_FILE[1]') {

      $bild               = OOMedia::getMediaByName('REX_FILE[1]');
      $bildTitle          = $bild->getTitle();
      $bildDateiName      = $bild->getFileName();
      $bildBreite         = $bild->getWidth();
      $bildHoehe          = $bild->getHeight();

      $focuspoint_data    = $bild->getValue('med_focuspoint_data');
      $focuspoint_css     = $bild->getValue('med_focuspoint_css');

      if ($focuspoint_data) {
        //   echo $focuspoint_data.' data-focus-w="'.$bildBreite.'" data-focus-h="'.$bildHoehe.'"<br/>';
        //   echo $focuspoint_css.'<br/>';
        echo '
        <div class="focuspoint"
          '.$focuspoint_data.'
          data-focus-w="'.$bildBreite.'"
          data-focus-h="'.$bildHoehe.'"
        >
          <img src="./files/'.$bildDateiName.'" alt="" />
        </div>
        ';

      }
    }

?>
