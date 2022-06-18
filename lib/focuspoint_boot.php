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
 *  In der boot.php wird überprüft, ob eine Backend-Seite aufgerufen wird, innerhalb derer
 *  besondere Einstellungen z.B. mittels Extension-Points vorzunehmen sind.
 *  Die Aktivitäten wurden in eine separate Datei ausgelagert, um für den Normalbetrieb der
 *  REDAXO-Instanz eine schlanke boot.php mit geringem Kompilieraufwand zu haben.
 *
 */

class focuspoint_boot {

    /**
     *  page=mediapool/media
     *
     *  Ressourcen für die Fokuspunkt-Erfassung im Mediapool einbinden
     * 
     *  @param rex_addon $fpAddon
     *  @return void
     */
    static public function mediaDetailPage( $fpAddon )
    {
        rex_view::addCssFile($fpAddon->getAssetsUrl('focuspoint.min.css'));
        rex_view::addJsFile($fpAddon->getAssetsUrl('focuspoint.min.js'));
        rex_extension::register('MEDIA_DETAIL_SIDEBAR', 'focuspoint::show_sidebar');
        rex_extension::register('METAINFO_CUSTOM_FIELD', 'focuspoint::customfield' );
    }

    /**
     *  page=metainfo/articles
     *  page=metainfo/categories
     *  page=metainfo/clangs
     *
     *  Auswahl des Metainfo-Datentyp "focuspoint (AddOn)" ausblenden da nur für Medien relevant
     * 
     *  @return void
     */
    static public function metainfoDefault()
    {
        if( rex_request('func', 'string') != 'delete' )
        {
            rex_extension::register( 'REX_FORM_GET', function( rex_extension_point $ep ){
                try {
                    // provide access to the form-elements
                    $formReflection = new focuspoint_reflection( $ep->getSubject() );
                    $fieldset = $formReflection->getPropertyValue( 'fieldset' );
                    // search the type-select
                    $typeid = $formReflection->executeMethod ( 'getElement', [$fieldset,'type_id'] );
                    $typeidReflection = new focuspoint_reflection( $typeid );
                    // get access to the internal REX_SELECT-element
                    $selectReflection = new focuspoint_reflection( $typeidReflection->getPropertyValue('select') );
                    $options = $selectReflection->getPropertyValue('options');
                    foreach( $options[0][0] as $i=>$o ){
                        if( $o[0] != rex_effect_abstract_focuspoint::META_FIELD_TYPE ) continue;
                        array_splice( $options[0][0], $i, 1, [] );
                        $selectReflection->setPropertyValue( 'options',$options );
                        $selectReflection->setPropertyValue( 'optCount',$selectReflection->getPropertyValue('optCount') - 1 );
                        return;
                    }
                } catch (\ReflectionException $e) {
                    return;
                }
            });

        }
    }

