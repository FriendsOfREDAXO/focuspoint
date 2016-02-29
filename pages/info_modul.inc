<?php

if ('REX_MEDIA[1]') {

    $bild               = OOMedia::getMediaByName('REX_MEDIA[1]');
    $bildTitle          = $bild->getTitle();
    $bildDateiName      = $bild->getFileName();
    $bildBreite         = $bild->getWidth();
    $bildHoehe          = $bild->getHeight();
    $focuspoint_css     = $bild->getValue('med_focuspoint_css');
    $focuspoint_data    = explode(",", $bild->getValue('med_focuspoint_data'), 2);

    if (count($focuspoint_data) == 2) {
        echo '
        <div class="focuspoint"
          data-focus-x="'.$focuspoint_data[0].'"
          data-focus-y="'.$focuspoint_data[1].'"
          data-image-w="'.$bildBreite.'"
          data-image-h="'.$bildHoehe.'">
          <img src="/files/'.$bildDateiName.'" alt="'.htmlspecialchars($bildTitle).'" />
        </div>
        ';
    } else {
        echo '<img src="/files/'.$bildDateiName.'" alt="'.htmlspecialchars($bildTitle).'" />';
    }

}

?>
