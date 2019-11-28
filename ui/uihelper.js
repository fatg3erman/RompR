// skin.js may redefine these jQuery functionss if necessary

jQuery.fn.menuReveal = function(callback) {
    if (callback) {
        this.slideToggle('fast',callback);
    } else {
        this.slideToggle('fast');
    }
    return this;
}

jQuery.fn.menuHide = function(callback) {
    if (callback) {
        this.slideToggle('fast',callback);
    } else {
        this.slideToggle('fast');
    }
    return this;

}

jQuery.fn.isOpen = function() {
    return this.hasClass('icon-toggle-open');
}

jQuery.fn.isClosed = function() {
    return this.hasClass('icon-toggle-closed');
}

jQuery.fn.toggleOpen = function() {
    if (this.hasClass('icon-toggle-closed')) {
        this.removeClass('icon-toggle-closed').addClass('icon-toggle-open');
    }
    return this;
}

jQuery.fn.toggleClosed = function() {
    if (this.hasClass('icon-toggle-open')) {
        this.removeClass('icon-toggle-open').addClass('icon-toggle-closed');
    }
    return this;
}

jQuery.fn.makeSpinner = function() {
    return this.each(function() {
        var self = $(this);
        if (self.hasClass('icon-spin6') || $(this).hasClass('spinner')) {
            debug.warn('UIHELPER', 'Trying to create spinner on already spinning element');
            return;
        }
        var originalclasses = new Array();
        var classes = '';
        if (self.attr("class")) {
            var classes = self.attr("class").split(/\s/);
        }
        for (var i = 0, len = classes.length; i < len; i++) {
            if (classes[i] == "invisible" || (/^icon/.test(classes[i]))) {
                originalclasses.push(classes[i]);
                self.removeClass(classes[i]);
            }
        }
        self.attr("originalclass", originalclasses.join(" "));
        self.addClass('icon-spin6 spinner');
        return this;
    });
}

jQuery.fn.stopSpinner = function() {
    this.each(function() {
        var self = $(this);
        self.removeClass('icon-spin6 spinner');
        if (self.attr("originalclass")) {
            self.addClass(self.attr("originalclass"));
            self.removeAttr("originalclass");
        }
    });
    return this;
}

jQuery.fn.makeTagMenu = function(options) {
    var settings = $.extend({
        textboxname: "",
        textboxextraclass: "",
        labelhtml: "",
        populatefunction: null,
        buttontext: null,
        buttonfunc: null,
        buttonclass: "",
        placeholder: 'Tag'
    },options);

    this.each(function() {
        var tbc = "enter combobox-entry";
        if (settings.textboxextraclass) {
            tbc = tbc + " "+settings.textboxextraclass;
        }
        if (settings.labelhtml != '') {
            $(this).append(settings.labelhtml);
        }
        var holder = $('<div>', { class: "expand"}).appendTo($(this));
        var textbox = $('<input>', { type: "text", class: tbc, name: settings.textboxname, placeholder: settings.placeholder }).appendTo(holder);
        var dropbox = $('<div>', {class: "drop-box tagmenu dropshadow"}).insertAfter($(this));
        var menucontents = $('<div>', {class: "tagmenu-contents"}).appendTo(dropbox);
        if (settings.buttontext !== null) {
            var submitbutton = $('<button>', {class: "fixed"+settings.buttonclass,
                style: "margin-left: 8px"}).appendTo($(this));
            submitbutton.html(settings.buttontext);
            if (settings.buttonfunc) {
                submitbutton.on('click', function() {
                    settings.buttonfunc(textbox.val());
                });
            }
        }

        dropbox.mCustomScrollbar({
        theme: "light-thick",
        scrollInertia: 120,
        contentTouchScroll: 25,
        advanced: {
            updateOnContentResize: true,
            updateOnImageLoad: false,
            autoScrollOnFocus: false,
            autoUpdateTimeout: 500,
        }
        });
        textbox.on('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var position = getPosition(ev);
            // This function relies on the fact that the size of the background image
            // that provides the icon we want to click on is 50% of the height of the element,
            // as defined in the icon theme css
            var elemright = textbox.width() + textbox.offset().left;
            var elh = textbox.height()/2+2;
            if (position.x > elemright - elh) {
                if (dropbox.is(':visible')) {
                    dropbox.slideToggle('fast');
                } else {
                    var data = settings.populatefunction(function(data) {
                        menucontents.empty();
                        for (var i in data) {
                            var d = $('<div>', {class: "backhi"}).appendTo(menucontents);
                            d.html(data[i]);
                            d.on('click', function() {
                                var cv = textbox.val();
                                if (cv != "") {
                                    cv += ",";
                                }
                                cv += $(this).html();
                                textbox.val(cv);
                            });
                        }
                        dropbox.slideToggle('fast', function() {
                            dropbox.mCustomScrollbar("update");
                        });
                    });
                }
            }
        });
    });
}

