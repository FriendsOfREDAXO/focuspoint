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
 *  Die abstrakte Klasse "rex_effect_abstract_focuspoint" erweitert die Basisklasse
 *  "rex_effect_abstract" um spezielle Methoden und Konstanten zum Umgang mit Fokuspunkten
 *
 *  Außerdem stellt sie diverse Konstanten und statische Variablen zur Verfügung.
 *
 *  Eigene Fokuspunkt-Effekte sollten unbedingt hiervon abgeleitet werden, nie direkt von
 *  "rex_effect_abstract"!
 */

use FriendsOfRedaxo\Focuspoint\FocuspointMedia;

/** @api */
abstract class rex_effect_abstract_focuspoint extends rex_effect_abstract
{
    public const URL_KEY = 'xy';
    public const MM_TYPE = 'focuspoint_media_detail';
    public const MED_DEFAULT = 'med_focuspoint';
    public const META_FIELD_TYPE = 'Focuspoint (AddOn)';
    public const HTML_PATTERN = '^(100|[1-9]?[0-9])\.[0-9],(100|[1-9]?[0-9])\.[0-9]$';
    public const PATTERN = '/^(?<x>(100|[1-9]?\d)\.\d),(?<y>(100|[1-9]?\d)\.\d)$/';
    public const STRING = '%3.1F,%3.1F';

    /** @var array<int> */
    public static $mitte = [50, 50];

    /** @var array<string> */
    public static $internalEffects = ['focuspoint_fit', 'focuspoint_resize'];

