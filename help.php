<?php
/*

Zeigt komplexe, auf Markdown basierte Dokumentationen im REDAXO-Backend an.

Zulässige Dateien:
    «addon_root»/README.md
    «addon_root»/LICENSE.md
    «addon_root»/CHANGELOG.md
    «addon_root»/docs/*

Die Dateien müssen im Wesentlichen so aufbereitet sein, dass sie auf Github korrekt angezeigt
werden. Für das REDAXO-Backend werden die Links durch help.php so verändert, dass sie einen
korrekten Aufruf der Backend-Seite ergeben.

Es gilt:

*   Github first; die Texte sollten zuerst auf Github funktionieren
*   Links müssen relativ sein (readme.md: -> docs/xyz.md, docs/xyz.md: ->../readme.md)
*   Es werden nur Markdown-Links "[label](link)" bzw. "![label](link)" umgebaut,
    keine HTML-Tags (A, IMG)
*   Links "?..." funktionieren in Redaxo, aber nicht auf Github.
*   Das mit help.php mögliche Navigations-Menü wird auf Github simuliert durch eine Linkliste am
    Anfang der Dateien. Die Linkliste ab Zeile 1 und die nachfolgende Leerzeile wird in der
    REDAXO-Anzeige entfernt.
        > - [nav1](link1)
        > - [nav2](link2)
        ...
*   Innerhalb von Code-Blöcken (``` bzw. `) findet keine Ersetzung statt.

Die Konfiguration für REDAXO erfolgt über die package.yml des Addons.

    In der Sektion "help" können Dokumenten-Profile für Seiten (page=..) angelegt werden.
    Sofern die Seite "help.php" für die Seitenaufbau nutzt, wird die zugehörige Parametrisierung
    der page herangezogen. Hat die page kein eigenes Profil, wird nach dem Profil "default" gesucht.

    help:
        default:
            profildaten
        pagename:
            profildaten
        ....

Die Profildaten je Page sind
    initial: docs/overview              Die inital anzuzeigende Seite wenn es keinen URL-Parameter
                                        gibt.
    0:                                  Erster Navigationseintrag, 1: zweiter Eintrag etc.
        title: ....                     Titel des Eintrags translate:xxx oder Text
        path: docs/page.md              Das zugehörige Dokument. Pfad relativ zum «addon_root»
        href: ?page=.. oder http://..   (opt.) Link statt «path». Wenn beide angegeben sind hat «path» Vorrang
        active: true                    (opt.) Falls «initial» fehlt wird das die Initialseite
        icon: fa fa-book                (opt.) Icon-Klassen

Beispiel aus der package.yml von Focuspoint:

help:
    default:
        0:
            title: translate:focuspoint_docs_overview
            icon: fa fa-book
            path: docs/overview.md
        1:
            title: translate:focuspoint_docs_edit
            icon: fa fa-book
            path: docs/edit.md
        2:
            title: translate:focuspoint_docs_mm
            icon: fa fa-book
            path: docs/media_manager.md
        3:
            title: translate:focuspoint_docs_install
            icon: fa fa-book
            path: docs/install.md
        4:
            title: translate:focuspoint_docs_api
            icon: fa fa-book
            path: docs/developer.md
    media_manager/overview/focuspoint:
        0:
            title: translate:focuspoint_effect_fit
            icon: fa fa-book
            path: docs/media_manager.md
        1:
            title: translate:focuspoint_doc
            icon: fa fa-book
            href: ?page=packages&subpage=help&package=focuspoint

In der package.yml kann eine Hilfesete über die normale Seitendefinitoon eingebaut werden. Wichtig
ist die richtige Seitenangabe mit "subPath: help.php":

    page:
        ....
        subpages:
            ....
            docs:
                title: translate:geolocation_manpage
                icon: fa fa-book
                pjax: false
                subPath: help.php

Extension-Points:

    HELP_NAVIGATION
        Bevor die Navigation in HTML gegossen wird, kann das Navigationsmenü noch via Extension-Point
        bearbeitet werden.

        $subject ist das Array mit den Navigationsdaten.

    HELP_HREF
        Jeder im Text gefundene Link kann vor der Ersetzung im Text noch bearbeitet werden:

        $subject ist der neu aufgebaute Markdown-Link [label](link), die einzelnen
        Original-Bestandteile sind in $params

*/

