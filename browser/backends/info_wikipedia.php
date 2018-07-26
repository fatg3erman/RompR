<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
$domain = "en";
$userdomain = false;
$mobile = (array_key_exists('layout', $_REQUEST) && ($_REQUEST['layout'] == 'phone' || $_REQUEST['layout'] == 'tablet')) ? true : false;
// Switch off error reporting prevents us from having to repeatedly check
// that the objects we're foreaching on actually exist. Errors get dumped
// to stdout and mess up the xml response. We don't wanna see them.
// Remember to switch this off if debugging this script.
error_reporting(0);

if (array_key_exists("lang", $_REQUEST)) {
    $domain = $_REQUEST["lang"];
}
debuglog("Using Language ".$domain,"WIKIPEDIA");

if(array_key_exists("wiki", $_REQUEST)) {
    // An intra-wiki link from a page we're displaying
    $a = preg_match('#(.*?)/(.*)#', rawurldecode($_REQUEST['wiki']), $matches);
    send_result(get_wikipedia_page( $matches[2], $matches[1].".wikipedia.org", false ));

} else if(array_key_exists("uri", $_REQUEST)) {
    // Full URI to get - eg this will be a link found from musicbrainz
    $uri = rawurldecode($_REQUEST['uri']);
    debuglog("URI request ".$uri,"WIKIPEDIA");
    $a = preg_match('#https*://(.*?)/#', $uri, $matches);
    $xml_response = get_wikipedia_page(basename($uri), $matches[1], true);
    if ($userdomain == false) {
        // Found a page, but not in the user's chosen domain
        if (array_key_exists('term', $_REQUEST)) {
            debuglog("Page was retreieved but not in user's chosen language. Checking via a search","WIKIPEDIA");
            $upage = wikipedia_find_exact($_REQUEST['term'], $domain);
            if ($upage != '') {
                $xml_response = $upage;
            }
        }
    }
    send_result($xml_response);

} else if (array_key_exists("artist", $_REQUEST)) {
    // Search for an artist
    $xml_response = getArtistWiki(rawurldecode($_REQUEST['artist']), rawurldecode($_REQUEST['disambiguation']));
    if ($xml_response == null) {
        send_failure(rawurldecode($_REQUEST['artist']));
    } else {
        send_result($xml_response);
    }

} else if (array_key_exists("album", $_REQUEST)) {
    // Search for an album
    debuglog("Doing album ".$_REQUEST['album'],"WIKIPEDIA");
    $xml_response = getAlbumWiki(rawurldecode($_REQUEST['album']), rawurldecode($_REQUEST['albumartist']));
    if ($xml_response == null) {
        send_failure(rawurldecode($_REQUEST['album']));
    } else {
        send_result($xml_response);
    }

} else if (array_key_exists("track", $_REQUEST)) {
    // Search for a track
    debuglog("Doing track ".$_REQUEST['track'],"WIKIPEDIA");
    $xml_response = getTrackWiki(rawurldecode($_REQUEST['track']), rawurldecode($_REQUEST['trackartist']));
    if ($xml_response == null) {
        send_failure(rawurldecode($_REQUEST['track']));
    } else {
        send_result($xml_response);
    }
}

// ==========================================================================
//
// Getting stuff from wikipedia, including language munging
//
// ==========================================================================

function wikipedia_request($url) {
    debuglog("Getting : ".$url,"WIKIPEDIA");
    $d = new url_downloader(array(
        'url' => $url,
        'cache' => 'wikipedia',
        'return_data' => true
    ));
    if ($d->get_data_to_file()) {
        return $d->get_data();
    } else {
        return null;
    }
}

