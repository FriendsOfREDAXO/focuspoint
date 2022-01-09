> - Installation und Einstellungen
>   - Installation
>   - [Einstellungen](settings.md)
> - [Kartensätze verwalten](mapset.md)
> - [Karten/Layer verwalten](layer.md)
> - [Karten-Proxy und -Cache](proxy_cache.md)
> - [Für Entwickler](devphp.md)
>   - [PHP](devphp.md)
>   - [Javascript](devjs.md)
>   - [JS-Tools](devtools.md)
>   - [geoJSON](devgeojson.md)
>   - [Rechnen (PHP)](devmath.md)

# Installation und Systemkonfiguration

## Inhalt

Die Installation erfolgt wie gewohnt im Backend der READXO-Instanz. Neben der Basisinstallation
gibt es eine individualisierte Installation.

- [Installation](#install)
- [Individualisierte Installation](#custom)
    - [Systemparameter anpassen (*config.yml*)](#parameter)
    - [Karten und Kartensätze vorbefüllen](#dataset)
    - [Installation als Proxy/Cache](#proxy)
    - [Eigene Scripte und CSS-Formatierungen](#ownjscss)
    - [LeafletJS ohne **Geolocation**](#compile1)
    - [**Geolocation** ohne LeafletJS](#compile0)
- [Berechtigungen](#perm)

<a name="install"></a>
## Installation

**Geolocation** ist nicht über den REDAXO-Installer verfügbar. Das
[Repository](https://github.com/christophboecker/geolocation/) muss bei GitHub
[als ZIP-Archiv heruntergeladen](https://github.com/christophboecker/geolocation/archive/master.zip)
und im Addon-Verzeichnis der RREDAXO-Instanz in den Ordner *src/addons/geolocation* entpackt werden.

In der Addon-Verwaltung der REDAXO-Instanz kann das Addon nun installiert werden.

Die Installation (I) bzw. Re-Installation (R) umfasst folgende Schritte

- Tabellen *rex_geolocation_mapset* und *rex_geolocation_layer* anlegen (I) bzw. bei angelegten
  Tabellen die notwendigen Felder sicherstellen (R).
- Wenn beide Tabellen leer sind, wird ein Demo-Datensatz installiert (I|R).
- Die Tabellen werden über YForm verwaltet. Die dazu nötigen Tablesets werden installiert (I) bzw.
  angepasst (R).
- Sofern es keinen Cron-Job "Geolocation: Cleanup Cache" gibt, wird er angelegt. Ein vorhandener Job
  wird nicht verändert, ist also update-sicher (I|R).
- Ein Teil der im Betrieb notwendigen Parameter werden in die Konfigurationstabelle *rex_config* im
  Namespace "geolocation" eingetragen bzw. fehlende werden ergänzt (I|R).
- Ein Teil der im Betrieb notwendigen Parameter werden als Konstanten in die *boot.php* eingetragen.
- JS- und CSS-Dateien aus dem Asset-Verzeichnis des Addons werden in das öffentliche
  Assets-Verzeichnis des addons kopiert
- Die **Geolocation**-spezifischen JS- und CSS-Dateien werden neu in das öffentliche
  Assets-Verzeichnis kompiliert (I|R).

<a name="custom"></a>
## Individualisierte Installation

Die Installation basiert auf den Dateien im Verzeichnis *src/addons/geolocation/install*. Über
ein weiteres Verzeichnis *data/addons/geolocation/* kann die Installation und bis zu einem gewissen
Grad auch die Re-Installaion individualisiert werden.

| Datei | Verwendung | Anmerkung |
|-|-|-|
| config.yml | Die Grundeinstellungen des **Geolocation**-Addons wie Standard-Kartenausschnitt, Job-Parameter, Ausgabefragment | Überschreibt die angegebenen gleichnamigen Werte in der Originaldatei |
| dataset.sql | SQL-Statements zur Erstbefüllung der Tabellen | *rex_geoocation_mapset*<br>*rex_geolocation_layer* |
| geolocation.css | Zusätzliche CSS-Einstellungen, gedacht für zusätzliche Leaflet-Plugins bzw. Leaflet-Erweiterungen, eigene Erweiterungen und Anpassungen an die REDAXO-Instanz | [Eigene Scripte und CSS-Formatierungen](#ownjscss) |
|geolocation.js | Zusätzliche JS-Scripte, gedacht für zusätzliche Leaflet-Plugins bzw.Leaflet-Erweiterungen und individuelle JS-Geolocation-Erweiterungen  | [Eigene Scripte und CSS-Formatierungen](#ownjscss) |
| load_assets.php | Multifile-Erweiterungen als Alternative zu den beiden vorgenannten Einzeldateien | [Eigene Scripte und CSS-Formatierungen](#ownjscss) |
| lang_js | Equivalient zu den lang-Dateien mit Texten, die im JS benutzt werden |  |

Das angegebene Verzeichnis ist update-sicher, da es bei einer De-Installation nicht gelöscht wird.
Bereits vor der ersten Installation können hier die individuellen Einstellungen platziert werden.

<a name="parameter"></a>
### Systemparameter anpassen (`config.yml`)

Wie oben beschrieben wird die Grundkonfiguration über die Dateien
- `data/addons/geolocation/config.yml`
- `src/addons/geolocation/install/config.yml`

vorgenommen. Die erstgenannte Datei überschreibt die Werte der zweiten; darüber können update-sicher
eigene Grundeinstellungen konserviert werden. Teilweise sind die Parameter auch über die
[Einstellungen](settings.md) änderbar. Diese Änderungen bleiben bei einer Re-Installation
erhalten, werden aber bei einer De-Installation gelöscht.

Änderungen der Parameter sind mit Vorsicht durchzuführen! Die Daten werden weder auf formale
logische Richtigkeit noch auf Vollständigkeit geprüft.

`src/addons/geolocation/install/config.yml`:

```yml
# Basiskonfiguration, diverse Konstanten
#
# Wird bei der Installation benutzt und u.a. teilweise in die boot.php eingetragen als
# define('ABC',wert); (ABC im Namespace Geolocation )
#
# Überschreiben durch korrespondierende Werte aus redaxo/data/addons/geolocation/config.yml
# Handle with care!

# Leistungsumfang (alles: full, nur Proxy/Cache: proxy)
#   mapset          false = Formulare des Addon auf Proxy/Cache einschränken
#   compile         0 = kein Leaflet und geolocation-JS in geolocation.min.js/css einfügen
#                   1 = Leaflet-Core, sonst keine eigenen Elemente; danach aus data
#                   2 = Leaflet und geolocation-JS; danach aus data
#   load            false =geolocation.min.js, geolocation.man.css nicht laden
scope:
    mapset: true
    compile: 2
    load: true

# Karten und Kartensätze aus  "dataset.sql" vorbelegen
dataset:
    load: true
    overwrite : false

# Time-To-Live im Karten-Cache
Geolocation\TTL_DEF: 10080
Geolocation\TTL_MIN: 0
Geolocation\TTL_MAX: 130000

# Maximale Anzahl Dateien pro Karten-Cache
Geolocation\CFM_DEF: 1000
Geolocation\CFM_MIN: 50
Geolocation\CFM_MAX: 100000

# URL-Name für API-Abrufe
Geolocation\KEY_TILES: 'geolayer'
Geolocation\KEY_MAPSET: 'geomapset'

# Fragment zur Kartenausgabe
Geolocation\OUT: 'geolocation_rex_map.php'

# Darstellungsoptionen
# true = mapset::mapoptions, sonst '|xxx|yyy|' mit den Keys aus mapset::mapoptions
mapoptions: true

# Anzuzeigender Default-Kartenausschnitt = Europa (für Tool "bounds")
bounds: '[35,-9],[60,25]'

# Standard-Zoom
zoom: 15
zoom_min: 2
zoom_max: 18

# Aufrufintervall für den Aufräum-Job des Cache
# und weitere Parameter
job_moment: 0
job_environment: '|frontend|backend|'
job_intervall:
    minutes:
        0: '30'
    hours:
        0: '05'
    days: 'all'
    weekdays: 'all'
    months: 'all'
```

Anwendungsbeispiel: In der Konfiguration ist als Standard-Kartenausschnitt "Europa" festgelegt. Das
kann über die Einstellungen, aber auch als update-sichere Vorbelegung z.B. auf D-A-CH geändert
werden:

`data/addons/geolocation/config.yml`:

```yml
# Anzuzeigender Default-Kartenausschnitt (Tool "bounds"): D-A-CH
bounds: '[46,5.5],[55,17]'
```

Die mit `Geolocation\` beginnenden Einträge werden unter dem angegebenen Namen als Konstanten in die
`boot.php` geschrieben. `bounds` und `zoom` werden auch in die JS-Datei `geolocation.min.js`
übernommen.

Der Scope `mapset: false` **ist mit Vorsicht zu nutzen**. Darüber kann die Verwaltungsseite von
Geolocation verändert werden. Alle Bereich betreffend Kartensätze werden ausgeblendet. Das ist
sinnvoll wenn **Geolocation** nur als Proxy/Cache genutzt wird. Die Verifizierungs-Regeln für
Formulare und Tabellen sind damit nicht aufgehoben! Angenommen es gibt Kartensätze, bei denen
Karteneinträge zugewiesen sind. Der Scope `mapset: false` blendet den Menüpunkt "Kartensatz" aus.
Die verknüpften Karten können nicht mehr gelöscht werden, da sie im Kartensatz eingebunden sind.
Soll nur die vereinfachte Proxy-Ansicht genutzt werden, sollte eine
[Installation mit angepasstem Datensatz](#proxy) erfolgen.

Um im Bedarfsfall selbst die konsolidierte Liste aller Konfigurationsparameter einzulesen, müssen
beide Dateien eingelesen und verbunden werden.
```php
$config = array_merge(
    \rex_file::getConfig( \rex_path::addonData(\Geolocation\ADDON,'config.yml'), [] ),
    \rex_file::getConfig( \rex_path::addon(\Geolocation\ADDON,'install/config.yml'), [] ),
);
```

<a name="dataset"></a>
### Karten und Kartensätze vorbefüllen

Wie oben beschrieben wird die Anpassung über die Dateien
- `data/addons/geolocation/config.yml`
- `data/addons/geolocation/dataset.sql`

durchgeführt.

#### Tabellen überschreiben

In der Basiseinstellung wird die Datei `dataset.sql` importiert, wenn _beide_ Tabellen leer sind.
(`overwrite: false`). Das verhindert versehentliches Überschreiben bei einer Re-Installation. Wenn
per `load: false` ohnehin kein Ladeprozess gestartet wird, hat `overwrite` keine Auswirkung.

```yml
# Karten und Kartensätze aus  "dataset.sql" vorbelegen
dataset:
    load: true
    overwrite: true
```

Um auf jeden Fall `dataset.sql` auszuführen, müssen beide Werte auf `true` stehen.

#### Tabellen nicht vorbefüllen

Über den Parameter in der `config.yml` wird der Ladevorgang unterbunden. Vorhandene Datensätze
bleiben erhalten.

```yml
# Karten und Kartensätze aus  "dataset.sql" vorbelegen
dataset:
    load: false
```

#### Eigene Kartendaten und Kartensätze

Im Normalfall werden bei der Installation (beide Tabellen leer) Beispieldaten geladen. Werden eigene
Einstellungen gewünscht, kann eine entsprechende `dataset.sql` z.B. als Datenbank-Export hinterlegt
werden. Die Tabellennamen werden von der Installationsroutine an das eingestellte Prefix der Instanz
angepasst (tausche `rex_` gegen das eingestellte Prefix).

```sql
--
-- Daten für Tabelle `rex_geolocation_layer`
--
TRUNCATE TABLE `rex_geolocation_layer`;
INSERT INTO `rex_geolocation_layer` (`id`, `name`, `url`, `subdomain`, `attribution`, `lang`, `layertype`, `ttl`, `cfmax`, `online`) VALUES
(...),
...;

--
-- Daten für Tabelle `rex_geolocation_mapset`
--
TRUNCATE TABLE `rex_geolocation_mapset`;
INSERT INTO `rex_geolocation_mapset` (`id`, `name`, `title`, `layer`, `overlay`, `mapoptions`, `outfragment`) VALUES
(...),
...;
```

<a name="proxy"></a>
### Installation als Proxy/Cache

Wie oben beschrieben wird die Anpassung über die Dateien
- `data/addons/geolocation/config.yml`
- `data/addons/geolocation/dataset.sql`

durchgeführt. In der Konfiguration `config.yml` wird der Betriebsumfang auf `mapset: false`
geändert. Anschließend sollte eine Re-Installation erfolgen oder noch besser eine Neu-Installation,
damit auch die Datenbank neu aufgebaut wird.

`data/addons/geolocation/config.yml`:
```yml
# Leistungsumfang
scope:
    mapset: false
    compile: 0
    load: false
```

Im reinen Proxy-Mode sind die Asset-Dateien zum Kartenaufbau nicht erforderlich bzw. werden
außerhalb des Addons erwartet. Daher ist es weder notwendig, die Asset-Dateien aufzubauen
(`compile: 0`), noch die Asset-Dateien im Frontend bzw. Backend zu laden (`load: false`).

Für eine Variante mit dennoch geladenem Leaflet, ober ohne Geolocation-Tools, siehe [unten](#compile1).

Die Tabellen können bzw. sollten leer bleiben.

`data/addons/geolocation/dataset.sql`:
```sql
--
-- Daten für Tabelle `rex_geolocation_layer`
--
TRUNCATE TABLE `rex_geolocation_layer`;
INSERT INTO `rex_geolocation_layer` (`id`, `name`, `url`, `subdomain`, `attribution`, `lang`, `layertype`, `ttl`, `cfmax`, `online`) VALUES
(...),
...;

--
-- Daten für Tabelle `rex_geolocation_mapset` im reinen Proxy-Mode irrelevant; sollte leer sein.
--
TRUNCATE TABLE `rex_geolocation_mapset`;
```

<a name="ownjscss"></a>
### Eigene Scripte und CSS-Formatierungen einbinden

Spätestens wenn die Leaflet-Karte um zusätzliche Plugins und/oder passende individuelle Tools
erweitert werden, stellt sich die Frage, wie sie im System einzubinden sind. In der klassischen
Variante werden die JS- und CSS-Dateien separat und zeitlich nach den **Geolocation**-Dateien in FE
und BE eingebunden.

**Geolocation** bietet alternativ die Möglichkeit, die zusätzlichen Komponenten minifiziert bzw.
komprimiert direkt in die **Geolocation**-Assets einzubinden. Bei jeder (Re-)Installation
bzw. beim Speichern der [Einstellungen](settings.md) werden die Asset-Dateien

- `/assets/addons/geolocation/geolocation.css`  
- `/assets/addons/geolocation/geolocation.js`  
- `/assets/addons/geolocation/geolocation_be.css`  

neu erzeugt. Dabei werden im Verzeichnis `data/addons/geolocation/` liegende gleichnamige Dateien
nach den Standardkomponenten eingefügt. Solange es sich um relativ einfachen bzw. ohnehin
individuell geschriebenen Code in jeweils einer Datei handelt, ist das Verfahren gut handhabbar.

Mittels der Utility [AssetPacker](https://github.com/christophboecker/AssetPacker) (siehe
lib-Verzeichnis) werden die Komponenten zusammengeführt und komprimiert. Der Code findet sich
in der Datei `lib/config_form.php`.

```php
// Assets neu kompilieren
\Geolocation\config_form::compileAssets();
```

Komplexere Erweiterungen z.B. aus mehreren Leaflet-Plugins setzen voraus, dass die infrage kommenden
Dateien zunächst zu einer einzigen Datei (`geolocation.css`, `geolocation.js`) zusammengefasst
sind. Alternativ kann eine Script-Datei `load_assets.php` bereitgestellt werden. Die
AssetPacker-Instanzen der drei Zieldateien sind im Script verfügbar.

```
array:6 [▼
    "addonDir" => "«path_to_redaxo»/redaxo/src/addons/geolocation/"
    "dataDir" => "«path_to_redaxo»/redaxo/data/addons/geolocation/"
    "assetDir" => "«path_to_redaxo»/assets/addons/geolocation/"
    "css" => Geolocation\AssetPacker\AssetPacker_css {#270 ▶}
    "js" => Geolocation\AssetPacker\AssetPacker_js {#271 ▶}
    "be_css" => Geolocation\AssetPacker\AssetPacker_css {#272 ▶}
]
```

Systemseitig werden zuerst die Komponenten von LeafletJS und die im Addon benutzten Plugins
eingebaut, dann der Code von **Geolocation** (Karten aufbauen). Hier eine vereinfacht Darstellung
des Ablaufs in `compileAssets`:

```php
$css = AssetPacker\AssetPacker::target( $assetDir.'geolocation.min.css')
    ->overwrite()
    ->addFile( $addonDir.'install/vendor/leaflet/leaflet.css') )
    ->addFile( $addonDir.'install/vendor/Leaflet.GestureHandling/leaflet-gesture-handling.min.css') )
    ->addFile( $addonDir.'install/geolocation.css') );
$js = AssetPacker\AssetPacker::target( $assetDir.'geolocation.min.js')
    ->overwrite()
    ->addFile( $addonDir.'install/vendor/leaflet/leaflet.js') )
    ->addFile( $addonDir.'install/vendor/Leaflet.GestureHandling/leaflet-gesture-handling.min.js') )
    ->addFile( $addonDir.'install/geolocation.js') );

$be_css = AssetPacker\AssetPacker::target( $assetDir.'geolocation_be.min.css')
    ->overwrite()
    ->addFile( $addonDir.'install/geolocation_be.css' );

if( is_file($dataDir.'load_assets.php') ) {
    include $dataDir.'load_assets.php';
} else {
    $css->addOptionalFile( $dataDir.'geolocation.css');
    $js->addOptionalFile( $dataDir.'geolocation.js');
    $be_css->addOptionalFile( $dataDir.'geolocation_be.css');
}

$css->create();
$js->create();
$be_css->create();
```

Wie Dateien hinzugefügt werden, beschreibt als Beispiel der Code in `compileAssets()` und darüber
hinaus das [Handbuch zu AssetPacker](https://github.com/christophboecker/AssetPacker/blob/main/README.md).
Hier ein schematisches Beispiel für eine `load_config.php`:

```php
// Asset-Dateien für Leaflet erweitern
$dir = \rex_path::addonData(\Geolocation\ADDON);
$js
    ->addFile( $dir.'/vendor/plugin_x/plugin_x.min.js')
    ->regReplace( '%//#\s+sourceMappingURL=.*?$%im','//' )
    ->addFile( $dir.'/vendor/plugin_y/plugin_y.js')
    ->regReplace( '%//#\s+sourceMappingURL=.*?$%im','//' )
    ->addFile( $dir.'/mytools.js');
$css
    ->addFile( $dir.'/vendor/plugin_x/plugin_x.min.css')
    ->addFile( $dir.'/vendor/plugin_x/plugin_y.css')
    ->addFile( $dir.'/mytools.css');
```

Außer beim Installieren bzw. Re-Installieren werden die Asset-Dateien bei Änderungen der
[Einstellungen](settings.md) neu kompiliert. Daher ist für Änderungen der Asset-Dateien keine
(Re-)Installation erforderlich; speichern der Einstellungen reicht aus.

<a name="compile1"></a>
### LeafletJS ohne **Geolocation**  

Auch in der Betriebsart "Proxy/Cache" benötigt man eine Kartensoftware á la LeafletJS und ein wenig
Javascript, um darauf aufbauend die Karte anzuzeigen. Über Installationsparameter Erweiterungen
können die Asset-Dateien entsprechend konfiguriert und geladen werden. Die Anpassung erfolgt wie
oben beschrieben über die Dateien
- `data/addons/geolocation/config.yml`
- `data/addons/geolocation/geolocation.js`
- `data/addons/geolocation/geolocation.css`
- `data/addons/geolocation/load_assets.php`

Die Schritte:

1. Über `config.yml` wird der automatisch generierte Umfang der JS/CSS-Dateien auf LeafletJS
   begrenzt. Eigener Code von **Geolocation** und dafür notwendige Leaflet-Erweiterungen werden
   nicht geladen.
   ```yml
   scope:
       mapset: false
       compile: 1
       load: true
   ```
2. Mit den übrigen drei Dateien wird wie [zuvor](#ownjscss) beschrieben der Kern um eigene Elemente
   erweitert (weitere Leaflet-Plugins, eigener Code zur Karten-Generierung).
3. Der Vollständigkeit halber sei darauf hingewiesen, dass wesentliche PHP-Teile von **Geolocation**
   für die Kartenausgabe auch durchaus in diesem Setup funktionieren können. Z.B. kann in dem Fall
   ein alternatives Ausgabefragment eingesetzt werden, wenn die Karten auf dem HERE-JS aufsetzen
   (oder Google oder Apple oder ...)
   ```yml
   Geolocation\OUT: 'my_nice_own_map_fragment.php'
   ```

<a name="compile0"></a>
### **Geolocation** ohne LeafletJS

**Geolocation** kann gänzlich ohne LeafletJS genutzt werden. [Zuvor](#compile1) wurde bereits die
grundlegende Vorgehensweise beschrieben, über das data-Verzeichnis eigenes JS zu laden.

Zusätzlich zu eigenem Anwendungscode muss in diesem Setup auch die Kartensoftware selbst geladen
werden, während LeafletJS außen vor bleibt.

Beim Neu-Compilieren der Asset-Dateien muss in der `config.yml` eingetragen sein:

```yml
scope:
    mapset: true # oder false
    compile: 0
    load: true
```

<a name="perm"></a>
## Berechtigungen

Die Berechtigungsverwaltung erfolgt über die Benutzer- und Rollenverwaltung in REDAXO.

| Rolle | Beschreibung |
|-|-|
|admin| Darf alles, sieht alles|
|geolocation[mapset]|Kartensätze zusammenstellen und verwalten|
|geolocation[layer]|Kartendaten / Layer-Daten bearbeiten. Da hier die grundlegende Funktion schnellbeeinträchtigt werden kann (z.B. falsche URLs), sollte diese Berechtigung ohnehin Entwicklern und Admins vorbehalten sein.|
|geolocation[clearcache]|Cache löschen; Das Recht bezieht sich auf per `rex_api` ausgelöstes Löschen (z.B. Lösch-Button der Addon-Seiten im Backend). Cronjobs sind nicht betroffen|

Passend dazu werden auch die Handbuchseiten eingeschränkt. Diese Installationsseite ist z.B. nur für
Admins sichtbar.

## Darkmode

Das Addon berücksichtigt im Grunde die Darkmode-Einstellung der Redaxo-Instanz. Die Darstellung im
Backend erfolgt mit dem Standard-CSS. Eine Einschränkung gilt für die Leaflet-Karten. LeafletJS
unterstützt generisch keinen Darkmode.

Es gibt Plugins für LeafletJS, die auch eine Darkmode-Darstellung ermöglichen sollen. Vor einer
Integration in **Geolocation** wären mit Sicherheit ausführliche Kompatibilitätstests und ggf.
weitere Anpassungen erforderlich. Ob sich der Aufwand lohnt, sei dahingestellt, denn auch die Karten
müssen Darkmode-Karten sein. Liefert der Anbieter solche Tiles?  