    /**
     *  page=metainfo/media
     *
     *  1) Metafelder werden in Media-Manager-Typen bzw. den eingebundenen Focuspunkt-Effekten
     *     referenziert. Sofern noch Effekte ein Focuspunkt-Metafeld nutzen, darf es nicht
     *     gelöscht werden. Überprüfung beim Löschen.
     *  2) Edit: Das Default-Metafeld für Fokuspunkte darf weder gelöscht werden noch darf es einen
     *     anderen Namen oder Datentyp erhalten. In der Eingabemaske (func=edit) werden die
     *     entsprechenden Felder gesperrt, gelöscht oder begrenzt. (per JS)
     *     Gilt auch für Fokuspunkt-Felder, die bereits in Effekten/Typen des MM genutzt werden.
     *  3) Liste: In der Liste der Metafelder wird ebenfalls das Default-Feld gegen Löschen gesperrt
     * 
     *  @return void
     */
    static public function metainfoMedia()
    {
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

        // limit changing the default-focuspoint-metafield and fields in use: fieldname, fieldtype, no delete
        if( rex_request('func', 'string') == 'edit' )
        {
            rex_extension::register( 'REX_FORM_GET', function( rex_extension_point $ep ){
                $form = $ep->getSubject();
                $fpMetafields = focuspoint::getMetafieldList( );
                $field_id = rex_request('field_id', 'int');
                if( array_key_exists( $field_id, $fpMetafields ) ) {
                    $fpField = $fpMetafields[ $field_id ];
                    $message = '';

                    try {
                        // provide access to the form-elements
                        $formReflection = new focuspoint_reflection( $ep->getSubject() );
                        $fieldset = $formReflection->getPropertyValue( 'fieldset' );
                        $elements = $formReflection->getPropertyValue( 'elements' );
                        $fselements = $elements[$fieldset] ?? [];

                        // the default-field is not restrictable to mediapool-categories
                        if( $fpField == rex_effect_abstract_focuspoint::MED_DEFAULT ) {
                            $message .= rex_i18n::msg('focuspoint_edit_msg_inuse2',$fpField).'<br>';
                        }
                        // focuspoint-fields in use will get restrictions
                        if ( $effects=focuspoint::getFocuspointMetafieldInUse( $fpField ) ) {
                             $message .= rex_i18n::msg('focuspoint_edit_msg_inuse1', $fpField) .
                                '<br>' . focuspoint::getFocuspointEffectsInUseMessage( $effects );
                        }
                        if( $message ) {
                            $message .= rex_i18n::msg('focuspoint_edit_msg_inuse3',rex_i18n::msg('minfo_field_label_name'),rex_i18n::msg('minfo_field_label_type'));
                            echo rex_view::info('<u><b>'.rex_i18n::msg('focuspoint_doc').'</b></u><br>'.$message) . "\n";
                            foreach( $fselements as $k=>$e ) {
                                if( $e->getFieldName() == 'name' ) {
                                    // prevent the name from being changed by turning the field in a hidden one.
                                    // Don´t use type=hidden due to rex_form-behavior
                                    $e->setPrefix( '<p class="form-control-static">'.$form->stripPrefix($e->getValue()).'</p>' );
                                    $e->setAttribute('class','hidden');
                                    continue;
                                }
                                if( $e->getFieldName() == 'type_id' ) {
                                    // replace by a simple hidden input to preserve the value
                                    // Don´t use type=hidden due to rex_form-behavior
                                    $x = $form->addInputField( 'input','type_id',null,[],false );
                                    $x->setLabel( $e->getLabel() );
                                    $x->setPrefix( '<p class="form-control-static">'.rex_effect_abstract_focuspoint::META_FIELD_TYPE.'</p>' );
                                    $x->setAttribute( 'class','hidden' );
                                    $fselements[$k] = $x;
                                    continue;
                                }
                                if( get_class($e) == 'rex_form_control_element' ) {
                                    // don´t delete the default-field or fields in use
                                    // so remove the delete-button
                                    $controlReflection = new focuspoint_reflection($e);
                                    $controlReflection->setPropertyValue( 'deleteElement',null );
                                    continue;
                                }
                            }
                        }
                        $elements[$fieldset] = $fselements;
                        $formReflection->setPropertyValue( 'elements',$elements );
                    } catch (\ReflectionException $e) {
                        return;
                    }
                }
            });
        }
        // don´t remove the default-Metafield from the list,
        rex_extension::register( 'REX_LIST_GET', function( rex_extension_point $ep ){
            $list = $ep->getSubject();
# Erkenntnis aus rexstan: $effectsInUse wird in der custom-function nicht (mehr) benutzt.
# Erst mal als Kommentar im Code belassen
#            $effectsInUse = focuspoint::getFocuspointEffectsInUse();
#            $list->setColumnFormat('delete', 'custom', function ($params) use($effectsInUse) {
            $list->setColumnFormat('delete', 'custom', function ($params) {
                $list = $params['list'];
                if( $list->getValue('name') == rex_effect_abstract_focuspoint::MED_DEFAULT ) {
                    return '<small class="text-muted">' . rex_i18n::msg('focuspoint_doc') . '</small>';
                }
                # planned with V3.0 because it is a breaking change:
                #   show detailed in-use-information insteag of a "blocked"
                # if( $inUse = focuspoint::metafield_is_in_use( $list->getValue('id') ) ) return '<small class="text-muted">'.$inUse.'</small>';
                if( $presetValue = $params['params'][0] ) return $list->formatValue('', $presetValue, false, 'delete');
                return $list->getColumnLink('delete', $list->getValue('delete'));
            }, [$list->getColumnFormat('delete')]);
        });
    }

