<?php

//----------
// marker, defaults and settings
//
// label for dedicatd URL-parameters
const URL_FILE = 'doc_file';
const URL_IMAGE = 'doc_img';

// parameters in package.yml
const YML_SECTION = 'help';
const YML_MODE = 'mode';
const YML_LANG = 'fallback';
const YML_NAVI = 'navigation';
const YML_CONT = 'content';
const YML_LEVL = 'level';
const YML_CHAP = 'scope';
const YML_MDBE = 'markdown_break_enabled';
const YML_HEAD = 'title';
const YML_REPO = 'repository';

// parameter-values and defaults
const MODE_DOC = 'docs';
const MODE_README = 'readme';
const DEF_MODE = MODE_README;
const DEF_LANG = '';
const DEF_NAVI = 'main_navi.md';
const DEF_CONT = 'main_intro.md';
const DEF_LEVL = '1';
const DEF_MDBE = '0';
const MDBE_ON = '1';
const DEF_HEAD = '';
const CHAP_LEVEL1 = 'chapter';
const CHAP_LEVEL2 = 'section';
const DEF_CHAP = CHAP_LEVEL2;

// others
const PATH_DOC = 'docs/';
const MARKER = 'rexdocreadme-';

//----------
// get configuration from package.yml
//  1) page-specific parameters (pages: or page:)
//  2) on addon-level
//  3) default-values
//

$page = rex_be_controller::getCurrentPagePart();
if( $page[0] == $this->getName() ) {
    // page = addon/.../...
    $pageDefaults = (array)$this->getProperty('page');
    while( $p = next($page) ){
        $pageDefaults = (array)$pageDefaults['subpages'][$p];
    }
} else {
    // or somewhere else in the "pages"
    //     works properly only with AddOn using the modern notation for sub-page-inclusion in pages/index.php:
    //          rex_be_controller::includeCurrentPageSubPath();
    $page = implode('/',$page);
    if( isset( $this->getProperty('pages')[$page] )) {
        $pageDefaults = (array)$this->getProperty('pages')[$page];
    } else {
        $pageDefaults = [];
    }
}
if( !isset($pageDefaults[YML_SECTION]) ) $pageDefaults[YML_SECTION] = [];
$defaults = array_merge (
    [YML_MODE=>DEF_MODE,YML_LANG=>rex_i18n::getLocale(),YML_NAVI=>DEF_NAVI,YML_CONT=>DEF_CONT,YML_MDBE=>DEF_MDBE,YML_HEAD=>DEF_HEAD,YML_LEVL=>DEF_LEVL,YML_CHAP=>DEF_CHAP],
    (array)$this->getProperty( YML_SECTION, [] ),
    $pageDefaults[YML_SECTION]
);

//----------
// get language-sequence from current language and fallback-languages
//
$language = array_merge( [rex_i18n::getLocale(), $defaults[YML_LANG]], rex::getProperty('lang_fallback', []) );
$language = array_values(array_unique( array_filter( $language, 'strlen' ) ));

//----------
// set pathNames
//
$addonRoot = $this->getPath();
$docsRoot = $addonRoot . PATH_DOC;

//----------
// imgabe requested instead of document?
$image = rex_request( URL_IMAGE, 'string');

//----------
// docs-mode: reduce $language to existing directories with fallback to readme-mode
if( $defaults[YML_MODE] == MODE_DOC || $image ) {
	$dummy = array_map(function($v) use ($docsRoot) { return "$docsRoot$v/"; }, $language);
	$dummy = array_filter( $dummy, function($v) { return is_dir($v); });
	if( $dummy ) {
		$first = array_shift( $dummy );
		$dummy = array_merge( [$first,$docsRoot], $dummy );
		$language = array_values( $dummy );
	} else {
		$defaults[YML_MODE] = MODE_README;
	}
}

//----------
// if image requested: send image
// requires docs-Directory (and prepared $language containing full path)
//  assure language-fallback
if ( $image )
{
    rex_response::cleanOutputBuffers();
    foreach( $language as $dir ) {
        $imageFile = "$dir$image";
        if( is_file( $imageFile ) ) {
            rex_response::sendFile( $imageFile, mime_content_type( $imageFile ) );
            exit;
        }
    }
    header( 'HTTP/1.1 Not Found' );
    exit;
}

//----------
// URL for documents and images
//
parse_str($_SERVER['QUERY_STRING'], $url);
$url = array_diff_key( $url, [URL_FILE=>0,URL_IMAGE=>0] );
$url = rex_url::backendController( $url, false );
$docUrl = $url . '&' . URL_FILE . '=';
$imgUrl = $url . '&' . URL_IMAGE . '=';

//----------
// prepare some variables
//
$files = [];
$navigation = '';
$content = '';
$selected = '';
$repoNavigation = '';
$repoContent = '';

