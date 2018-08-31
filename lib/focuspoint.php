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
 *  Die Klasse "focuspoint" stellt Service-Funktionen bereit, die in Extension-Points
 *  aufgerufen werden
 *
 *  @method string show_sidebar( rex_extension_point $ep )
 *  @method array customfield( rex_extension_point $ep )
 *  @method string checkUninstallDependencies( )
 *  @method string checkActivateDependencies( )
 *  @method string checkDeactivateDependencies( )
 *  @method string metafield_is_in_use( rex_extension_point $ep )
 *  @method array getMetafieldList( $extern=false )
 *  @method array getFocuspointMetafieldInUse( string $feld )
 *  @method array getFocuspointEffects( $extern=false )
 *  @method array getFocuspointEffectsInUse( )
 *  @method string getFocuspointEffectsInUseMessage( array $effekte )
 */

class focuspoint
{

    /**
     *  Erzeugt den HTML-Code, der in der Sidebar des Media-Detailformulars zur interaktiven
     *  Auswahl des Fokuspunktes eingebaut wird.
     *
     *  Sollte die Mediendatei kein Bild sein (z.B. eine PDF), wird statt dessen Javacript gesendet,
     *  dass die Eingabefelder für Fokuspunkte ausblendet.
     *
     *  show_sidebar ermittelt, welche Media-Manager-Typen Fokuspunkt-Effekte enthalten (also
     *  Effekte, die von 'rex_effect_abstract_focuspoint' abstammen. Wenn es keine Effekte gibt,
     *  wird die Preview nicht eingebaut.
     *
     *  Falls es mehr als ein Metafeld gibt und mindestens eines davon "hidden" ist, wird eine
     *  Feldliste für ein Feldauswahl-Element erzeugt.
     *
     *  Das HTML wird vom Fragment "fp_panel.php" erzeugt.
     *
     *  @param  rex_extension_point $ep
     *
     *  @return string|void   modifiziertes Sidebar-Html | keine Änderung
     */

    public static function show_sidebar( rex_extension_point $ep )
    {
        // Abbruch wenn kein Bild
        $params = $ep->getParams();

        if( !$params['is_image'] )
        {
            echo '<script>$(document).ready(function() {$(".focuspoint-form-group").addClass("hidden");});</script>';
            return;
        }

        // Identifiziere das Vorschaubild
        //
        //      <a href="index.php?rex_media_type=rex_mediapool_maximized&amp;rex_media_file=filename&amp;buster=1527191505">
        //          <img class="img-responsive" src="index.php?rex_media_type=rex_mediapool_detail&amp;rex_media_file=filename&amp;buster=1234567890" alt="0" title="0">
        //      </a>
        //
        // Konkret: suche zuerst den A-Tag.
        // Der Teil zwischen P1 und P2 wird später durch das neue HTML ersetzt.

        $text = $ep->getSubject();
        $mediafile = $params['filename'];

        $referenz = '<a href="index.php?rex_media_type=rex_mediapool_maximized&amp;rex_media_file='.$mediafile;

        $p1 = stripos( $text, $referenz );
        if( $p1 === false ) return;

        $p2 = stripos( $text, '</a>', $p1 + strlen($referenz) );
        if( $p2 === false ) return;
        $p2 = $p2 + 4;


        $fragment = new rex_fragment();
        $fragment->setVar ('mediafile', $mediafile );

        // relevante Media-Typen abrufen (nur Mediatypes, die Fokuspoint-Effekte beinhalten)
        // benutze Felder zuordnen
        // array[typ] = [ feld1, feld2, ...]
        $typen = array_unique( array_column( self::getFocuspointEffectsInUse(), 'name', 'type_id' ) );
        sort( $typen );
        $typen = array_combine($typen,array_fill(0,count($typen),[]));
        foreach( self::getMetafieldList( ) as $f ) {
            foreach( self::getFocuspointMetafieldInUse( $f ) as $e ) $typen[$e['name']][] = $f;
        }
        $fragment->setVar( 'mediatypes', $typen );

        // Option-Liste der Felder aufbauen - falls es mindestens zwei Felder und davon mindestens ein hidden-Feld gibt.
        $qry = 'SELECT name,title,params FROM '.rex::getTable('metainfo_field').' WHERE name LIKE "med_%" AND type_id = (SELECT id FROM '.rex::getTable('metainfo_type').' WHERE label="'.rex_effect_abstract_focuspoint::META_FIELD_TYPE.'") ORDER BY priority ASC';
        $felder = rex_sql::factory()->getArray( $qry );
        if( count($felder) > 1 )
        {
            $feldauswahl = [];
            $hidden = false;
            foreach( $felder as $feld )
            {
                $feldauswahl[ $feld['name'] ] = $feld['title'] ? rex_i18n::translate($feld['title']) : htmlspecialchars($feld['name']);
                $hidden = $hidden || strtolower($feld['params']) == 'hidden';
            }
            if( $hidden ) $fragment->setVar( 'fieldselect', $feldauswahl );
        }

        return substr_replace( $text, $fragment->parse('fp_panel.php'), $p1, $p2 - $p1 +1 );
    }


