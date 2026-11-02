# Focuspoint für REDAXO 6

Fokuspunkt für Bilder des Medienpools: Auf der Detailseite eines Bildes zeigt die Fokuspunkt-Auswahl den Punkt als
Fadenkreuz (ohne Wert die Bildmitte), ein Klick ins Bild oder die Pfeiltasten setzen den wichtigsten Bildbereich. Medientypen mit festem Seitenverhältnis schneiden dann um diesen Punkt herum zu statt
mittig – Köpfe bleiben im Bild.

Dies ist die REDAXO-6-Fassung (Branch `5.x`). Die Fassung für REDAXO 5 liegt bei
[FriendsOfREDAXO/focuspoint](https://github.com/FriendsOfREDAXO/focuspoint).

## Installation

```bash
composer require friendsofredaxo/focuspoint:5.x-dev
php bin/console migrate
```

`migrate` legt das Metafeld als Spalte `meta_focuspoint` in `rex_media` an. Der Wert hat dasselbe Format wie
`med_focuspoint` der REDAXO-5-Fassung („x,y" in Prozent, z. B. `52.3,35.5`) und lässt sich direkt übernehmen:

```sql
UPDATE rex_media SET meta_focuspoint = med_focuspoint WHERE med_focuspoint <> '';
```

## Zuschnitt im Medientyp

Am einfachsten über die Basisklasse `FocuspointCropType`: Name und Maße stehen am Attribut, beliebig viele Formate
in einer Klasse.

```php
use FriendsOfRedaxo\Focuspoint\MediaType\FocuspointCropType;
use Redaxo\Core\MediaManager\AsMediaType;
use Redaxo\Core\MediaManager\NegotiatesFormat;
use Redaxo\Core\MediaManager\NegotiatesImageFormats;

#[AsMediaType('teaser', width: 800, height: 450)]
#[AsMediaType('hero_mobile', width: 1000, height: 1250, placeY: 20)]
final readonly class CropTypes extends FocuspointCropType implements NegotiatesFormat
{
    use NegotiatesImageFormats;
}
```

Der Zuschnitt arbeitet wie `coverDown()` von Intervention Image: größter Ausschnitt im Zielverhältnis, dann
verkleinern, nie vergrößern. Der Fokuspunkt steht im Ausschnitt an der Stelle `placeX`/`placeY` (Prozent, Standard
Mitte), soweit das Bild reicht – mit `placeY: 20` etwa im oberen Fünftel, wenn unten Text über dem Bild liegt. Ohne
gesetzten Punkt liegt der Ausschnitt mittig. Nach einer Änderung des Punktes leert der Core den Cache des Bildes; nach
einem Update dieses Pakets `console cache:clear`, denn der Bild-Cache hängt am Quelltext der eigenen Unterklasse.

In eigenen Medientypen geht dasselbe direkt mit dem Modifier:
`$context->image->modify(FocusCover::forFile($context->filename, 800, 450));`

## Im Frontend

Für Bilder, die per CSS beschnitten werden (`object-fit: cover`):

```php
use FriendsOfRedaxo\Focuspoint\Focuspoint;

$position = Focuspoint::forMedia($media)?->cssPosition();   // "52.3% 35.5%"
echo '<img src="…" style="object-fit:cover; object-position:' . ($position ?? '50% 50%') . '">';
```

Ist das Bild vorher schon serverseitig zugeschnitten, liegt der Punkt im gelieferten Bild an anderer Stelle. Dann die
Lage im Ergebnis verwenden:

```php
$type = MediaTypeRegistry::get('teaser');   // FocuspointCropType
$position = $type->focusInResult($media)?->cssPosition();
```

## Unterschiede zur REDAXO-5-Fassung

- Kein Effekt `focuspoint_fit` mit Zoom-Stufen: REDAXO 6 hat keine Effekt-Ketten mehr, Medientypen sind Klassen.
  Der Zuschnitt ist ein Intervention-Modifier.
- Ein Metafeld (`meta_focuspoint`) statt beliebig vieler Fokuspunkt-Felder. Die Fokuspunkt-Auswahl ist aufgebaut
  wie bisher (Fadenkreuz, Koordinaten, Zurücksetzen auf Ausgangswert oder Entfernen, Vergrößern), ohne
  Vorschau der Medientypen.