    /**
     *  page=media_manager/types
     *
     *  Verhindert in der Liste der verfügbaren Media-Manager-Typen bzw. im Edit-Formular,
     *  dass der von Focuspoint selbst benötigte Media-Manager-Typ "rex_effect_abstract_focuspoint::MM_TYPE"
     *  gelöscht oder sein Name verändert wird.
     * 
     *  @return void
     */
    static public function media_managerTypes()
    {
        if( rex_request('effects', 'int') != 1 ) {

            // Listenansicht anpassen und hier schon unzulässige Button deaktivieren
            rex_extension::register( 'REX_LIST_GET', function( rex_extension_point $ep ){
                $list = $ep->getSubject();
                $list->setColumnFormat('deleteType', 'custom', function($params){
                    $list = $params['list'];
                    if( $list->getValue('name') == rex_effect_abstract_focuspoint::MM_TYPE ) {
                        return '<small class="text-muted">' . rex_i18n::msg('focuspoint_doc') . '</small>';
                    }
                    $field = $params['field'];
                    if( $presetValue = $params['params'][0] ) {
                        return $list->formatValue($list->getValue($field), $presetValue, false, $field);
                    }
                    return $list->getColumnLink($field, $list->getValue($field));
                }, [$list->getColumnFormat('deleteType')]);
            });


        }

        // Verhindert beim Editieren des Support-Media-Types im Medienpool dass der Name überschrieben wird.
        // Außerdem darf der Eintrag nicht gelöscht werden, daher muss der Löschbutton unterdrückt werden.
        if( 'edit' == rex_request('func', 'string') ) {

            rex_extension::register( 'REX_FORM_CONTROL_FIELDS', function( rex_extension_point $ep ){
                $sql = rex_sql::factory();
                $qry = 'SELECT * FROM ' . rex::getTable('media_manager_type') . ' WHERE id=? and name=?';
                $sql->setQuery($qry, [rex_request('type_id', 'int'),rex_effect_abstract_focuspoint::MM_TYPE]);
                if( $sql->getRows() ) {
                    $cf = $ep->getSubject();
                    $cf['delete'] = '';
                    $ep->setSubject( $cf );
                    rex_extension::register( 'REX_FORM_GET', function( rex_extension_point $ep ){
                        $form = $ep->getSubject();
                        // provide access to the form-elements
                        $formReflection = new focuspoint_reflection( $form );
                        $fieldset = $formReflection->getPropertyValue( 'fieldset' );
                        $elements = $formReflection->getPropertyValue( 'elements' );
                        $fselements = $elements[$fieldset] ?? [];
                        foreach( $fselements as $k=>$e ) {
                            if( 'name' == $e->getFieldName() ) {
                                // prevent the name from being changed by turning the field in a hidden one.
                                // Don´t use type=hidden due to rex_form-behavior
                                $e->setPrefix( '<p class="form-control-static">'.$e->getValue().'</p>' );
                                $e->setAttribute('class','hidden');
                                break;
                            }
                        }
                    });
                }
            });

        }

        // Falls doch ein "delete" ankommt und den Default-Type betrifft: abblocken
        if( 'delete' == rex_request('func', 'string') ) {
            $sql = rex_sql::factory();
            $qry = 'SELECT * FROM ' . rex::getTable('media_manager_type') . ' WHERE id=? and name=?';
            $sql->setQuery($qry, [rex_request('type_id', 'int'),rex_effect_abstract_focuspoint::MM_TYPE]);
            if( $sql->getRows() ) {
                $_REQUEST['func'] = '';
                rex_extension::register('PAGE_TITLE_SHOWN', function(rex_extension_point $ep) {
                    $result = rex_i18n::msg('focuspoint_isinuse_dontdeletedefault',rex_effect_abstract_focuspoint::MM_TYPE);
                    $ep->setSubject(rex_view::error($result) . $ep->getSubject());
                });
            };
        }
    }

    /**
     *  page=packages
     *
     *  leitet auf einen spezialisierten API-Handler um.
     * 
     *  @param rex_addon $fpAddon
     *  @return void
     */
    static public function packages( $fpAddon )
    {
        if( rex_request('package', 'string') == $fpAddon->getName()
            && isset($_REQUEST['rex-api-call'])
            && $_REQUEST['rex-api-call'] == 'package' )
        {
            $_REQUEST['rex-api-call'] = 'focuspoint_package';
        }
    }

}
