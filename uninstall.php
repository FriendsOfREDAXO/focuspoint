<?php

$somethingIsWrong = false;
if ($somethingIsWrong) {
    throw new rex_functional_exception('Something is wrong');
}

if ($somethingIsWrong) {
    $this->setProperty('installmsg', 'Something is wrong');
    $this->setProperty('install', true);
}

rex_metainfo_delete_field('med_focuspoint_data');
rex_metainfo_delete_field('med_focuspoint_css');

$REX['ADDON']['install']['focuspoint'] = 0;
