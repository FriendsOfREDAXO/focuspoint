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
 *  focuspoint_resize
 *
 *  @method string getName()
 *  @method void execute()
 *  @method array getParams()
 *  @method void resizeMax($w, $h)
  * @method void resizeMin($w, $h)
 */




class rex_effect_focuspoint_resize extends rex_effect_abstract_focuspoint
{
    private $options= ['maximum', 'minimum', 'exact'];

    /**
     *  Gibt den Namen des MM-Effektes zurück
     *
     *  @return     string      Effekt-Name
     */
	public function getName()
	{
		return rex_i18n::msg('focuspoint_effect_focuspoint_resize');
	}

    /**
     *  Erzeugt den Bildeffekt
     *
     *  @return     void
     */
    public function execute()
    {

		/*
			Fokuspunkt ermitteln:
				zuerst den Fallback-Wert bzw. Default-Wert des Effekts
				dann den FP des Bildes
		*/
		$focuspoint_data = $this->getFocus( focuspoint_media::get( $this->media->getMediaFilename() ), $this->getDefaultFocus( ) );

		/*
			Umrechnen in die koordinaten-BAsis der 1.x-Versionen;
			dann kann der Algorithmus bleiben wie er ist
		*/
		$focuspoint_data = [ ($focuspoint_data[0]/50)-1, 1-($focuspoint_data[1]/50) ];

		$this->media->asImage();

		$gdimage = $this->media->getImage();
		$w = $this->media->getWidth();
		$h = $this->media->getHeight();


		// Mittelpunkt finden
		$x = ceil($w / 2);
			$y = ceil($h / 2);

		// focusoffsets einarbeiten
		$fp_w = $focuspoint_data[0];
			$fp_h = $focuspoint_data[1];

		// Neuen Mittelpunkt finden
		$nx = $x + ceil($x * $fp_w);
			$ny = $y - ceil($y * $fp_h);

		// Abstand zum Rand herausfinden
		$nw = $w - $nx; // 1/2 Breite
		if ($fp_w < 0) {
			$nw = $nx; // 1/2 Breite
		}

		$nh = $ny; // 1/2 Breite
		if ($fp_h < 0) {
			$nh = $h - $ny; // 1/2 Breite
		}

		$npx = $nx - $nw;
		$npy = $ny - $nh;
		$nw = $nw * 2;
		$nh = $nh * 2;

		if (function_exists('ImageCreateTrueColor')) {
			$des = @ImageCreateTrueColor($nw, $nh);
		} else {
			$des = @ImageCreate($nw, $nh);
		}

		$this->keepTransparent($des);
		imagecopyresampled($des, $gdimage, 0, 0, $npx, $npy, $nw, $nh, $nw, $nh);

		$gdimage = $des;
		$this->media->refreshImageDimensions();

		$w = $nw;
		$h = $nh;

        if (!isset($this->params['style']) || !in_array($this->params['style'], $this->options)) {
            $this->params['style'] = 'maximum';
        }

        // relatives resizen
        if (substr(trim($this->params['width']), -1) === '%') {
            $this->params['width'] = round($w * (rtrim($this->params['width'], '%') / 100));
        }
        if (substr(trim($this->params['height']), -1) === '%') {
            $this->params['height'] = round($h * (rtrim($this->params['height'], '%') / 100));
        }

        if ($this->params['style'] == 'maximum') {
            $this->resizeMax($w, $h);
        } elseif ($this->params['style'] == 'minimum') {
            $this->resizeMin($w, $h);
        } else {
            // warp => nichts tun
        }

        // ----- not enlarge image
        if ($w <= $this->params['width'] && $h <= $this->params['height'] && $this->params['allow_enlarge'] == 'not_enlarge') {
            $this->params['width'] = $w;
            $this->params['height'] = $h;
            $this->keepTransparent($gdimage);

            return;
        }

        if (!isset($this->params['width'])) {
            $this->params['width'] = $w;
        }

        if (!isset($this->params['height'])) {
            $this->params['height'] = $h;
        }

        if (function_exists('ImageCreateTrueColor')) {
            $des = @imagecreatetruecolor($this->params['width'], $this->params['height']);
        } else {
            $des = @imagecreate($this->params['width'], $this->params['height']);
        }

        if (!$des) {
            return;
        }

        // Transparenz erhalten
        $this->keepTransparent($des);
        imagecopyresampled($des, $gdimage, 0, 0, 0, 0, $this->params['width'], $this->params['height'], $w, $h);

        $this->media->setImage($des);
        $this->media->refreshImageDimensions();
    }