/**
 *  @var rex_addon $this
*/
if( !class_exists('help_documentation') )
{
    class help_documentation {

        public $navigation = [];
        public $initialPage = '';
        public $activePage = '';
        public $dir = '';
        public $dirLen = 0;
        public $filename = '';
        public $targetfile = '';
        public $context = null;

        //  $filename       ist der Name der anzuzeigenden Datei im $dir. Sofern es keine anderen Angaben
        //                  gibt, wird die README.md des Addons angezeigt.
        //                  Der Name kann Pfadanteile aufweisen (docs/xyz.md, ../xyz.md)
        //  $navigation     Array mit Angaben zum Aufbau einer Sub-Navigation innerhalb der Seite.
        //
        //  README.md ist die Fallback-Datei, die angezeigt wird wenn keine andere Datei benannt ist.
        //
        //  Alternativ wird der Dateiname aus der Package.yml entnommen. Zum zugehörigen Regelwerk siehe oben.
        //  Hier sind dann auch die Angaben zur $navigation zu finden.
        //
        //  Oberste Priorität ist die Angabe in der URL (...&doc=...)

        function __construct( \rex_addon $addon )
        {
            $this->context = $addon;
            //  Im weiteren Verlauf wird immer wieder das aktuelle Verzeichnis (Root des Addons) benötigt
            $this->dir = $addon->getPath();
            $this->dirLen = strlen( $this->dir );

            $navigation = $addon->getProperty('help',[]);
            $this->navigation = $navigation[rex_be_controller::getCurrentPage()] ?? $navigation['default'] ?? [];
            if( isset($this->navigation['initial']) && $this->navigation['initial'] ){
                $this->initialPage = $this->navigation['initial'];
                unset( $this->navigation['initial'] );
            }

            foreach( $this->navigation as $k=>$v ) {
                if( isset($v['path']) ){
                    if( !$this->activePage ) $this->activePage = $v['path'];
                    if( isset($v['active']) && true===$v['active'] ){
                        $this->activePage = $v['path'];
                        break;
                    }
                }
            }
            $this->filename = rex_request( 'doc','string',$this->initialPage ?: $this->activePage ?: 'README.md' );
            $this->filename =  mb_ereg_replace('\\\\|/', DIRECTORY_SEPARATOR, $this->filename, 'msr');

            $this->targetfile = $this->getDocumentName( );
        }

        //  Der $file_name kann auch Pfadanteile enthalten. Auch "../"-Elemente sind zulässig.
        //  Mit bösartigen URLS kann der REDAXO-Instanz eine Abfrage untegeschoben werden, die darüber
        //  Zugang zu Dateien erlangen, die nicht extern sichtbar sein sollen bzw. dürfen.
        //
        //  Daher werden die Pfade normiert und geprüft (realpath(..))
        //  Zulässig sind am Ende nur ausgewählte Pfade, die innerhalb des Addon liegen.
        //      «addon_root»/README.md
        //      «addon_root»/LICENSE.md
        //      «addon_root»/CHANGELOG.md
        //      «addon_root»/docs/*
        //
        //  Als Nebeneffekt wird geprüft, ob die DAtei überhaupt exisitert bzw. ob sie in einer
        //  Sprachversion existiert (pfad/dateiname.lang.suffix), die statt des Originalnamens genutzt wird.

        function getDocumentName( )
        {
            $pathinfo = pathinfo( $this->dir . $this->filename );

            //  Pfad normieren, Suche nach sprachspezifischer Datei
            $real_path = realpath( $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename']  . '.'.rex_i18n::getLanguage().'.' . $pathinfo['extension'] );
            if( !$real_path)
            {
                //  Pfad normieren, Suche nach normaler Datei
                $real_path = realpath( $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '.' . $pathinfo['extension'] );
            }

            if( $real_path )
            {
                $real_dir = substr($real_path,0,$this->dirLen);
                if( 0 == strcasecmp($this->dir,$real_dir) )
                {
                    $filename = substr($real_path,$this->dirLen);
                    if( 0 == strcasecmp(substr($filename,0,5),'docs'.DIRECTORY_SEPARATOR)
                     || 0 == strcasecmp($filename,'readme.md')
                     || 0 == strcasecmp($filename,'changelog.md')
                     || 0 == strcasecmp($filename,'license.md') )
                    {
                        return $filename;
                    }
                }
            }
            return false;
        }

        function getFilePath()
        {
            return $this->targetfile ? $this->dir . $this->targetfile : false;
        }

        function isAsset( )
        {
            return '.md' !== substr($this->filename,-3);
        }

        //  Nicht-Markdown-Dateien (meist Bilder), werden direkt ausgegeben.
        //  Danach abbrechen.

        function sendAsset( ){
            rex_response::cleanOutputBuffers();
            if ( $path = $this->getFilePath() ) {
                rex_response::sendFile( $path, rex_file::mimeType( $path ) );
            } else {
                header( 'HTTP/1.1 Not Found' );
            }
            exit();
        }

        //  Entferne ein für die Github-Ansicht eingebautes Menü, das hier der $navigation entspricht
        //  Das sind alle Zeilen ab Zeile 1, die mit "> - " beginnen sowie die abschließende Leerzeile

        function stripGithubNavigation( $text )
        {
            if( preg_match( '/^(\>\s+\-\s?.*?\\n)*\s*\\n/', $text, $matches ) ){
                $text = substr( $text, strlen($matches[0]));
            }
            return $text;
        }

        //  Im Text werden alle Links, die nicht Datei-intern (#...) und nicht URIs (z.B. http://...)
        //  sind, werden so umgebaut, dass sie durch diese Seite geschleust werden.
        //  Der Link ist die URL der aktuellen Seite mit dem zusätzlichen Parameter '&doc=originallink'
        //  per EP kann der Link noch einmal umgearbeitet werden.
        //  Datei die Code-Blöcke auslassen

        function replaceLinks( $text )
        {
            $request = $_REQUEST;
            unset( $request['doc'] );
            $baseurl = rex_url::currentBackendPage( $request,false ) . '&doc=' . dirname($this->targetfile) . DIRECTORY_SEPARATOR;

            # Code-Blöcke identifizieren und herauslösen, damit keine darin enthaltenen Links geändert werden.

            $marker = md5( time() );
            $count = 0;
            $original = [];
            $text = preg_replace_callback( '/(```.*?```|`.*?`)/s', function( $matches) use( $marker, &$count, &$original){
                    $count++;
                    $marker = "##$marker-$count##";
                    $original["/$marker/"] = $matches[0];
                    return $marker;
                }, $text );

            # Links umbauen; nur Markdown! [label](link) bzw. ![label](link)

            $text = preg_replace_callback (
                '/((!?)\[(.*?)\]\()\s*(.*?)\s*(\))/',
                function( $matches ) use( $baseurl )
                {
                    $link = $matches[4];
                    //  leere Links ignorieren
                    //  Dokument-interne Referenzen (#) ignorieren
                    //  REDAXO-Interne Aufrufe (?...) ignorieren
                    //  Dokumente mit kompletter URL ignorieren (irgendwas://sonstnochwas)
                    if( !$link
                     || '#' == substr($link,0,1)
                     || '?' == substr($link,0,1)
                     || preg_match( '/^.*?\:\/\/.*?$/',$link) )
                    {
                        $term = $matches[0];
                    }
                    //  alle anderen Varianten umbauen
                    else
                    {
                        $term = $matches[1] . $baseurl . $link . $matches[5];
                    }
                    return rex_extension::registerPoint(new rex_extension_point(
                        'HELP_HREF',
                        $term,
                        ['label'=>$matches[3],'href'=>$matches[4],'isImageLink'=>($matches[2]>''),'context'=>$this->context]
                    ));
                },
                $text );

            # Code-Blöcke wieder einfügen
            if( $original ) {
                $text = preg_replace( array_keys($original), $original, $text );
            }

            return $text;
        }

        //  Falls angefordert wird ein Tab-Menü mit den Hauptseiten eines Hilfe-Systems gebaut.
        //  Es wird nicht beim einzelnen Aufruf geprüft, ob es sinnvolle Referenzen im $navigation-Array
        //  gibt. Das muss der Entwickler sicherstellen.
        //  Der Link ist die URL der aktuellen Seite mit dem zusätzlichen Paramater 'doc=seitenlink'

        function getNavigation( ){
            $tabs = [];
            $request = $_REQUEST;
            foreach( $this->navigation as $nav )
            {
                $href = $nav['href'] ?? '' ?: '';
                if( isset($nav['path']) && $nav['path'] ){
                    $request['doc'] = $nav['path'] ?? '' ?: '';
                    $href = rex_url::currentBackendPage( $request,false );
                }
                if( $href ) {
                    $tabs[] = [
                        'linkClasses' => [],
                        'itemClasses' => [],
                        'linkAttr' => [],
                        'itemAttr' => [],
                        'href' => $href,
                        'title' => $nav['title'] ?? '' ?: '',
                        'active' => $this->filename == ($nav['path'] ?? '' ?: ''),
                        'icon' => $nav['icon'] ?? false ?: false
                    ];
                }
            }
            $tabs = rex_extension::registerPoint(new rex_extension_point(
                'HELP_NAVIGATION',
                $tabs, ['profile'=>$this->navigation,'context'=>$this->context]
            ));
            if( $tabs )
            {
                $fragment = new rex_fragment();
                $fragment->setVar('left', $tabs, false);
                return $fragment->parse('core/navigations/content.php');
            }
            return '';
        }

        //  Die Markdown-Datei wird in Inhaltsverzeichnis und Inhalt aufgedröselt
        //  beide stehen nebeneinander in zwei Spalten.

        function getDocument( $text )
        {
            [$toc, $content] = rex_markdown::factory()->parseWithToc( $text,2,3,false );
            $fragment = new rex_fragment();
            $fragment->setVar('content', $content, false);
            $fragment->setVar('toc', $toc, false);
            return $fragment->parse('core/page/docs.php');
        }

    }
}

// Here we go ....

$publish = new help_documentation( $this );

if( $publish->isAsset() ) {
    $publish->sendAsset();
}

$content = $publish->getNavigation( );

if( $path = $publish->getFilePath() ) {

    $text = rex_file::get( $path );

    $text = $publish->stripGithubNavigation( $text );
    $text = $publish->replaceLinks( $text );

    $content .= $publish->getDocument( $text );

}

echo $content;
