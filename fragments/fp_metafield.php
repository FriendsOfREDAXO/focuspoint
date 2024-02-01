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
 *  Erzeugt Meta-Felder für Fokuspunkte
 *  Alles Pflicht-Felder
 *  Es findet keine Überprüfung statt
 *
 *  $this->id           Feld-ID
 *  $this->feldname     Input-Feld: Attr name
 *  $this->value        aktueller Feldwert
 *  $this->label        Label (dt/DL/dd)
 *  $this->default      DefaultWert für dieses Meta-Feld, auf den ein Reset erfolgt
 *  $this->hidden       true => ausblenden
 */

namespace FriendsOfRedaxo\Focuspoint;

use rex_fragment;

if (!isset($this->default)) {
    $this->default = '';
}
$feld = new rex_fragment();
$feld->setVar('elements', [
    [
        'class' => 'focuspoint-input-group',
        'left' => '<button class="btn btn-default" type="button"><i class="rex-icon fa-crosshairs"></i></button>',
        'field' => "<input id=\"{$this->id}\" name=\"$this->name\" value=\"$this->value\" pattern=\"^(100|[1-9]?[0-9])[.][0-9],(100|[1-9]?[0-9])[.][0-9]$\" type=\"text\" class=\"form-control\" data-default=\"$this->default\" data-fpinitial=\"$this->value\" />",
    ],
], false);

$feld->setVar('elements', [
    [
        'label' => $this->label,
        'field' => $feld->parse('core/form/input_group.php'),
        'class' => 'focuspoint-form-group' . ($this->hidden ? ' hidden' : ''),
    ],
], false);
echo $feld->parse('core/form/form.php');
