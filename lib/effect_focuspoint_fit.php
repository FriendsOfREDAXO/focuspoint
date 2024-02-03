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
 *  focuspoint_fit
 *
 *  passt ein Bild genau in einen Zielrahmen ein, ohne es zu stauchen, mit schwarzen Rändern
 *  aufzufüllen oder über den Rand hinausstehen zu lassen.
 *
 *  Es wird ein Ausschnitt in der Größe des Zielbildes um den Fokuspunkt ausgeschnitten. Über den
 *  Zoom-Faktor kann der Auschnitt so vergrößert werden, dass 0 bis 100% des abgeschnittenen Teils
 *  doch ins Bild kommen - das aber um den Preis, dass sich evtl der Fokuspunkt verschiebt.
 *
 *  100% würde bedeuten, dass das Bild bestmöglich in das Zielformat eingepasst wird und
 *  Überstände abgeschnitten werden. Das entspricht dann dem effect_resize mit den Optionen
 *  "maximize" und "enlarge", jedoch ohne dessen Überstände, denn die werden gekappt.
 *
 *  Da ein Default-Wert für den Fokuspunkt angegeben werden kann (vpos/hpos), funktioniert der
 *  Effekt auch unabhängig vom addon "fokuspoint".
 *
 *  Es gilt hier immer das Primat des Zielbildes. Anders gesagt: dessen Dimension soll erreicht
 *  werden. Sind Bilder in einer oder beiden Dimensionen kleiner als das Zielformat, werden sie
 *  ungefragt vergrößert.
 *
 *  Kleine Aufweichung des Prinzips:
 *      Dimensionen können auch als % (vom Quellbild) oder als Ziel-Aspect-Ratio (fr)
 *      angegeben werden. Dann hängt die Zielgröße ganz oder teilweise vom Quellbild ab
 *      und ist eben nicht mehr genau vorhersehbar. Details siehe Doku.
 *
 *  ----------------
 *
 *  Die Bild-Variablen sind wie folgt benannt:
 *  erster Buchstabe ist die Grafik: Quelle (s), Ziel (d), Ausschnitt (c), Fokuspunkt der Quelle (f)
 *  zweiter Buchstabe ist die Verwendung: Offset (x bzw. y), Höhe (h), Breite (w), AspectRatio (r)
 *  Beispiel: $dw, $dh, $dr
 */

use FriendsOfRedaxo\Focuspoint\FocuspointMedia;

/** @api */
class rex_effect_focuspoint_fit extends rex_effect_abstract_focuspoint
{
    public const PATTERN = '^([1-9]\d*\s*(px)?|(100|[1-9]?\d)(\.\d)?\s*%|[1-9]\d*\s*fr)$';
    /** @var array<string> */
    private $optionsZoom = ['0%', '25%', '50%', '75%', '100%'];
    /** @var int */
    private $targetByAR;

    /**
     *  Gibt den Namen des MM-Effektes zurück.
     *
     *  @return     string      Effekt-Name
     */
    public function getName()
    {
        return rex_i18n::msg('focuspoint_effect_fit');
    }