    /**
     *  Die Funktion liefert das Feld-HTML für Fokuspunkt-Felder (Meta-Typ "Focuspoint (AddOn)").
     *
     *  Das HTML wird von einem Fragment "fp_metafield.php" erzeugt.
     *
     *  Wenn in der Metafeld-Definition "Parameter" auf "hidden" gesetzt wird, wird das Element
     *  im Formular ausgeblendet.
     *
     *  Das HTML wird vom Fragment "fp_metafield.php" erzeugt.
     *
     *  @param  rex_extension_point $ep
     *
     *  @return array   Metafield-Html, ....
     */

    public static function customfield( rex_extension_point $ep )
    {
        $subject = $ep->getSubject();
        if( $subject['type'] != rex_effect_abstract_focuspoint::META_FIELD_TYPE ) return;
        $default = $subject['sql']->getValue('default');
        if( !rex_effect_abstract_focuspoint::str2fp($subject['sql']->getValue('default') ) ) {
            $default = '';
        }

        $feld = new rex_fragment();
        $feld->setVar( 'label', $subject[4], false );
        $feld->setVar( 'id', $subject[3] );
        $feld->setVar( 'name', str_replace('rex-metainfo-','',$subject[3]) );
        $feld->setVar( 'value', $subject['values'][0] );
        $feld->setVar( 'default', $default );
        $feld->setVar( 'hidden', strtolower(trim($subject['sql']->getValue('params'))) == 'hidden' );

        return [ $feld->parse('fp_metafield.php'), $subject[1], $subject[2], $subject[3], $subject[4], $subject[5] ];
    }

    /**
     *  Die Funktion überprüft Abhängigkeiten und bereitet die Ergebnisse als HTML-Liste auf.
     *
     *  Verwendung: De-Installation und Löschen des Addon absichern
     *
     *  Geprüft werden:
     *      Gibt es Fokuspunkt-Metafelder (zusätzlich zu med_focuspoint)
     *      Gibt es Fokuspoint-Effekte zusätzlich zu den im Addon enthaltenen
     *      Sind Effekte im Media-Manager in Benutzung
     *
     *  @return string
     */

    public static function checkUninstallDependencies()
    {
        $message = '';

        // ermittle die Meta-Felder vom Typ 'Focuspoint (AddOn)', die nicht 'med_focuspoint' sind.
        if( $felder = self::getMetafieldList( true ) )
        {
            $message .= '<li>' . rex_i18n::msg('focuspoint_uninstall_metafields', rex_effect_abstract_focuspoint::META_FIELD_TYPE ) .
                        '<ul><li>' . implode('</li><li>',$felder) .
                        '</li></ul></li>';
        }

        // ermittle alle Effekte der Liste, die im Media-Manager genutzt werden
        if( $mmEffekteMsg = self::getFocuspointEffectsInUse() )
        {
            $mmEffekteMsg = self::getFocuspointEffectsInUseMessage( $mmEffekteMsg );
            $message .= "<li>$mmEffekteMsg</li>";
        }

        if( $message )
        {
            $message = '<strong>' . rex_i18n::msg( 'focuspoint_uninstall_dependencies' ) . "</strong><ul>$message</ul>";
        }
        return $message;
    }

    /**
     *  Die Funktion überprüft Abhängigkeiten und bereitet die Ergebnisse als HTML-Liste auf.
     *
     *  Verwendung: (Re)-Aktivierung des Addon absichern
     *
     *  Geprüft werden:
     *      Meta-Feldtyp "Focuspoint (AddOn)" vorhanden
     *      Meta-Feld "med_focuspoint" vom Typ "Focuspoint (AddOn)" vorhanden
     *      Media-Manager-Typ "focuspoint_media_detail" vorhanden (Effekte werden nicht geprüft)
     *
     *  @return string
     */

