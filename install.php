<?php

if ($somethingIsWrong) {
    $this->setProperty('installmsg', 'Something is wrong');
    $this->setProperty('install', false);
}


rex_metainfo_add_field('Focuspoint Data', 'med_focuspoint_data', '3','','1','','','','');
rex_metainfo_add_field('Focuspoint CSS', 'med_focuspoint_css', '3','','1','','','','');
