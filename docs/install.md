> - [Grundlagen](#overview)
> - [Bildern Fokuspunkte zuweisen](edit.md)
> - [Media-Manager: der Effekt "focuspoint-fit"](media_manager.md)
> - Addon-Verwaltung
> - [Hinweise für Entwickler (API)](developer.md)

# Addon-Verwaltung

<a name="manage-install"></a>
## Installieren

Die Installation umfasst:

* Metafeldtyp `Focuspoint (AddOn)` anlegen
* Metafeld `med_focuspoint` vom Typ `Focuspoint (AddOn)` anlegen
* Media-Manager-Typ `focuspoint_media_detail` anlegen
* Verzeichnis `/assets/addons/focuspoint` anlegen und befüllen

Anschließend stehen alle Funktionalitäten wie in diesem Dokument beschrieben zur Verfügung.

Sollte es bereits einen Metafeldtyp `Focuspoint (AddOn)` geben, wird dessen `type_id` benutzt.

Sollte es bereits ein Meta-Feld `med_focuspoint` geben, das einen anderen Feldtyp hat, wird die
Installation abgebrochen. Das Feld muss zuerst gelöscht oder umbenannt werden.

Sollte es bereits einen Media-Manager-Typ `focuspoint_media_detail` geben, wird lediglich der
damit verbundene Effekt reinitialisiert.

<a name="manage-reinstall"></a>
## Re-Installieren

REDAXO nutzt für die Re-Installation dieselbe Routine wie für die Installation. Zwischenzeitliche Änderungen (z.B. im Assets-Verzeichnis) werden zurückgedreht.

Existierende Fokuspunkt-Metafelder (Typ `Focuspoint (AddOn)`) bleiben unangetastet.

<a name="manage-activate"></a>
## Aktivieren

Aktivierung hebt eine Deaktivierung auf. Dabei wird überprüft, ob zwischenzeitlich gravierende
Änderungen vorgenommen wurden:

- Metafeldtyp `Focuspoint (AddOn)` entfernt
- Metafeld `med_focuspoint` vom Typ `Focuspoint (AddOn)` entfernt
- Media-Manager-Typ `focuspoint_media_detail` entfernt

In diesen Fällen kommt ein Warnhinweis, die Aktivierung unterbleibt und eine [Re-Installation](#manage-reinstall)
ist notwendig.

<a name="manage-deactivate"></a>
## De-Aktivieren

> **Durch De-Aktivieren des Addons wird Media-Manager-Typen, die die mitgelieferten Effekte bzw.
auf der Klasse `rex_effect_abstract_focuspoint` basierende Effekte nutzen, die Grundlage entzogen!**

Vor dem De-Aktivieren wird überprüft ob im Media-Manager Typen eingerichtet sind, die auf der
Klasse `rex_effect_abstract_focuspoint` basierende Effekte nutzen. Wenn ja, wird der Vorgang
abgebrochen.

Die Metafelder für Fokuspunkte (Typ `Focuspoint (AddOn)`) bleiben erhalten, auch wenn sie im Detailformular
für Medien nicht mehr angezeigt werden.

Es wird **nicht** überprüft, **ob** es eigenentwickelte, aber nicht eingesetzte Effekte in anderen AddOns gibt, die auf der Klasse
`rex_effect_abstract_focuspoint` basieren. Diese Abhängigkeit muss durch das andere AddOn über dessen
Konfiguration (`package.yml`, Abschnitt `requires:`) beschrieben werden.

<a name="manage-uninstall"></a>
## De-Installieren

> **Durch De-Installation des Metafeldtyps `Focuspoint (AddOn)` wird allen darauf basierenden
Meta-Feldern die Basis entzogen!**

> **Durch De-Aktivieren des Addons wird Media-Manager-Typen, die die mitgelieferten Effekte bzw.
auf der Klasse `rex_effect_abstract_focuspoint` basierende Effekte nutzen, die Grundlage entzogen!**

Vor der De-Installaton werden die Abhängigkeiten überprüft und der Vorgang ggf. abgebrochen und zur
Beseitigung der Konflikte aufgefordert.

Die eigentliche De-Installation umfasst:

* Media-Manager-Typ `focuspoint_media_detail` löschen
* Metafeld `med_focuspoint` entfernen
* Metafeldtyp `Focuspoint (AddOn)` entfernen
* Verzeichnis `/assets/addons/focuspoint` löschen

Es wird **nicht** überprüft, **ob** es eigenentwickelte Effekte in anderen AddOns gibt, die auf der Klasse
`rex_effect_abstract_focuspoint` basieren. Diese Abhängigkeit muss durch das andere AddOn über dessen
Konfiguration (`package.yml`, Abschnitt `requires:`) beschrieben werden.


<a name="manage-delete"></a>
## Löschen

Zusätzlich zum [De-Installieren](#manage-uninstall) werden alle AddOn-Dateien gelöscht.
