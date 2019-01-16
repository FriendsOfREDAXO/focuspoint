# Fokuspunkt: Einführung

Die Mitte eines Bildes ist nicht oder nicht in jedem Fall auch der inhaltliche Mittelpunkt
eines Bildes. Wird ein Bild in unterschiedlichen Formaten, Zuschnitten und Zusammenhängen eingesetzt,
muss darauf geachtet werden, dass immer das Wesentliche bzw. Wichtigste zu sehen ist und
nicht Nebensächlichkeiten.

![Beispiel](example.png)

Beim automatischen Zuschnitt kann viel schiefgehen.

Besser wäre es, man könnte einem Bild Informationen mitgeben, wo der inhaltliche Mittelpunkt - _der
Fokuspunkt_ - des Bildes ist.

Das Fokuspoint-AddOn ist genau dafür entwickelt worden. Mit ihm können

- für einzelne Medien interaktiv festgelegt werden, wo der Bildschwerpunkt ist,
- Bilder unter Berücksichtigung des Fokuspunktes zugeschnitten werden,
- unabhängig vom Format des Quellbildes Layout-sichere Zielformate erzeugt werden,

Die [interaktive Festlegung](media_edit_interactive.md) der Fokuspunkte wird im Medienpool vorgenommen.
Die Bildausgabe sollte über entsprechend konfigurierte [Bildtypen](mm_overview.md) des Media-Managers erfolgen.


# Koordinaten

Der Fokuspunkt ist eine [Koordinate](coordinates.md) im Bild mit den zwei Komponenten X (Breite, horizontal) und Y (Höhe, vertikal).
Nullpunkt ist die obere linke Ecke des Bildes. Die Koordinate ist ein Prozentwert der Originalgröße
zwischen 0.0 und 100.0.

# Meta-Datenfelder

Fokuspunkte werden in [Metafeldern](metafield.md) einer Media-Datei gespeichert. Ein Metafeld wird automatisch bei der Installation
angelegt (`med_focuspoint`). Falls benötigt können weitere Felder angelegt werden. Es gibt dazu einen
Metadatentyp `Focuspoint (AddOn)`.

# Fokuspunkte festlegen

Die einfachste Variante der Eingabe ist die [interaktive Auswahl](media_edit_interactive.md) im Bild selbst. Man klickt einfach auf den
gewünschten Fokuspunkt, der in das [Eingabefeld](media_edit_input.md) übertragen wird.

# Bilder um den Fokuspunkt zentriert ausgeben

Bilder werden z.B. über [Typen im Media-Manager](mm_overview.md) Fokuspunkt-bezogen erzeugt. Wie das
genau geht beschreibt die Dokumentation des Media-Managers. Es ist also kein spezifisches
Ausgabemodul für Fokuspunkt-bezogene Bilder notwendig.



> **Hinweis:**  
>
> Diese Dokumentation wird auf Github gepflegt:  
> https://github.com/friendsofradaxo/focuspoint/plugins/docs
> Ergänzungen oder Korrekturen bitte am besten direkt dort als Issue oder Pull request erstellen.