//----------
// complex multi-file manual
//
if( $defaults[YML_MODE] == MODE_DOC ) {

	//----------
	// initial values for docs-mode
	//
    $contentFile = rex_request( URL_FILE, 'string', $defaults[YML_CONT] );
    $selected = $contentFile;
	$navigationFile = $defaults[YML_NAVI];

	//----------
	// read content-file and navigation file with language-fallback
	//
    foreach( $language as $dir ) {
		if( $content = rex_file::get("$dir$contentFile",'') ) {
            $contentFile = substr("$dir$contentFile",strlen($addonRoot) );
            break;
        }
	}
	foreach( $language as $dir ) {
		if( $navigation = rex_file::get("$dir$navigationFile",'') ) {
            $navigationFile = substr("$dir$navigationFile",strlen($addonRoot) );
            break;
        }
	}

	//---------
	// get files allowed for simplified internal reference
    //
    if( $content > '' || $navigation > '') {
        foreach( array_reverse($language) as $lang ) {
    		foreach( rex_finder::factory( $lang ) as $v ) $files[$v->getFilename()] = $v;
    	}
    }

    //----------
    // Error for $content not found
    //
    if( $content == '' ) {
        $content = $this->i18n('docs_not_found');
    }

    //----------
    // prepare Github-links
    if( $defaults[YML_REPO] ) {
        $repoNavigation = str_replace( '%%', $navigationFile, $defaults[YML_REPO] );
        $repoContent = str_replace( '%%', $contentFile, $defaults[YML_REPO] );
    }


}

