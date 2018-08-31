# Übersicht

Fokuspunkt-orientierte Bilder sollten über den Media-Manager und entsprechende Effekte erzeugt werden.
Erstens wird der Client von Bildoperationen auf JS-Basis entlastet und zweitens sind die Bilder
gecached.

Dabei ist unbedingt zu beachten:

- Fokuspunkt-basierte Effekte setzen voraus, dass das Seitenverhältnis (Aspect-Ratio) des Originalbildes vorliegt.
- Ein "Resize" ohne Änderung des Seitenverhältnisses ist unkritisch, denn die Fokuspunkt-Koordinaten sind ein Prozentsatz der Bildabmessungen.
- Die Reihenfolge mit Bildeffekten (blur, sharpen, etc.), die bezogen auf das Seitenverhältnis keine Änderung durchführen, ist unkritisch
- Formatneutrale Fokuspunkt-Effekte müssen vor (einem) formatverändernden Effekt ausgeführt werden.
- Nicht jeder Grafiktyp wird vom Media-Manager klaglos verarbeitet. SVG ist z.B. nicht unterstützt.

Mit im Focuspoint-AddOn ist der Effekt "focuspoint_fit", der Bilder und Bildausschnitte genau definierter Größe
erzeugt, deren Zuschnitt auf dem Fokuspunkt beruht. Die Zielbilder sind unabhängig vom Quellformat
immer in der angetrebten Zielgröße.

Eigene Fokuspunkt-bezogene Effekte - egal ob formatverändernd oder formatneutral - sollten immer
von der Klasse "[rex_effect_abstract_focuspoint](developer.md#api-refa)" abgeleitet werden.

Media-Manager-Typen, die Effekte auf Basis der Klasse `rex_effect_abstract_focuspoint` aufweisen, können in der
interaktiven [Fokuspunkt-Festlegung](media_edit_interactive.md) in der Vorschau genutzt werden.