    public static function checkActivateDependencies()
    {
        $message = '';
        $sql = rex_sql::factory();
        $qry = 'SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label=:label LIMIT 1';
        $sql->setQuery($qry, [':label' => rex_effect_abstract_focuspoint::META_FIELD_TYPE]);
        if( $sql->getRows() == 0 ) {
            $message .= '<li>'.rex_i18n::msg( 'focuspoint_activate_missing_metainfotype' ).'</li>';
        }
        $qry = 'SELECT type_id FROM ' . rex::getTable('metainfo_field') . ' WHERE name=:name LIMIT 1';
        $sql->setQuery($qry, [':name' => rex_effect_abstract_focuspoint::MED_DEFAULT]);
        if( $sql->getRows() == 0 ) {
            $message .= '<li>'.rex_i18n::msg( 'focuspoint_activate_missing_metainfofield' ).'</li>';
        }
        $qry = 'SELECT id FROM ' . rex::getTable('media_manager_type') . ' WHERE name=:name LIMIT 1';
        $sql->setQuery($qry, [':name' => rex_effect_abstract_focuspoint::MM_TYPE]);
        if( $sql->getRows() == 0 ) {
            $message .= '<li>'.rex_i18n::msg( 'focuspoint_activate_missing_mediamanagertype' ).'</li>';
        }
        if( $message )
        {
            $message = '<strong>' . rex_i18n::msg( 'focuspoint_activate_dependencies' ) . "</strong><ul>$message</ul>";
        }
        return $message;
    }


    /**
     *  Die Funktion überprüft Abhängigkeiten und bereitet die Ergebnisse als HTML-Liste auf.
     *
     *  Verwendung: Deaktivierung des Addon absichern
     *
     *  Geprüft werden:
     *      Sind Fokuspoint-Effekte im Media-Manager in Benutzung
     *
     *  @return string
     */

    public static function checkDeactivateDependencies()
    {
        if( $message = self::getFocuspointEffectsInUse() )
        {
            $message = self::getFocuspointEffectsInUseMessage( $message );
            $message = '<strong>' . rex_i18n::msg( 'focuspoint_deactivate_dependencies' ) . "</strong><br>$message";
        }
        return $message;
    }


    /**
     *  Die Funktion ermittelt, ob Fokuspunkt-Meta-Felder in Effekten des Media-Managers benutzt
     *  werden und daher nicht gelöscht werden dürfen.
     *
     *  med_focuspoint darf nie gelöscht werden, da es das Fallback-Feld ist.
     *
     *  Identifizierungsmerkmal ist im Feld "parameters" von rex_media_manager_type_effect der
     *  Eintrag "rex_effect_«focuspunktfeld»_meta":"«metafeld»".
     *
     *  @param  int $id Nummer des Feldes (Datensatz-Id in rex_metainfo_field)
     *  @return string  leerer String oder Rückmeldung der gefundenen Einträge
     */

    public static function metafield_is_in_use( $id )
    {
        // Name des zu löschenden Metafeldes
        $feld = self::getMetafieldList( );
        if( !isset( $feld[$id] ) ) return;
        $feld = $feld[$id];

        // Das Default-Feld "med_focuspoint" darf so oder so nie gelöcht werden.
        if( $feld == rex_effect_abstract_focuspoint::MED_DEFAULT )
        {
            $result = rex_i18n::msg('focuspoint_isinuse_dontdeletedefault', $feld);
        }

        // Andere Felder gezielt überprüfen
        elseif( $result = self::getFocuspointMetafieldInUse( $feld )  )
        {
            $result = '<strong>' .rex_i18n::rawMsg(
                    'focuspoint_isinuse_message',
                    $feld,
                    rex_url::backendController(['page' => 'media_manager/types'])
                    ) . '</strong><br>' . self::getFocuspointEffectsInUseMessage( $result );
        }
        return $result;
    }


    /**
     *  Die Funktion ermittelt die Liste aller Fokuspunkt-Metafelder
     *
     *  Die Felder werden über den Typ "Focuspoint (AddOn)" identifiziert.
     *
     *  @param  bool $extern    wenn true werden nur Felder geliefert, die zusätzlich zum
     *                          AddOn-eigenen Feld "med_fosuspunkt" angelegt wurden
     *
     *  @return array           Key/Value-Array mit id=>name
     */

    public static function getMetafieldList( $extern=false )
    {
        $qry = 'SELECT f.id,name FROM '.
                rex::getTable('metainfo_field').' f LEFT JOIN '.rex::getTable('metainfo_type').
                ' t ON f.type_id = t.id WHERE label LIKE "'.rex_effect_abstract_focuspoint::META_FIELD_TYPE.'"';
        if( $extern ) $qry .= ' AND name NOT LIKE "'.rex_effect_abstract_focuspoint::MED_DEFAULT.'"';
        return rex_sql::factory()->getArray( $qry, [], PDO::FETCH_KEY_PAIR );
    }


