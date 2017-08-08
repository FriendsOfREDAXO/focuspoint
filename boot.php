<?php

if (rex::isBackend()) {
  rex_extension::register('MEDIA_ADDED', 'rex_focuspoint::set_media');
  rex_extension::register('MEDIA_UPDATED', 'rex_focuspoint::set_media');
  rex_extension::register('MEDIA_FORM_EDIT', 'rex_focuspoint::show_form_info');
};

rex_media_manager::addEffect('rex_effect_focuspoint_resize');
rex_media_manager::addEffect('rex_effect_focuspoint_fit');
