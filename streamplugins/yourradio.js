var yourRadioPlugin = {

	loadStations: function() {
		if ($('#yourradiostations').is(':empty')) {
	        $('i[name="yourradiolist"]').makeSpinner();
			$('#yourradiostations').load('utils/userstreams.php?populate', function() {
	            $('i[name="yourradiolist"]').stopSpinner();
			    $('[name="spikemilligan"]').click(yourRadioPlugin.loadSuppliedStation);
		    	$("#anaconda").on("drop", yourRadioPlugin.handleDropRadio);
				layoutProcessor.postAlbumActions();
				if (layoutProcessor.sortFaveRadios) {
		            $("#yourradiostations").sortableTrackList({
		                items: ".menuitem",
		                insidedrop: yourRadioPlugin.saveRadioOrder,
		                scroll: true,
		                scrollparent: "#radiolist",
		                scrollspeed: 80,
		                scrollzone:120,
		                allowdragout: true
		            });
				}
			});
		}
	},

	updateStreamName: function(streamid, name, uri, callback) {
        $.post("utils/userstreams.php", { updatename: 1, streamid: streamid, name: name, uri: uri })
		.done( function(data) {
			if (callback) {
				callback();
			}
			if (!prefs.hide_radiolist) {
				$('#yourradiostations').html(data);
			}
		});
	},

	addFave: function(data) {
        data.addfave = 1;
        $.post("utils/userstreams.php", data)
            .done( function(data) {
                $('#yourradiostations').html(data);
				layoutProcessor.postAlbumActions();
                infobar.notify(infobar.NOTIFY,"Added To Your Radio Stations");
            });
	},

	removeUserStream: function(name) {
        $.post("utils/userstreams.php", {remove: name})
            .done( function(data) {
                $('#yourradiostations').html(data);
				layoutProcessor.postAlbumActions();
            })
            .fail( function() {
	            playlist.repopulate();
	            infobar.notify(infobar.ERROR, language.gettext("label_general_error"));
            });
	},

	saveRadioOrder: function() {
	    var radioOrder = Array();
	    $("#yourradiostations").find(".clickradioremove").each( function() {
	        radioOrder.push($(this).attr('name'));
	    });

	    $.ajax({
	            type: 'POST',
	            url: 'utils/userstreams.php',
	            data: {'order[]': radioOrder}
	    });
	},

	handleDropRadio: function() {
	    setTimeout(function() { yourRadioPlugin.loadSuppliedStation }, 1000);
	},

	loadSuppliedStation: function() {
	    var el = new Array();
	    el.push($('<div>', {class: 'invisible clickstream', name: $("#yourradioinput").val(), supply: 'user'}));
	    playlist.addItems(el, null);
	    el[0].remove();
	},

	handleClick: function(event) {
        var clickedElement = findClickableElement(event);
	    if (clickedElement.hasClass("menu")) {
	        doMenu(event, clickedElement);
	    } else if (clickedElement.hasClass("clickradioremove")) {
	        event.stopImmediatePropagation();
	        yourRadioPlugin.removeUserStream(clickedElement.attr("name"));
	    } else if (prefs.clickmode == "double") {
	        if (clickedElement.hasClass("clickstream") || clickedElement.hasClass("clicktrack")) {
	            event.stopImmediatePropagation();
	            trackSelect(event, clickedElement);
	        }
	    } else if (prefs.clickmode == "single") {
	        onSourcesDoubleClicked(event);
	    }

	}

}

menuOpeners['yourradiolist'] = yourRadioPlugin.loadStations;
clickRegistry.addClickHandlers('#anaconda', yourRadioPlugin.handleClick, onSourcesDoubleClicked);
