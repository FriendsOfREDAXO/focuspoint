<?php
/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     2.1
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  ------------------------------------------------------------------------------------------------
 *
 *  In der boot.php wird überprüft, ob eine Backend-Seite aufgerufen wird, innerhalb derer
 *  besondetr Einstellungen z.B. mittels Extension-Points vorzunehmen sind.
 *  Die Aktivitäten wurden in eine separate Datei ausgelagert, um für den Normalbetrieb der
 *  REDAXO-Instanz eine schlanke boot.php mit geringen Kompilieraufwand zu haben.
 *
 *
 *  @method void function mediaDetailPage( rex_addon $fpAddon )
 *  @method void function metainfoDefault()
 *  @method void function metainfoMedia()
 *  @method void function media_managerTypes()
 *  @method void function packages( rex_addon $fpAddon )
 */

 class focuspoint_boot {

    /**
     *  page=mediapool/media
     *
     *  Ressourcen für die Fokuspunkt-Erfassung im Mediapool einbinden
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
     */
    static public function metainfoDefault()
    {
        if( rex_request('func', 'string') != 'delete' )
        {
            rex_extension::register( 'METAINFO_TYPE_FIELDS', function( rex_extension_point $ep ){
                echo '<script type="text/javascript">$(document).ready(function(){ $("select[name$=\'[type_id]\'] option:contains(\'',rex_effect_abstract_focuspoint::META_FIELD_TYPE,'\')").detach();});</script>';
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
        // limit changing the default-focuspoint-metafield: fieldname, fieldtype, no delete
        if( rex_request('func', 'string') == 'edit' )
        {
            rex_extension::register( 'REX_FORM_GET', function( rex_extension_point $ep ){
                $form = $ep->getSubject();
                $fpMetafields = focuspoint::getMetafieldList( );
                $field_id = rex_request('field_id', 'int');
                if( array_key_exists( $field_id, $fpMetafields ) ) {
                    $fpField = $fpMetafields[ $field_id ];
                    $message = '';
                    $allCategories = '';
                    if( $fpField == rex_effect_abstract_focuspoint::MED_DEFAULT ) {
                        $message .= '<u><b>'.rex_i18n::msg('focuspoint_doc').'</b></u><br>'.rex_i18n::msg('focuspoint_edit_msg_inuse2',$fpField).'<br>';
                        $allCategories = '$(\'#enable-restrictions-checkbox\').prop( "disabled", true );';            
                    } elseif ( $effects=focuspoint::getFocuspointMetafieldInUse( $fpField ) ) {
                        $message .= '<u><b>'.rex_i18n::msg('focuspoint_doc').'</b></u><br>'.rex_i18n::msg('focuspoint_edit_msg_inuse1',$fpField).'<ul>';
                        foreach( $effects as $v ) {
                            $message .= '<li><a href="'
                                     . rex_url::backendController
                                             ([
                                                'page' => 'media_manager/types',
                                                'type_id' => $v['type_id'],
                                                'effects' => 1,
                                             ])
                                     . '">'.$v['name'].'</a></li>';
                        }
                        $message .= '</ul>';
                    }
                    if( $message ) {
                        $message .= rex_i18n::msg('focuspoint_edit_msg_inuse3',rex_i18n::msg('minfo_field_label_name'),rex_i18n::msg('minfo_field_label_type'));
                        $message = $form->getMessage() . "\n" . $message;
                        $l = strlen( rex_request($form->getName() . '_msg', 'string') );
                        if( $l ) $message = substr( $message, $l + 1 );
                        $form->setMessage( $message );
                        $id = rex_string::normalize(rex_i18n::msg('minfo_field_fieldset'),'-');
                        echo '<script type="text/javascript">$(document).ready(function(){',
                             '$(\'#rex-metainfo-field-',$id,'-name\').prop( "disabled", true );',
                             $allCategories,
                             '$(\'#rex-metainfo-field-',$id,'-delete\').remove();',
                             '$(\'#rex-metainfo-field-',$id,'-type-id option:not([selected])\').hide().remove();',
                             '});</script>';
                    }
                }
            });
        }
        // don´t remove the default-Metafield from the list
        rex_extension::register( 'REX_LIST_GET', function( rex_extension_point $ep ){
            $list = $ep->getSubject();
            $list->setColumnFormat('delete', 'custom', function ($params) {
                $list = $params['list'];
                if( $list->getValue('name') == rex_effect_abstract_focuspoint::MED_DEFAULT ) {
                    return '<small class="text-muted">' . rex_i18n::msg('focuspoint_doc') . '</small>';
                }
                if( $presetValue = $params['params'][0] ) return $list->formatValue('', $presetValue, false, 'delete');
                return $list->getColumnLink('delete', $list->getValue('delete'));
            }, [$list->getColumnFormat('delete')]);
        });
    }

    /**
     *  page=media_manager/types
     *
     *  Verhindert in der Liste der verfügbaren Media-Manager-typen, dass der von Focuspoint selbst
     *  benötigte Media-Manager-Typ "focuspoint" gelöscht oder verändert wird.
     *  Falls es sich nicht um die Zeile für "focuspoint" handelt, wird der ursprünglich
     *  vorgesehene Zellinhalt ausgegeben.
     */
    static public function media_managerTypes()
    {
        if( rex_request('effects', 'int') != 1 ) {
            rex_extension::register( 'REX_LIST_GET', function( rex_extension_point $ep ){

                $function = function ($params) {
                    $list = $params['list'];
                    if( $list->getValue('name') == rex_effect_abstract_focuspoint::MM_TYPE ) {
                        return '<small class="text-muted">' . rex_i18n::msg('focuspoint_doc') . '</small>';
                    }
                    $field = $params['field'];
                    if( $presetValue = $params['params'][0] ) {
                        return $list->formatValue($list->getValue($field), $presetValue, false, $field);
                    }
                    return $list->getColumnLink($field, $list->getValue($field));
                };

                $list = $ep->getSubject();
                $list->setColumnFormat('deleteType', 'custom', $function, [$list->getColumnFormat('deleteType')]);
                $list->setColumnFormat('editType', 'custom', $function, [$list->getColumnFormat('editType')]);
                $label = rex_i18n::msg('media_manager_type_functions');
                $list->setColumnFormat($label, 'custom', $function, [$list->getColumnFormat($label)]);
            });
        }
    }

    /**
     *  page=packages
     *
     *  leitet auf einen spezialisierten API-Handler um.
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
