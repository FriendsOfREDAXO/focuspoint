<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     4.0.2
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *  Teile der Verarbeitung sind - bis auf die Abfragen - ausgelagert, um die Code-Übersetzung
 *  nur durchzuführen, wenn es notwendig ist, jedoch nicht bei jedem Aufruf.
 *
 *  @var rex_addon $this
 */

if (rex::isBackend())
{

    switch ( rex_request('page', 'string') )
    {
        case 'mediapool/media':
            // provide support for media detail-page
            focuspoint_boot::mediaDetailPage( $this );
            break;

        case 'metainfo/articles':
        case 'metainfo/categories':
        case 'metainfo/clangs':
            // delete focuspoint-datatype from html-select for articles/categories/clangs
            focuspoint_boot::metainfoDefault();
            break;

        case 'metainfo/media':
            // prevent deletion of meta-fields still in use by effects
            // limit changing the default-focuspoint-metafield: fieldname, fieldtype, no delete
            // don´t remove the default-Metafield
            focuspoint_boot::metainfoMedia();
            break;

        case 'media_manager/types':
            // prevent deletion and editing of mediamanager-type used by focuspoint
            focuspoint_boot::media_managerTypes();
            break;

        case 'packages':
            // prevent deactivation if in use by effects
            // effective only in dialog-mode via AddOn-administration-page
            focuspoint_boot::packages( $this );
            break;
    }

}

rex_media_manager::addEffect('rex_effect_focuspoint_fit');
