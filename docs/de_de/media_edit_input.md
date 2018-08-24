# Direkte Fokuspunkt-Erfassung

Die Koordinaten werden in Metafeldern des Medien-Datensatzes gespeichert. Die Erfassung
erfolgt in der Detailansicht des Medienpools.

Nachstehend wird die Fokuspunkt-Erfassung in den Eingabefeldern erläutert:

![Fokuspunkt eingeben](edit03.jpg)

Bei der Installation wird ein erstes Fokuspunkt-Metafeld `med_focuspoint` anglegt,
das im Detailformular als "Fokuspunkt" angezeigt wird.

Im obigen Beispielbild ist ein zweites Feld "Horizont-Fokus" enthalten.

## Fokuspunkte erfassen

Im Eingabereich werden die Fokuspunkt-Koordinaten eingegeben. Das [Format](coordinates.md) muss unbedingt eingehalten werden.

Beim Verlassen des Feldes (z.B. Klick auf ein anderes Feld) werden die Koordinaten in die
interaktive Auswahl synchronisiert.

Für die Eingabefelder sind Formatüberprüfungen hinterlegt. Bei ungültigen Werten springt das
Fadenkreuz in die Bildmitte und das Eingabefeld erhält einen roten Rand. Beim Versuch, das Formular
zu speichern, wird zudem eine konkrete Fehlermeldung angezeigt.

## Zwischen Fokuspunkten wechseln

Der Fadenkreuz-Button <i class="rex-icon fa-crosshairs"></i> dient als Umschalter zwischen den
Fokuspunkt-Feldern und wirkt wie ein Radio-Button. Das jeweils aktive Feld - das auch in der interaktiven Auswahl aktiviert ist -
wird blau angezeigt.

> Dieses Umschaltverfahren (analog zu Radio-Buttons) ist der Grund, warum Fokuspunkt-Metafelder direkt hintereinander stehen sollten.