//----------
// else readme-mode or fallback from docs-mode due to missíng docsRoot
//
else {

	//----------
	// initial values for readme-mode
	$contentFile = "{$addonRoot}README.md";
    $contentFile = 'README.md';

	//----------
	// if docsRoot search for language-specific file (with fallback)
	// and get list of (supporting) files from docsRoot (i.e. images)
	if( is_dir($docsRoot) ) {
		foreach( array_merge([$language],$fallback) as $lang ) {
			if( !is_file( "{$docsRoot}README.$lang.md" ) ) continue;
			$contentFile = PATH_DOC . "README.$lang.md";
			break;
		}

		foreach( rex_finder::factory( $docsRoot ) as $v ) $files[$v->getFilename()] = $v;
	}

	//----------
	// read content-file and normalize line-breaks for future use
    $content = "\n" . str_replace(["\n\r","\r"],"\n",rex_file::get( "$addonRoot$contentFile", '' ));

    //----------
    // Detect Menu-Level
    // Level 0 = don´t show navigation
    $mlStart = is_numeric($defaults[YML_LEVL]) ? intval($defaults[YML_LEVL]) : DEF_LEVL;
    if( $mlStart > 0 ) {
        $mlEnd = $mlStart + 1;

        //----------
    	// detect chapters by headline down to level $mlEnd
    	// offset include preceeding anchor-tags
        // remember one anchor for later use
        // for
        $pattern = '/\n((<a name=".*"><\/a>\n+)*)(#{1,'.$mlEnd.'}+)\s+(.*)/';
        preg_match_all( $pattern, $content, $match, PREG_OFFSET_CAPTURE );
        foreach( $match[0] as $k=>$v ) {
            if( $hook = $match[1][$k][0] ) {
                preg_match ('/name="(.*?)"/',$hook,$hook);
                $hook = isset( $hook[1] ) ? $hook[1] : '';
            }
            $match[0][$k] = ['id'=>$k,'level'=>strlen($match[3][$k][0]) - $mlStart + 1 , 'titel'=>$match[4][$k][0], 'offset'=>$v[1], 'anchor'=>$hook, 'size'=>(strlen($match[4][$k][0])+$match[4][$k][1])];
            $prevItem = $k;
        }
        $match = $match[0];

        //----------
        // for chapters without anchor: add anchor and update offsets etc. accordingly
        //
        $offset = 0;
        $i = 0;
        foreach( $match as $k=>$v ) {
            $v['offset'] += $offset;
            $match[$k]['offset'] = $v['offset'];
            $match[$k]['size'] += $offset;
            if( $v['anchor'] ) continue;
            $hook = PHP_EOL.'<a name="'.MARKER.$k.'"></a>';
            $content = substr_replace( $content, $hook, $v['offset'],0 );
            $offset += strlen($hook);
            $match[$k]['size'] += strlen($hook);
            $match[$k]['anchor'] = MARKER.$k;
        }

        //----------
        // get chapter-endpoint by detecting the next chapter-start
        $endOfPart = strlen($content);
        foreach( array_reverse($match,true) as $k=>$v ) {
            $match[$k]['end'] = $endOfPart;
            $match[$k]['size'] = $endOfPart - $match[$k]['size'];
            $endOfPart = $v['offset'] - 1;
        }

        //----------
        // eliminate headlines (and chapters) above $mlStart
        //
        $match = array_filter( $match, function($v){return $v['level']> 0;});
        $match[key($match)]['level'] = 1;

        //----------
        // set "group-end" for top-level
        $groupEnd = -1;
        foreach( array_reverse($match,true) as $k=>$v ) {
            if( $groupEnd == -1 ) $groupEnd = $v['end'];
            if( $v['level'] == 1 ) {
                $match[$k]['group'] = $groupEnd;
                $groupEnd = -1;
            }
        }

        //----------
        // identify internal anchors and the corresponding chapter
        preg_match_all( '/<a name="(.*?)">/', $content, $dummy, PREG_OFFSET_CAPTURE );
        $anchor = [];
        foreach( $dummy[1] as $v) {
            $pos = $v[1];
            $pos = array_filter( $match, function($v) use($pos){ return $v['offset']<=$pos && $pos<=$v['end']; });
            $anchor[$v[0]] = key( $pos );
        }
        $anchor = array_filter( $anchor, function($v){ return $v !== null;});
        $defaultChapter = key($anchor);

        //----------
        // detect requested chapter
        //     empty  --> default first chapter in the $match-List
        //     string --> check if in $anchor-List, otherwise first chapter
        $chapter = rex_request( URL_FILE, 'string', $defaultChapter);
        $chapter = array_key_exists( $chapter,$anchor ) ? $chapter : $defaultChapter;
        $selected = $chapter;
        $chapter = $anchor[$chapter];

        //----------
        // if "chapter" requested (=Level 1):
        //      find top-level-chapter
        //      find and correct level-2-links to 'level-1-chapter#level-2-chapter'
        if( $defaults[YML_CHAP] == CHAP_LEVEL1 ) {

            //----------
            // select the level-1-chapter in scope
            $x = $match[$chapter];
            $matchL1 = array_filter( $match, function($v)use($x){return $v['level']==1 && $v['offset']<=$x['offset'] && $x['end']<=$v['group'];} );
            $matchL1 = reset($matchL1);
            $start = $matchL1['offset'];
            $end = $matchL1['group'];

            //----------
            // links are links with #anchor to the subchapters
            foreach( $match as $k=>$v ) {
                if( $v['level'] == 1 ) {
                    $link = $v['anchor'];
                } else {
                    $match[$k]['anchor'] = "$link#{$v['anchor']}";
                }
            }
        } else {
            $start = $match[$chapter]['offset'];
            $end = $match[$chapter]['end'];
        }

        //----------
        // reduce $content to the selected chapter
        $content = substr( $content, $start+1, $end - $start );

        //----------
        // show Sub-Menü if level is 1 and no text
//        if( $match[$chapter]['size'] == 0 and $match[$chapter]['level'] == 1 ) {
//            $content .= 'hier könnte was stehen';
//        }

        //----------
        // setup the navigation
        foreach( $match as $k=>$v ) {
            $navigation .= str_repeat(' ', $v['level'] ) . '- ';
            if( $defaults[YML_CHAP] != CHAP_LEVEL1 && $v['size'] == 0 ) {
                $navigation .= $v['titel'];
            } else {
                $navigation .= "[{$v['titel']}]($docUrl{$v['anchor']})";
            }
            $navigation .= PHP_EOL;
        }

        //----------
        // prepare Github-links
        if( $defaults[YML_REPO] ) {
            $repoContent = str_replace( '%%', $contentFile, $defaults[YML_REPO] );
        }

    }
}

//----------
// mark selected item ($navigation only)
$search = '/(\[.*?\]\(.*?'.preg_quote($selected).'\))(\{(.*?)\})?/';
$replace = function ($item) {
        $classes = ['.bg-primary'];
        if( isset( $item[2] ) && $item[3] ) {
            $classes[] = $item[3];
        }
        return $item[1].'{'.implode(' ',$classes).'}';
	};
$navigation = preg_replace_callback( $search, $replace, $navigation );

//----------
// replace links to internal files ($files) with proper URL
$search = '/(!)?\[(.*?)\]\((.*?)\)/';
$replace = function ($item) use ($files,$imgUrl,$docUrl) {
        if( !$item[3] ) return $item[0];
        $link = explode('#',$item[3]);
        $url = (isset($files[$link[0]]) ? ($item[1]=='!'?$imgUrl:$docUrl) : '') . $item[3];
        $link = "{$item[1]}[{$item[2]}]($url)";
		return $link;
	};
$content = preg_replace_callback( $search, $replace, $content );
$navigation = preg_replace_callback( $search, $replace, $navigation );

//----------
// parse Markdown to HTML
$parser = new ParsedownExtra();
if( $defaults[YML_MDBE] == MDBE_ON ) $parser->setBreaksEnabled(true);
$content = $parser->text($content);
$navigation = $parser->text($navigation);
unset( $parser );


