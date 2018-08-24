<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     2.0
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *
 */

if (rex::isBackend())
{

    switch ( rex_request('page', 'string') )
    {
        case 'mediapool/media':
            // provide support for media detail-page
            rex_view::addCssFile($this->getAssetsUrl('focuspoint.min.css'));
            rex_view::addJsFile($this->getAssetsUrl('focuspoint.min.js'));
            rex_extension::register('MEDIA_DETAIL_SIDEBAR', 'focuspoint::show_sidebar');
            rex_extension::register('METAINFO_CUSTOM_FIELD', 'focuspoint::customfield' );
            break;

        case 'metainfo/articles':
        case 'metainfo/categories':
        case 'metainfo/clangs':
            // delete focuspoint-datatype from html-select for articles/categories/clangs
            if( ($func = rex_request('func', 'string')) && $func != 'delete' )
            {
                rex_extension::register( 'METAINFO_TYPE_FIELDS', function( rex_extension_point $ep ){
                    echo '<script>$(document).ready(function(){ $("select[name$=\'[type_id]\'] option:contains(\''.rex_effect_abstract_focuspoint::META_FIELD_TYPE.'\')").detach();});</script>';
                });
            }
            break;

        case 'metainfo/media':
            // prevent deletion of meta-fields still in use by effects
            if( rex_request('func', 'string') == 'delete' )
            {
                rex_extension::register( 'PACKAGES_INCLUDED', function( rex_extension_point $ep ){
                    if( $result = focuspoint::metafield_is_in_use( rex_request('field_id', 'int', 0) ) )
                    {
                        $_REQUEST['func'] = '';
                        rex_extension::register('PAGE_TITLE_SHOWN', function(rex_extension_point $ep) use ($result) {
                            $ep->setSubject(rex_view::error($result) . $ep->getSubject());
                        });
                    }
                });
            }
        case 'packages':
            // prevent deactivation if in use by effects
            // effective only in dialog-mode via AddOn-administration-page
            if( rex_request('package', 'string') == $this->getName()
                && isset($_REQUEST['rex-api-call'])
                && $_REQUEST['rex-api-call'] == 'package' )
            {
                $_REQUEST['rex-api-call'] = 'focuspoint_package';
            }
            break;
    }

}

rex_media_manager::addEffect('rex_effect_focuspoint_fit');
// deprecated:
rex_media_manager::addEffect('rex_effect_focuspoint_resize');