    /**
     *  Erzeugt den Bildeffekt gemäß der obigen Beschreibung.
     *
     *  @return     void
     */
    public function execute()
    {
        /*
            Bilddaten
        */
        $this->media->asImage();
        $gdimage = $this->media->getImage();
        $sw = $this->media->getWidth();
        $sh = $this->media->getHeight();
        $sr = $sw / $sh;

        /*
            Fokuspunkt ermitteln:
                zuerst den Fallback-Wert bzw. Default-Wert des Effekts
                dann den FP des Bildes.
                Umrechnen in absolute Bildkoordinaten (Pixel)
        */
        [$fx, $fy] = $this->getFocus(FocuspointMedia::get($this->media->getMediaFilename()), $this->getDefaultFocus(), [$sw, $sh]);

        /*--------------------------

        Parameter überprüfen und ungültige Werte korrigieren

            Zielhöhe und/oder Zielbreite müssen angegeben sein. Akzeptiert werden Zahlen und
            Zahlen mit %, px und fr.
            Ungültige Werte führen zum Abbruch
                1111 => Größe in Pixel,
                1111px => Größe in Pixel
                11% => % der Originalgröße
                11fr => "Anteil/fraction" zur Eingabe von Aspect-Ratios des Zielbildes.
            Ungültige Werte und Wert-Kombinationen führen zum Abbruch
            Im Fall "fr" müssen beide Werte vom Typ fr sein. 0 oder 2.
        */
        $this->targetByAR = 0;
        $dw = $this->decodeSize($this->params['width'], $sw);
        $dh = $this->decodeSize($this->params['height'], $sh);
        if (null === $dw && null === $dh) {
            return;
        }
        // STAN: Strict comparison using === between 1 and 0 will always evaluate to false.
        // Auch wenn rexstan hier meckert, aber es stimmt so: decodeSize incrementiert targetByAR
        // @phpstan-ignore-next-line
        if (1 === $this->targetByAR) {
            return;
        }

        /*
            Den Zoom-Faktor auslesen und setzen
            Entweder soll nur der Auschnitt genommen werden (0%) oder möglichst viel vom
            Rest (best fit=100%) oder eben eine der Zwischenstufen 25,50 oder 75%.
            Falls Breite/Höhe als AspectRatio (fr) angegeben wurden: immer 100%
        */
        switch ($this->params['zoom']) {
            case $this->optionsZoom[1]: $zoom = 0.25;
                break;
            case $this->optionsZoom[2]: $zoom = 0.5;
                break;
            case $this->optionsZoom[3]: $zoom = 0.75;
                break;
            case $this->optionsZoom[4]: $zoom = 1;
                break;
            default: $zoom = 0;
        }

        /*--------------------------
        An die Arbeit ... :

            Das Zielformat bestimmen:

                Breite x Höhe angegeben => wie angegeben nehmen
                Nur Breite angegeben    => Höhe über den AspectRatio des Originals bestimmen
                Nur Höhe angegeben      => Breite über den AspectRatio des Originals bestimmen
        */
        $dw = null === $dw ? $dh * $sr : $dw;
        $dh = null === $dh ? $dw / $sr : $dh;
        $dr = $dw / $dh;
        $too_wide = ($sr >= $dr);

        /*
            Im Fall, dass die Bildgröße via AspectRatio angegeben wird, wie z.B. mit Breite 16fr
            und  Höhe 9fr, was 16:9 entspricht), muss das Zielformat auf die Bildgröße
            geändert werden. Zoom ist dann irrelevant.
        */
        // STAN: Strict comparison using === between 2 and 0 will always evaluate to false.
        // Auch wenn rexstan hier meckert, aber es stimmt so: decodeSize incrementiert targetByAR
        // @phpstan-ignore-next-line
        if (2 === $this->targetByAR) {
            $dw = $too_wide ? floor($sh * $dr) : $sw;
            $dh = $too_wide ? $sh : floor($sw / $dr);
            $zoom = 0;
        }
        /*
            Den Ausschnitt festlegen - Basisgröße

                Das Zielformat und das Auschnittsformat ist identisch. Aber beide Dimensionen dürfen
                nicht größer sein als die Bildgröße. Ist eine Dimension zu klein wird das
                Ausschnittsformat entsprechend reduziert.
                (anders gesagt: das Bild wird vergrößert)
        */
        $cw = $dw;
        $ch = $dh;
        if ($sw < $cw || $sh < $ch) {
            $scale = ($too_wide ? $sh / $dh : $sw / $dw);
            $cw = floor($cw * $scale);
            $ch = floor($ch * $scale);
        }
        /*
            Den Ausschnitt festlegen - Zoomen

                Grade wenn große Bilder auf einen kleinen Ausschnitt treffen, wäre ein Zoom
                sinnvoll. Der Zoom-Faktor sagt, wieviel % vom Abstand zwischen Originalbild und
                Ausschnitt mit hineingenooen werden sollen. Faktisch wird der Ausschnitt um einen
                entsprechenden Faktor vergrößert.
        */
        if (0 < $zoom) {
            $faktor = $too_wide ? (($sh - $ch) * $zoom + $ch) / $ch : (($sw - $cw) * $zoom + $cw) / $cw;
            $cw = floor($cw * $faktor);
            $ch = floor($ch * $faktor);
        }
        /*
            Den Bildauschnitt positionieren:

                Der Bildausschnitt wird so gelegt, dass der Fokuspunkt in der Mitte liegt.
                Falls dann der Ausschnitt irgendwo über die Ränder ragt, wird er in das Bild
                zurückgeschoben. Der Fokuspunkt ist dann natürlich nicht mehr in der Mitte.
                Das Ergebnis ist die Offset-Position des Auschnitts im Originalbild.
        */
        $cx = $fx - floor($cw / 2);
        $cy = $fy - floor($ch / 2);
        $cx = min($sw - $cw, max(0, $cx));
        $cy = min($sh - $ch, max(0, $cy));

        /*--------------------------

            Ausgabe der Grafik
        */
        if (function_exists('ImageCreateTrueColor')) {
            $des = @imagecreatetruecolor((int) $dw, (int) $dh);
        } else {
            $des = @imagecreate((int) $dw, (int) $dh);
        }

        if (false === $des) {
            return;
        }

        // Die Fehlermeldung von rexstan beruht auf der Prüfung gegen PHP8. Dort sind GD-Objekte vom Typ "GdImage" und nicht "resoource"
        // Daher werden nachfolgend drei Zeilen ignoriert.
        // TODO: 5.0.0 Auflösen; wir sind bei nur noch PHP 8
        // @phpstan-ignore-next-line
        $this->keepTransparent($des);
        // @phpstan-ignore-next-line
        imagecopyresampled($des, $gdimage,
            0, 0, (int) $cx, (int) $cy,
            (int) $dw, (int) $dh, (int) $cw, (int) $ch);

        // @phpstan-ignore-next-line
        $this->media->setImage($des);
        $this->media->refreshImageDimensions();

    }

