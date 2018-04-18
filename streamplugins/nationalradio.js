var nationalRadioPlugin = {

    loadBigRadio: function() {
        if ($("#bbclist").is(':empty')) {
            $('[name="bbclist"]').makeSpinner();
            $("#bbclist").load("streamplugins/02_nationalradio.php?populate=2&country="+prefs.newradiocountry, function() {
                $('[name="bbclist"]').stopSpinner().removeClass('icon-toggle-closed');
                if (!$('[name="bbclist"]').hasClass('icon-toggle-open')) {
                    $('[name="bbclist"]').addClass('icon-toggle-open');
                }
                setFunkyBoxSize();
                nationalRadioPlugin.setTheThing();
            });
        } else {
            setFunkyBoxSize();
        }
    },

    setTheThing: function() {
        $('[name="radiosearcher"]').click(function(ev){
            ev.preventDefault();
            ev.stopPropagation();
            var position = getPosition(ev);
            var elemright = $('[name="radiosearcher]').width() + $('[name="radiosearcher"]').offset().left;
            if (position.x > elemright - 24) {
                $('[name="radiosearcher"]').val("");
                nationalRadioPlugin.changeradiocountry();
            }
        });
        $('[name="radiosearcher"]').hover(makeHoverWork);
        $('[name="radiosearcher"]').mousemove(makeHoverWork);
        $('[name="radiosearcher"]').keyup(onKeyUp);
        $('[name="bumfeatures"]').click(nationalRadioPlugin.searchBigRadio);
    },

    changeradiocountry: function() {
    	prefs.save({newradiocountry: $("#radioselector").val()});
        $('[name="radiosearcher"]').val("");
        nationalRadioPlugin.loadBigRadioHtml('populate=1&country='+prefs.newradiocountry);
    },

    loadBigRadioHtml: function(qstring, callback) {
        debug.log("RADIO","Getting",qstring);
        $('[name="bbclist"]').makeSpinner();
        $("#alltheradiostations").load("streamplugins/02_nationalradio.php?"+qstring, function() {
        	$('[name="bbclist"]').stopSpinner().removeClass('icon-toggle-closed');
        	if (!$('[name="bbclist"]').hasClass('icon-toggle-open')) {
        		$('[name="bbclist"]').addClass('icon-toggle-open');
        	}
            setFunkyBoxSize();
        });
    },

    searchBigRadio: function() {
        var term = $('[name="radiosearcher"]').val();
        if (term != '') {
            debug.log("RADIO","Searching For",term);
            nationalRadioPlugin.loadBigRadioHtml('populate=1&country='+prefs.newradiocountry+'&search='+encodeURIComponent(term));
        }
    },

    searchRadioMore: function(element) {
        var page = element.parent().parent().children('input[name="spage"]').val();
        var term = element.parent().parent().children('input[name="term"]').val();
        debug.log("RADIO","Searching For Page",page,"of",term);
        $.get('streamplugins/02_nationalradio.php?populate=1&country='+prefs.newradiocountry+'&page='+page+'&search='+term, function(data) {
            element.parent().parent().remove();
            $('#alltheradiostations').append(data);
            setFunkyBoxSize();
        });
    },

    browseRadio: function(element) {
        var page = 0;
        if (element.hasClass('clickradioback')) {
            page = element.parent().parent().children('input[name="prev"]').val();
        } else if (element.hasClass('clickradioforward')) {
            page = element.parent().parent().children('input[name="next"]').val();
        }
        var url = element.parent().parent().children('input[name="url"]').val();
        debug.log("RADIO","Getting page",page,"from",url);
        nationalRadioPlugin.loadBigRadioHtml('populate=1&country='+prefs.newradiocountry+'&page='+page+'&url='+encodeURIComponent(url));
    },

    handleClick: function(event) {
        var clickedElement = findClickableElement(event);
        if (clickedElement.hasClass("menu")) {
            doMenu(event, clickedElement);
        } else if (clickedElement.hasClass("clickradioback")) {
            event.stopImmediatePropagation();
            nationalRadioPlugin.browseRadio(clickedElement);
        } else if (clickedElement.hasClass("clickradioforward")) {
            event.stopImmediatePropagation();
            nationalRadioPlugin.browseRadio(clickedElement);
        } else if (clickedElement.hasClass("clicksearchmore")) {
            event.stopImmediatePropagation();
            nationalRadioPlugin.searchRadioMore(clickedElement);
        } else if (prefs.clickmode == "double") {
            if (clickedElement.hasClass("clickstream")) {
                event.stopImmediatePropagation();
                trackSelect(event, clickedElement);
            }
        } else if (prefs.clickmode == "single") {
            onCollectionDoubleClicked(event);
        }

    }

}

menuOpeners['bbclist'] = nationalRadioPlugin.loadBigRadio;
clickRegistry.addClickHandlers('#nationalradio', nationalRadioPlugin.handleClick, onCollectionDoubleClicked);