function get_wikipedia_page($page, $site, $langsearch) {

    // $page will be eg 'Air_(French_band)'
    // $site will be eg 'en.wikipedia.org'
    // $langsearch is true if we want to find a page in the user's language

    // $domain is the language the user wants to use - eg 'fr'
    global $domain;
    global $userdomain;
    global $mobile;

    // $request_domain is the language of the page we've been asked to get
    $r = preg_match("#(.*?)\.#", $site, $matches);
    $request_domain = $matches[1];
    $format_domain = $request_domain;
    $req = "";

    if ($langsearch) {

        debuglog("Request for page ".$page." from ".$site.". Domain is ".$request_domain." and user domain is ".$domain,"WIKIPEDIA");

        $user_link = ($request_domain == $domain) ? $page : null;
        $english_link = ($site == "en.wikipedia.org") ? $page : null;

        debuglog("User Link is ".$user_link." and english link is ".$english_link,"WIKIPEDIA");

        if ($domain != $request_domain) {
            debuglog("Asked for page ".$page." from site ".$site." but user wants domain ".$domain,"WIKIPEDIA");
            // Find language links for the requested page
            $langlinks = wikipedia_request("http://".$site."/w/api.php?action=query&prop=langlinks&titles=".$page."&format=xml");
            if ($langlinks !== null) {
                $langs = simplexml_load_string($langlinks);
                if ($langs->query->pages->page->langlinks) {
                    foreach($langs->query->pages->page->langlinks->ll as $ll) {
                        $l = $ll['lang'];
                        $t = dom_import_simplexml($ll)->textContent;
                        debuglog("Found language link ".$l." title ".$t,"WIKIPEDIA");
                        if ($l == $domain) {
                            $user_link = preg_replace('/ /', '_', $t);
                        }
                        if ($l == "en" && $english_link == null) {
                            $english_link = preg_replace('/ /', '_', $t);
                        }
                    }
                }
            }
        }

        debuglog("Language Scan Complete for ".$page,"WIKIPEDIA");
        debuglog("User Link is ".$user_link." and english link is ".$english_link,"WIKIPEDIA");

        if ($user_link !== null) {
            $format_domain = $domain;
            $userdomain = true;
            $page = $user_link;
            $site = $domain.'.wikipedia.org';
        } else if ($english_link !== null) {
            $page = $english_link;
            $site = "en.wikipedia.org";
            $format_domain = "en";
        }

    }

    if ($mobile) {
        $req = 'http://'.$site.'/w/api.php?action=mobileview&sections=all&prop=text&page='.$page.'&format=xml';
    } else {
        $req = 'http://'.$site.'/w/api.php?action=parse&prop=text&page='.$page.'&format=xml';
    }

    $xml = wikipedia_request($req);
    if ($xml !== null) {
        $info = "";
        if ($mobile) {
            $info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $reformat = '<?xml version="1.0" encoding="UTF-8"?><api><parse><text xml:space="preserve">';
            foreach($info->mobileview->sections->section as $section) {
                $reformat .= htmlspecialchars($section, ENT_QUOTES);
            }
            $reformat .= '</text></parse><rompr><domain>'.$format_domain.'</domain><page>'.$page.'</page></rompr></api>';
            return $reformat;
        } else {
            $info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $html = $info->parse->text;
            $matches = array();
            if (preg_match( '/REDIRECT <a href="\/wiki\/(.*?)"/', $html, $matches )) {
                $xml = wikipedia_request('http://'.$format_domain.'.wikipedia.org/w/api.php?action=parse&prop=text&page='.$matches[1].'&format=xml');
                $info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            } else if (preg_match( '/<ul class="redirectText"><li><a href=\"(.*?)\/w\/index.php\?title=(.*?)(\&.+)*\"/', $html, $matches)) {
                debuglog("Getting redirect page for ".$matches[2]." from ".$matches[1],"WIKIPEDIA");
                // Wierd. $matches[1] always == "". WTF?
                $xml = wikipedia_request('http://'.$format_domain.'.wikipedia.org/w/api.php?action=parse&prop=text&page='.$matches[2].'&format=xml');
                $info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            }
            return wrap_response($info, $format_domain, $page);
        }
    } else {
        return "";
    }

}

function wrap_response($xml, $domain, $page) {
    $meta = $xml->addChild('rompr');
    $meta->addChild('domain', $domain);
    $meta->addChild('page', $page);
    return $xml->asXML();
}

function join_responses($bits) {
    $t = "";
    $d = "";
    $p = "";
    foreach ($bits as $b) {
        $info = simplexml_load_string($b, 'SimpleXMLElement', LIBXML_NOCDATA);
        $t .= htmlspecialchars($info->parse->text, ENT_QUOTES);
        $d = $info->rompr->domain;
        $p = $info->rompr->page;
    }
    $reformat = '<?xml version="1.0" encoding="UTF-8"?><api><parse><text xml:space="preserve">'.$t.'</text></parse><rompr><domain>'.$d.'</domain><page>'.$p.'</page></rompr></api>';
    return $reformat;
}

