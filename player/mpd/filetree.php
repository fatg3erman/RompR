<?php

function doFileSearch($cmd, $domains = null) {
    global $connection, $dbterms, $array_params;
    $tree = new mpdlistthing(null);
    $parts = true;
    $fcount = 0;
    $filedata = array();
    $foundfile = false;
    if (count($domains) == 0) {
        $domains = null;
    }
    send_command($cmd);
    while(!feof($connection) && $parts) {
        $parts = getline($connection);
        if (is_array($parts)) {
            switch($parts[0]) {
                case "file":
                    if (!$foundfile) {
                        $foundfile = true;
                    } else {
                        if ($dbterms['tags'] !== null || $dbterms['rating'] !== null) {
                            // If this is a search and we have tags or ratings to search for, check them here.
                            if (check_url_against_database($filedata['file'], $dbterms['tags'], $dbterms['rating']) == true) {
                                if (!is_array($domains) || in_array(getDomain($filedata['file']),$domains)) {
                                    $tree->newItem($filedata);
                                    $fcount++;
                                }
                            }
                        }  else {
                            if (!is_array($domains) || in_array(getDomain($filedata['file']),$domains)) {
                                $tree->newItem($filedata);
                                $fcount++;
                            }
                        }
                        $filedata = array();
                    }
                    $filedata[$parts[0]] = trim($parts[1]);
                    break;

                case "playlist":
                    $filedata[$parts[0]] = trim($parts[1]);
                    if ($dbterms['tags'] === null && $dbterms['rating'] === null) {
                        $tree->newItem($filedata);
                        $fcount++;
                    }
                    $filedata = array();
                    break;

                case "Title":
                case "Time":
                case "AlbumArtist":
                case "Album":
                case "Artist":
                    if (in_array($parts[0], $array_params)) {
                        $filedata[$parts[0]] = array_unique(explode(';',trim($parts[1])));
                    } else {
                        $filedata[$parts[0]] = explode(';',trim($parts[1]))[0];
                    }
                    break;
            }
        }
    }

    if (array_key_exists('file', $filedata)) {
        if (!is_array($domains) || in_array(getDomain($filedata['file']),$domains)) {
            $tree->newItem($filedata);
            $fcount++;
        }
    }

    printFileSearch($tree, $fcount);
}

function printFileSearch($tree, $fcount) {
    $prefix = "sdirholder";
    $dircount = 0;
    print '<div class="menuitem">';
    print "<h3>".get_int_text("label_searchresults")."</h3>";
    print "</div>";
    print '<div style="margin-bottom:4px">
            <table width="100%" class="playlistitem">
            <tr><td align="left">'.$fcount.' '.get_int_text('label_files').'</td></tr>
            </table>
            </div>';
    $tree->getHTML($prefix, $dircount);
}

function printFileItem($displayname, $fullpath, $time) {
    global $prefs;
    $ext = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION));
    print '<div class="clickable clicktrack ninesix draggable indent containerbox padright line" name="'.
        rawurlencode($fullpath).'">';
    print '<i class="'.audioClass($ext).' fixed smallicon"></i>';
    print '<div class="expand">'.$displayname.'</div>';
    if ($time > 0) {
        print '<div class="fixed playlistrow2 tracktime">'.format_time($time).'</div>';
    }
    print '</div>';
}

function printPlaylistItem($displayname, $fullpath) {
    print '<div class="clickable clickcue ninesix draggable indent containerbox padright line" name="'.
        rawurlencode($fullpath).'">';
    print '<i class="icon-doc-text fixed smallicon"></i>';
    print '<div class="expand">'.$displayname.'</div>';
    print '</div>';
}

function printDirectoryItem($fullpath, $displayname, $prefix, $dircount, $printcontainer = false) {
    $c = ($printcontainer) ? "searchdir" : "directory";
    print '<div class="clickable '.$c.' clickalbum draggable containerbox menuitem clickdir" name="'.
        $prefix.$dircount.'">';
    print '<input type="hidden" name="'.rawurlencode($fullpath).'">';
    print '<i class="icon-toggle-closed menu mh fixed" name="'.$prefix.$dircount.'"></i>';
    print '<i class="icon-folder-open-empty fixed smallicon"></i>';
    print '<div class="expand">'.htmlentities(urldecode($displayname)).'</div>';
    print '</div>';
    if ($printcontainer) {
        print '<div class="dropmenu" id="'.$prefix.$dircount.'">';
    }
}

class mpdlistthing {

    // Note: This is for displaying SEARCH RESULTS ONLY as a file tree.
    // Directory clicking only works on this when the entire results set
    // is loaded into the browser at once. Don't fuck with it, it's got teeth.

    public function __construct($name, $parent = null, $filedata = null) {
        $this->children = array();
        $this->name = $name;
        $this->parent = $parent;
        $this->filedata = $filedata;
    }

