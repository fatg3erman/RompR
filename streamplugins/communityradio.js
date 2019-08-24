var communityRadioPlugin = {

    page: 0,
    searching: false,

    loadBigRadio: function() {
        if ($("#communityradiolist").hasClass('notfilled')) {
            $('i[name="communityradiolist"]').makeSpinner();
            $("#communityradiolist").load(communityRadioPlugin.getUri(1),
                function() {
                    $('i[name="communityradiolist"]').stopSpinner();
                    $('#communityradiolist').removeClass('notfilled');
                    communityRadioPlugin.setTheThing();
                    uiHelper.hackForSkinsThatModifyStuff("#communitystations");
                    layoutProcessor.postAlbumActions();
                }
            );
        }
    },

    update: function() {
        $('i[name="communityradiolist"]').makeSpinner();
        $('#communitystations').load(communityRadioPlugin.getUri(2),
            function() {
                $('i[name="communityradiolist"]').stopSpinner();
                uiHelper.hackForSkinsThatModifyStuff("#communitystations");
                layoutProcessor.postAlbumActions();
            }
        );
    },

    getUri: function(p) {
        var uri;
        if (communityRadioPlugin.searching) {
            uri = 'streamplugins/04_communityradio.php?populate='+p+
                '&listby=search'+
                '&order='+prefs.communityradioorderby+
                '&page='+communityRadioPlugin.page;
            $('.comm_radio_searchterm').each(function() {
                if ($(this).val() != '') {
                    uri += '&'+$(this).attr('name')+'='+$(this).val();
                }
            });
        } else {
            uri = 'streamplugins/04_communityradio.php?populate='+p+
                '&listby='+prefs.communityradiolistby+
                '&country='+prefs.communityradiocountry+
                '&tag='+prefs.communityradiotag+
                '&language='+prefs.communityradiolanguage+
                '&order='+prefs.communityradioorderby+
                '&page='+communityRadioPlugin.page;
        }
        return encodeURI(uri);

    },

    setTheThing: function() {
        $('input[name="commradiolistby"]').on('click', communityRadioPlugin.changeListBy);
        $('select[id="commradioorderby"]').on('change', communityRadioPlugin.changeOrderBy);
        $('button[name="commradiosearch"]').on('click', communityRadioPlugin.search);
        $('#communityradiolist select.comradiolistby').on('change', communityRadioPlugin.changeOption);
        var w = 0;
        $.each($(".cslt"), function() {
            if ($(this).width() > w) {
                w = $(this).width();
            }
        });
        w += 8;
        $(".comm-search-label").css("width", w+"px");
        w = 0;
        $.each($(".cclb"), function() {
            if ($(this).width() > w) {
                w = $(this).width();
            }
        });
        w += 8;
        $(".commradiolistby").css("width", w+"px");

    },

    changeOption: function() {
        var n = $(this).attr('id');
        var pref = Array();
        pref[n] = $(this).val();
        prefs.save(pref);
        var listby = n.replace('communityradio', '');
        $('input#commradiolistby'+listby).prop('checked', true);
        communityRadioPlugin.changeListBy();
    },

    changeListBy: function() {
        var listby = $('input[name="commradiolistby"]:checked').val();
        prefs.save({communityradiolistby: listby});
        communityRadioPlugin.page = 0;
        communityRadioPlugin.searching = false;
        $('.comm_radio_searchterm').val('');
        communityRadioPlugin.update();
    },

    changeOrderBy: function() {
        var orderby = $('select[id="commradioorderby"]').val();
        prefs.save({communityradioorderby: orderby});
        communityRadioPlugin.page = 0;
        communityRadioPlugin.update();
    },

    search: function() {
        communityRadioPlugin.searching = true;
        communityRadioPlugin.page = 0;
        communityRadioPlugin.update();
    },

    handleClick: function(event, clickedElement) {
        debug.log("COMM RADIO", "Handling Click");
        if (clickedElement.hasClass('clickcommradioforward')) {
            communityRadioPlugin.page++;
            clickedElement.off('click').makeSpinner();
            communityRadioPlugin.update();
        } else if (clickedElement.hasClass('clickcommradioback')) {
            communityRadioPlugin.page--;
            clickedElement.off('click').makeSpinner();
            communityRadioPlugin.update();
        } else if (clickedElement.hasClass('clickcommradiopager')) {
            communityRadioPlugin.page = clickedElement.attr('name');
            clickedElement.off('click').makeSpinner();
            communityRadioPlugin.update();
        }
    }
}

menuOpeners['communityradiolist'] = communityRadioPlugin.loadBigRadio;
clickRegistry.addClickHandlers('commradio', communityRadioPlugin.handleClick);
