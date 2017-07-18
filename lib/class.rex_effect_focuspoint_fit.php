<?php

/*

    focuspoint_fit

    passt ein Bild genau in einen Zielrahmen ein, ohne es zu stauchen, mit schwarzen Rändern
    aufzufüllen oder über den Rand hinausstehen zu lassen.

    Es wird ein Ausschnitt in der Größe des Zielbildes um den Fokuspunkt ausgeschnitten. Über den
    Zoom-Faktor kann der Auschnitt so vergrößert werden, dass 0 bis 100% des abgeschnittenen Teils
    doch ins Bild kommen - das aber um den Preis, dass sich der Fokuspunkt verschiebt.

    100% würde bedeuten, dass das Bild bestmöglich in das Zielformat eingepasst wird und
    Überstände abgeschnitten werden. Das entspricht dann dem effect_resize mit den Optionen
    "maximize" und "enlarge", jedoch ohne Überstände, denn die werden gekappt.

    Da ein Default-Wert für den Fokuspunkt angegeben werden kann (vpos/hpos), funktioniert der
    Effekt auch unabhängig vom addon "fokuspoint".

    Es gilt hier immer das Primat des Zielbildes. Anders gesagt: dessen Dimension soll erreicht
    werden. Sind Bilder in einer oder beiden Dimensionen kleiner als das Zielformat, werden sie
    ungefragt vergrößert.

    ----------------

    Die Bild-Variablen sind wie folgt benannt:
    erster Buchstabe ist die Grafik: Quelle (s), Ziel (d), Ausschnitt (c), Fokuspunkt der Quelle (f)
    zweiter Buchstabe ist die Verwendung: Offset (x bzw. y), Höhe (h), Breite (w), AspectRatio (r)
    Beispiel: $dw, $dh, $dr
*/


class rex_effect_focuspoint_fit extends rex_effect_abstract
{
    private $optionsZoom;
    private $optionsFP;

    public function __construct()
    {
        $this->optionsZoom = [rex_i18n::msg('media_manager_effekt_focuspointfit_modus_excerpt'),
                                '25%', '50%', '75%',
                               rex_i18n::msg('media_manager_effekt_focuspointfit_modus_fit')];
        $this->optionsFP    = [rex_i18n::msg('media_manager_effekt_focuspointfit_fp_pic'),
                               rex_i18n::msg('media_manager_effekt_focuspointfit_fp_inherit')];
    }

