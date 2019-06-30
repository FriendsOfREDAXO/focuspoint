# Changelog

## **30.06.2019 Version 2.1**

- Kleinere Schreibfehler korrigiert (danke @claudihey)
- In den Media-Manager-Effekten unterstützt die Koordinatenermittlung
    auch den Fall, dass das Bild nicht aus dem Medienpool kommt, sondern per 'effect_mediapath' aus
    einem anderen Verzeichnis. Als Koordinaten werden url (xy=..), Effektkonfiguration (Fallback) und
    der allgemeine Fallback "Bildmitte" herangezogen.
- die Klasse `focuspoint_media` hat eine zusätzliche Methode `hasFocus` bekommen,
    mit der abgeprüft wird, ob das Fokuspunkt-Metafeld gesetzt ist (also eine gültige Koordinate enthält).
- Im AddOn "Metainfo" wurde die Bearbeitung von Feldern, die den Metainfo-Datentyp "Focuspoint (AddOn)"
    haben, beschränkt. Das Default-Feld "med_focuspoint" kann nicht gelöscht werden; Feldname und
    Datentyp können nicht geändert werden.
    Gleiches gilt für selbst angelegte Metainfo-Felder des Typs "Focuspoint (AddOn)", sobald sie in einem
    Media-Manager-Effekt eingesetzt werden, der auf der Klasse `rex_effect_abstract_focuspoint` basiert.
- Im AddOn "Media-Manager" ist Bearbeiten und Löschen des Typs "focuspoint_media_detail" gesperrt.
    (Hinweis von @tbaddade)
- Die `boot.php` wurde entschlackt, um die Initialisierung der REDAXO-Instanz zu entlasten; die
    entprechenden Codeböcke sind nach `focuspoint_boot.php` ausgelagert und werden nur bei Bedarf
    geladen.
- Der Effekt 'focuspoint_resize' für den Media-Manager ist seit Release 2.0 auf "deprecated" gesetzt.
    Wie angekündigt ist der Effekt ab Version 2.1 noch im Addon enthalten, er wird aber nicht mehr
    in der `boot.php` aktiviert. (Siehe Dokumentation). Wer den Effekt noch benötigt, muss in
    an anderer Stelle selbst aktivieren (`rex_media_manager::addEffect('rex_effect_focuspoint_resize');`).

## **08.09.2018 Version 2.0.2**

- Bugfix: Kompatibilität von "focuspoint_media" zu PHP 5.6 hergestellt (Danke @schuer)

## **03.09.2018 Version 2.0.1**

- Bugfix: Update-Fehler behoben (Zoom-Faktor des Fit-Effekt wurde nicht übernommen).

## **30.08.2018 Version 2.0.0**

**ein komplett neu entwickeltes Major-Release**

## Wichtiger Hinweis

> Sowohl die Datenfelder als auch Parameter der Effekte sind geändert. Im Rahmen des Update
> werden frühere Versionen vor 2.0 automatisch umgestellt und die nicht mehr benötigten Felder gelöscht.
> Wer die Fokuspunkt-Parameter direkt auswertet statt die Zielbilder via Media-Manager zu erzeugen,
> muss die betroffenen Scripte anpassen. Informatioen dazu sind in der Dokumentation zu finden (siehe unten).

## Features

- Meta-Datenfelder für Medien

    - Eigener Meta-Datentyp "Focuspoint (AddOn)" für Fokuspunkt-Metafelder
    - Individuelle Fokuspunkt-Metafelder können angelegt werden
    - **Die synchronen Felder 'med_focuspoint_data' und 'med_focuspoint_css' sind durch 'med_focuspoint' ersetzt**
    - Das Koordinaten-Format ist nun ein Prozentsatz der Breite bzw. Höhe: 0.0 bis 100.0, Koordinatenursprung oben links.
    - automatische Umstellung beim erstmaligen Update auf eine Version ab 2.0.0
    - Metafelder, die in Effekten genutzt werden, können nicht gelöscht werden.

- Fokuspunkt-Erfassung

    - Eingabefelder

        - neu gestaltet als Custom-Field.
        - Interaktion zwischen Eingabefeld und der interaktiven Fokuspunkt-Auswahl
        - Felder können ausgeblendet werden, so dass nur die interaktive Auswahl angeboten wird
        - Der Button vor dem Eingabefeld dient als Umschalter zwischen mehreren Feldern und zeigt an, welches grade interaktiv angezeigt ist

    - neu gestaltete interaktive Fokuspunkt-Zuweisung

        - Reset auf den Ausgangswert, Reset auf "Bildmitte"
        - Zoom für bessere Detailauswahl. Basisbild mit 1000px Breite statt 600px entsprehend der maximalen Koordinatenauflösung (100.0)
        - Preview mit echten temporären Bildern; daher "Abbruch" der Eingabe möglich.
        - Unterstützung mehrerer Fokuspunkt-Felder
        - Cursorposition laufend angezeigt bei der Auswahl
        - über EP MEDIA_DETAIL_SIDEBAR eingehängt

