## Focuspoint für REDAXO 5

Das Addon erweitert den Medienpool um die Fähigkeit, den Fokuspunkt eines Bilds zu bestimmen, um es bei der Darstellung auf der Website daran auszurichten.

<img src="https://raw.githubusercontent.com/eace/focuspoint/assets/focuspoint_02.jpg" style="width: 100%; max-width: 888px" />

Die ermittelten Daten werden in der Datenbank hinterlegt und können bei der Bildausgabe berücksichtigt werden. Hierfür wird das jQuery-Plugin von [Jono Menz](https://github.com/jonom/jquery-focuspoint) benutzt.

___

Schreibt doch bitte auftretende Fehler, Notices und Wünsche als Issue auf [Github](https://github.com/FriendsOfREDAXO/focuspoint/issues)

___

Das Changelog findet sich hier: [CHANGELOG.md](https://github.com/FriendsOfREDAXO/focuspoint/blob/master/CHANGELOG.md)

---


## Hilfe

Folgendes im Template einfügen:

```php
<link rel="stylesheet" href="/assets/addons/focuspoint/focuspoint.css" />

<!-- jQuery Einbindung Anfang
     Nur notwendig wenn jQuery nicht anders eingebunden wird ->
<script type="text/javascript" src="http://code.jquery.com/jquery-2.1.0.min.js" /></script>
<!-- // Query Einbindung Ende -->

<script type="text/javascript" src="./assets/addons/focuspoint/jquery_focuspoint.js"></script>
<script type="text/javascript">
$( document ).ready(function() {
  $('.focuspoint').focusPoint();
});
</script>
```

Bei der Installation wurde ein Effekt beim Media Manager AddOn hinzugefügt. Sollte dieser fehlen, bitte ein reinstall durchführen

Diese Ausgabe dient als Beispiel für ein Modul:
```php
<?php

if ('REX_MEDIA[1]') {

  $file         = rex_media::get(REX_MEDIA[1]);
  $filename     = $file->getFilename();
  $titel        = $file->getTitle();
  $width        = $file->getValue('width');
  $height       = $file->getValue('height');
  $focuspoint_css     = $file->getValue('focuspoint_css');
  $focuspoint_data    = explode(",", $file->getValue('med_focuspoint_data'), 2);

  if (count($focuspoint_data) == 2) {
    echo '
       <div class="focuspoint"
          data-focus-x="'.$focuspoint_data[0].'"
          data-focus-y="'.$focuspoint_data[1].'"
          data-image-w="'.$width.'"
          data-image-h="'.$height.'">
          <img src="/media/'.$filename.'" alt="'.htmlspecialchars($titel).'" />
        </div>
        ';
    } else {
        echo '<img src="/media/'.$filename.'" alt="'.htmlspecialchars($titel).'" />';
    }

}
?>
```

Diese Beispiel zeigt wie Daten via CSS im Modul benutzt werden könnten:
```php
// auslesen
$media = rex_media::get("REX_MEDIA[id=1 output=1]");
$back = $media->getValue('focuspoint_css');
$back = explode(",",$back);
// CSS Style:
background-position: <?php echo $position; unset($position); ?>

```


## Lizenz

The MIT License (MIT)

Copyright (c) 2016 Friends Of REDAXO

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


## Credits

- [Jono Menz](https://github.com/jonom/jquery-focuspoint)
- [FriendsOfREDAXO](https://github.com/FriendsOfREDAXO)

<br/>

**Projekt-Lead**

- [Oliver Kreischer](https://github.com/olien)



