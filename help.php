<?php
/**
 *  HELP.PHP für REDAXO-Addons
 *
 *  @author     Christoph Böcker <https://github.com/christophboecker>
 *  @version    2.0
 *  @copyright  Christoph Böcker <https://github.com/christophboecker>
 *  @license    MIT
 *  @see        https://github.com/christophboecker/help.php  Repository on Github
 *  @see        https://github.com/christophboecker/help.php/blob/master/manual.md  Manual/Documentation
 *
 *  für REDAXO ab V5.7
 *
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
        public $filetype = '';
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
            $this->filetype = pathinfo( $this->filename,PATHINFO_EXTENSION );

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
        //      «addon_root»/CREDITS.md
        //      «addon_root»/docs/*
        //
        //  Als Nebeneffekt wird geprüft, ob die DAtei überhaupt exisitert bzw. ob sie in einer
        //  Sprachversion existiert (pfad/dateiname.lang.suffix), die statt des Originalnamens genutzt wird.

        function getDocumentName( )
        {
            // Zerlege den Pfadnamen in path, name, lang und suffix.
            // lang ist optional. Ohne suffix => NO_GO
            $filepath = $this->dir . $this->filename;
            $pattern = '/^(?<path>(.*?\/)*)(?<name>.*?)(?<lang>\.[a-zA-Z]{2})?(?<suffix>\.\w+)$/';
            if( !preg_match( $pattern, $filepath, $pathinfo ) ) {
                return false;
            }
            $pathinfo = array_filter( $pathinfo, 'is_string', ARRAY_FILTER_USE_KEY );

            // Suche zunächst die Datei mit dem aktuellen Sprachcode
            $pathinfo['lang'] = '.' . rex_i18n::getLanguage();
            $real_path = realpath( implode('',$pathinfo) );
            if( !$real_path)
            {
                //  Pfad normieren, Suche nach normaler Datei
                $pathinfo['lang'] = '';
                $real_path = realpath( implode('',$pathinfo) );
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
                     || 0 == strcasecmp($filename,'credits.md')
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
            return 'md' !== $this->filetype;
        }

        //  Nicht-Markdown-Dateien (meist Bilder), werden direkt ausgegeben.
        //  Danach abbrechen.
        //  Non-Images als Download-Anhang senden

        function sendAsset( ){
            rex_response::cleanOutputBuffers();
            if ( ($path = $this->getFilePath()) && rex_media::isDocType($this->filetype) ) {
                $mime = rex_file::mimeType( $path );
                if( 'image/' === substr($mime,0,6) ) {
                    rex_response::sendFile( $path, $mime );
                } else {
                    rex_response::sendFile( $path, $mime, 'attachment', basename($path) );
                }
            } else {
                header( 'HTTP/1.1 Not Found' );
            }
            exit();
        }

        //  Entferne ein für die Github-Ansicht eingebautes Menü, das hier der $navigation entspricht
        //  Das sind alle Zeilen ab Zeile 1, die mit "> - " beginnen sowie die abschließende Leerzeile

        function stripGithubNavigation( $text )
        {
            return preg_replace( '/^(\>\s+\-\s?.*?\\n)*\s*\\n/', '', $text );
        }

        //  Im Text werden alle Links, die nicht Datei-intern (#...) und nicht URIs (z.B. http://...)
        //  sind, werden so umgebaut, dass sie durch diese Seite geschleust werden.
        //  Der Link ist die URL der aktuellen Seite mit dem zusätzlichen Parameter '&doc=originallink'
        //  per EP kann der Link noch einmal umgearbeitet werden.
        //  Datei die Code-Blöcke auslassen

        function replaceLinks( $text )
        {
            $request = $_REQUEST;
            $request['doc'] = dirname($this->targetfile);

            # Code-Blöcke identifizieren und herauslösen, damit keine darin enthaltenen Links geändert werden.

            $original = [];
            $text = preg_replace_callback( '/(```.*?```|`.*?`)/s', function( $matches) use(&$original){
                    $marker = '<!--' . md5($matches[0]) . '-->';
                    $original["/$marker/"] = $matches[0];
                    return $marker;
                }, $text );

            # Links umbauen; nur Markdown! [label](link) bzw. ![label](link)
            $text = preg_replace_callback (
                '/((!?)\[(.*?)\]\()\s*(.*?)\s*(\))/',
                function( $matches ) use( $request )
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
                        $href = $link;
                    }
                    //  alle anderen Varianten umbauen
                    else
                    {
                        $href = help_documentation::getLink( $request, $link );
                        $term = $matches[1] . $href . $matches[5];
                    }
                    return rex_extension::registerPoint(new rex_extension_point(
                        'HELP_HREF',
                        $term,
                        ['source'=>$matches[0],'label'=>$matches[3],'link'=>$matches[4],'href'=>$href,'isImageLink'=>($matches[2]>''),'context'=>$this->context]
                    ));
                },
                $text );

            # Code-Blöcke wieder einfügen
            if( $original ) {
                $text = preg_replace( array_keys($original), $original, $text );
            }

            return $text;
        }

        static function getLink( $request, $link )
        {
            $url = '';
            if( preg_match('/^(?<link>.*?)(#(?<hook>.*?))?$$/',$link,$linkinfo) ){
                if( $linkinfo['link'] ?? '' ) $request['doc'] .= DIRECTORY_SEPARATOR . $linkinfo['link'];
                $url = rex_url::currentBackendPage( $request,false );
                if( $linkinfo['hook'] ?? '' ) $url .= '#' . $linkinfo['hook'];
            }
            return $url;
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

        //  Wenn es im Addon-Asset-Verzeichnis Ressourcen-Dateien gibt, werden sie geladen
        //  /assests/addons/myaddon/help.min.js bzw. /assests/addons/myaddon/help.min.css

        function getJsCss( )
        {
            $HTML = '';
            $path = $this->context->getAssetsPath('help.min.js');
            if( file_exists($path) ){
                $file = $this->context->getAssetsUrl('help.min.js');
                $url = rex_url::backendController(['asset' => $file, 'buster' => filemtime($path)]);
                $HTML .= '<script type="text/javascript" src="' . $url .'"></script>';
            }
            $path = $this->context->getAssetsPath('help.min.css');
            if( file_exists($path) ){
                $file = $this->context->getAssetsUrl('help.min.css');
                $url = rex_url::backendController(['asset' => $file, 'buster' => filemtime($path)]);
                $HTML .= '<link rel="stylesheet" type="text/css" media="all" href="' . $url .'" />';
            }
            return $HTML;
        }

    }
}

// Here we go ....

$publish = new help_documentation( $this );

if( $publish->isAsset() ) {
    $publish->sendAsset();
}

$text = '';
if( $path = $publish->getFilePath() ) {

    $text = rex_file::get( $path );

    $text = $publish->stripGithubNavigation( $text );
    $text = $publish->replaceLinks( $text );

    $text = $publish->getDocument( $text );

}
?>
<?=$publish->getJsCss()?>
<div class="help-documentation">
    <?=$publish->getNavigation( )?>
    <?=$text?>
</div>
