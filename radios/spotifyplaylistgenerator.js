var spotiCrazyRadio = function() {

    return {

        setup: function() {
            if (player.canPlay('spotify')) {

                //
                // Spotify Playlist Generator
                //
                $("#pluginplaylists_crazy").append('<div class="textcentre textunderline"><b>Create Your Own Spotify Playlist Generator</b></div>');
                $("#pluginplaylists_crazy").append('<div class="textcentre">Enter some Genres, set the parameters, and click Play.<br>You can drag both ends of the sliders to set a range.</div>');

                var a = $('<div>', {class: "containerbox", style: "margin-right:8px"}).appendTo("#pluginplaylists_crazy");
                var c = $('<div>', {class: "containerbox expand spacer dropdown-container"}).
                    appendTo(a).makeTagMenu({
                    textboxname: 'spotigenres',
                    labelhtml: '<span class="padright">Genres</span>',
                    populatefunction: populateSpotiTagMenu
                });

                var html = '<div class="containerbox dropdown-container spacer">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Energy</span></div>';
                html += '<div class="expand dropdown-holder" id="energyo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="energy" class="spotiradioslider"></div>').appendTo("#energyo").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Danceability</span></div>';
                html += '<div class="expand dropdown-holder" id="danceo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="danceability" class="spotiradioslider"></div>').appendTo("#danceo").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Happiness</span></div>';
                html += '<div class="expand dropdown-holder" id="happyo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="valence" class="spotiradioslider"></div>').appendTo("#happyo").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Instrumentalness</span></div>';
                html += '<div class="expand dropdown-holder" id="instro">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="instrumentalness" class="spotiradioslider"></div>').appendTo("#instro").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">Acousticness</span></div>';
                html += '<div class="expand dropdown-holder" id="acko">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="acousticness" class="spotiradioslider"></div>').appendTo("#acko").rangechooser();

                var html = '<div class="containerbox dropdown-container spacer">';
                html += '<div class="fixed padright spl"><span class="bacon alignmid">BPM</span></div>';
                html += '<div class="expand dropdown-holder" id="tempoo">';
                html += '</div>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);
                var b = $('<div name="tempo" class="spotiradioslider"></div>').appendTo("#tempoo").rangechooser({range: 250});

                html = '<div class="containerbox dropdown-container spacer" class="bacon"><div class="expand"></div>';
                html += '<button class="fixed alignmid" '+
                    'onclick="saveCrazyRadioSettings(event)">Save These Settings</button>';
                html += '<button class="fixed alignmid" '+
                    'onclick="playlist.radioManager.load(\'spotiCrazyRadio\')">'+
                    language.gettext('button_playradio')+'</button>';
                html += '</div>';
                $("#pluginplaylists_crazy").append(html);

                loadSavedCrazies();
            }
        }
    }
}();

var crazySettings = new Array();

function loadCrazy(i) {
    debug.log("LOAD CRAZY","Loading",crazySettings[i]);
    $('[name="spotigenres"]').val(crazySettings[i].genres);
    $('.spotiradioslider').each(function() {
        var attribute = $(this).attr('name');
        $(this).rangechooser("setRange",crazySettings[i][attribute]);
    });
    playlist.radioManager.load('spotiCrazyRadio');
}

function loadSavedCrazies() {
    $('.crazyradio').remove();
    $.get('radios/crazymanager.php?action=get', function(data) {
        debug.log("CRAZY RADIO","Saved Data",data);
        crazySettings = data;
        var html;
        for (var i in crazySettings) {
            var html = $(playlist.radioManager.standardBox('crazyradio', i, 'icon-spotify-circled', crazySettings[i].playlistname));
            html.append(
                '<div class="tagh albumthing helpfulalbum">'+
                '<i class="icon-cancel-circled smallicon clickicon clickremcrazy" name="'+i+'"></i>'+
                '</div>'
            );
            $("#pluginplaylists_spotify").append(html);
        }
        layoutProcessor.adjustLayout();
        $('.crazyradio').on('dblclick', function(evt) {
            loadCrazy($(evt.delegateTarget).attr('name'));
        });
        $('.clickremcrazy').on('click', function(evt) {
            removeCrazy($(evt.delegateTarget).attr('name'));
        });
    }, 'json');
}

function removeCrazy(i) {
    debug.log("CRAZY BUGGER","Removing",i);
    $('.crazyradio[name="'+i+'"]').remove();
    $.get('radios/crazymanager.php?action=remove&index='+i, refreshCrazyList);
}

function refreshCrazyList() {
    $('.crazybugger').remove();
    setTimeout(loadSavedCrazies, 500);
}

function actuallySaveCrazyRadioSettings() {
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
        data: JSON.stringify(settings),
        success: refreshCrazyList,
        error: function() {
            infobar.notify(infobar.ERROR,"Couldn't save it. Sorry, something went wrong");
        }
    });
}

function saveCrazyRadioSettings(e) {
    var fnarkle = new popup({
        width: 400,
        height: 300,
        title: language.gettext("button_createplaylist"),
        xpos: e.clientX,
        ypos: e.clientY});
    var mywin = fnarkle.create();
    var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
    var e = $('<div>',{class: 'expand'}).appendTo(d);
    var i = $('<input>',{class: 'enter', id: 'scplname', type: 'text', size: '200'}).appendTo(e).keyup(onKeyUp);
    var b = $('<button>',{class: 'fixed'}).appendTo(d);
    b.html(language.gettext('button_save'));
    fnarkle.useAsCloseButton(b, actuallySaveCrazyRadioSettings);
    b.keyup(onKeyUp);
    fnarkle.open();
}

function doSpotifyTagRadio() {
    playlist.radioManager.load('spotiCrazyRadio');
}

playlist.radioManager.register("spotiCrazyRadio", spotiCrazyRadio, 'radios/code/spotiCrazyRadio.js');