function send_result($xml) {
    header('Content-Type: text/xml');
    print $xml;
}

function send_failure($term) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><api><parse><text xml:space="preserve">';
    $xml .= htmlspecialchars('<h3 align="center">', ENT_QUOTES).get_int_text("wiki_fail", array($term)).htmlspecialchars('</h3>', ENT_QUOTES);
    $xml .= '</text></parse>';
    $xml .= '<rompr><domain>null</domain><page>null</page></rompr></api>';
    send_result($xml);
}

// ==========================================================================
//
// Utility Functions
//
// ==========================================================================

function prepare_string($searchstring) {
    // Escape naughty characters
    $searchstring = preg_replace( '/(\(|\)|\^|\$|\\\\|\/)/', '\\\\$1', $searchstring );
    return $searchstring;
}

function wikipedia_find_exact($searchfor, $domain) {

    $xml = wikipedia_request('http://'.$domain.'.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($searchfor) . '&srprop=score&format=xml');
    if ($xml == null) {
        return '';
    }
    $info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $page = null;

    // This is international, so we only look for an exact match (we can't possibly translate every possibility that's in artist_search, etc)
    foreach ($info->query->search->p as $id) {
        $searchstring = $id['title'];
        $searchstring = prepare_string($searchstring);
        if (preg_match('/^\s*' . $searchstring . '\s*$/i', $searchfor)) {
            $page = $id['title'];
            break;
        }
    }

    if ($page == null) {
        return '';
    } else {
        return get_wikipedia_page(preg_replace('/ /', '_', $page), $domain.".wikipedia.org", false);
    }

}

function find_dismbiguation_page($page) {

    $searchfor = $page.' (disambiguation)';
    debuglog("Searching Wikipedia for ".$searchfor, "WIKIPEDIA");
    $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($searchfor) . '&srprop=score&format=xml');
    $results = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

    foreach ($results->query->search->p as $id) {
        if ($id['title'] == $searchfor) {
            debuglog("returning disambiguation page for ".$page,"WIKIPEDIA");
            return get_wikipedia_page(preg_replace('/ /', '_', $id['title']), "en.wikipedia.org", true);
        }
    }

    return '';
}

