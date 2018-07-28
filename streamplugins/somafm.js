var somaFmPlugin = {

	loadSomaFM: function() {
	    if ($("#somafmlist").hasClass('notfilled')) {
	    	$('i[name="somafmlist"]').makeSpinner();
	        $("#somafmlist").load("streamplugins/01_somafm.php?populate", function( ) {
				$('i[name="somafmlist"]').stopSpinner();
				$('#somafmlist').removeClass('notfilled');
				layoutProcessor.postAlbumActions();
	        });
		}
	},

    handleClick: function(event) {
        var clickedElement = findClickableElement(event);
        if (clickedElement.hasClass("menu")) {
            doMenu(event, clickedElement);
        }
    }

}

menuOpeners['somafmlist'] = somaFmPlugin.loadSomaFM;
clickRegistry.addClickHandlers('#somafmplugin', somaFmPlugin.handleClick);
