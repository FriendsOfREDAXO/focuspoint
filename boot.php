<?php
$addon = rex_addon::get("focuspoint");

if (rex::isBackend()) {
    rex_extension::register('MEDIA_ADDED', 'rex_focuspoint::set_media');
    rex_extension::register('MEDIA_UPDATED', 'rex_focuspoint::set_media');
    rex_extension::register('MEDIA_FORM_EDIT', 'rex_focuspoint::show_form_info');
    rex_extension::register('MEDIA_FORM_ADD', 'rex_focuspoint::remove_inputs');
    rex_view::addCssFile($addon->getAssetsUrl('focuspoint.css'));
    rex_view::addJsFile($addon->getAssetsUrl('jquery_focuspoint.js'));
};

rex_media_manager::addEffect('rex_effect_focuspoint_resize');
rex_media_manager::addEffect('rex_effect_focuspoint_fit');