function wikipedia_get_list_of_suggestions($term) {

    global $domain;
    debuglog("Getting list of suggestions for ".$term." from ".$domain.".wikipedia.org", "WIKIPEDIA");
    $xml = wikipedia_request('http://'.$domain.'.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($term) . '&srprop=score&format=xml');
    if ($xml != "") {
        $html = '<?xml version="1.0" encoding="UTF-8"?><api><parse><text xml:space="preserve">';
        $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (count($xml->query->search->p) == 0) {
            return null;
        }
        $html .= htmlspecialchars('<h3 align="center">', ENT_QUOTES).get_int_text("wiki_suggest", array($term)).htmlspecialchars('</h3>', ENT_QUOTES);
        $html .= htmlspecialchars('<h3 align="center">', ENT_QUOTES).get_int_text("wiki_suggest2").htmlspecialchars('</h3>', ENT_QUOTES);
        $html .= htmlspecialchars('<ul>', ENT_QUOTES);
        foreach ($xml->query->search->p as $id) {
            $link = preg_replace('/\s/', '_', $id['title']);
            $html .= htmlspecialchars('<li><a href="#" name="', ENT_QUOTES).$domain.'/'.htmlspecialchars($link, ENT_QUOTES).htmlspecialchars('" class="infoclick clickwikilink">'.$id['title'].'</a></li>', ENT_QUOTES);
        }
        $html .= htmlspecialchars("</ul>", ENT_QUOTES);
        $html .= '</text></parse>';
        $html .= '<rompr><domain>'.$domain.'</domain><page>'.htmlspecialchars($term, ENT_QUOTES).'</page></rompr></api>';

        return $html;
    } else {
        return "";
    }

}

// ==========================================================================
//
// Artist Search
//
// ==========================================================================


function getArtistWiki($artist_name, $disambig) {

    global $domain;

    // First, try a search and exact match in the user's chosen language.
    // This is to catch the case where a page exists on that user's wikipedia
    // domain and it has no language links to the en site
    if ($domain != "en") {
        $h = wikipedia_find_exact($artist_name, $domain);
        if ($h != '') {
            return $h;
        }
    }

    // Now try a search on the english site. We can be more wide-ranging in this search
    // we do this in English because (a) it has the most stuff and (b) I can speak it.
    // We can find translation links later.
    $h = wikipedia_artist_search($artist_name, $disambig);
    if ($h != '') {
        return $h;
    }

    // No results returned. If there's an '&' or 'and' or '+' in the name - such as 'Fruitbat & Umbrella'
    // try querying for 'Fruitbat' and 'Umbrella' separately and if there are any results, display them all
    $artist = preg_replace('/ and /', ' & ', $artist_name);
    $artist = preg_replace('/\+/', '&', $artist);
    $jhtml = array();
    if (preg_match('/ & /', $artist) > 0) {
        $alist = explode(' & ', $artist);
        foreach ($alist as $artistname) {
            $j = wikipedia_artist_search($artistname, "");
            if ($j != '') {
                $jhtml[] = $j;
            }
        }
    } elseif (preg_match('/,/', $artist) > 0) {
        $alist = explode(',', $artist);
        $jhtml = array();
        foreach ($alist as $artistname) {
            $j = wikipedia_artist_search($artistname, "");
            if ($j != '') {
                $jhtml[] = $j;
            }
        }
    }
    if (count($jhtml) > 0) {
        return join_responses($jhtml);
    }

    $h = find_dismbiguation_page($artist_name);
    if ($h != '') {
        return $h;
    }

    return wikipedia_get_list_of_suggestions($artist_name);
}

function wikipedia_artist_search($artist, $disambig) {

    $page = null;
    if ($disambig != "") {
        $searchfor = $artist.' ('.$disambig.')';
        debuglog("Searching Wikipedia for ".$searchfor, "WIKIPEDIA ARTIST");
        $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($searchfor) . '&srprop=score&format=xml');
        $artistinfo = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        // First look for exact match
        foreach ($artistinfo->query->search->p as $id) {
            $searchstring = $id['title'];
            $searchstring = prepare_string($searchstring);
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $searchfor)) {
                $page = $id['title'];
                break;
            }
        }

        if ($page == null) {
            $poss = array();
            foreach ($artistinfo->query->search->p as $id) {
                if (preg_match('/\(.*?band\)|\(.*?musician\)|\(.*?singer\)/i', $id['title'])) {
                    $poss[] = $id['title'];
                }
            }
            if (count($poss) == 1) {
                $page = array_shift($poss);
            }
        }
    }

    if ($page == null) {
        debuglog("Searching Wikipedia for ".$artist, "WIKIPEDIA ARTIST");
        $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($artist) . '&srprop=score&format=xml');
        $artist2info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach ($artist2info->query->search->p as $id) {

            $searchstring = $id['title'];
            $searchstring = prepare_string($searchstring);
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $artist)) {
                $page = $id['title'];
                break;
            }

            $poss = array();
            if (preg_match('/\(.*?band\)|\(.*?musician\)|\(.*?singer\)/i', $id['title'])) {
                $poss[] = $id['title'];
            }
            if (count($poss) == 1) {
                $page = array_shift($poss);
                break;
            }

            $searchstring = $id['title'];
            $searchstring = prepare_string($searchstring);
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', "The " . $artist)) {
                $page = $id['title'];
                break;
            }
            $searchstring = $id['title'];
            $searchstring = prepare_string($searchstring);
            if (preg_match('/^\s*The ' . $searchstring . '\s*$/i', $artist)) {
                $page = $id['title'];
                break;
            }

            if (preg_match('/&/', $id['title'])) {
                $searchstring = $id['title'];
                $searchstring = preg_replace( '/&/', 'and', $searchstring );
                $searchstring = prepare_string($searchstring);
                if (preg_match('/^\s*' . $searchstring . '\s*$/i', $artist)) {
                    $page = $id['title'];
                    break;
                }
            }

            if (preg_match('/and/', $id['title'])) {
                $searchstring = $id['title'];
                $searchstring = preg_replace( '/and/', '&', $searchstring );
                $searchstring = prepare_string($searchstring);
                if (preg_match('/^\s*' . $searchstring . '\s*$/i', $artist)) {
                    $page = $id['title'];
                    break;
                }
            }

            // Any '.'? Let's remove them (both ways round)
            if (preg_match('/\./', $id['title'])) {
                $searchstring = $id['title'];
                $searchstring = preg_replace( '/\./', '', $searchstring );
                $searchstring = prepare_string($searchstring);
                if (preg_match('/^\s*' . $searchstring . '\s*$/i', $artist)) {
                    $page = $id['title'];
                    break;
                }
            }
            if (preg_match('/\./', $artist)) {
                $searchstring = $id['title'];
                $t = preg_replace( '/\./', '', $artist );
                $searchstring = prepare_string($searchstring);
                if (preg_match('/^\s*' . $searchstring . '\s*$/i', $t)) {
                    $page = $id['title'];
                    break;
                }
            }

            // Words for numbers, numbers for words.
            $numbers = array('/1/','/2/','/3/','/4/','/5/','/6/','/7/','/8/','/9/');
            $words = array("one", "two", "three", "four", "five", "six", "seven", "eight", "nine");

            $searchstring = $id['title'];
            $searchstring = preg_replace( $numbers, $words, $searchstring);
            $searchstring = prepare_string($searchstring);
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $artist) ||
                preg_match('/^\s*' . $searchstring . '\s*$/i', "The ".$artist)) {
                $page = $id['title'];
                break;
            }

            $numbers = array('1','2','3','4','5','6','7','8','9');
            $words = array("/one/", "/two/", "/three/", "/four/", "/five/", "/six/", "/seven/", "/eight/", "/nine/");
            $searchstring = $id['title'];
            $searchstring = preg_replace( $words, $numbers, $searchstring);
            $searchstring = prepare_string($searchstring);
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $artist) ||
                preg_match('/^\s*' . $searchstring . '\s*$/i', "The ".$artist)) {
                $page = $id['title'];
                break;
            }

        }
    }

    if ($page == null && preg_match('/.*\(.*\).*/', $artist)) {
        $sf = trim(preg_replace('/\(.*?\)/','',$artist));;
        debuglog("Searching Wikipedia for ".$sf, "WIKIPEDIA ARTIST");
        $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($sf) . '&srprop=score&format=xml');
        $artist3info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach ($artist3info->query->search->p as $id) {
            $searchstring = $id['title'];
            $searchstring = prepare_string($searchstring);
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $sf)) {
                $page = $id['title'];
                break;
            }
        }
    }

    if ($page == null) {
        return '';
    }

    debuglog("Artist search found page ".$page,"WIKIPEDIA ARTIST");
    return get_wikipedia_page(preg_replace('/ /', '_', $page), "en.wikipedia.org", true);

}

