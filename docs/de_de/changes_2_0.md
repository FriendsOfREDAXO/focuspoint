# Änderungen in Version 2.0

Version 2.0 ist komplett neu entwickelt. Am Grundprinzip hat sich nichts geändert.
Allerdings sind einige Erweiterungen hinzugekommen, die die Nutzung erweitern und erleichtern, sowie
strukturelle Änderungen.

- die Klasse `focuspoint_media` erweitert `rex_media` mit einer zusätzlichen Methode zum Abruf valider Koordinaten
- Die Klasse `rex_effect_abstract_focuspoint` erweitert "rex_effect_abstract" um Fokuspunkt-bezogene Methoden und dient als Basis der mitgelieferten und für eigene Media-Manager-Effekte
- Zusätzliche eigene Fokuspunkt-Felder mit dem neuen Metatyp `Focuspoint (AddOn)`
- Verbesserte interaktive Fokuspunkt-Zuordnung im Media-Detailformular
- Neue, umfassende Dokumentation
- Umstellung der Metafelder `med_focuspoint_data` und `med_focuspoint_css` auf nur ein Feld `med_focuspoint`
- Höhere Auflösung der Koordinaten (1/1000 der Bildbreite bzw. Bildhöhe)
- Ankündigung: Wegfall des Media-Manager-Effektes "fokuspoint_resize"

## Geänderte Datenhaltung

Die Datenhaltung wurde umgestellt von zwei redundanten Feldern (`med_focuspoint_data` und `med_focuspoint_css`
mit letzlich derselben Koordinate) auf nur noch ein Metafeld (`med_focuspoint`). Sofern die Ausgabe über
den Media-Manager erfolgt, müssen keine Anpassungen im Script vorgenommen werden. In Fällen, in denen die Metafelder
direkt genutzt werden, sind Anpassungen im Script erforderlich.

### Feld: med_focuspoint_css

Das Feld stellte die Koordinate als ganzzahligen Prozenzsatz von Breite und Höhe dar. Nullpunkt ist die Bildecke oben links.
Mit Ausnahme des enthaltenen Prozentzeichen sind die Werte grundsätzlich vergleichbar mit dem neuen [Feldformat](coordinates.md).

- alt: 50%,60%
- neu: 50.0,60.0

Um einen dem alten Feld vergleichbaren Wert zu erhalten, sollte die Koordinate in ihren
Einzelwerten abgerufen werden. Man kann die Einzelwerte nach Bedarf weiterverarbeiten.

```
$fpMedia = focuspoint_media::get( $filename );  // statt rex_media::get( $filename )
list( $x, $y) = $fpMedia->getFocus();            // Abruf von "med_focuspoint" als [$x,$y]
$fp = "$x%,$y%";                                // Verwendung
```

### Feld: med_focuspoint_data

Für dieses Feld lag der Koordinatenursprung genau in der Bildmitte; die Koordinaten waren ein
Wert zwischen -1 (links bzw. unten) und 1 (rechts bzw. oben).

- alt: 0.0,-0.2
- neu: 50.0,60.0

```
$fpMedia = focuspoint_media::get( $filename );  // statt rex_media::get( $filename )
list( $x_neu, $y_neu) = fpMedia->getFocus();    // Abruf von "med_focuspoint" als [$x,$y]
$x_alt = $x_neu / 50 - 1;                       // X-Koordinate umrechnen
$y_alt = 1 - $y_neu / 50;                       // Y-Koordinate umrechnen
```

## Media-Manager-Effekt "focuspoint_resize"

Der Effekt `focuspoint_resize` für den Media-Manager wird nicht mehr unterstützt, da sich in Umfragen keine
Nutzer gemeldet haben. Der Effekt ist noch im Addon enthalten und wird erst in Version 2.2 vollständig entfernt werden.
Die Dokumentation geht nicht weiter auf den Effekt ein.

**Empfehlung:**
Ersatz durch den Effekt `focuspoint_fit`. Die Parametrisierung ist allerdings so unterschiedlich, dass
keine konkrete Empfehlung gegeben werden kann, wie das Resize-Ergebnis exakt reproduziert werden kann.
Der Grund: "resize" orientiert sich am Ausgangsbild, "fit" am Zielformat.

Wer auf keinen Fall auf den Effekt verzichten kann, sollte ihn perspektivisch in das Projekt-Addon
verschieben.

**Roadmap:**

- Version 2.0: Der Effekt ist als "künftig wegfallend" markiert, steht aber weiterhin (ohne Dokumentation) zur Verfügung.
- Version 2.1: Der Effekt wird zwar weiterhin ausgeliefert, aber nicht in der boot.php aktiviert.
- Version 2.2: Der Effekt wird komplett entfernt.
