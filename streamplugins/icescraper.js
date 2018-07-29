var icecastPlugin = {

    refreshMyDrink: function(path) {
        if ($("#icecastlist").hasClass('notfilled')) {
    		icecastPlugin.makeabadger();
            $("#icecastlist").load("streamplugins/85_iceScraper.php?populate", icecastPlugin.spaghetti);
        } else if (path) {
    		icecastPlugin.makeabadger();
            $("#icecastlist").load("streamplugins/85_iceScraper.php?populate=1&path="+path, icecastPlugin.spaghetti);
        }
    },

    makeabadger: function() {
        $('i[name="icecastlist"]').makeSpinner();
    },

    spaghetti: function() {
    	$('i[name="icecastlist"]').stopSpinner();
        // $('[name="searchfor"]').on('keyup', onKeyUp);
        $('[name="cornwallis"]').on('click', icecastPlugin.iceSearch);
        $("#icecastlist").removeClass('notfilled');
        layoutProcessor.postAlbumActions();
    },

    iceSearch: function() {
    	icecastPlugin.makeabadger();
        $("#icecastlist").load("streamplugins/85_iceScraper.php?populate=1&searchfor="+encodeURIComponent($('input[name="searchfor"]').val()), icecastPlugin.spaghetti);
    },

    handleClick: function(event, clickedElement) {
        if (clickedElement.hasClass("clickicepager")) {
            icecastPlugin.refreshMyDrink(clickedElement.attr('name'));
        }
    }

}

menuOpeners['icecastlist'] = icecastPlugin.refreshMyDrink;
clickRegistry.addClickHandlers('icescraper', icecastPlugin.handleClick);
