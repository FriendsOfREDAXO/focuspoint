<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     4.2.3
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *  Safely add meta-type for focuspoint-metafields "focuspoint (AddOn)"
 *  and default-field "med_focuspoint"
 *
 *  As re-install uses this routine we have to ensure, that already existings entries
 *  are not deleted or overwritten.
 *
 *  As well we have to cover that a field "med_focuspoint" might already exists from other sources
 *  with a different meta-type.
 */

namespace FriendsOfRedaxo\Focuspoint;

use rex;
use rex_addon;
use rex_effect_abstract_focuspoint;
use rex_i18n;
use rex_metainfo_table_manager;
use rex_sql;

use function count;
use function in_array;

/** @var rex_addon $this */

// make addon-parameters available
include_once 'lib/effect_abstract_focuspoint.php';

$successMsg = [];
$message = '';
$sql = rex_sql::factory();

// Erst einmal prüfen, welche Elemente bereits existieren und ob die Daten stimmig sind.
// Daraus Aktionsanforderungen ableiten statt sofort fehlende Elemente anzulegen.
// Grund: das in den SQL-Statements auch DDL-Befehle (ALTER TABLE) vorkommen können, funktionierten
// die SQL-Transaktionen bzw. Committ/rollBack nicht über die gesamte Installation.
// (Ist eine Art Workaround)

$db_metafield = rex::getTable('metainfo_field');
$db_metatype = rex::getTable('metainfo_type');
$db_mmtype = rex::getTable('media_manager_type');
$db_mmeffect = rex::getTable('media_manager_type_effect');
$db_media = rex::getTable('media');

$meta_action_type = false;
$meta_action_field = false;
$meta_action_media = false;
$meta_action_connect = false;

$mm_action_type = false;
$mm_action_effect = false;

// --- Metainfos analysieren -----------------------------------------------------------------------

$qry = 'SELECT a.id,a.type_id,b.id AS tid,b.label AS tlabel FROM ' . $db_metafield . ' AS a LEFT JOIN ' . $db_metatype . ' AS b ON a.type_id=b.id WHERE a.name=:name';
$meta_field = $sql->getArray($qry, [':name' => rex_effect_abstract_focuspoint::MED_DEFAULT]);
if (0 < count($meta_field)) {
    $meta_field_id = $meta_field[0]['id'];
    $meta_field_type = $meta_field[0]['type_id'];
    $meta_field_tid = $meta_field[0]['tid'];
    $meta_field_tlabel = $meta_field[0]['tlabel'];
} else {
    $meta_field_id = null;
    $meta_field_type = null;
    $meta_field_tid = null;
    $meta_field_tlabel = null;
}

$qry = 'SELECT id FROM ' . $db_metatype . ' WHERE label=:label LIMIT 1';
$meta_type = $sql->getArray($qry, [':label' => rex_effect_abstract_focuspoint::META_FIELD_TYPE]);
if (0 < count($meta_type)) {
    $meta_type_id = $meta_type[0]['id'];
} else {
    $meta_type_id = null;
}

// Typ und Feld existieren. Die Typ-ID im Feld entspricht der Metatyp-Id
if (null !== $meta_field_id && null !== $meta_type_id && $meta_field_type === $meta_type_id) {
    // ok, alles passt zusammen
}

// Typ und Feld existieren. Die Typ-ID im Feld verweist auf einen anderen Typ
elseif (null !== $meta_field_id && null !== $meta_type_id && $meta_field_type === $meta_field_tid) {
    // Das Metafeld hat den falschen Typ. Abbruch und Aufforderung zum Aufräumen
    // Abbruch
    $message = '1 ' . rex_i18n::msg('focuspoint_install_field_exists', rex_effect_abstract_focuspoint::MED_DEFAULT, rex_effect_abstract_focuspoint::META_FIELD_TYPE);
}

// Typ und Feld existieren. Die Typ-ID im Feld verweist auf einen nicht existenten Typ
elseif (null !== $meta_field_id && null !== $meta_type_id && null === $meta_field_tid) {
    // Feld ändern auf meta_type_id
    $meta_action_connect = true;
}

