<?php

if ('REX_MEDIA[1]') {

  $file         = rex_media::get(REX_MEDIA[1]);
  $filename     = $file->getFilename();
  $titel        = $file->getTitle();
  $width        = $file->getValue('width');
  $height       = $file->getValue('height');
  $focuspoint_css     = $file->getValue('focuspoint_css');
  $focuspoint_data    = explode(",", $file->getValue('med_focuspoint_data'), 2);

  if (count($focuspoint_data) == 2) {
    echo '
       <div class="focuspoint"
          data-focus-x="'.$focuspoint_data[0].'"
          data-focus-y="'.$focuspoint_data[1].'"
          data-image-w="'.$width.'"
          data-image-h="'.$height.'">
          <img src="/media/'.$filename.'" alt="'.htmlspecialchars($titel).'" />
        </div>
        ';
    } else {
        echo '<img src="/media/'.$filename.'" alt="'.htmlspecialchars($titel).'" />';
    }

}

?>
