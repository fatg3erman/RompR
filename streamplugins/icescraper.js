var icecastPlugin = {

    refreshMyDrink: function(path) {
        if ($("#icecastlist").is(':empty')) {
    		icecastPlugin.makeabadger();
            $("#icecastlist").load("streamplugins/85_iceScraper.php?populate", icecastPlugin.spaghetti);
        } else if (path) {
    		icecastPlugin.makeabadger();
            $("#icecastlist").load("streamplugins/85_iceScraper.php?populate=1&path="+path, icecastPlugin.spaghetti);
        }
    },

    makeabadger: function() {
        $('[name="icecastlist"]').makeSpinner();
    },

    spaghetti: function() {
    	$('[name="icecastlist"]').stopSpinner();
        $('[name="searchfor"]').keyup(onKeyUp);
        $('[name="cornwallis"]').click(icecastPlugin.iceSearch);
    },

    iceSearch: function() {
    	icecastPlugin.makeabadger();
        $("#icecastlist").load("streamplugins/85_iceScraper.php?populate=1&searchfor="+encodeURIComponent($('input[name="searchfor"]').val()), icecastPlugin.spaghetti);
    },

    handleClick: function(event) {
        var clickedElement = findClickableElement(event);
        if (clickedElement.hasClass("menu")) {
            doMenu(event, clickedElement);
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

menuOpeners['icecastlist'] = icecastPlugin.refreshMyDrink;
clickRegistry.addClickHandlers('#icecastplugin', icecastPlugin.handleClick, onCollectionDoubleClicked);