    public function newItem($filedata) {

        global $prefs;

        // This should only be called from outside the tree.
        // This is the root object's pre-parser

        if (array_key_exists('playlist', $filedata)) {
            $decodedpath = $filedata['playlist'];
            $filedata['file_display_name'] = basename($decodedpath);
        } else {
            $decodedpath = rawurldecode($filedata['file']);
        }

        if (substr($decodedpath, 0, 12) == "[unplayable]") {
            return;
        }

        // All the different fixups for all the different mopidy backends
        // and their various random ways of doing things.
        if (preg_match('/podcast\+http:\/\//', $decodedpath)) {
            $filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
                $filedata['Title'] : basename($decodedpath);
            $filedata['file_display_name'] =
                preg_replace('/Album: /','',$filedata['file_display_name']);
            $decodedpath = preg_replace('/podcast\+http:\/\//','podcast/',$decodedpath);
        
        } else if (preg_match('/:artist:/', $decodedpath)) {
            $filedata['file_display_name'] = concatenate_artist_names($filedata['Artist']);
            $decodedpath = preg_replace('/(.+?):(.+?):/','$1/$2/',$decodedpath);
        
        } else if (preg_match('/:album:/', $decodedpath)) {
            $matches = array();
            $a = preg_match('/(.*?):(.*?):(.*)/',$decodedpath,$matches);
            if ($filedata['AlbumArtist'] === null) {
                $filedata['AlbumArtist'] = $filedata['Artist'] ? $filedata['Artist'] : "Unknown";
            }
            $decodedpath = $matches[1]."/".$matches[2]."/".
                concatenate_artist_names($filedata['AlbumArtist'])."/".$matches[3];
            $filedata['file_display_name'] = $filedata['Album'];
        
        } else if (preg_match('/local:track:/', $decodedpath)) {
            $filedata['file_display_name'] = basename($decodedpath);
            $decodedpath = preg_replace('/:track:/','/',$decodedpath);
        
        } else if (preg_match('/:track:/', $decodedpath)) {
            $matches = array();
            $a = preg_match('/(.*?):(.*?):(.*)/',$decodedpath,$matches);
            $decodedpath = $matches[1]."/".$matches[2]."/".
                concatenate_artist_names($filedata['Artist'])."/".
                $filedata['Album']."/".$matches[3];
            $filedata['file_display_name'] = $filedata['Title'];
        
        } else if (preg_match('/soundcloud:song\//', $decodedpath)) {
            $filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
                $filedata['Title'] : basename($decodedpath);
            $decodedpath = preg_replace('/soundcloud:song/','soundcloud/'.
                concatenate_artist_names($filedata['Artist']),$decodedpath);
        
        } else if (preg_match('/^internetarchive:/', $decodedpath)) {
            $filedata['file_display_name'] = $filedata['Album'];
            $decodedpath = preg_replace('/internetarchive:/','internetarchive/',$decodedpath);
        
        } else if (preg_match('/youtube:video\//', $decodedpath)) {
            $filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
                $filedata['Title'] : basename($decodedpath);
            $decodedpath = preg_replace('/youtube:video/','youtube',$decodedpath);
        
        } else if (preg_match('/tunein:station/', $decodedpath)) {
            $filedata['file_display_name'] = (array_key_exists('Album', $filedata)) ?
                $filedata['Album'] : basename($decodedpath);
            $decodedpath = 'tunein/';
            if (array_key_exists('Artist', $filedata)) {
                $decodedpath .= concatenate_artist_names($filedata['Artist']).'/';
            }

        } else {
            if ($prefs['player_backend'] == "mopidy") {
                $filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
                    $filedata['Title'] : basename($decodedpath);
            } else {
                $filedata['file_display_name'] = basename($filedata['file']);
            }
        }

        $pathbits = explode('/', $decodedpath);
        $name = array_shift($pathbits);

        if (!array_key_exists($name, $this->children)) {
            $this->children[$name] = new mpdlistthing($name, $this);
        }

        $this->children[$name]->newChild($pathbits, $filedata);
    }

    public function newChild($pathbits, $filedata) {
        $name = array_shift($pathbits);
        if (count($pathbits) == 0) {
            $this->children[$name] = new mpdlistthing($filedata['file_display_name'], $this, $filedata);
        } else {
            if (!array_key_exists($name, $this->children)) {
                $this->children[$name] = new mpdlistthing($name, $this);
            }
            $this->children[$name]->newChild($pathbits, $filedata);
        }
    }

    public function getHTML($prefix, &$dircount) {
        if ($this->name !== null) {
            if (count($this->children) > 0) {
                // Must be a directory
                printDirectoryItem($this->parent->getName($this->name), $this->name,
                    $prefix, $dircount, true);
                $dircount++;
                foreach ($this->children as $child) {
                    $child->getHTML($prefix, $dircount);
                }
                print '</div>';
            } else {
                if (array_key_exists('playlist', $this->filedata)) {
                    printPlaylistItem($this->filedata['file_display_name'],$this->filedata['file']);
                } else {
                    printFileItem($this->filedata['file_display_name'],
                        $this->filedata['file'], $this->filedata['Time']);
                }
            }
        } else {
            foreach ($this->children as $child) {
                $child->getHTML($prefix, $dircount);
            }
        }
    }

    public function getName($name) {
        if ($this->name !== null) {
            $name = $this->name."/".$name;
        }
        if ($this->parent !== null) {
            $name = $this->parent->getName($name);
        }
        return $name;
    }

}

?>