    /**
     *  Die Funktion ermittelt die Liste aller Media-Manager-Typen/Effekte, in denen ein gegebenes
     *  Feld eingesetzt sind.
     *
     *  Identifiziert wird das Feld über den Eintrag in Parameters
     *      parameters[rex_effect_«effect»][rex_effect_«effect»_meta] = «feld»"
     *  Annahme dabei: Das im Effect eingesetzte Fokuspunkt-Metafeld ist über die Klasse
     *  rex_effect_abstract_focuspoint erzeugt und nutze das Parameterfeld "meta".
     *
     *  @param  string $feld  Name des Fokuspunkt-Metafeldes
     *  @return array         Array mit einem Subarray je Type/Effekt mit
     *                          name        => Name des Typ
     *                          type_id     => id des Typs "name" (rex_media_manager_type)
     *                          effect      => Name des eingesetzten Fokuspunkt-Effektes
     *                          id          => id des effektes (rex_media_manager_type_effect)
     *                          parameters  => Parameter des Effektes
     */

    public static function getFocuspointMetafieldInUse( $feld )
    {
        $effects = self::getFocuspointEffectsInUse( );
        foreach( $effects as $k=>$v )
        {
            $params = json_decode( $v['parameters'], true );
            $effekt = "rex_effect_{$v['effect']}";
            $meta = "{$effekt}_meta";
            if( isset($params[$effekt][$meta]) && $params[$effekt][$meta] == $feld ) continue;
            unset( $effects[$k] );
        }
        return $effects;
    }


    /**
     *  Die Funktion ermittelt die Liste aller Fokuspunkt-Effekte
     *
     *  Die Effekte werden über die Klasse "rex_effect_abstract_focuspoint" identifiziert, von der
     *  alle, auch externe (nicht mitgelieferte) Effekte abgeleitet sein sollten.
     *
     *  @param  bool $extern    wenn true werden nur Effekte ermittelt, die zusätzlich zu den
     *                          AddOn-eigenen Effekten beim Media-Manager registriert wurden.
     *
     *  @return array           Key/Value-Array mit rex_effect_«name»=>«name»
     */

    public static function getFocuspointEffects( $extern=false )
    {
        $effects = array_filter(
                rex_media_manager::getSupportedEffects(),
                function ($class){ return is_subclass_of( $class, 'rex_effect_abstract_focuspoint');},
                ARRAY_FILTER_USE_KEY
            );
        if( $extern ) {
            $effects = array_diff( $effects, rex_effect_abstract_focuspoint::$internalEffects );
        }
        return $effects;
    }


    /**
     *  Die Funktion ermittelt die Liste aller Fokuspunkt-Effekte, die in einem Media-Manager-Typ
     *  eingesetzt sind.
     *
     *  @return array   Array mit einem Subarray je Type/Effekt mit
     *                      name        => Name des Typ
     *                      type_id     => id des Typs "name" (rex_media_manager_type)
     *                      effect      => Name des eingesetzten Fokuspunkt-Effektes
     *                      id          => id des effektes (rex_media_manager_type_effect)
     *                      parameters  => Parameter des Effektes
     */

    public static function getFocuspointEffectsInUse( )
    {
        if( $effects = self::getFocuspointEffects() )
        {
            $qry = 'SELECT name, effect, parameters, type_id, a.id as id FROM '.
                    rex::getTable('media_manager_type_effect').' as a, '.rex::getTable('media_manager_type').
                    ' as b WHERE effect IN ("'.implode( '","',$effects ).'") AND b.id = a.type_id';
            return rex_sql::factory()->getArray( $qry );
        }
        return [];
    }


    /**
     *  Die Funktion bereitet die angegebenen Effekte zu einer UL/LI-Meldung auf.
     *
     *  @param  array $effekte
     *  @return string
     */

    public static function getFocuspointEffectsInUseMessage( array $effekte )
    {
        $message = '';
        foreach( $effekte as $effect )
        {
            $target = "rex_effect_{$effect['effect']}";
            $name = new $target();
            $message .= '<li>' . rex_i18n::rawMsg(
                'focuspoint_isinuse_entry',
                $effect['name'],
                rex_url::backendController([
                        'page' => 'media_manager/types',
                        'type_id' => $effect['type_id'],
                        'effects' => 1,
                    ]),
                $name->getName(),
                rex_url::backendController([
                        'page' => 'media_manager/types',
                        'effects' => 1,
                        'func' => 'edit',
                        'type_id' => $effect['type_id'],
                        'effect_id' => $effect['id'],
                    ])
                ) .' / '.$effect['effect'] . '</li>';

        }
        if( $message )
        {
            $message = rex_i18n::msg( 'focuspoint_uninstall_effects_in_use' ) .
                       "<ul>$message</ul>";
        }
        return $message;
    }


}