- Media-Manager_Effekte

    - Die mitgelieferten Effekte an die neue Datenstruktur angepasst
    - Effekte basieren auf der Klasse `rex_effect_abstract_focuspoint`
    - Default-Felder für alle Effekte:
        - `meta`: anzuwendendes Fokuspunkt-Feld
        - `focus`: Fallback-Koordinate
    - **automatische Umstellung der Media-Manager-Typen bzw. deren Effekte auf die neuen Parameter beim erstmaligen Update auf eine Version ab 2.0.0**

- JS und CSS

    - wird nur auf Media-Detailseiten eingebunden

- De-Installieren/Löschen/De-Aktivieren

    - prüft auf Rückbezüge und verhindert ggf. die Aktion
        (z.B. wenn auf der Klasse `rex_effect_abstract_focuspoint` beruhende Effekte im Media-Manager benutzt werden )

- Entwicklersupport

    - Basisklasse `rex_effect_abstract_focuspoint` als Ausgangspunkt eigene Effekte
    - Media-Klasse `focuspoint_media` für Medien mit Fokuspunkt
    - Abruf von Medien mit "on the fly"-Koordinaten (via rex-api-call)

- Dokumentation

    - komplett neu und umfangreich
    - Aufruf via AddOn-Verwaltung (Hilfe-Button)
    - Anleitung für den Umstieg
    - Auszüge zum Fit-Effekt in den Media-Manager eingehängt


## **24.03.2018 Version 1.4.2**

- Vorschau nur wenn Mediatyp einen Focuspoint-Effekt besitzt (thx @eaCe)

## **07.03.2018 Version 1.4.1**

- Update class.rex_effect_focuspoint_fit.php #39

## **06.03.2018 Version 1.4.0**

- Nicht-Statische Methode nicht statisch aufrufen… Danke @tbaddade
- initialize class outside of loop...
- Eintrag "mediapool_focuspoint_preview" in den .lang-Dateien ergänzt

## **23.01.2018 Version 1.3.30**

- Bildformat als Aspect-Ratio und input-check mit Pattern (#34)

## **17.12.2017 Version 1.3.20**

- Vorschau für Mediatypen hinzugefügt (#30)

## **14.12.2017 Version 1.3.12**

- Inputfelder bei Upload/Sync versteckt (#25)

## **24.11.2017 Version 1.3.11**

- Media Manager Effekte umbenannt (#29)

## **24.11.2017 Version 1.3.10**

- rex_sql_exception bei abweichendem tabellen-prefix behoben (#28)
- README erweitert

## **17.11.2017 Version 1.3.9**

- Menülink unter AddOns entfernt (#27)

## **08.08.2017 Version 1.3.8**

- Minimale CSS Anpassung

## **17.07.2017 Version 1.3.7**

- Focuspoint wird nun auch initialisiert, wenn die Bilddetailansicht von einem Media Widget aus geöffnet wird (dann wird der Dateiname statt der Datei Id übergeben) (IngoWinter)


## **17.07.2017 Version 1.3.6**

- reset Focuspoint Link (IngoWinter)


## **07.03.2017 Version 1.3.5**

- Neuer Effekt: focuspoint_fit (christophboecker)
- Unterstützung von jpeg Dateien (tbaddade / skerbis)
- Neue Sprachdateien (ytraduko-bot)
- "Target"-Image ausgetauscht


## **07.03.2017 Version 1.3.3**

- Feldzuordnung korrigiert. Danke an [Norbert](https://github.com/tyrant88)

---

## **25.02.2017 Version 1.3.2**

- Update en_gb.lang - Danke an @ynamite!

---

## **14.10.2016 Version 1.3.1**

- CSS Augabewert angepasst (Komma)

___

## **11.10.2016 Version 1.3**

- Umzug zu FoR

___

## **17.03.2016 Version 1.2**

- Bild im Medienpool wird jetzt nicht mehr in der Originalgröße benutzt
  (Danke an Roman Huy-Prech für die Anpassung)

___

## **02.03.2016 Version 1.0**

- Media Manager Effekt für REX5 angepasst

___


## **29.02.2016 Version 0.5**

- Anpassungen an Redaxo 5

---

##### ToDo siehe [ISSUES](https://github.com/FriendsOfREDAXO/focuspoint/issues) #####