    /**
     *  Stellt die Felder für die Effekt-Konfiguration als Array bereit.
     *
     *  Die Basisfelder werden aus der Parent-Klasse abgerufen und um die Felder für
     *  Breite und Höhe des Zielbildes sowie den Zoom-Faktor ergänzt.
     *
     *  @return list<array{label: string, name: string, type: 'int'|'float'|'string'|'select'|'media', default?: mixed, notice?: string, prefix?: string, suffix?: string, attributes?: array, options?: array}>
     *
     * Ursprünglich war die Meldung "Return type (array<string, string>) of method rex_effect_focuspoint_fit::getParams() should be compatible with return type (....) of method rex_effect_abstract_focuspoint::getParams()"
     * Daher obige @ return aus rex_effect_abstract_focuspoint::getParams() kopiert und hier eingefügt. Das ergibt nun 2 x diese Meldung:
     * STAN: Method rex_effect_focuspoint_fit::getParams() return type has no value type specified in iterable type array.
     * Hängt vermutlich mit "attributes?: array, options?: array" zusammen. Das ignorieren wir also ...
     * TODO: Issue im Core aufmachen
     * @phpstan-ignore-next-line
     */
    public function getParams()
    {
        $felder = parent::getParams();
        $info = rex_i18n::msg('focuspoint_edit_notice_widthheigth_fit');
        $felder[] = [
            'label' => rex_i18n::msg('focuspoint_edit_label_width'),
            'name' => 'width',
            'type' => 'int',
            'notice' => $info,
            'attributes' => ['pattern' => self::PATTERN],
        ];
        $felder[] = [
            'label' => rex_i18n::msg('focuspoint_edit_label_heigth'),
            'name' => 'height',
            'type' => 'int',
            'notice' => $info,
            'attributes' => ['pattern' => self::PATTERN],
        ];
        $felder[] = [
            'label' => rex_i18n::msg('focuspoint_edit_label_zoom'),
            'name' => 'zoom',
            'type' => 'select',
            'options' => $this->optionsZoom,
            'notice' => rex_i18n::msg('focuspoint_edit_notice_zoom'),
        ];

        return $felder;
    }

    /**
     *  Hilfsfunktion: umrechnen von Höhen-/Breitenangaben.
     *
     *  ohne Einheit oder mit Einheit PX: durchreichen
     *  mit Einheit %: über $ref in absolute Werte umrechnen
     *  mit Einheit fr: keine Fmrechnenung, aber FR-Zähler hochsetzen
     *
     *  @param    string        $value  auszuwertende Zeichenfolge
     *  @param    float|int     $ref    Referenzwert (Breite oder Höhe des Bildes)
     *
     *  @return   float|null    umgerechneter Wert oder null für ungültiges Format
     */
    public function decodeSize($value, $ref = 0)
    {
        $value = trim($value);
        if (0 === preg_match('/' . self::PATTERN . '/', $value)) {
            return null;
        }
        if (str_contains($value, '%')) {
            $value = trim(str_replace('%', '', $value));
            $value = (float) $value;
            $value = max(0, min(100, $value));
            if (0 < $ref) {
                $value = $ref * $value / 100;
            }
            return (float) $value;
        }
        if (str_contains($value, 'fr')) {
            $value = trim(str_replace('fr', '', $value));
            ++$this->targetByAR;
            return (int) $value;
        }
        return (int) trim(str_replace('px', '', $value));
    }
}
