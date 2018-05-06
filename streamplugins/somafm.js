var somaFmPlugin = {

	loadSomaFM: function() {
	    if ($("#somafmlist").is(':empty')) {
	    	$('[name="somafmlist"]').makeSpinner();
	        $("#somafmlist").load("streamplugins/01_somafm.php?populate", function( ) {
				$('[name="somafmlist"]').stopSpinner();
	        });
		}
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

menuOpeners['somafmlist'] = somaFmPlugin.loadSomaFM;
clickRegistry.addClickHandlers('#somafmplugin', somaFmPlugin.handleClick, onCollectionDoubleClicked);
