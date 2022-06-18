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
 *  only necessary for updates from versions pre 2.0
 *
 *  SQL-transaction is rolled back in case of update-errors
 *
 *  @var rex_addon $this
 */

if (rex_string::versionCompare($this->getVersion(), '2.0', '<'))
{
    // activate .lang-files currently in a temporary directory
    rex_i18n::addDirectory( __DIR__.'/lang' );

    // prerequisites, to fetch predefined strings
    include_once( 'lib/effect_focuspoint.php' );

    $sql = rex_sql::factory();
    $sql->beginTransaction();
    $message = '';

    // Add or identify meta_type for focuspoint-fields
    $qry = 'SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label=:label LIMIT 1';
    $type_id = $sql->getArray($qry, [':label' => rex_effect_abstract_focuspoint::META_FIELD_TYPE]);

    $type_id = $type_id
            ? $type_id[0]['id']
            : rex_metainfo_add_field_type(rex_effect_abstract_focuspoint::META_FIELD_TYPE, 'string', 20);

    // if valid type_id add default-field
    if( is_numeric($type_id) )
    {

        // Identify existing metafield by name and read current type_id
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
            $result = rex_metainfo_add_field('translate:focuspoint_field_label', rex_effect_abstract_focuspoint::MED_DEFAULT, 3, '', $type_id, '', '', '', '');
            if( $result !== true )
            {
                $message = rex_i18n::msg( 'focuspoint_install_field_error', rex_effect_abstract_focuspoint::MED_DEFAULT, "<strong><i>$result</i></strong>" );
            }
            // for unknown reason in "update.php" rex_metainfo_add_field does not add the new field to rex_media, additional measure required
            else {
                rex_sql_table::get(rex::getTable('media'))
                    ->ensureColumn(new rex_sql_column(rex_effect_abstract_focuspoint::MED_DEFAULT, 'varchar(20)', true, ''),'revision')
                    ->ensure();
            }
        }

        // Field prepared
        if( !$message )
        {

            try {

                // transfer coordinates from old data_field "med_focuspoint_data" to new default-field

                $tab = rex::getTable( 'media' );
                $qry = 'SHOW COLUMNS FROM '.$tab.' WHERE Field = "med_focuspoint_data"';
                if( $sql->getArray( $qry) )
                {

                    $qry = 'SELECT id,med_focuspoint_data FROM '.$tab.' WHERE med_focuspoint_data > ""';
                    /** @var array<integer,string> $liste */
                    $liste = $sql->getArray( $qry, [], PDO::FETCH_KEY_PAIR );
                    foreach( $liste as $k=>$v )
                    {
                        if( preg_match_all( '/(?<x>[+-]?[0-1][.][0-9]{2}),(?<y>[+-]?[0-1][.][0-9]{2})/', $v, $tags ) )
                        {
                            $x = ($tags['x'][0] + 1) * 50;
                            $y = (1 - $tags['y'][0]) * 50;
                            $x = max( 0, min( 100,$x ) );
                            $y = max( 0, min( 100,$y ) );
                            $sql->setTable( $tab );
                            $sql->setValue( rex_effect_abstract_focuspoint::MED_DEFAULT, sprintf( rex_effect_abstract_focuspoint::STRING, $x, $y ) );
                            $sql->setWhere( 'id = :id', [':id'=>$k] );
                            $sql->update();
                        }
                    }
                }

                // remove outdated meta-fields

                rex_metainfo_delete_field('med_focuspoint_data');
                rex_metainfo_delete_field('med_focuspoint_css');

                // update parameter-set per field "focuspoint-fit" and "focuspoint-resize" to new structure"

                $tab = rex::getTable('media_manager_type_effect');
                $mitte = sprintf( rex_effect_abstract_focuspoint::STRING, rex_effect_abstract_focuspoint::$mitte[0], rex_effect_abstract_focuspoint::$mitte[1] );
                $qry = "SELECT id,parameters FROM $tab";
                /** @var array<integer,string> */
                $liste = $sql->getArray( $qry, [], PDO::FETCH_KEY_PAIR );
                foreach( $liste as $k=>$v )
                {
                    $v = json_decode( $v, true );
                    if( isset($v['rex_effect_focuspoint_fit'] ) )
                    {
                        if( !isset($v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_focus']) )
                        {
                            $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_focus'] = sprintf(
                                rex_effect_abstract_focuspoint::STRING,
                                fpUpdateNumParaOk( $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_hpos'], 0, 50, 100 ),
                                fpUpdateNumParaOk( $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_vpos'], 0, 50, 100 )
                            );
                            unset( $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_hpos'] );
                            unset( $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_vpos'] );
                        }
                        if( !isset($v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_meta']) )
                        {
                            $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_meta'] =
                                $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_fp'] == rex_i18n::msg('media_manager_effekt_focuspointfit_fp_inherit')
                                ? 'default ('.rex_i18n::msg('focuspoint_edit_label_focus').')'
                                : rex_effect_abstract_focuspoint::MED_DEFAULT;
                            unset( $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_fp'] );
                        }
                        if( isset($v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_zoom']) &&
                            preg_match( '(0%|25%|50%|75%|100%)', $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_zoom'], $match )  &&
                            count($match) > 0 )
                        {
                            $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_zoom'] = $match[0];
                        } else {
                            $v['rex_effect_focuspoint_fit']['rex_effect_focuspoint_fit_zoom'] = '0%';
                        }
                    }
                    if( isset($v['rex_effect_focuspoint_resize'] ) )
                    {
                        if( !isset($v['rex_effect_focuspoint_resize']['rex_effect_focuspoint_resize_focus']) )
                        {
                            $v['rex_effect_focuspoint_resize']['rex_effect_focuspoint_resize_focus'] = $mitte;
                        }
                        if( !isset($v['rex_effect_focuspoint_resize']['rex_effect_focuspoint_resize_meta']) )
                        {
                            $v['rex_effect_focuspoint_resize']['rex_effect_focuspoint_resize_meta'] = rex_effect_abstract_focuspoint::MED_DEFAULT;
                        }
                    }
                    $sql->setTable( $tab );
                    $sql->setValue( 'parameters', json_encode( $v ) );
                    $sql->setWhere( 'id=:id', [':id'=>$k] );
                    $sql->update();
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

            } catch (Exception $e) {
                $message = rex_i18n::msg( 'focuspoint_update_error' ) . ' ' . $e->getMessage();
            }
        }
    }
    // no valid type_id
    else
    {
        $message = rex_i18n::msg( 'focuspoint_install_type_error', rex_effect_abstract_focuspoint::META_FIELD_TYPE, "<strong><i>$type_id</i></strong>" );
    }

    if( $message )
    {
        $sql->rollBack();
        $this->setProperty('updatemsg', $message );
    }
    else
    {
        $sql->commit();
    }

}


/**
 *  @param string|integer|float $para
 *  @param int|float $low
 *  @param int|float $default
 *  @param int|float $high
 *  @return int|float
 */

function fpUpdateNumParaOk( $para, $low=0, $default=0, $high=0 )
{
    $para = trim( $para );
    return ( empty($para) || !is_numeric($para) || $para < $low || $para > $high ) ? $default : (int)$para;
}
