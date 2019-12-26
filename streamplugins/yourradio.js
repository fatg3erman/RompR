var yourRadioPlugin = {

	loadStations: function() {
		if ($('#yourradiostations').is(':empty')) {
			$('i[name="yourradiolist"]').makeSpinner();
			$('#yourradiostations').load('utils/userstreams.php?populate', function() {
				$('i[name="yourradiolist"]').stopSpinner();
				$('[name="spikemilligan"]').on('click', yourRadioPlugin.loadSuppliedStation);
				$("#anaconda").on("drop", yourRadioPlugin.handleDropRadio);
				uiHelper.doThingsAfterDisplayingListOfAlbums($('#yourradiostations'));
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
				uiHelper.doThingsAfterDisplayingListOfAlbums($('#yourradiostations'));
				infobar.notify(language.gettext('label_addedradio'));
			});
	},

	removeUserStream: function(name) {
		$.post("utils/userstreams.php", {remove: name})
			.done( function(data) {
				$('#yourradiostations').html(data);
				uiHelper.doThingsAfterDisplayingListOfAlbums($('#yourradiostations'));
			})
			.fail( function() {
				playlist.repopulate();
				infobar.error(language.gettext("label_general_error"));
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
		if ($("#yourradioinput").val() != '') {
			player.controller.addTracks({
				type: "stream",
				url: $("#yourradioinput").val(),
				image: null,
				station: null
			}, playlist.playFromEnd(), null, false);
		}
	},

	handleClick: function(event, clickedElement) {
		if (clickedElement.hasClass("clickradioremove")) {
			event.stopImmediatePropagation();
			yourRadioPlugin.removeUserStream(clickedElement.attr("name"));
		}

	}

}

menuOpeners['yourradiolist'] = yourRadioPlugin.loadStations;
clickRegistry.addClickHandlers('yourradio', yourRadioPlugin.handleClick);
