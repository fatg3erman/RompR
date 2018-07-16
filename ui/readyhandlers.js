$(document).ready(function(){
    debug.log("INIT","Document Ready Event has fired");
    get_geo_country();
    if (prefs.usertheme) {
        prefs.setTheme(prefs.usertheme);
    } else {
        prefs.setTheme(prefs.theme);
    }
    if (prefs.do_not_show_prefs) {
        $('.choose_prefs').remove();
    }
    infobar.createProgressBar();
    pluginManager.doEarlyInit();
    createHelpLinks();
    player.controller.initialise();
    layoutProcessor.initialise();
    checkServerTimeOffset();
    $('.combobox').makeTagMenu({textboxextraclass: 'searchterm', textboxname: 'tag', labelhtml: '<div class="fixed searchlabel nohide"><b>'+language.gettext("label_tag")+'</b></div>', populatefunction: tagAdder.populateTagMenu});
    $('.tagaddbox').makeTagMenu({textboxname: 'newtags', populatefunction: tagAdder.populateTagMenu, buttontext: language.gettext('button_add'), buttonfunc: tagAdder.add});
    browser.createButtons();
    setClickHandlers();
    setChooserButtons();
    player.defs.replacePlayerOptions();
    $(".toggle").click(prefs.togglePref);
    $(".saveotron").keyup(prefs.saveTextBoxes);
    $(".saveomatic").change(prefs.saveSelectBoxes);
    $(".savulon").click(prefs.toggleRadio);
    $(".clickreplaygain").click(player.controller.replayGain);
    playlist.preventControlClicks(true);
    prefs.setPrefs();
    if (prefs.playlistcontrolsvisible) {
        $("#playlistbuttons").show();
    }
    if (prefs.collectioncontrolsvisible) {
        $("#collectionbuttons").show();
    }
    if (prefs.podcastcontrolsvisible) {
        $("#podcastbuttons").show();
    }
    $('.inputfile').on('change', function() {
        var filenames = $.map($(this).prop('files'), function(val) {
            return val.name.replace(/.*(\/|\\)/, '')
        });
        if (filenames.length > 3) {
            $(this).next().html(filenames.length + ' files selected');
        } else {
            $(this).next().html(filenames.join('<br />'));
        }
        $(this).parent().next('input[type="button"]').fadeIn('fast');
    });
    showUpdateWindow();
    window.addEventListener("storage", onStorageChanged, false);
    bindPlaylistClicks();
    $(window).bind('resize', layoutProcessor.adjustLayout);
    pluginManager.setupPlugins();
    setAvailableSearchOptions();
    layoutProcessor.adjustLayout();
    // Some debugging info, saved to the backend so we can see it
    prefs.save({test_width: $(window).width(), test_height: $(window).height()});
    coverscraper = new coverScraper(0, false, false, prefs.downloadart);
    lastfm = new LastFM(prefs.lastfm_user);
    layoutProcessor.changeCollectionSortMode();
    layoutProcessor.sourceControl(prefs.chooser);
    if (prefs.browser_id == null) {
        prefs.save({browser_id: Date.now()});
    }
    setTimeout(cleanBackendCache, 5000);
    if (prefs.auto_discovembobulate) {
        setTimeout(function() {
            pluginManager.autoOpen(language.gettext('button_infoyou'));
        }, 1000);
    }
});

// if ('serviceWorker' in navigator) {
//   window.addEventListener('load', function() {
//     navigator.serviceWorker.register('sw.js').then(function(registration) {
//       console.log('ServiceWorker registration successful with scope: ', registration.scope);
//     }, function(err) {
//       console.log('ServiceWorker registration failed: ', err);
//     });
//   });
// }

function cleanBackendCache() {
    if (player.updatingcollection || !player.collectionLoaded || player.collection_is_empty) {
        debug.trace("INIT","Deferring cache clean because collection is not ready",
                        player.updatingcollection, player.collectionLoaded, player.collection_is_empty);
        setTimeout(cleanBackendCache, 200000);
    } else {
        debug.shout("INIT","Starting Backend Cache Clean");
        collectionHelper.disableCollectionUpdates();
        $.get('utils/cleancache.php', function() {
            debug.shout("INIT","Cache Has Been Cleaned");
            collectionHelper.enableCollectionUpdates();
            setTimeout(cleanBackendCache, 86400000)
        });
    }
}

function get_geo_country() {
    if (prefs.country_userset == false) {
        // It's helpful and important to get the country code set, as many users won't see it
        // and it's necessary for the Spotify info panel to return accurate data
        $.getJSON("utils/getgeoip.php", function(result) {
            debug.shout("GET COUNTRY", 'Country:',result.country,'Code:',result.countryCode);
            if (result.country != 'ERROR') {
                $("#lastfm_country_codeselector").val(result.countryCode);
                prefs.save({lastfm_country_code: result.countryCode});
            } else {
                debug.error("GET COUNTRY","Country code error",result);
            }
        });
    }
}

function createHelpLinks() {
    var helplinks = {};
    helplinks[language.gettext('button_local_music')] = 'https://fatg3erman.github.io/RompR/Music-Collection';
    helplinks[language.gettext('label_searchfor')] = 'https://fatg3erman.github.io/RompR/Searching-For-Music';
    helplinks[language.gettext('button_internet_radio')] = 'https://fatg3erman.github.io/RompR/Internet-Radio';
    helplinks[language.gettext('label_podcasts')] = 'https://fatg3erman.github.io/RompR/Podcasts';
    helplinks[language.gettext('label_pluginplaylists')] = 'https://fatg3erman.github.io/RompR/Personalised-Radio';
    helplinks[language.gettext('label_lastfm')] = 'https://fatg3erman.github.io/RompR/LastFM';
    helplinks[language.gettext('config_players')] = 'https://fatg3erman.github.io/RompR/Using-Multiple-Players';
    for (var i in helplinks) {
        debug.log("HELPLINKS","Appending Help Link For",i);
        $('b:contains("'+i+'")').parent('.configtitle').not('.nohelp').append('<a href="'+helplinks[i]+'" target="_blank"><i class="icon-info-circled playlisticonr tright tooltip" title="'+language.gettext('label_gethelp')+'"></i></a>');
    }
}
