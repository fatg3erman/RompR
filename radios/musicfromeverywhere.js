var genreRadio = function() {

    return {

        setup: function() {
            //
            // Genre (Music from Everywhere)
            //
            var a = $('<div>', {class: "pluginitem radioplugin_wide"}).appendTo('#pluginplaylists_everywhere');
            var b = $('<div>', {class: "helpfulalbum fullwidth containerbox", style: "padding-top:4px"}).appendTo(a);
            var c = $('<div>', {class: "containerbox expand spacer dropdown-container"}).appendTo(b);
            c.append('<div class="fixed"><i class="icon-wifi svg-square"/></i></div>'+
                    '<div class="fixed padright"><span class="alignmid">'+language.gettext('label_genre')+'</span></div>'+
                    '<div class="expand dropdown-holder"><input class="enter" id="humphrey" type="text" onkeyup="onKeyUp(event)" /></div>'+
                    '<button class="fixed alignmid" onclick="playlist.radioManager.load(\'genreRadio\', $(\'#humphrey\').val())">'+language.gettext('button_playradio')+'</button>');
        }
    }
}();

var faveArtistRadio = function() {

    return {

        setup: function() {
            //
            // Favourite Artists (Music from Everywhere)
            //
            $('#pluginplaylists_everywhere').append(playlist.radioManager.standardBox('fartistradio', null, 'icon-artist', language.gettext('label_radio_fartist')));
            $('.fartistradio').on(clickBindType(), function(evt) {
                evt.stopPropagation();
                playlist.radioManager.load('faveArtistRadio', null);
            });
        }
    }
}();

var singleArtistRadio = function() {

    var tuner;
    var artist;

    return {

        setup: function() {
            //
            // Tracks By Artist (Music from Everywhere)
            //
            var a = $('<div>', {class: "pluginitem radioplugin_wide"}).appendTo('#pluginplaylists_everywhere');
            var b = $('<div>', {class: "helpfulalbum fullwidth containerbox", style: "padding-top:4px"}).appendTo(a);
            var c = $('<div>', {class: "containerbox expand spacer dropdown-container"}).appendTo(b);
            c.append('<div class="fixed"><i class="icon-artist svg-square"/></i></div>'+
                    '<div class="fixed padright"><span class="alignmid">'+language.gettext('label_singleartistradio')+'</span></div>'+
                    '<div class="expand dropdown-holder"><input class="enter" id="franklin" type="text" onkeyup="onKeyUp(event)" /></div>'+
                    '<button class="fixed alignmid" onclick="playlist.radioManager.load(\'singleArtistRadio\', $(\'#franklin\').val())">'+language.gettext('button_playradio')+'</button>');
        }
    }
}();

var lastFMArtistRadio = function() {

    return {

        setup: function() {
            if (lastfm.isLoggedIn && player.canPlay('spotify')) {
                // This isn't really spotify radio but it doesn't make sense unless spotify is available
                //
                // Last.FM Lucky Dip (Music from Everywhere)
                //
                var html = '<div class="pluginitem radioplugin_normal">'+
                        '<div class="helpfulalbum fullwidth">'+
                        '<i class="smallcover smallcover-svg icon-lastfm-1" style="margin:0px"></i>'+
                        '<div class="tagh albumthing"><b>'+
                        language.gettext('label_lastfm_mix')+
                        '</b></div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmartistradio dropdown-container" name="7day">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>Weekly Dip</b></div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmartistradio dropdown-container" name="1month">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>Monthly Dip</b></div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmartistradio dropdown-container" name="12month">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>Yearly Dip</b></div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmartistradio dropdown-container" name="overall">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>All Time Dip</b></div>'+
                        '</div>'+
                        '</div>'+
                        '</div>'+
                        '</div>';
                $("#pluginplaylists_everywhere").append(html);
                $('.lfmartistradio').on(clickBindType(), function(evt) {
                    evt.stopPropagation();
                    playlist.radioManager.load('lastFMArtistRadio', $(evt.delegateTarget).attr('name'));
                });
            }
        }
    }
}();

var lastFMTrackRadio = function() {

    return {
        setup: function() {
            if (lastfm.isLoggedIn && player.canPlay('spotify')) {
                // This isn't really spotify radio but it doesn't make sense unless spotify is available
                //
                // Last.FM Mix Radio (Music from Everywhere)
                //
                var html = '<div class="pluginitem radioplugin_normal">'+
                        '<div class="helpfulalbum fullwidth">'+
                        '<i class="smallcover smallcover-svg icon-lastfm-1" style="margin:0px"></i>'+
                        '<div class="tagh albumthing"><b>'+
                        language.gettext('label_lastfm_track')+
                        '</b></div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmtrackradio dropdown-container" name="7day">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>Daily Mix</b></div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmtrackradio dropdown-container" name="1month">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>Monthly Mix</b></div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmtrackradio dropdown-container" name="12month">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>Yearly Mix</b></div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="tagh albumthing">'+
                        '<div class="clickicon fullwidth containerbox line clickable lfmtrackradio dropdown-container" name="overall">'+
                        '<div class="expand"><i class="svg-square icon-lastfm-1"></i><b>All Time Mix</b></div>'+
                        '</div>'+
                        '</div>'+
                        '</div>'+
                        '</div>';
                $("#pluginplaylists_everywhere").append(html);
                $('.lfmtrackradio').on(clickBindType(), function(evt) {
                    evt.stopPropagation();
                    playlist.radioManager.load('lastFMTrackRadio', $(evt.delegateTarget).attr('name'));
                });
            }
        }
    }
}();

playlist.radioManager.register("lastFMArtistRadio", lastFMArtistRadio, 'radios/code/lastfmartistradio.js');
playlist.radioManager.register("lastFMTrackRadio", lastFMTrackRadio, 'radios/code/lastfmtrackradio.js');
playlist.radioManager.register("faveArtistRadio", faveArtistRadio, 'radios/code/faveartistradio.js');
playlist.radioManager.register("genreRadio", genreRadio,'radios/code/genreradio.js');
playlist.radioManager.register("singleArtistRadio", singleArtistRadio, 'radios/code/singleartistradio.js');
