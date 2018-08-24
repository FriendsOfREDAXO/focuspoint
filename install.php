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
 *  Safely add meta-type for focuspoint-metafields "focuspoint (AddOn)"
 *  and default-field "med_focuspoint"
 *
 *  As re-install uses this routine we have to ensure, that already existings entries
 *  are not deleted or overwritten.
 *
 *  As well we have to cover that a field "med_focuspoint" might already exists from other sources
 *  with a different meta-type.
 */

// make addon-parameters available
include_once ( 'lib/effect_focuspoint.php' );

$sql = rex_sql::factory();
$sql->beginTransaction();
$message = '';

// Add or identify meta_type for focuspoint-fields
$qry = 'SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label=:label LIMIT 1';
$type_id = $sql->getArray($qry, [':label' => rex_effect_abstract_focuspoint::META_FIELD_TYPE]);

$type_id = $type_id
        ? (int)$type_id[0]['id']
        : rex_metainfo_add_field_type(rex_effect_abstract_focuspoint::META_FIELD_TYPE, 'varchar', 20);

// if valid type_id add default-field
if( is_numeric($type_id) )
{
    // Identify yet existing metafield by name and read current type_id
    $qry = 'SELECT type_id FROM ' . rex::getTable('metainfo_field') . ' WHERE name=:name LIMIT 1';
    $field = $sql->getArray($qry, [':name' => rex_effect_abstract_focuspoint::MED_DEFAULT]);

    if( $field )
    {
        if( $field[0]['type_id'] != $type_id )
        {
            $message = rex_i18n::msg( 'focuspoint_install_field_exists', rex_effect_abstract_focuspoint::MED_DEFAULT,rex_effect_abstract_focuspoint::META_FIELD_TYPE );
        }
    }
    else
    {
        // field does not exist. Add field
        $result = rex_metainfo_add_field('translate:focuspoint_field_label', rex_effect_abstract_focuspoint::MED_DEFAULT, '', '', $type_id, '', '', '', '');
        if( $result !== true )
        {
            $message = rex_i18n::msg( 'focuspoint_install_field_error', rex_effect_abstract_focuspoint::MED_DEFAULT, "<strong><i>$result</i></strong>" );
        }
    }

    // add media-manager-type for interactiv focuspoint-selection
    // don't check existance of effect "resize"; just setup.
    $sql->setQuery('select id, name from '.rex::getTable('media_manager_type').' where name="'.rex_effect_abstract_focuspoint::MM_TYPE.'" LIMIT 1');
    if( $sql->getRows() ) {
        $id = $sql->getValue('id');
    } else {
        $sql->setTable( rex::getTable('media_manager_type') );
        $sql->setValue( 'name', rex_effect_abstract_focuspoint::MM_TYPE );
        $sql->insert();
        $id = $sql->getLastId();
    }
    $sql->setTable( rex::getTable('media_manager_type_effect') );
    $sql->setWhere( 'type_id='.$id );
    $sql->delete();
    $sql->setTable( rex::getTable('media_manager_type_effect') );
    $sql->setValue( 'type_id', $id );
    $sql->setValue( 'effect', 'resize' );
    $sql->setValue( 'parameters', '{"rex_effect_resize":{"rex_effect_resize_width":"1024","rex_effect_resize_height":"1024","rex_effect_resize_style":"maximum","rex_effect_resize_allow_enlarge":"enlarge"}}' );
    $sql->addGlobalUpdateFields();
    $sql->addGlobalCreateFields();
    $sql->insert();

}
// no valid type_id
else
{
    $message = rex_i18n::msg( 'focuspoint_install_type_error', rex_effect_abstract_focuspoint::META_FIELD_TYPE, "<strong><i>$type_id</i></strong>" );
}

if( $message )
{
    $sql->rollBack();
    $this->setProperty('installmsg', $message );
}
else
{
    $sql->commit();
}
