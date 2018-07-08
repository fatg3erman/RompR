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
        var originalclasses = new Array();
        var classes = '';
        if ($(this).attr("class")) {
            var classes = $(this).attr("class").split(/\s/);
        }
        for (var i = 0, len = classes.length; i < len; i++) {
            if (classes[i] == "invisible" || (/^icon/.test(classes[i]))) {
                originalclasses.push(classes[i]);
                $(this).removeClass(classes[i]);
            }
        }
        $(this).attr("originalclass", originalclasses.join(" "));
        $(this).addClass('icon-spin6 spinner');
    });
}

jQuery.fn.stopSpinner = function() {
    return this.each(function() {
        $(this).removeClass('icon-spin6 spinner');
        if ($(this).attr("originalclass")) {
            $(this).addClass($(this).attr("originalclass"));
            $(this).removeAttr("originalclass");
        }
    });
}

jQuery.fn.bindPlayClicks = function() {
    return this.each(function() {
        $(this).unbind('click').unbind('dblclick').bind('click', onSourcesClicked);
        if (prefs.clickmode == 'double') {
            $(this).bind('dblclick', onSourcesDoubleClicked);
        }
    });
}

jQuery.fn.makeTagMenu = function(options) {
    var settings = $.extend({
        textboxname: "",
        textboxextraclass: "",
        labelhtml: "",
        populatefunction: null,
        buttontext: null,
        buttonfunc: null,
        buttonclass: ""
    },options);

    this.each(function() {
        var tbc = "enter combobox-entry";
        if (settings.textboxextraclass) {
            tbc = tbc + " "+settings.textboxextraclass;
        }
        $(this).append(settings.labelhtml);
        var holder = $('<div>', { class: "expand"}).appendTo($(this));
        var textbox = $('<input>', { type: "text", class: tbc, name: settings.textboxname }).
            appendTo(holder);
        var dropbox = $('<div>', {class: "drop-box tagmenu dropshadow"}).appendTo(holder);
        var menucontents = $('<div>', {class: "tagmenu-contents"}).appendTo(dropbox);
        if (settings.buttontext !== null) {
            var submitbutton = $('<button>', {class: "fixed"+settings.buttonclass,
                style: "margin-left: 8px"}).appendTo($(this));
            submitbutton.html(settings.buttontext);
            if (settings.buttonfunc) {
                submitbutton.click(function() {
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
        textbox.hover(makeHoverWork);
        textbox.mousemove(makeHoverWork);
        textbox.click(function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var position = getPosition(ev);
            var elemright = textbox.width() + textbox.offset().left;
            if (position.x > elemright - 24) {
                if (dropbox.is(':visible')) {
                    dropbox.slideToggle('fast');
                } else {
                    var data = settings.populatefunction(function(data) {
                        menucontents.empty();
                        for (var i in data) {
                            var d = $('<div>', {class: "backhi"}).appendTo(menucontents);
                            d.html(data[i]);
                            d.click(function() {
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
    return this.each( function() {
        var top = $(this).children().first().children('.mCSB_container').offset().top;
        var conheight = $(this).children().first().children('.mCSB_container').height();
        var ws = getWindowSize();
        var avheight = ws.y - top;
        var nh = Math.min(avheight, conheight);
        $(this).css({height: nh+"px", "max-height":''});
        $(this).mCustomScrollbar("update");
        if ($(this).attr("id") == "hpscr") {
            $(this).mCustomScrollbar("scrollTo", '.current', {scrollInertia:0});
        }
    });
}

jQuery.fn.addBunnyEars = function() {
    this.each(function() {
        if ($(this).hasBunnyEars()) {
            $(this).removeBunnyEars();
        } else {
            var w = $(this).outerWidth(true);
            var up = $('<div>', { class: 'playlistup containerbox clickable'}).prependTo($(this));
            up.html('<i class="icon-increase medicon expand"></i>').css('width', w+'px');
            var down = $('<div>', { class: 'playlistdown containerbox clickable'}).appendTo($(this));
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
                $('#aalbum'+albumindex).html(v.tracklist);
                // This may look slightly messy but re-insertgin the dropdown instead
                // of just removing it re-opening it is much cleaner from a user
                // experience perspective.
                var dropdown = $('#aalbum'+albumindex);
                if (dropdown.is(':visible')) {
                    reinsert = true;
                    dropdown.detach().html(v.tracklist);
                }
                uiHelper.findAlbumParent('aalbum'+albumindex).remove();
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
                    uiHelper.findAlbumDisplayer('aalbum'+albumindex).find('.menu').toggleOpen();
                    dropdown.insertAfter(uiHelper.findAlbumDisplayer('aalbum'+albumindex));
                    infobar.markCurrentTrack();
                }
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
            try {
                return layoutProcessor.removeAlbum(key);
            } catch (err) {
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
                $("#aartist"+v).remove();
                uiHelper.findArtistDisplayer('aartist'+v).remove();
                layoutProcessor.postAlbumActions();
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
        
        createPluginHolder: function(icon, title) {
            try {
                return layoutProcessor.createPluginHolder(icon, title);
            } catch (err) {
                var d = $('<div>', {class: 'topdrop'}).prependTo('#righthandtop');
                var i = $('<i>', {class: 'tooltip', title: title}).appendTo(d);
                i.addClass(icon);
                if (small_plugin_icons) {
                    i.addClass('smallpluginicon clickicon');
                } else {
                    i.addClass('topimg')
                }
                return d;
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
                    $("#fruitbat").find('.fridge').tipTip({edgeOffset: 8});
                    infobar.notify(infobar.NOTIFY, "Subscribed to Podcast");
                    podcasts.doNewCount();
                    layoutProcessor.postAlbumActions();
                });
            }
        }
            
    }

}();