jQuery.fn.fanoogleMenus = function() {
    this.each( function() {
        if ($(this).is(':visible')) {
            var top = $(this).children().first().children('.mCSB_container').offset().top;
            var conheight = $(this).children().first().children('.mCSB_container').height();
            var ws = getWindowSize();
            var avheight = ws.y - top;
            var nh = Math.min(avheight, conheight);
            $(this).css({height: nh+"px"});
            $(this).mCustomScrollbar("update");
        }
    });
    return this;
}

jQuery.fn.addBunnyEars = function() {
    this.each(function() {
        if ($(this).hasBunnyEars()) {
            $(this).removeBunnyEars();
        } else {
            var w = $(this).outerWidth(true);
            var up = $('<div>', { class: 'playlistup containerbox clickplaylist'}).prependTo($(this));
            up.html('<i class="icon-increase medicon expand"></i>').css('width', w+'px');
            var down = $('<div>', { class: 'playlistdown containerbox clickplaylist'}).appendTo($(this));
            down.html('<i class="icon-decrease medicon expand"></i>').css('width', w+'px');
            $(this).addClass('highlighted');
            if ($(this).hasClass('item')) {
                $(this).next().addClass('highlighted').slideUp('fast');
            }
        }
    });
    return this;
}

jQuery.fn.hasBunnyEars = function() {
    if ($(this).find('.playlistup').length > 0) {
        return true;
    } else {
        return false;
    }
}

jQuery.fn.removeBunnyEars = function() {
    this.each(function() {
        $(this).find('.playlistup').remove();
        $(this).find('.playlistdown').remove();
        $(this).removeClass('highlighted');
        if ($(this).hasClass('item')) {
            $(this).next().removeClass('highlighted');
        }
    });
    playlist.doPopMove();
    return this;
}

// Functions that could just be in layoutProcessor, but it makes maintenance easier
// if we have a proxy like this so we don't have to add new stuff to every single skin.