// ==========================================================================
//
// Album Search
//
// ==========================================================================


function getAlbumWiki($album_name, $artist_name) {

    global $domain;
    // First, try a search and exact match in the user's chosen language.
    // This is to catch the case where a page exists on that user's wikipedia
    // domain and it has no language links to the en site
    if ($domain != "en") {
        $h = wikipedia_find_exact($album_name, $domain);
        if ($h != '') {
            return $h;
        }
    }

    // Now try a search on the english site. We can be more wide-ranging in this search
    // we do this in English because (a) it has the most stuff and (b) I can speak it.
    // We can find translation links later.
    $h = wikipedia_album_search($album_name, $artist_name);
    if ($h != '') {
        return $h;
    }

    $h = find_dismbiguation_page($album_name);
    if ($h != '') {
        return $h;
    }

    return wikipedia_get_list_of_suggestions($album_name);

}

function wikipedia_album_search($album, $artist) {

    $album = munge_album_name($album);
    debuglog("Searching Wikipedia for ".$album." (album)", "WIKIPEDIA ALBUM");
    $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($album." (album)") . '&srprop=score&format=xml');
    $albuminfo = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

    $page = null;

    foreach ($albuminfo->query->search->p as $id) {
        $searchstring = prepare_string($album).'\s+\('.prepare_string($artist).' album\)';
        // debuglog("1. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
        if (preg_match('/^\s*' . $searchstring . '/i', $id['title'])) {
            debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
            $page = $id['title'];
            break;
        }
    }

    if ($page == null) {
        foreach ($albuminfo->query->search->p as $id) {
            $searchstring = prepare_string($album).'\s+\(album\)';
            // debuglog("2. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
            if (preg_match('/^\s*' . $searchstring . '/i', $id['title'])) {
                debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
                $page = $id['title'];
                break;
            }
        }
    }

    if ($page == null) {
        foreach ($albuminfo->query->search->p as $id) {
            $searchstring = prepare_string($album).'\s+\(\d+ album\)';
            // debuglog("2. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
            if (preg_match('/^\s*' . $searchstring . '/i', $id['title'])) {
                debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
                $page = $id['title'];
                break;
            }
        }
    }

    if ($page == null) {
        foreach ($albuminfo->query->search->p as $id) {
            $searchstring = prepare_string($album);
            // debuglog("3. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $id['title'])) {
                debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
                $page = $id['title'];
                break;
            }
        }
    }

    if ($page == null) {
        debuglog("Searching Wikipedia for ".$album, "WIKIPEDIA ALBUM");
        $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($album) . '&srprop=score&format=xml');
        $album2info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach ($album2info->query->search->p as $id) {
            $searchstring = prepare_string($album);
            // debuglog("3. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $id['title'])) {
                debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
                $page = $id['title'];
                break;
            }
        }
    }

    if ($page == null) {
        return null;
    }
    debuglog("Album search found page ".$page,"WIKIPEDIA ALBUM");
    return get_wikipedia_page(preg_replace('/ /', '_', $page), "en.wikipedia.org", true);

}

