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
 *  Die Klasse "FocuspointMedia" ist von "rex_media" abgeleitet und erleichetrt den
 *  Umgang mit Medien, deren Darstellung auf Fokuspunkten basiert.
 */

namespace FriendsOfRedaxo\Focuspoint;

use rex_effect_abstract_focuspoint;
use rex_media;

/** @api */
class FocuspointMedia extends rex_media
{
    /**
     *  Gibt die Bildinstanz zurück und prüft dabei ab, ob es ein Bild ist (isImage).
     *
     *  @param  string $name    Bildname im Medianpool
     */
    public static function get($name)
    {
        $media = parent::get($name);
        if (null !== $media) {
            if (!$media->isImage()) {
                $media = null;
            }
        }
        return $media;
    }

    /**
     *  Ermittelt die Fokuspunkt-Koordinaten des Bildes.
     *
     *  Liefert ein Koordinatenpaar (Prozentwert) aus dem angegebenen Metafeld oder (default) aus
     *  dem Feld "med_focuspoint". Ist das feld leer oder hat einen formal ungültigen wert, wird
     *  der angegebene Default zurückgegeben. Ist $default leer, wird 50,50 zurückgegeben.
     *
     *  Wurde $wh angegeben, rechnet die Funktion die Prozentwerte der Koordinaten sofort in absolute
     *  Werte um. Ist $wh=true wird die Bildgröße herangezogen, ist $wh ein Array, wird mit den
     *  angegeben Werten gerechnet.
     *
     *  Die als $default und $wh angegebenen Array müssen format korrekt sein.
     *  Das wird nicht überprüft. Also [x,y] mit x|y von 0.0 bis 100.0.
     *
     *  @param  string             $metafield  Metafeld, aus dem die Koordinaten entnommen werden.
     *                                          default: med_focuspoint
     *  @param  array<mixed>       $default    Default-Koordinaten falls das Metafeld leer oder ungültig ist.
     *                                          Wenn $default fehlt: 50,50
     *  @param  array<mixed>|bool   $wh         array [breite,höhe] mit den absoluten Referenzwerten, auf die
     *                                          sich die Prozentwerte der Koordinaten beziehen, oder True für
     *                                          [bildbreite,bildhöhe]
     *
     *  @return array<float|int>                [x,y] als Koordinaten-Array
     */
    public function getFocus($metafield = null, ?array $default = null, $wh = false)
    {
        // read the field
        if (null === $metafield) {
            $metafield = rex_effect_abstract_focuspoint::MED_DEFAULT;
        }
        $xy = (string) $this->getValue($metafield);

        $fp = rex_effect_abstract_focuspoint::str2fp($xy);
        if (false === $fp) {
            $fp = null === $default ? rex_effect_abstract_focuspoint::$mitte : $default;
        }

        if (false !== $wh) {
            if (true === $wh) {
                $wh = [$this->getWidth(), $this->getHeight()];
            }
            $fp = rex_effect_abstract_focuspoint::rel2px($fp, $wh);
        }

        return $fp;
    }

    /**
     *  Ermittelt, ob ein Fokuspunkt gesetzt ist.
     *
     *  Liefert true zurück, wenn
     *  (1) das angegebene Fokuspunkt-Metafeld existiert und
     *  (2) das Feld einen formal gültigen Wert liefert.
     *
     *  @param  string     $metafield   Metafeld, aus dem die Koordinaten entnommen werden.
     *                                  default: med_focuspoint
     *
     *  @return bool
     */
    public function hasFocus($metafield = null)
    {
        // read the field
        if (null === $metafield) {
            $metafield = rex_effect_abstract_focuspoint::MED_DEFAULT;
        }
        $xy = (string) $this->getValue($metafield);
        // check for a valid entry
        return false !== rex_effect_abstract_focuspoint::str2fp($xy);
    }
}
