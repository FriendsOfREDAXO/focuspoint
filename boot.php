<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     4.2.0
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *  Teile der Verarbeitung sind - bis auf die Abfragen - ausgelagert, um die Code-Übersetzung
 *  nur durchzuführen, wenn es notwendig ist, jedoch nicht bei jedem Aufruf.
 */

namespace FriendsOfRedaxo\Focuspoint;

use rex;
use rex_addon;
use rex_effect_focuspoint_fit;
use rex_media_manager;

/** @var rex_addon $this */

if (rex::isBackend()) {

    switch (rex_request('page', 'string')) {
        case 'mediapool/media':
            // provide support for media detail-page
            FocuspointBoot::mediaDetailPage($this);
            break;

        case 'metainfo/articles':
        case 'metainfo/categories':
        case 'metainfo/clangs':
            // delete focuspoint-datatype from html-select for articles/categories/clangs
            FocuspointBoot::metainfoDefault();
            break;

        case 'metainfo/media':
            // prevent deletion of meta-fields still in use by effects
            // limit changing the default-focuspoint-metafield: fieldname, fieldtype, no delete
            // don´t remove the default-Metafield
            FocuspointBoot::metainfoMedia();
            break;

        case 'media_manager/types':
            // prevent deletion and editing of mediamanager-type used by focuspoint
            FocuspointBoot::media_managerTypes();
            break;

        case 'packages':
            // prevent deactivation if in use by effects
            // effective only in dialog-mode via AddOn-administration-page
            FocuspointBoot::packages($this);
            break;
    }

}

rex_media_manager::addEffect(rex_effect_focuspoint_fit::class);
