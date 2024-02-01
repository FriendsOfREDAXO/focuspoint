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
 *  Erzeugt den HTML-Container zur interaktiven Fokuspunkt-Auswahl
 *
 *  $this->mediafile        muss        Name der Mediendatei im Medianpool
 *  $this->mediatypes       optional    Mediatypes mit Fokuspunkt-Effekten.
 *                                      Wenn angegeben wird ein Auswahlbutton und die Preview angelegt
 *                                      ..[typ] = [ 'label'=>typ, 'meta'=>'liste der felder' ]
 *  $this->fieldselect      optional    key/pair aus feldname/feldtitel falls es mehr als ein
 *                                      Fokuspunkt-Metafeld gibt und mindestens eines "hidden" ist
 *                                      Dann wird ein Select eingebaut.
 */

namespace FriendsOfRedaxo\Focuspoint;

use rex_effect_abstract_focuspoint;
use rex_fragment;
use rex_i18n;
use rex_media;
use rex_media_manager;

/** @var rex_fragment $this */
?>
<div id="focuspoint-panel" class="focuspoint-panel panel panel-default">
    <div class="panel-heading"><?= rex_i18n::msg('focuspoint_detail_header') ?></div>
<?php
$mediatypes = '';
if (isset($this->mediatypes) && \is_array($this->mediatypes)) {
    $mediatypeList = $this->mediatypes;
    array_walk($mediatypeList, static function (&$t, $k) {
    $t = '<li data-ref="' . $k . '" data-field="' . implode(' ', $t['meta']) . '"><a href="#" >' . $t['label'] . '</a></li>';
    });
    $mediatypes = implode('', $mediatypeList);
}
if (isset($this->fieldselect) && \is_array($this->fieldselect)) {
    $fieldselect = $this->fieldselect;
    array_walk($fieldselect, static function (&$v, $k) {
    $v = "<option value='$k'>$v</option>";
    });
    $fieldselect = implode('', $fieldselect);
?>
    <div class="input-group focuspoint-panel-select">
        <span class="input-group-addon"><?= rex_i18n::msg('focuspoint_detail_select') ?></span>
        <select class="form-control"><?= $fieldselect ?></select>
    </div>
<?php
}
?>
    <div class="focuspoint-panel-image">
        <img src="<?= rex_media_manager::getUrl(rex_effect_abstract_focuspoint::MM_TYPE, $this->mediafile, rex_media::get($this->mediafile)->getUpdateDate()) ?>">
        <div class="focuspoint-panel-enabler hidden"></div>
    </div>
    <small class="focuspoint-panel-enabler hidden"><span></span></small>
    <div class="btn-toolbar btn-sm focuspoint-panel-enabler hidden" role="toolbar">
        <div class="btn-group">
            <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?= rex_i18n::msg('focuspoint_detail_reset') ?> <span class="caret"></span></button>
            <ul class="dropdown-menu">
                <li data-button="reset"><a class="dropdown-item" href="#"><?= rex_i18n::msg('focuspoint_detail_initial') ?></a></li>
                <li data-button="remove"><a class="dropdown-item" href="#"><?= rex_i18n::msg('focuspoint_detail_remove') ?></a></li>
            </ul>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-button="zoom" ><i class="rex-icon fa-search-plus"></i><i class="rex-icon fa-search-minus"></i></button>
<?php
if ('' < $mediatypes) {
?>
        <div class="focuspoint-panel-typeselect btn-group">
            <button type="button" class="btn btn-default  btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?= rex_i18n::msg('focuspoint_detail_preview') ?> <span class="badge"></span> <span class="caret"></span></button>
            <ul class="dropdown-menu"><?= $mediatypes ?></ul>
            <button type="button" data-button="schalter" class="btn btn-default hidden btn-sm"><i class="rex-icon fa-times"></i></button>
        </div>
<?php } ?>
    </div>
<?php
if ('' < $mediatypes) {
?>
    <div class="hidden panel-body">
        <img >
    </div>
<?php } ?>
</div>

<script type="text/javascript">
    $(document).ready( function() {
        fpCreateController( $('#focuspoint-panel'), "<?= $this->mediafile ?>" );
    });
</script>
