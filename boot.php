<?php

if (rex::isBackend()) {

    if( rex_request('page', 'string') == 'mediapool/media' )
    {
        rex_view::addCssFile($this->getAssetsUrl('focuspoint.css'));

        rex_extension::register('MEDIA_FORM_EDIT', function( $media ) {
            return rex_focuspoint::show_form_info( $media );
        });


        rex_extension::register('MEDIA_FORM_ADD', function( $media ) {
             rex_focuspoint::remove_inputs( $media );
        });
    }

}

rex_media_manager::addEffect('rex_effect_focuspoint_resize');
rex_media_manager::addEffect('rex_effect_focuspoint_fit');