//----------
// format Github-links
$button = ['label'=>' '.$this->i18n('docs_repository_button'),'icon'=>'editmode','attributes'=>['class'=>['btn-xs','btn-default','pull-right']]];
if( $repoContent ) {
    $button['url'] = $repoContent;
    $fragment = new rex_fragment();
    $fragment->setVar('buttons',[$button],false);
    $repoContent = $fragment->parse('core/buttons/button.php');
}
if( $repoNavigation ) {
    $button['url'] = $repoNavigation;
    $fragment = new rex_fragment();
    $fragment->setVar('buttons',[$button],false);
    $repoNavigation = $fragment->parse('core/buttons/button.php');
}

//----------
// generate output

if( $defaults[YML_HEAD] ) {
    echo rex_view::title(rex_i18n::translate($defaults[YML_HEAD]));
}

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('docs_content').$repoContent, false );
$fragment->setVar('body', $content, false);
$content = $fragment->parse('core/page/section.php');

if( $navigation ) {

	$fragment = new rex_fragment();
	$fragment->setVar('title', $this->i18n('docs_navigation').$repoNavigation, false );
	$fragment->setVar('body', $navigation, false);
	$navigation = $fragment->parse('core/page/section.php');

	$fragment = new rex_fragment();
	$fragment->setVar('content', [$navigation,$content], false);
	$fragment->setVar('classes', ['col-md-4 yform-docs-navi','col-md-8 yform-docs-content'], false);
	$content = $fragment->parse('core/page/grid.php');

}

$fragment = new rex_fragment();
$fragment->setVar('content', $content, false);
$fragment->setVar('class', ' rex-yform-docs', false);
echo $fragment->parse('core/page/section.php');
?>
<style>
.rex-yform-docs img {max-width:100%;}
.rex-yform-docs h1 {
    font-size: 22px;
    margin-top: 25px;
    margin-bottom: 20px;
    border-top: 1px solid gray;
    padding-top: 5px;
}
.rex-yform-docs h1:first-child {
    margin-top: 5px;
    border-top: 0;
    padding-top: 0;
}
.rex-yform-docs h2 {
    font-size: 16px;
    margin-top: 40px;
    text-transform: uppercase;
    margin-bottom: 20px;
    letter-spacing: 0.02em;
    border-bottom: 1px solid #ccc;
    padding: 13px 15px 10px;
    background: #eee;
}
.rex-yform-docs h3 {
    margin-top: 40px;
    margin-bottom: 5px;
}

.rex-yform-docs blockquote {
    margin: 20px 0;
    background: #f3f6fb;
}
.rex-yform-docs blockquote h2 {
    margin: -10px -20px 20px;
    background: transparent;
	border-top: 1px #ccc;
}

.rex-yform-docs ol {
    padding-left: 18px;
}

.rex-yform-docs ul {
    margin-bottom: 10px;
	padding-bottom: 5px;;
    padding-left: 16px;
}
.rex-yform-docs ul li {
    list-style-type: square;
    list-style-position: outside;
}
.rex-yform-docs ul ul {
    padding-top: 5px;
}
.rex-yform-docs ul ul li {
    list-style-type: circle;
    list-style-position: outside;
    padding-bottom: 0;
}

.rex-yform-docs p,
.rex-yform-docs li {
    font-size: 14px;
    line-height: 1.6;
}

.rex-yform-docs hr {
    margin-top: 40px;
    border-top: 1px solid #ddd;
}

.rex-yform-docs table {
    width: 100%;
    max-width: 100%;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    margin: 20px 0 30px;
}
.rex-yform-docs th {
    background: #f7f7f7;
    border-bottom: 2px solid #ddd;
    border-collapse: separate;
}
.rex-yform-docs th,
.rex-yform-docs td {
    border-top: 1px solid #ddd;
    padding: 8px;
    line-height: 1.42857143;
    vertical-align: top;
    font-size: 13px;
}


.rex-yform-docs .yform-docs-navi ul {
    margin-bottom: 10px;
    padding-left: 0;
}
.rex-yform-docs .yform-docs-navi ul li {
    list-style-type: none;
    background: #eee;
    padding: 0 15px;
    line-height: 40px;
}
.rex-yform-docs .yform-docs-navi ul {
    background: #fff;
    margin-left: -15px;
    margin-right: -15px;
}
.rex-yform-docs .yform-docs-navi ul li li {
    list-style-type: none;
    background: #fff;
    line-height: 30px;
}
.rex-yform-docs .yform-docs-navi ul li li:before {
font-family: FontAwesome;
    content: '\f0a9';
    margin-right: 10px;
}
.rex-yform-docs .yform-docs-navi ul sup {
    display: none;
}
</style>