    /**
     *  Wandelt einen Fokuspunkt-Koordinaten-String in eine numerische Darstellung um.
     *
     *  Ist der Parameter $wh angegeben, werden die Koordinaten in absolute Werte umgerechnet.
     *
     *  @param  string          $xy     Zeichenkette mit dem Koordinaten-Paar ("12.3,34.5")
     *  @param  array<mixed>    $wh     array [breite,höhe] mit den Referenzwerten, auf die sich
     *                                  die Prozentwerte der Koordinaten beziehen
     *
     *  @return array<float>|false       [x,y] als Koordinaten-Array oder false für ungültiger String
     */
    public static function str2fp(string $xy, ?array $wh = null)
    {
        $i = preg_match_all(self::PATTERN, $xy, $tags);
        if (0 < $i) {
            $xy = [min(100, max(0, $tags['x'][0])), min(100, max(0, $tags['y'][0]))];
            if (is_array($wh)) {
                $xy = self::rel2px($xy, $wh);
            }
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
     *  @return array<int>            [x,y] als absolute Werte der Fokuspunkt-Koordinate
     */
    public static function rel2px(array $xy, array $wh)
    {
        $xy[0] = (int) round(($wh[0] - 1) * $xy[0] / 100);
        $xy[1] = (int) round(($wh[1] - 1) * $xy[1] / 100);
        return $xy;
    }

    /**
     *  Ermittelt den "Default-Fokus".
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
    public function getDefaultFocus(?array $default = null, ?array $wh = null)
    {
        $xy = self::str2fp($this->params['focus']);
        if (false === $xy) {
            $xy = null === $default ? self::$mitte : $default;
        }
        if (null !== $wh) {
            $xy = self::rel2px($xy, $wh);
        }
        return $xy;
    }

    /**
     *  Ermittelt das relevante Fokuspunkt-Meta-Feld für den Effekt.
     *
     *  Das Feld 'meta' des Efektes enthält den Namen des Fokuspunkt-Meta-Feldes.
     *  steht er auf 'defaut', ist kein Meta-Feld ausgewählt.
     *
     *  @return string           Feldname
     */
    public function getMetaField()
    {
        return str_starts_with($this->params['meta'], 'default ') ? '' : $this->params['meta'];
    }

    /**
     *  Ermittelt die im Effekt anzuwendenden Fokuspunkt-Koordinaten.
     *
     *  Vorgeschaltet ist die Auswertung der URL. Über den REX_API_CALL "focuspoint"
     *  können ebenfalls bearbeitete Bilder erzeugt werden. Die Fokuspunkt-Effekte
     *  werten hier die URL aus und nutzen den Wert bevorzugt.
     *  Falls $media kein gültiges Objekt aus dem Medienpool ist (z.B. wenn der Medienpfad mit dem
     *  vorgeschalteten Effekt "mediapath" geändert wurde), werden die Defaultwerte herangezogen.
     *
     *  @param  FocuspointMedia     $media    Media-Objekt oder null
     *  @param  array<mixed>        $default  Default-Koordinaten falls auf anderem Wege keine
     *                                        gültigen Koordinaten ermittelt werden können
     *  @param  array<mixed>        $wh       array [breite,höhe] mit den Referenzwerten, auf die
     *                                        sich die Prozentwerte der Koordinaten beziehen
     *
     *  @return array<float>                  [x,y] als Koordinaten-Array
     */
    public function getFocus($media = null, ?array $default = null, ?array $wh = null)
    {
        $xy = rex_request(self::URL_KEY, 'string', null);
        if (is_string($xy)) {
            // nur relevant für temporäre Bilder; funktioniert nicht mit Cache!
            // hier eingebaut zur Funktionsfähigkeit von focuspoint_api
            $fp = self::str2fp($xy);
            if (false === $fp) {
                $fp = null !== $media && is_a($media, FocuspointMedia::class)
                    ? $media->getFocus($xy, $default) // $xy = Meta-Feld-Name??
                    : $this->getDefaultFocus($default);
            }
        } else {
            // Standard
            $fp = null !== $media && is_a($media, FocuspointMedia::class)
                ? $media->getFocus($this->getMetaField(), $default)
                : $this->getDefaultFocus($default);
        }
        return is_array($wh) && 2 === count($wh) ? self::rel2px($fp, $wh) : $fp;
    }

    /**
     *  Stellt die Basis-Felder für eine Effekt-Parametriesierung zur Verfügung.
     *
     *  Konkret:
     *      * Auswahl des genutzten Meta-Feldes oder "default" für "Default-Koordinaten"
     *      * Eingabefeld für die Default-Koordinaten
     *
     *  @return list<array{label: string, name: string, type: 'int'|'float'|'string'|'select'|'media', default?: mixed, notice?: string, prefix?: string, suffix?: string, attributes?: array, options?: array}>
     *
     * Ursprünglich war die Meldung "Return type (array) of method rex_effect_abstract_focuspoint::getParams() should be covariant with return type (....) of method rex_effect_abstract::getParams()"
     * Daher obige @ return aus rex_effect_abstract::getParams() kopiert und hier eingefügt. Das ergibt nun 2 x diese Meldung:
     * STAN: Method rex_effect_abstract_focuspoint::getParams() return type has no value type specified in iterable type array.
     * Hängt vermutlich mit "attributes?: array, options?: array" zusammen. Das ignorieren wir also ...
     * @phpstan-ignore-next-line
     */
    public function getParams()
    {
        $qry = 'SELECT id,LOWER(name) as name FROM ' . rex::getTable('metainfo_field') . ' WHERE type_id=(SELECT id FROM ' . rex::getTable('metainfo_type') . ' WHERE label="' . self::META_FIELD_TYPE . '"  LIMIT 1) AND name LIKE "med_%" ORDER BY name ASC';
        $felder = rex_sql::factory()->getArray($qry, [], PDO::FETCH_KEY_PAIR);
        $felder[] = 'default => ' . rex_i18n::msg('focuspoint_edit_label_focus');
        $default = current($felder);
        if (($k = array_search(self::MED_DEFAULT, $felder, true)) !== false) {
            $default = $felder[$k];
        }
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
                'attributes' => ['pattern' => self::HTML_PATTERN],
                'notice' => 'x,y: 0.0,0.0 ... 100.0,100.0',
                //                'default' => sprintf( self::STRING, self::$mitte[0], self::$mitte[1]),
            ],
        ];
    }
}
