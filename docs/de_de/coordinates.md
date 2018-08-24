# Koordinaten

**Koordinaten** sind stets ein Wertepaar [x,y]; dabei steht

- x für die horizontale Position
- y für die vertikale Position

Koordinaten-Ursprung ist die Ecke oben links des Bildes. Der x-Wert bezieht sich auf den Abstand
vom linken Bildrand, der y-Wert ist der Abstand vom oberen Bildrand.

![Koordinaten](coordinates.jpg)

Die Koordinaten werden als prozentualer Anteil der Bildbreite bzw. Bildhöhe angegeben.

- '0.0,0.0': Ecke oben links
- '50.0,50.0' : Bildmitte
- '100.0,100.0' : Ecke unten rechts

Der ausgewählte Fokuspunkt im Beispielbild ist an der Position **'79,5,62,0'**.

In Eingabefeldern müssen Koordinaten immer mit einer Nachkommastelle eingegeben werden und dürfen
keine Leerzeichen enthalten. Für ungültige oder leere Felder wird der jeweiligen Default-Wert (meist die Bildmitte, also '50.0,50.0') herangezogen.

Methoden und Funktionen erwarten oder liefern eine Koordinate als Array:

```
    $xy = [ 0 => «x», 1 => «y» ];
```