    /**
     *  Stellt die Felder für die Effekt-Konfiguration als Array bereit.
     *
     *  Die Basisfelder werden aus der Parent-Klasse abgerufen und um die Felder für
     *  Breite und Höhe des Zielbildes, die Berechnungsmethode und die Vergrößerungsoption ergänzt.
     *
     *  @return     array   Felddefinitionen
     */
    public function getParams()
    {
        return array_merge( parent::getParams(),[
            [
                'label' => rex_i18n::msg('focuspoint_edit_label_width'),
                'name' => 'width',
                'type' => 'int',
                'notice' => rex_i18n::msg('focuspoint_edit_notice_widthheigth_resize'),
            ],
            [
                'label' => rex_i18n::msg('focuspoint_edit_label_heigth'),
                'name' => 'height',
                'type' => 'int',
                'notice' => rex_i18n::msg('focuspoint_edit_notice_widthheigth_resize'),
            ],
            [
                'label' => rex_i18n::msg('focuspoint_edit_label_style'),
                'name' => 'style',
                'type' => 'select',
                'attributes' => [ 'onchange'=>'focuspoint_resize_sw()'],
                'options' => $this->options,
                'default' => 'fit',
                'suffix' =>  '
                        <script type="text/javascript">
                            $(document).ready( function() {
                                focuspoint_resize_sw();
                            });
                            function focuspoint_resize_sw ()
                            {
                                $("#media-manager-rex-effect-focuspoint-resize-allow-enlarge-select").closest(".rex-form-group").toggleClass("hidden",$("#media-manager-rex-effect-focuspoint-resize-style-select").val() != "exact" );
                            }
                        </script>',
            ],
            [
                'label' => rex_i18n::msg('focuspoint_edit_label_allow_enlarge'),
                'name' => 'allow_enlarge',
                'type' => 'select',
                'options' => ['enlarge', 'not_enlarge'],
                'default' => 'enlarge',
            ],
        ]);
    }
    /**
     *  Hilfsfunktion: Errechnen von Zielkoordinaten.
     *
     *  Die Methode gibt keine verändert Klassenvariablen, liefert aber keinen Return-Wert
     *
     *  @var    num     $w  Bildbreite
     *  @var    num     $h  Bildhöhe
     *
     *  @return void
     */
    private function resizeMax($w, $h)
    {
        if (!empty($this->params['height']) && !empty($this->params['width'])) {
            $img_ratio = $w / $h;
            $resize_ratio = $this->params['width'] / $this->params['height'];

            if ($img_ratio >= $resize_ratio) {
                // --- width
                $this->params['height'] = ceil($this->params['width'] / $w * $h);
            } else {
                // --- height
                $this->params['width'] = ceil($this->params['height'] / $h * $w);
            }
        } elseif (!empty($this->params['height'])) {
            $img_factor = $h / $this->params['height'];
            $this->params['width'] = ceil($w / $img_factor);
        } elseif (!empty($this->params['width'])) {
            $img_factor = $w / $this->params['width'];
            $this->params['height'] = ceil($h / $img_factor);
        }
    }

    /**
     *  Hilfsfunktion: Errechnen von Zielkoordinaten.
     *
     *  Die Methode gibt keine verändert Klassenvariablen, liefert aber keinen Return-Wert
     *
     *  @var    num     $w  Bildbreite
     *  @var    num     $h  Bildhöhe
     *
     *  @return void
     */
    private function resizeMin($w, $h)
    {
        if (!empty($this->params['height']) && !empty($this->params['width'])) {
            $img_ratio = $w / $h;
            $resize_ratio = $this->params['width'] / $this->params['height'];

            if ($img_ratio < $resize_ratio) {
                // --- width
                $this->params['height'] = ceil($this->params['width'] / $w * $h);
            } else {
                // --- height
                $this->params['width'] = ceil($this->params['height'] / $h * $w);
            }
        } elseif (!empty($this->params['height'])) {
            $img_factor = $h / $this->params['height'];
            $this->params['width'] = ceil($w / $img_factor);
        } elseif (!empty($this->params['width'])) {
            $img_factor = $w / $this->params['width'];
            $this->params['height'] = ceil($h / $img_factor);
        }
    }

}