    public function execute()
    {
        $this->media->asImage();
        $gdimage = $this->media->getImage();
        $sw = $this->media->getWidth();
        $sh = $this->media->getHeight();
        $sr = $sw / $sh;

        /*--------------------------

        Parameter überprüfen und ungültige Werte korrigieren

            Zielhöhe und/oder Zielbreite müssen angegeben sein. Akzeptiert werden Zahlen und
            Zahlen mit %. nnn => Größe in Pixel, nnn% => % der Originalgröße.
            Ungültige Werte führen zum Abbruch
        */
            $dw = $this->decodeSize( $this->params['width'],$sw );
            $dh = $this->decodeSize( $this->params['height'],$sh );
            if ( empty($dw) && empty($dh) ) return;
        /*
            Fokuspunkt ermitteln: Fallback-Werte bzw. Werte für die Option "fp_inherit"
            Das sind immer Werte zwischen 0 und 100. Default ist 50 = Mitte
        */
            $fx = $this->numParaOk( $this->params['hpos'], 0, 50, 100 );
            $fy = $this->numParaOk( $this->params['vpos'], 0, 50, 100 );

        /*
            Fokuspunkt ermitteln: Individueller Bilderwert überschreibt vpos/hpos
            ... aber nur, wenn nicht über die Option "fp_inherit" abgeschaltet
            Der Wert wird aus med_focuspoint_css gelesen, also die %-Werte.
        */
            if ( $this->params['fp'] == $this->optionsFP[0] )
            {
                $filename = $this->media->getMediaFilename();
                if ( $im_image = rex_media::get($filename) )
                {
                    $focuspoint_data = trim( str_replace( '%','',$im_image ->getValue('med_focuspoint_css') ) );
                    if ( $focuspoint_data )
                    {
                        $focuspoint_data = explode( ',',$focuspoint_data );
                        if ( count($focuspoint_data) == 2 ) {
                            $fx = $this->numParaOk( $focuspoint_data[0], 0, $fx, 100 );
                            $fy = $this->numParaOk( $focuspoint_data[1], 0, $fy, 100 );
                        }
                    }
                }
            }
            $fx = floor( $sw * $fx / 100 );
            $fy = floor( $sh * $fy / 100 );
        /*
            Den Zoom-Faktor auslesen und setzen
            Entweder soll nur der Auschnitt genommen werden (0%) oder möglichst viel vom
            Rest (best fit=100%) oder eben eine der Zwischenstufen 25,50 oder 75%.
        */
            switch ( $this->params['zoom'] )
            {
                case $this->optionsZoom[1]: $zoom = 0.25; break;
                case $this->optionsZoom[2]: $zoom = 0.5; break;
                case $this->optionsZoom[3]: $zoom = 0.75; break;
                case $this->optionsZoom[4]: $zoom = 1; break;
                default: $zoom = 0;
            }

        /*--------------------------
        An die Arbeit ... :

            Das Zielformat bestimmen:

                Breite x Höhe angegeben => wie angegeben nehmen
                Nur Breite angegeben    => Höhe über den AspectRatio des Originals bestimmen
                Nur Höhe angegeben      => Breite über den AspectRatio des Originals bestimmen
        */
            $dw = empty( $dw ) ? $dh * $sr : $dw;
            $dh = empty( $dh ) ? $dw / $sr : $dh;
            $dr = $dw / $dh;
            $too_wide = ( $sr >= $dr );
        /*
            Den Ausschnitt festlegen - Basisgröße

                Das Zielformat und das Auschnittsformat ist identisch. Aber beide Dimensionen dürfen
                nicht größer sein als die Bildgröße. Ist eine Dimension zu klein wird das
                Ausschnittsformatientsprechend reduziert.
                (anders gesagt: das Bild wird vergrößert)
        */
            $cw = $dw;
            $ch = $dh;
            if ( $sw < $cw || $sh < $ch )
            {
                $scale = ( $too_wide ? $dh/$sh : $dw/$sw);
                $cw = floor( $cw * $scale );
                $ch = floor( $ch * $scale );
            }
        /*
            Den Ausschnitt festlegen - Zoomen

                Grade wenn große Bilder auf einen kleinen Ausschnitt treffen, wäre ein Zoom
                sinnvoll. Der Zoom-Faktor sagt, wieviel % vom Abstand zwischen Originalbild und
                Ausschnitt mit hineingenooen werden sollen. Faktscih wird der Ausschnitt um einen
                entsprechenden Faktor vergrößert.
        */
            if ( $zoom )
            {
                $faktor = $too_wide ? (($sh-$ch) * $zoom + $ch) / $ch : (($sw-$cw) * $zoom + $cw) / $cw;
                $cw = floor( $cw * $faktor );
                $ch = floor( $ch * $faktor );
            }
        /*
            Den Bildauschnitt positionieren:

                Der Bildausschnitt wird so gelegt, dass der Fokuspunkt in der Mitte liegt.
                Falls dann der Ausschnitt irgendwo über die Ränder ragt, wird er in das Bild
                zurückgeschoben. Der Fokuspunkt ist dann natürlich nicht mehr in der Mitte.
                Das Ergebnis ist die Offset-Position des Auschnitts im Originalbild.
        */
            $cx = $fx - floor( $cw/2 );
            $cy = $fy - floor( $ch/2 );
            $cx = min( $sw-$cw, max( 0,$cx ) );
            $cy = min( $sh-$ch, max( 0,$cy ) );

        /*--------------------------

            Ausgabe der Grafik
        */
        if (function_exists('ImageCreateTrueColor')) {
            $des = @imagecreatetruecolor($dw, $dh);
        } else {
            $des = @imagecreate($dw, $dh);
        }

        if (!$des) {
            return;
        }

        $this->keepTransparent($des);
        imagecopyresampled($des, $gdimage,
                           0, 0, $cx, $cy,
                           $dw, $dh, $cw, $ch);

        $this->media->setImage($des);
        $this->media->refreshImageDimensions();
    }


    public function getParams()
    {
        return [
            [
                'label' => rex_i18n::msg('media_manager_effect_resize_width'),
                'name' => 'width',
                'type' => 'int',
                'notice' => rex_i18n::msg('media_manager_effekt_resize_notice'),
             ],
            [
                'label' => rex_i18n::msg('media_manager_effect_resize_height'),
                'name' => 'height',
                'type' => 'int',
                'notice' => rex_i18n::msg('media_manager_effekt_resize_notice'),
            ],
            [
                'label' => rex_i18n::msg('media_manager_effekt_focuspointfit_zoom'),
                'name' => 'zoom',
                'type' => 'select',
                'options' => $this->optionsZoom,
            ],
            [
                'label' => rex_i18n::msg('media_manager_effekt_focuspointfit_hpos'),
                'name' => 'hpos',
                'type' => 'int',
                'notice' => rex_i18n::msg('media_manager_effekt_focuspointfit_hpos_notice'),
            ],
            [
                'label' => rex_i18n::msg('media_manager_effekt_focuspointfit_vpos'),
                'name' => 'vpos',
                'type' => 'int',
                'notice' => rex_i18n::msg('media_manager_effekt_focuspointfit_vpos_notice'),
            ],
            [
                'label' => rex_i18n::msg('media_manager_effekt_focuspointfit_fp'),
                'name' => 'fp',
                'type' => 'select',
                'options' => $this->optionsFP,
            ],
        ];
    }


    private function numParaOk( $para, $low=0, $default=0, $high=0 )
    {
        $para = trim( $para );
        return ( empty($para) || !is_numeric($para) || $para < $low || $para > $high ) ? $default : (int)$para;
    }

    private function decodeSize( $value, $ref=0 )
    {
        $value = trim( $value );
        if ( !preg_match( '/^\d*[%]*$/',$value ) ) return NULL;
        if ( strpos( $value,'%' ) )
        {
            $value = str_replace( '%','',$value);
            if ( $value > 100 ) $value = 100;
            $value = $ref * $value / 100;
        }
        return $value;
    }


}
