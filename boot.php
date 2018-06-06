<?php

if (rex::isBackend()) {

    if( rex_request('page', 'string') == 'mediapool/media' )
    {
        rex_view::addCssFile($this->getAssetsUrl('focuspoint.css'));

        rex_extension::register('MEDIA_FORM_EDIT', function( $media ) {
            include_once( 'functions/class.rex_focuspoint.inc.php');
            return rex_focuspoint::show_form_info( $media );
        });


        rex_extension::register('MEDIA_FORM_ADD', function( $media ) {
            include_once( 'functions/class.rex_focuspoint.inc.php');
            rex_focuspoint::remove_inputs( $media );
        });
    }

}

rex_media_manager::addEffect('rex_effect_focuspoint_resize');
rex_media_manager::addEffect('rex_effect_focuspoint_fit');