// nur Typ existiert
elseif (null !== $meta_type_id && null === $meta_field_tid) {
    // Feld auf den Typ anlegen
    $meta_action_field = true;
    $meta_action_connect = true;
}

// nur Feld existiert und verweist auf einen existenten Typ
elseif (null !== $meta_field_id && null !== $meta_field_tid) {
    // Das Metafeld hat den falschen Typ. Abbruch und Aufforderung zum Aufräumen
    // Abbruch
    $message = '2 ' . rex_i18n::msg('focuspoint_install_field_exists', rex_effect_abstract_focuspoint::MED_DEFAULT, rex_effect_abstract_focuspoint::META_FIELD_TYPE);
}

// nur Feld existiert und verweist auf einen nicht existenten Typ
elseif (null !== $meta_field_id) {
    // Typ anlegen und mit dem Feld verbinden
    $meta_action_type = true;
    $meta_action_connect = true;
}

// weder Feld noch Typ existieren
else {
    $meta_action_type = true;
    $meta_action_field = true;
    $meta_action_connect = true;
}

// Für das Metafeld besteht die Abhängigkeit zur zugehörigen Spalte in rex_media
$sql->setQuery('SELECT * FROM ' . $db_media . ' LIMIT 1');
$media_feld = in_array(rex_effect_abstract_focuspoint::MED_DEFAULT, $sql->getFieldnames(), true);
// Metafeld existiert in rex_metainfo_field, aber die Spalte in rex_media fehlt
if (!$meta_action_field && !$media_feld) {
    $meta_action_media = true;
}
// Metafeld existiert nicht in rex_metainfo_field, aber die Spalte in rex_media ist existent
elseif ($meta_action_field && $media_feld) {
    // Da die Spalte wichtige Informationen enthalten könnte: Meldung und Abbruch
    $message = rex_i18n::msg('focuspoint_install_media_exists', rex_effect_abstract_focuspoint::MED_DEFAULT, $db_media);
}

// --- Media-Manager-Einträge analysieren ----------------------------------------------------------

$qry = 'SELECT a.id,b.type_id from ' . $db_mmtype . ' AS a LEFT JOIN ' . $db_mmeffect . ' AS b ON a.id = b.type_id WHERE a.name=:name LIMIT 1';
$mm_type = $sql->getArray($qry, [':name' => rex_effect_abstract_focuspoint::MM_TYPE]);
$mm_type_id = $mm_type[0]['id'] ?? null;
$mm_action_type = null === $mm_type_id;
$mm_action_effect = null === ($mm_type[0]['type_id'] ?? null);

// --- Metainfos anlegen ---------------------------------------------------------------------------

// Die fehlenden Elemente sind vorab ermittelt. Fehler z.B. durch doppelte Kennungen sollten also
// nicht auftreten. Das Restrisiko einer unvollständigen Installation mit Restanten ist minimal.
// Fehlende Elemente werden ergänzt

if ('' < $meta_action_type && '' === $message) {
    $result = rex_metainfo_add_field_type(rex_effect_abstract_focuspoint::META_FIELD_TYPE, 'varchar', 20);
    if (is_numeric($result)) {
        $meta_type_id = (int) $result;
        $meta_action_connect = true;
        $successMsg[] = rex_i18n::msg('focuspoint_install_type_ok', rex_effect_abstract_focuspoint::META_FIELD_TYPE);
    } else {
        $message = rex_i18n::rawMsg('focuspoint_install_type_error', rex_effect_abstract_focuspoint::META_FIELD_TYPE, "<strong><i>$$result</i></strong>");
    }
}

if ($meta_action_field && '' === $message) {
    $result = rex_metainfo_add_field('translate:focuspoint_field_label', rex_effect_abstract_focuspoint::MED_DEFAULT, 0, '', $meta_type_id, '', '', '', '');
    if (true === $result) {
        $successMsg[] = rex_i18n::msg('focuspoint_install_field_ok', rex_effect_abstract_focuspoint::MED_DEFAULT);
        $meta_action_connect = false; // impliziet beim Anlegen durchgeführt
        $meta_action_media = false; // impliziet beim Anlegen durchgeführt
    } else {
        $message = rex_i18n::rawMsg('focuspoint_install_field_error', rex_effect_abstract_focuspoint::MED_DEFAULT, "<strong><i>$result</i></strong>");
    }
}