// ==========================================================================
//
// Track Search
//
// ==========================================================================


function getTrackWiki($track_name, $artist_name) {

    global $domain;
    // First, try a search and exact match in the user's chosen language.
    // This is to catch the case where a page exists on that user's wikipedia
    // domain and it has no language links to the en site
    if ($domain != "en") {
        $h = wikipedia_find_exact($track_name, $domain);
        if ($h != '') {
            return $h;
        }
    }

    // Now try a search on the english site. We can be more wide-ranging in this search
    // we do this in English because (a) it has the most stuff and (b) I can speak it.
    // We can find translation links later.
    $h = wikipedia_track_search($track_name, $artist_name);
    if ($h != '') {
        return $h;
    }

    $h = find_dismbiguation_page($track_name);
    if ($h != '') {
        return $h;
    }

    return wikipedia_get_list_of_suggestions($track_name);
}

function wikipedia_track_search($track, $trackartist) {

    debuglog("Searching Wikipedia for ".$track." (song) by ".$trackartist, "WIKIPEDIA TRACK");
    $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($track." (song)") . '&srprop=score&format=xml');
    $albuminfo = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

    // Comments assume the following:
    // track is 'A Track'
    // artist is 'An Artist'

    $page = null;

    // Look for 'A Track (An Artist song)'
    foreach ($albuminfo->query->search->p as $id) {
        $searchstring = prepare_string($track).'\s+\('.prepare_string($trackartist).' song\)';
        // debuglog("1. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
        if (preg_match('/^\s*' . $searchstring . '/i', $id['title'])) {
            debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
            $page = $id['title'];
            break;
        }
    }

    // Look for 'A Track (song)'
    if ($page == null) {
        foreach ($albuminfo->query->search->p as $id) {
            $searchstring = prepare_string($track).'\s+\(song\)';
            // debuglog("2. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
            if (preg_match('/^\s*' . $searchstring . '/i', $id['title'])) {
                debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
                $page = $id['title'];
                break;
            }
        }
    }

    // Look for 'A Track'
    if ($page == null) {
        foreach ($albuminfo->query->search->p as $id) {
            $searchstring = prepare_string($track);
            // debuglog("3. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $id['title'])) {
                debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
                $page = $id['title'];
                break;
            }
        }
    }

    if ($page == null) {
        debuglog("Searching Wikipedia for ".$track, "WIKIPEDIA TRACK");
        $xml = wikipedia_request('http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . rawurlencode($track) . '&srprop=score&format=xml');
        $album2info = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach ($album2info->query->search->p as $id) {
            $searchstring = prepare_string($track);
            // debuglog("3. Checking page ".$id['title']." against ".$searchstring, "WIKIDEBUG");
            if (preg_match('/^\s*' . $searchstring . '\s*$/i', $id['title'])) {
                debuglog("Found Page : ".$id['title'], "WIKIPEDIA");
                $page = $id['title'];
                break;
            }
        }
    }

    if ($page == null) {
        return null;
    }

    debuglog("Track search found page ".$page,"WIKIPEDIA TRACK");
    return get_wikipedia_page(preg_replace('/ /', '_', $page), "en.wikipedia.org", true);

}

?>
