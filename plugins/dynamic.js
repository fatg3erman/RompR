pluginManager.addPlugin(language.gettext("button_infoyou"), null, null, 'plugins/code/helpfulthings.js', true);
pluginManager.addPlugin(language.gettext("label_charts"), null, null, 'plugins/code/charts.js', true);
pluginManager.addPlugin(language.gettext("label_recentlyplayed"), null, null, 'plugins/code/recentlyplayed.js', true);
pluginManager.addPlugin(language.gettext("label_playlistmanager"), null, null, 'plugins/code/playlistmanager.js', true);
pluginManager.addPlugin(language.gettext("config_tagrat"), null, null, 'plugins/code/ratingmanager.js', true);
pluginManager.addPlugin(language.gettext("label_viewwishlist"), null, null, 'plugins/code/wishlistviewer.js', true);
pluginManager.addPlugin(language.gettext("label_albumstolistento"), null, null, 'plugins/code/albumstolistento.js', true);
pluginManager.addPlugin(language.gettext("label_metabackup"), null, null, 'plugins/code/metaBackup.js', true);
pluginManager.addPlugin(language.gettext("label_opmlimporter"), null, null, 'plugins/code/opmlImporter.js', true);
pluginManager.addPlugin(language.gettext("label_lfm_playcountimporter"), null, null, 'plugins/code/lfmimporter.js', true);
pluginManager.addPlugin(language.gettext("label_unplayabletracks"), null, null, 'plugins/code/unplayabletracks.js', true);

function lfmDataExtractor(data) {

	this.getCheckedData = function(subtype) {
		debug.debug("LASTFM", "Checking Data");
		var r;
		if (!data) {
			debug.debug("LASTFM", " - No Data");
			r = new Object();
			r[subtype] = {error: 1, message: language.gettext("lastfm_notfound", [language.gettext("label_"+subtype)])};
		} else if (data && data.error) {
			debug.debug("LASTFM", " - Error Data",data.error, data.message);
			r = new Object();
			r[subtype] = data;
		} else {
			debug.debug("LASTFM", " - Good Data");
			return data;
		}
		return r;
	}

    this.error = function() {
        if (data && data.error) {
            return data.message;
        } else {
            return false;
        }
    }

	this.errorcode = function() {
		if (data && data.error) {
			return data.error;
		} else {
            return false;
        }
	}

    this.id = function() {
		try {
        	return data.id || "";
		} catch(err) {
			return '';
		}
    }

    this.artist = function() {
		try {
        	return data.artist || "";
		} catch(err) {
			return '';
		}
    }

    this.name = function() {
		try {
    		return data.name || "";
		} catch(err) {
			return '';
		}
	}

    this.listeners = function() {
        try {
            return data.stats.listeners || 0;
        } catch(err) {
            try {
                return  data.listeners || 0;
            } catch (err) {
                return 0;
            }
        }
    }

    this.playcount = function() {
        try {
            return data.stats.playcount || 0;
        } catch(err) {
            try {
                return  data.playcount || 0;
            } catch(err) {
                return 0;
            }
        }
    }

    this.duration = function() {
        try {
            return data.duration || 0;
        } catch(err) {
            return 0;
        }
    }

    this.releasedate = function() {
        try {
            return  data.releasedate || "Unknown";
        } catch(err) {
            return "Unknown";
        }
    }

    this.mbid = function() {
        try {
            return data.mbid || false;
        } catch(err) {
            return false;
        }
    }

    this.userplaycount = function() {
        try {
            return data.stats.userplaycount || 0;
        } catch(err) {
			try {
            	return  data.userplaycount || 0;
			} catch(err) {
				return 0;
			}
        }
    }

    this.url = function() {
        try {
            return  data.url || null;
        } catch(err) {
            return null;
        }
    }

    this.bio = function() {
        try {
            if (data.wiki) {
                return data.wiki.content;
            } else if (data.bio && data.bio.content) {
                return data.bio.content;
            } else if (data.bio && data.bio.summary) {
                return data.bio.summary;
            } else {
                return false;
            }
        } catch(err) {
            return false;
        }
    }

    this.userloved = function() {
		try {
        	var loved =  data.userloved || 0;
        	return (loved == 1) ? true : false;
		} catch(err) {
			return false;
		}
    }

    this.tags = function() {
		try {
	        if (data.tags) {
	            try {
	                return getArray(data.tags.tag);
	            } catch(err) {
	                return [];
	            }
	        } else {
	            try {
	                return getArray(data.toptags.tag);
	            } catch(err) {
	                return [];
	            }
	        }
		} catch(err) {
			return [];
		}
    }

    this.tracklisting = function() {
        try {
            return getArray(data.tracks.track);
        } catch(err) {
            return [];
        }
    }

    this.image = function(size) {
        // Get image of the specified size.
        // If no image of that size exists, return a different one - just so we've got one.
        try {
            var url = "";
            var temp_url = "";
            for(var i in data.image) {
                temp_url = data.image[i]['#text'];
                if (data.image[i].size == size) {
                    url = temp_url;
                }
            }
            if (url == "") { url = temp_url; }
            return url;
        } catch(err) {
            return "";
        }
    }

    this.similar = function() {
        try {
            return getArray(data.similar.artist);
        } catch(err) {
            return [];
        }
    }

    this.similarimage = function(index, size) {
        try {
            var url = "";
            var temp_url = "";
            for(var i in data.similar.artist[index].image) {
                temp_url = data.similar.artist[index].image[i]['#text'];
                if (data.similar.artist[index].image[i].size == size) {
                    url = temp_url;
                    break;
                }
            }
            if (url == "") {
                url = temp_url;
            }
            return url;
        } catch(err) {
            return "";
        }

    }

    this.url = function() {
		try {
        	return data.url || null;
		} catch(err) {
			return null;
		}
    }

}
