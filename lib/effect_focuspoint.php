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
 *  Die abstrakte Klasse "rex_effect_abstract_focuspoint" erweitert die Basisklasse
 *  "rex_effect_abstract" um spezielle Methoden und Konstanten zum Umgang mit Fokuspunkten
 *
 *  Außerdem stellt sie diverse Konstanten und statische Variablen zur Verfügung.
 *
 *  Eigene Fokuspunkt-Effekte sollten unbedingt hiervon abgeleitet werden, nie direkt von
 *  "rex_effect_abstract"!
 *
 */

abstract class rex_effect_abstract_focuspoint extends rex_effect_abstract
{
    const URL_KEY = 'xy';
    const MM_TYPE = 'focuspoint_media_detail';
    const MED_DEFAULT = 'med_focuspoint';
    const META_FIELD_TYPE = 'Focuspoint (AddOn)';
    const HTML_PATTERN = '^(100|[1-9]?[0-9])\.[0-9],(100|[1-9]?[0-9])\.[0-9]$';
    const PATTERN = '/^(?<x>(100|[1-9]?\d)\.\d),(?<y>(100|[1-9]?\d)\.\d)$/';
    const STRING = '%3.1F,%3.1F';

    /** @var array<int> */
    static $mitte = [50,50];
    
    /** @var array<string> */
    static $internalEffects = [ 'focuspoint_fit', 'focuspoint_resize' ];

    /**
     *  Wandelt einen Fokuspunkt-Koordinaten-String in eine numerische Darstellung um.
     *
     *  Ist der Parameter $wh angegeben, werden die Koordinaten in absolute Werte umgerechnet.
     *
     *  @param  string          $xy     Zeichenkette mit dem Koordinaten-Paar ("12.3,34.5")
     *  @param  array<mixed>    $wh     Array [breite,höhe] mit den Referenzwerten, auf die sich
     *                                  die Prozentwerte der Koordinaten beziehen.
     *
     *  @return array<float>|bool       [x,y] als Koordinaten-Array oder false für ungültiger String
     */
    static public function str2fp( string $xy, array $wh=null )
    {
        if( $i = preg_match_all( self::PATTERN, (string)$xy, $tags ) )
        {
            $xy = [ min( 100, max( 0, $tags['x'][0] ) ), min( 100, max( 0, $tags['y'][0] ) ) ];
            if( $wh ) $xy = self::rel2px( $xy, $wh );
            return $xy;
        }
        return false;
    }


    /**
     *  rechnet relative Fokuspunkt-Koordinaten in absolute um.
     *
     *  @param  array<mixed>    $xy     Array [x,y] der relativen Koordinaten (0..100)
     *  @param  array<mixed>    $wh     Array [breite,höhe] der Bildabmessungen
     *
     *  @return array<integer>            [x,y] als absolute Werte der Fokuspunkt-Koordinate
     */
    static public function rel2px( array $xy, array $wh )
    {
        $xy[0] = (int) round( ($wh[0]-1) * $xy[0] / 100 ) ;
        $xy[1] = (int) round( ($wh[1]-1) * $xy[1] / 100  );
        return $xy;
    }

    /**
     *  Ermittelt den "Default-Fokus"
     *
     *  Basis ist das Feld 'focus' in dem der Defaultwert für den Effekt konfiguriert ist
     *  Sollt das Feld leer sein oder ungültig, wird der angegebene $default herangezogen.
     *  Gibt es den auch nicht, wird  aus Bildmitte gesetzt.
     *
     *  @param  array<mixed>    $default    Ein Array [x,y] mit den Koordinaten als Prozent der Bilddimension
     *  @param  array<mixed>    $wh         Array [breite,höhe] der Bildabmessungen
     *
     *  @return array<float>                [x,y] als Koordinaten-Array
     */
    public function getDefaultFocus( array $default=null, array $wh=null )
    {
        $xy = self::str2fp( $this->params['focus'] );
        if( !$xy ) {
            $xy = $default === null ? self::$mitte : $default;
        }
        if( $wh ) $xy = self::rel2px( $xy, $wh );
        return $xy;
    }


