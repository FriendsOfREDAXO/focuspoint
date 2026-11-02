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

```php
use Redaxo\Core\MediaManager\AsMediaType;
use Redaxo\Core\MediaManager\MediaContext;
use Redaxo\Core\MediaManager\MediaType;
use FriendsOfRedaxo\Focuspoint\Modifier\FocusCover;

#[AsMediaType('teaser')]
final class Teaser implements MediaType
{
    public function process(MediaContext $context): void
    {
        $context->image->modify(FocusCover::forFile($context->filename, 800, 450));
    }
}
```

`FocusCover` arbeitet wie `coverDown()` von Intervention Image: größter Ausschnitt im Zielverhältnis, dann
verkleinern, nie vergrößern. Ohne gesetzten Fokuspunkt liegt der Ausschnitt mittig. Nach einer Änderung des Punktes
leert der Core den Cache des Bildes.

## Im Frontend

Für Bilder, die per CSS beschnitten werden (`object-fit: cover`):

```php
use FriendsOfRedaxo\Focuspoint\Focuspoint;

$position = Focuspoint::forMedia($media)?->cssPosition();   // "52.3% 35.5%"
echo '<img src="…" style="object-fit:cover; object-position:' . ($position ?? '50% 50%') . '">';
```

## Unterschiede zur REDAXO-5-Fassung

- Kein Effekt `focuspoint_fit` mit Zoom-Stufen: REDAXO 6 hat keine Effekt-Ketten mehr, Medientypen sind Klassen.
  Der Zuschnitt ist ein Intervention-Modifier.
- Ein Metafeld (`meta_focuspoint`) statt beliebig vieler Fokuspunkt-Felder. Die Fokuspunkt-Auswahl ist aufgebaut
  wie bisher (Fadenkreuz, Koordinaten, Zurücksetzen auf Ausgangswert oder Entfernen, Vergrößern), ohne
  Vorschau der Medientypen.
