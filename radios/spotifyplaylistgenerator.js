var crazyRadioManager = function() {

    var crazySettings = new Array();

    return {

        loadSavedCrazies: function() {
            $('.crazyradio').remove();
            $.get('radios/crazymanager.php?action=get', function(data) {
                debug.log("CRAZY RADIO","Saved Data",data);
                crazySettings = data;
                for (var i in crazySettings) {
                    var crazy = playlist.radioManager.standardBox('spotiCrazyRadio', i, 'icon-spotify-circled', crazySettings[i].playlistname).appendTo("#pluginplaylists_spotify");
                    crazy.append(
                        '<div class="fixed">'+
                        '<i class="icon-cancel-circled collectionicon clickable crazyradio clickremcrazy" name="'+i+'"></i>'+
                        '</div>'
                    );
                }
                layoutProcessor.adjustLayout();
                uiHelper.setupPersonalRadioAdditions();
                $('.clickremcrazy').on('click', function(evt) {
                    removeCrazy($(evt.delegateTarget).attr('name'));
                });
            }, 'json');
        },

        refreshCrazyList: function() {
            $('.crazyradio').each(function() {
                if ($(this).parent().hasClass('collectionitem')) {
                    $(this).parent().remove();
                } else {
                    $(this).remove();
                }
            });
            crazyRadioManager.loadSavedCrazies();
        },

        go: function() {
            // Dummy param Date.now() to make sure radioManager stops the previous
            // one if we've changed genres.
            playlist.radioManager.load('spotiCrazyRadio', Date.now());
        },

        load: function(i) {
            if (crazySettings.hasOwnProperty(i)) {
                debug.log("LOAD CRAZY","Loading",crazySettings[i]);
                $('[name="spotigenres"]').val(crazySettings[i].genres);
                $('.spotiradioslider').each(function() {
                    var attribute = $(this).attr('name');
                    $(this).rangechooser("setRange",crazySettings[i][attribute]);
                });
            }
        },

        handleClick: function(event, clickedElement) {
            if (clickedElement.hasClass('clickremcrazy')) {
                var i = clickedElement.attr('name');
                debug.log("CRAZY BUGGER","Removing",i);
                if ($('.crazyradio[name="'+i+'"]').parent().hasClass('collectionitem')) {
                    $('.crazyradio[name="'+i+'"]').parent().remove();
                } else {
                    $('.crazyradio[name="'+i+'"]').remove();
                }
                $.get('radios/crazymanager.php?action=remove&index='+i, crazyRadioManager.refreshCrazyList);
            }
        },

        actuallySaveCrazyRadioSettings: function() {
            var settings = {
                playlistname: $('#scplname').val(),
                genres: $('[name="spotigenres"]').val()
            }
            $('.spotiradioslider').each(function() {
                var attribute = $(this).attr('name');
                var range = $(this).rangechooser("getRange");
                settings[attribute] = {min: range.min, max: range.max};
            });
            $.ajax({
                type: 'POST',
                url: 'radios/crazymanager.php?action=save',
                data: JSON.stringify(settings)
            })
            .done(crazyRadioManager.refreshCrazyList)
            .fail(function() {
                infobar.error(language.gettext('label_general_error'));
            });
            return true;
        },

        saveCrazyRadioSettings: function(e) {
            var fnarkle = new popup({
                css: {
                    width: 400,
                    height: 300
                },
                title: language.gettext("button_createplaylist"),
                atmousepos: true,
                mousevent: e
            });
            var mywin = fnarkle.create();
            var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
            var e = $('<div>',{class: 'expand'}).appendTo(d);
            var i = $('<input>',{class: 'enter', id: 'scplname', type: 'text', size: '200'}).appendTo(e);
            var b = $('<button>',{class: 'fixed'}).appendTo(d);
            b.html(language.gettext('button_save'));
            fnarkle.useAsCloseButton(b, crazyRadioManager.actuallySaveCrazyRadioSettings);
            fnarkle.open();
        }

    }

}();

var spotiCrazyRadio = function() {

    return {

        setup: function() {
            if (player.canPlay('spotify')) {

                //
                // Spotify Playlist Generator
                //
                $("#pluginplaylists_crazy").append('<div class="textcentre textunderline"><b>Create Your Own Spotify Playlist Generator</b></div>');
                $("#pluginplaylists_crazy").append('<div class="textcentre tiny">Enter some Genres, set the parameters, and click Play.<br>You can drag both ends of the sliders to set a range.</div>');

                var a = $('<div>', {class: "containerbox menuitem spacer", style: "margin-right:8px"}).appendTo("#pluginplaylists_crazy");
                var c = $('<div>', {class: "containerbox expand spacer dropdown-container"}).
                    appendTo(a).makeTagMenu({
                    textboxname: 'spotigenres',
                    labelhtml: '<span class="padright">Genres</span>',
                    populatefunction: populateSpotiTagMenu
                });

                var html = '<div class="containerbox dropdown-container spacer menuitem">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Energy</span></div>';
                html += '<div class="expand dropdown-holder" id="energyo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="energy" class="spotiradioslider"></div>').appendTo("#energyo").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer menuitem">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Danceability</span></div>';
                html += '<div class="expand dropdown-holder" id="danceo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="danceability" class="spotiradioslider"></div>').appendTo("#danceo").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer menuitem">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Happiness</span></div>';
                html += '<div class="expand dropdown-holder" id="happyo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="valence" class="spotiradioslider"></div>').appendTo("#happyo").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer menuitem">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Instrumentalness</span></div>';
                html += '<div class="expand dropdown-holder" id="instro">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="instrumentalness" class="spotiradioslider"></div>').appendTo("#instro").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer menuitem">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Acousticness</span></div>';
                html += '<div class="expand dropdown-holder" id="acko">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="acousticness" class="spotiradioslider"></div>').appendTo("#acko").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer menuitem">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">BPM</span></div>';
                html += '<div class="expand dropdown-holder" id="tempoo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="tempo" class="spotiradioslider"></div>').appendTo("#tempoo").rangechooser({range: 250});

                html = '<div class="containerbox dropdown-container spacer menuitem" class="bacon"><div class="expand"></div>';
                html += '<button class="fixed alignmid" '+
                    'onclick="crazyRadioManager.saveCrazyRadioSettings(event)">Save These Settings</button>';
                html += '<button class="fixed alignmid" '+
                    'onclick="crazyRadioManager.go()">'+
                    language.gettext('button_playradio')+'</button>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);

                crazyRadioManager.loadSavedCrazies();
            }
        }
    }
}();

playlist.radioManager.register("spotiCrazyRadio", spotiCrazyRadio, 'radios/code/spotiCrazyRadio.js');
clickRegistry.addClickHandlers('crazyradio', crazyRadioManager.handleClick);