    /**
     *  Ermittelt das relevante Fokuspunkt-Meta-Feld für den Effekt
     *
     *  Das Feld 'meta' des Efektes enthält den Namen des Fokuspunkt-Meta-Feldes.
     *  steht er auf 'defaut', ist kein Meta-Feld ausgewählt.
     *
     *  @return string           Feldname
     */
    public function getMetaField()
    {
        return str_starts_with($this->params['meta'],'default ') ? '' : $this->params['meta'];
    }


    /**
     *  Ermittelt die im Effekt anzuwendenden Fokuspunkt-Koordinaten
     *
     *  Vorgeschaltet ist die Auswertung der URL. Über den REX_API_CALL "focuspoint"
     *  können ebenfalls bearbeitete Bilder erzeugt werden. Die Fokuspunkt-Effekte
     *  werten hier die URL aus und nutzen den Wert bevorzugt.
     *  Falls $media kein gültiges Objekt aus dem Medienpool ist (z.B. wenn der Medienpfad mit dem
     *  vorgeschalteten Effekt "mediapath" geändert wurde), werden die Defaultwerte herangezogen.
     *
     *  @param  focuspoint_media    $media    Media-Objekt oder null
     *  @param  array<mixed>        $default  Default-Koordinaten falls auf anderem Wege keine
     *                                        gültigen Koordinaten ermittelt werden können
     *  @param  array<mixed>        $wh       Array [breite,höhe] mit den Referenzwerten, auf die
     *                                        sich die Prozentwerte der Koordinaten beziehen.
     *
     *  @return array<float>                  [x,y] als Koordinaten-Array
     */
    function getFocus( $media=null, array $default=null, array $wh=null )
    {
        if( $xy = rex_request( self::URL_KEY, 'string', null) )
        {
			// nur relevant für temporäre Bilder; funktioniert nicht mit Cache!
			// hier eingebaut zur Funktionsfähigkeit von focuspoint_api
			$fp = self::str2fp( $xy );
			if( !$fp ) {
				$fp = $media !== null && is_a($media,'focuspoint_media')
					? $media->getFocus( $xy, $default ) // $xy = Meta-Feld-Name??
					: $this->getDefaultFocus( $default );
			}
		} else {
			// Standard
			$fp = $media !== null && is_a($media,'focuspoint_media')
				? $media->getFocus( $this->getMetaField(), $default )
				: $this->getDefaultFocus( $default );
		}
        return $wh ? self::rel2px( $fp,$wh ) : $fp;

    }

    /**
     *  Stellt die Basis-Felder für eine Effekt-Parametriesierung zur Verfügung.
     *
     *  Konkret:
     *      * Auswahl des genutzten Meta-Feldes oder "default" für "Default-Koordinaten"
     *      * Eingabefeld für die Default-Koordinaten
     *
     *  @return array<mixed>   Felddefinitionen
     */
    public function getParams()
    {

        $qry = 'SELECT id,name FROM '.rex::getTable('metainfo_field').' WHERE type_id=(SELECT id FROM '.rex::getTable('metainfo_type').' WHERE label="'.self::META_FIELD_TYPE.'"  LIMIT 1) AND name LIKE "med_%" ORDER BY name ASC';
        $felder = rex_sql::factory()->getArray( $qry, [], PDO::FETCH_KEY_PAIR );
        $felder[] = 'default => '.rex_i18n::msg('focuspoint_edit_label_focus');
        $default = current($felder);
        if( ($k = array_search(self::MED_DEFAULT,$felder)) !== false ) $default = $felder[$k];
        return [
            [
                'label' => rex_i18n::msg('focuspoint_edit_label_meta'),
                'name' => 'meta',
                'type' => 'select',
                'options' => $felder,
                'default' => $default,
            ],
            [
                'label' => rex_i18n::msg('focuspoint_edit_label_focus'),
                'name' => 'focus',
                'type' => 'string',
                'attributes' => [ 'pattern' => self::HTML_PATTERN ],
                'notice' => 'x,y: 0.0,0.0 ... 100.0,100.0',
#                'default' => sprintf( self::STRING, self::$mitte[0], self::$mitte[1]),
            ],
        ];
    }

}
