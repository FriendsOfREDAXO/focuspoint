# Changelog

## **27.05.2024 Version 4.2.3**

- Bugfix: Installations-Abbruch wegen nicht initialisiertem $mm_type_id gefixed (#138; Danke @tbaddade)

## **20.02.2024 Version 4.2.2**

- Bugfix: Abhängigkeiten in `package.yml` an die seit Version 4.1.0 geltenden Voraussetzungen angepasst (#136; Danke @godsdog)

## **16.02.2024 Version 4.2.1**

- Bugfix: falscher Dateiname ([#133](https://github.com/FriendsOfREDAXO/focuspoint/issues/133) @tbaddade)

## **03.02.2024 Version 4.2.0**

- Umstellung der Klassennamen im Namespace auf CamelCase unter Wegfall von _.
  Beispiel `focuspoint_media` -> `FocuspointMedia`,
- Anpassen der Dateinamen an die Klassennamen,

Ausnahme: auf `rex_effect` und `rex_api` aufsetzende Klassen

Bugfix: 
- strtolower mittels array_walk durch geänderte SQL-Abfrage ersetzt (#129)
- in `rex_effect_abstract_focuspoint` Namespace korrekt berücksichtigt `is_a($media, FocuspointMedia::class)` (#130)

## **01.02.2024 Version 4.1.0**

- Umstellung auf den Namespace **FriendsOfRedaxo\Focuspoint**. Aus Klasse `xyz` wird `FriendsOfRedaxo\Focuspoint\xyz`. 
- Mit RexStan bereinigt (Level 5, PHP 8.2, Extensions: REDAXO SuperGlobals | Bleeding-Edge | Strict-Mode | Deprecation Warnings | PHPUnit | phpstan-dba | report mixed | dead code).
- Erstmalig PHP-CS-Fixer formatiert. 
- Ab jetzt ist PHP 8.1 Mindestvoraussetzung und REDAXO 5.15. 

Diese Version ist die Vorbereitung auf REDAXO 6. Es gibt keine Änderungen am Funktionsumfang.

Die Umstellung im Namespace hat jetzt nur Auswirkungen für Entwickler, die zusätzlich eigene Focuspoint-Effekte scheiben oder
anderweitig auf die Focuspoint-Tools/Klassen zugreifen. Für eine Übergangszeit ist der alte Aufruf mit `xyz`
weiterhin möglich. In der Entwicklungsumgebung sind die Aufrufe als **Deprecated** gekennzeichnet und sollten
rasch auf die neue Variante umgestellt werden. Mit Version 5.0.0 wird die alte Aufrufvariante endgültig entfernt.

Zur Umstellung kann man entweder
- Den Klassennamen um den Namespace erweitern: `FriendsOfRedaxo\Focuspoint\xyz::func(...)`.
- Weiter `xyz` nutzen und einmalig am Anfang der Datei ein Use-Statement einfügen: `use FriendsOfRedaxo\Focuspoint\xyz;`

## **18.03.2023 Version 4.0.4**

- Bugfix: mitigates deprecated warning (PHP 8.1) or exception(PHP 8.2) when using a target sizes like "80%" in the effect "focuspoint_fit"

## **03.01.2023 Version 4.0.3**

- Bugfix: mitigates deprecated warning (PHP 8.1) or exception(PHP 8.2) when using a target sizes like "16fr/9fr" in the effect "focuspoint_fit"

## **18.06.2022 Version 4.0.2**

- Another correction regarding preview issue
- Code refinement with [rexstan](https://staabm.github.io/2022/06/18/rexstan-REDAXO-AddOn.html) up to level 6
  based on PHP8 specification. Some notifications are suppressed by `@phpstan-ignore-next-line`, as the root
  cause is outside focuspoint; three are not covered
- `focuspoint_media::_construct` removed: only relevant for PHP 5.6, which is obsolete. The required REDAXO-Versions needs PHP 7.3+.

## **05.06.2022 Version 4.0.1**

- Corrected an error witch prevented a proper media-type related preview un the focuspoint-selection
  of the media manager window. Thanks to LEAakaLAP und Markus Neubauer.
- Developer-Section of the documentation extended with another use case for ExtensionPoint
  `FOCUSPOINT_PREVIEW_SELECT` regarding media-types for images outside the media pool.
   
## **17.11.2021 Version 4.0.0**

- Changes in the mediapool markup forced an update in the hook mechanismen, which enables Focuspoint´s interactive selection in the mediapool-sidebar. Due the
changed mechanismen Focuspoint 4.0 and onward is incompatible with REDAXO 5.12.x and prior versions.

## **23.10.2021 Version 3.1.0**

- Enhanced CSS for darkmode compatibility (thanks to @schuer); active with REDAXO 5.13.

## **01.03.2021 Version 3.0.2**

- Bugfix: PHP8 fixed an error in MySQL transaction handling regarding DDL-statements, with now can
  results  properly in an exception during installation. The new installation routine works without
  transactions.
- A successfull installation now provides detailed information (available since REDAXO 5.12).

## **09.12.2020 Version 3.0.1**

- PHP version-dependency in package.yml changed to enable installation with PHP 8 as well as PHP 7

## **28.11.2020 Version 3.0.0**

- Documentation reworked for proper dual-use on Github and in the REDAXO backend.
  HELP.PHP is rewritten accordingly. Supporting css/js added (help.min.xxx).
- Media-manager-effect "focuspoint_resize", deprecated since 30.08.2018 (2.0.0), is finally removed.
  Save the file `lib/focuspoint_resize.php` to a save location bevor updating to 3.0.0 if you need the effect
- Editing the addon-internal media-manager-type `focuspoint_media_detail` is partly enabled.
  Deletion and renaming is still prohibited.

## **09.03.2020 Version 2.2.2**

Bugfix: the method focuspoint::customfield did return only a partial set of values.
This could lead to problems on subsequent calls to EP "METAINFO_CUSTOM_FIELD". Now the full set
is returned.

## **06.03.2020 Version 2.2.1**

The URL used to identify and replace the standard-file-view in a Media-Edit-Window was not
correctly identified in REDAXO 5.10 due to changes in rex_media_manager::getUrl. The corrected code
is backward compatible (tested with REX 5.9).

## **10.02.2020 Version 2.2.0**

Enhancements:

- Extension-Point "FOCUSPOINT_PREVIEW_SELECT": the preview-dropdown in the media-editor uses the
    meta-types´s name-field as item-label. While this name tends be a more technically one, but
    not self-explaining for editors, the EP offers a way to change the label. For details take a
    look into the documentation-section "Für Entwickler (API)".
    You can find the documentation via the addon-administration-page
    (requested by @DanielWeitenauer)

Bugfixes:

- Requirements-section in package.yml with enhanced explicit mention of dependencies to
    media_manager, mediapool and PHP (thanks to @skerbis, @staabm)
- Focuspoint restricts the AddOn "Metainfo" in changing meta-fields of fieldtype "Focuspoint (AddOn)"
    (no delete, no rename, no change of fieldtype on "med_focuspoint" or fields in use)
    Saving other allowed changes was not possible in some situations. The blocking mechanismen
    is rewritten. (reported by @pschuchmann)
- typo-corrections in language- and documentation-files


## **27.09.2019 Version 2.1.2**

- Maintenance-version, no functional changes
    Zoom-factors corrected (wrong 755% replaced by correct 75%). Thanks to @rkemmere

## **22.08.2019 Version 2.1.1**

- Maintenance-version, no functional changes
- Injection of focuspoint-help into Media-Manager is changed in REX 5.8.0 with respect to the new
  way, the Media-Manager-help (overview) is provided
- Requirements-section in package.yml changed to reflect REX 5.8.0
- Traducción en castellano - thanks to @nandes2062

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
> muss die betroffenen Scripte anpassen. Informationen dazu sind in der Dokumentation zu finden (siehe unten).

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
