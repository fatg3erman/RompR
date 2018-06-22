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
        $('input[name="commradiolistby"]').bind('click', communityRadioPlugin.changeListBy);
        $('input[name="commradioorderby"]').bind('click', communityRadioPlugin.changeOrderBy);
        $('button[name="commradiosearch"]').bind('click', communityRadioPlugin.search);
        $('#communityradiolist select').on('change', communityRadioPlugin.changeOption);
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
        var orderby = $('input[name="commradioorderby"]:checked').val();
        prefs.save({communityradioorderby: orderby});
        communityRadioPlugin.page = 0;
        communityRadioPlugin.update();
    },
    
    search: function() {
        communityRadioPlugin.searching = true;
        communityRadioPlugin.page = 0;
        communityRadioPlugin.update();
    },

    handleClick: function(event) {
        debug.log("COMM RADIO", "Handling Click");
        var clickedElement = findClickableElement(event);
        if (clickedElement.hasClass("menu")) {
            doMenu(event, clickedElement);
        } else if (clickedElement.hasClass('clickcommradioforward')) {
            communityRadioPlugin.page++;
            clickedElement.unbind('click').makeSpinner();
            communityRadioPlugin.update();
        } else if (clickedElement.hasClass('clickcommradioback')) {
            communityRadioPlugin.page--;
            clickedElement.unbind('click').makeSpinner();
            communityRadioPlugin.update();
        } else if (prefs.clickmode == "double") {
            if (clickedElement.hasClass("clickstream")) {
                event.stopImmediatePropagation();
                trackSelect(event, clickedElement);
            }
        } else if (prefs.clickmode == "single") {
            onSourcesDoubleClicked(event);
        }
    }
}

menuOpeners['communityradiolist'] = communityRadioPlugin.loadBigRadio;
clickRegistry.addClickHandlers('#communityradiolist', communityRadioPlugin.handleClick, onSourcesDoubleClicked);