if ($meta_action_media && '' === $message) {
    $tableManager = new rex_metainfo_table_manager('rex_media');
    if ($tableManager->addColumn(rex_effect_abstract_focuspoint::MED_DEFAULT, 'varchar', 20, '')) {
        $successMsg[] = rex_i18n::msg('focuspoint_install_media_ok', rex_effect_abstract_focuspoint::MED_DEFAULT, $db_media);
    } else {
        $message = rex_i18n::rawMsg('focuspoint_install_media_error', rex_effect_abstract_focuspoint::MED_DEFAULT, $db_media);
    }
}

if ($meta_action_connect && '' === $message) {
    $sql->setTable($db_metafield);
    /**
     * STAN: Value 'type_id' does not exist in table selected via setTable().
     * Falsch. rex_metainfo_field.type_id ist ein Standardfeld.
     * @phpstan-ignore-next-line
     */
    $sql->setValue('type_id', $meta_type_id);
    $sql->setWhere('name=:name', [':name' => rex_effect_abstract_focuspoint::MED_DEFAULT]);
    $sql->update();
    $successMsg[] = rex_i18n::msg('focuspoint_install_link_ok', rex_effect_abstract_focuspoint::META_FIELD_TYPE, rex_effect_abstract_focuspoint::MED_DEFAULT);
}

// --- Media-Manager-Einträge anlegen --------------------------------------------------------------

if ($mm_action_type && '' === $message) {
    $sql->setTable($db_mmtype);
    /**
     * STAN: Value 'name' does not exist in table selected via setTable().
     * Falsch. media_manager_type.name ist ein Standardfeld.
     * @phpstan-ignore-next-line
     */
    $sql->setValue('name', rex_effect_abstract_focuspoint::MM_TYPE);
    $sql->addGlobalCreateFields();
    $sql->addGlobalUpdateFields();
    $sql->insert();
    $mm_type_id = $sql->getLastId();
    $mm_action_effect = true;
    $successMsg[] = rex_i18n::msg('focuspoint_install_mmtype_ok', rex_effect_abstract_focuspoint::MM_TYPE);
}

if ($mm_action_effect && '' === $message) {
    $sql->setTable($db_mmeffect);
    /**
     * STAN: Value 'type_id' does not exist in table selected via setTable().
     * Falsch. media_manager_type_effect.type_id ist ein Standardfeld.
     * @phpstan-ignore-next-line
     */
    $sql->setValue('type_id', $mm_type_id);
    /**
     * STAN: Value 'effect' does not exist in table selected via setTable().
     * Falsch. media_manager_type_effect.effect ist ein Standardfeld.
     * @phpstan-ignore-next-line
     */
    $sql->setValue('effect', 'resize');
    /**
     * STAN: Value 'parameters' does not exist in table selected via setTable().
     * Falsch. media_manager_type_effect.parameters ist ein Standardfeld.
     * @phpstan-ignore-next-line
     */
    $sql->setValue('parameters', '{"rex_effect_resize":{"rex_effect_resize_width":"1024","rex_effect_resize_height":"1024","rex_effect_resize_style":"maximum","rex_effect_resize_allow_enlarge":"enlarge"}}');
    $sql->addGlobalUpdateFields();
    $sql->addGlobalCreateFields();
    $sql->insert();
    $successMsg[] = rex_i18n::msg('focuspoint_install_mmeffect_ok', rex_effect_abstract_focuspoint::MM_TYPE);
}

// Fehlermeldung übertragen; Abbruch
if ('' < $message) {
    $this->setProperty('installmsg', $message);
    return;
}

if (0 < count($successMsg)) {
    $this->setProperty('successmsg', '<ul><li>' . implode('</li><li>', $successMsg) . '</ul>');
}
