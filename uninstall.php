<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     4.1.0
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *  1) Überprüfe auf Abhängigkeiten
 *      - Gibt es außer "med_focuspoint" weitere Meta_Felder vom Datentyp "Focuspoint (AddOn)"?
 *      - Gibt es Effekte außer den mitgelieferten, die auf "rex_effect_abstract_focuspoint" basieren?
 *      - Gibt davon Effekte, die im Media-Manager im Einsatz sind.
 *
 *      Da abgebrochene Uninstalls das Addon "deactivated" hinterlassen, wird zusätzlich über den
 *      EP PAGE_TITLE_SHOWN das Addon wieder aktiviert.
 *
 *  2) Lösche die zuvor installierten Standards
 *      - Meta-Feld "med_focuspoint"
 *      - Meta-Typ "Focuspoint (AddOn)"
 */

namespace FriendsOfRedaxo\Focuspoint;

use rex;
use rex_addon;
use rex_effect_abstract_focuspoint;
use rex_media_manager;
use rex_sql;

use function count;

/** @var rex_addon $this */

// make addon-parameters available
include_once 'lib/effect_focuspoint.php';

$sql = rex_sql::factory();

// remove default-meta-field
rex_metainfo_delete_field(rex_effect_abstract_focuspoint::MED_DEFAULT);

// remove meta-type
$qry = 'SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label=:label LIMIT 1';
$typeId = $sql->getArray($qry, [':label' => rex_effect_abstract_focuspoint::META_FIELD_TYPE]);

if (0 < count($typeId)) {
    rex_metainfo_delete_field_type($typeId[0]['id']);
}

// remove media-manager-type
$sql->setQuery('select id from ' . rex::getTable('media_manager_type') . ' where name="' . rex_effect_abstract_focuspoint::MM_TYPE . '" LIMIT 1');
if (0 < (int) $sql->getRows()) {
    $id = $sql->getValue('id');
    $sql->setTable(rex::getTable('media_manager_type'));
    $sql->setWhere('id=' . $id);
    $sql->delete();
    $sql->setTable(rex::getTable('media_manager_type_effect'));
    $sql->setWhere('type_id=' . $id);
    $sql->delete();
}

// ... delete corresponding cache files
rex_media_manager::deleteCache(null, rex_effect_abstract_focuspoint::MM_TYPE);