var uiHelper = function() {

    return {

        findAlbumDisplayer: function(key) {
            try {
                return layoutProcessor.findAlbumDisplayer(key);
            } catch (err) {
                if ($("#"+key).length > 0) {
                    return $("#"+key);
                } else {
                    return $('i[name="'+key+'"]').parent();
                }
            }
        },

        findAlbumParent: function(key) {
            try {
                return layoutProcessor.findAlbumParent(key);
            } catch (err) {
                return $('i[name="'+key+'"]').parent();
            }
        },

        findArtistDisplayer: function(key) {
            try {
                return layoutProcessor.findArtistDisplayer(key);
            } catch (err) {
                if ($("#"+key).length > 0) {
                    // If it already exists
                    return $("#"+key);
                } else {
                    // Opener div (standard UI)
                    return $('i[name="'+key+'"]').parent();
                }
            }
        },

        insertAlbum: function(v) {
            try {
                return layoutProcessor.insertAlbum(v);
            } catch (err) {
                var albumindex = v.id;
                var reinsert = false;
                $('#'+v.why+'album'+albumindex).html(v.tracklist);
                // This may look slightly messy but re-inserting the dropdown instead
                // of just removing it and re-opening it is much cleaner from a user
                // experience perspective.
                var dropdown = $('#'+v.why+'album'+albumindex);
                if (dropdown.is(':visible')) {
                    reinsert = true;
                    dropdown.detach().html(v.tracklist);
                }
                uiHelper.findAlbumParent(v.why+'album'+albumindex).remove();
                switch (v.type) {
                    case 'insertAfter':
                        debug.log("Insert After",v.where);
                        $(v.html).insertAfter(uiHelper.findAlbumDisplayer(v.where));
                        break;

                    case 'insertAtStart':
                        debug.log("Insert At Start",v.where);
                        $(v.html).prependTo($('#'+v.where));
                        break;
                }
                if (reinsert) {
                    uiHelper.findAlbumDisplayer(v.why+'album'+albumindex).find('.menu').toggleOpen();
                    dropdown.insertAfter(uiHelper.findAlbumDisplayer(v.why+'album'+albumindex));
                    infobar.markCurrentTrack();
                }
                uiHelper.makeResumeBar(dropdown);
                layoutProcessor.postAlbumActions();
            }
        },

        insertArtist: function(v) {
            try {
                return layoutProcessor.insertArtist(v);
            } catch(err) {
                switch (v.type) {
                    case 'insertAfter':
                        debug.log("Insert After",v.where);
                        switch (prefs.sortcollectionby) {
                            case 'album':
                            case 'albumbyartist':
                                $(v.html).insertAfter(uiHelper.findAlbumDisplayer(v.where));
                                break;

                            case 'artist':
                                $(v.html).insertAfter(uiHelper.findArtistDisplayer(v.where));
                                break;
                        }
                        break;

                    case 'insertAtStart':
                        debug.log("Insert At Start",v.where);
                        $(v.html).prependTo($('#'+v.where));
                        break;
                }
            }
        },

        removeAlbum: function(key) {
            debug.mark('UIHELPER', 'Removing Album',key);
            try {
                return layoutProcessor.removeAlbum(key);
            } catch (err) {
                debug.log('UIHELPER', 'Using default function');
                $('#'+key).remove();
                uiHelper.findAlbumDisplayer(key).remove();
                uiHelper.findAlbumParent(key).remove();
                layoutProcessor.postAlbumActions();
            }
        },

        removeArtist: function(v) {
            try {
                return layoutProcessor.removeArtist(v);
            } catch (err) {
                $("#"+v).remove();
                uiHelper.findArtistDisplayer(v).remove();
                layoutProcessor.postAlbumActions();
            }
        },

        albumBrowsed: function(menutoopen, data) {
            try {
                return layoutProcessor.albumBrowsed(menutoopen, data);
            } catch(err) {
                $("#"+menutoopen).html(data);
            }
        },

        setupPersonalRadio: function(key) {
            try {
                return layoutProcessor.setupPersonalRadio(key);
            } catch (err) {

            }
        },

        setupPersonalRadioAdditions: function(key) {
            try {
                return layoutProcessor.setupPersonalRadioAdditions(key);
            } catch (err) {

            }
        },

        emptySearchResults: function() {
            try {
                return layoutProcessor.emptySearchResults();
            } catch (err) {
                $('#searchresultholder').empty();
            }
        },

        fixupArtistDiv: function(jq, name) {
            try {
                return layoutProcessor.fixupArtistDiv(jq, name);
            } catch (err) {

            }
        },

        hackForSkinsThatModifyStuff: function(id) {
            try {
                return layoutProcessor.hackForSkinsThatModifyStuff(id);
            } catch (err) {

            }
        },

        postPlaylistLoad: function() {
            try {
                return layoutProcessor.postPlaylistLoad();
            } catch (err) {

            }
        },

        getElementPlaylistOffset: function(element) {
            try {
                return layoutProcessor.getElementPlaylistOffset(element);
            } catch (err) {

            }
        },

        createPluginHolder: function(icon, title, id, panel) {
            try {
                return layoutProcessor.createPluginHolder(icon, title, id, panel);
            } catch (err) {
                return false;
            }
        },

        makeDropHolder: function(name, d, dontsteal) {
            try {
                return layoutProcessor.makeDropHolder(name);
            } catch (err) {
                var c = 'topdropmenu dropshadow rightmenu normalmenu stayopen';
                if (dontsteal) {
                    c += ' dontstealmyclicks';
                }
                return $('<div>', {class: c, id: name}).appendTo(d);
            }
        },

        postAlbumMenu: function(element) {
            try {
                return layoutProcessor.postAlbumMenu(element);
            } catch (err) {

            }
        },

        postPodcastSubscribe: function(data, index) {
            try {
                return layoutProcessor.postPodcastSubscribe(data, index);
            } catch (err) {
                $('i[name="podcast_'+index+'"]').parent().fadeOut('fast', function() {
                    $('i[name="podcast_'+index+'"]').parent().remove();
                    $('#podcast_'+index).remove();
                    $("#fruitbat").html(data);
                    infobar.notify(language.gettext('label_subscribed'));
                    podcasts.doNewCount();
                    layoutProcessor.postAlbumActions();
                });
            }
        },

        panelMapping: function() {
            try {
                return layoutProcessor.panelMapping();
            } catch(err) {
                return {
                    "albumlist": 'albumlist',
                    "searcher": 'searcher',
                    "filelist": 'filelist',
                    "radiolist": 'radiolist',
                    "podcastslist": 'podcastslist',
                    "audiobooklist": 'audiobooklist',
                    "playlistslist": 'playlistslist',
                    "pluginplaylistslist": 'pluginplaylistslist'
                }
            }
        },

        makeResumeBar: function(target) {
            try {
                layoutProcessor.makeResumeBar(target);
            } catch(err) {
                target.find('input.resumepos').each(function() {
        			var pos = parseInt($(this).val());
        			var duration = parseInt($(this).next().val());
        			debug.log("PODCASTS", "Episode has a progress bar",pos,duration);
        			var thething = $(
        				'<div>',
        				{
        					class: 'containerbox fullwidth playlistrow2 dropdown-container podcastresume playable ',
        					name: $(this).prev().attr('name')
        				}
        			).insertBefore($(this));
        			thething.append('<div class="fixed padright">'+language.gettext('label_resume')+'</div>');
        			var bar = $('<div>', {class: 'expand', style: "height: 0.5em"}).appendTo(thething);
        			bar.rangechooser({range: duration, startmax: pos/duration, interactive: false});
        		});

            }
        },

        setupCollectionDisplay: function() {
            try {
                layoutProcessor.setupCollectionDisplay();
            } catch (err) {

            }
        },

        showTagButton: function() {
            try {
                return layoutProcessor.showTagButton();
            } catch (err) {
                return true;
            }
        }
    }

}();
