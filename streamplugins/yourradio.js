var yourRadioPlugin = {

	reloadStations: function() {
		if (!prefs.hide_radiolist) {
			clickRegistry.loadContentIntoTarget($('#yourradiostations'), $('i[name="yourradiolist"]'), true, 'utils/userstreams.php?populate');
		}
	},

	loadStations: function() {
		if (layoutProcessor.sortFaveRadios) {
			$("#yourradiolist").sortableTrackList({
				items: ".menuitem",
				insidedrop: yourRadioPlugin.saveRadioOrder,
				scroll: true,
				scrollparent: "#radiolist",
				scrollspeed: 80,
				scrollzone:120,
				allowdragout: true
			});
		}
		return 'utils/userstreams.php?firstload';
	},

	updateStreamName: async function(streamid, name, uri) {
		await $.ajax({
			type: 'POST',
			data: { updatename: 1, streamid: streamid, name: name, uri: uri },
			url: "utils/userstreams.php"
		});
		yourRadioPlugin.reloadStations();
	},

	addFave: async function(data) {
		data.addfave = 1;
		await $.ajax({
			type: 'POST',
			data: data,
			url: "utils/userstreams.php"
		});
		yourRadioPlugin.reloadStations();
		infobar.notify(language.gettext('label_addedradio'));
	},

	removeUserStream: async function(name) {
		await $.ajax({
			type: 'POST',
			data: {remove: name},
			url: "utils/userstreams.php"
		});
		yourRadioPlugin.reloadStations();
	},

	saveRadioOrder: function() {
		var radioOrder = new Array();
		$("#yourradiolist").find(".clickradioremove").each( function() {
			radioOrder.push($(this).attr('name'));
		});
		debug.log('YOURRADIO', 'Saving radio order',radioOrder);
		$.ajax({
				type: 'POST',
				url: 'utils/userstreams.php',
				data: {'order[]': radioOrder}
		});
	},

	handleDropRadio: function() {
		setTimeout(function() { yourRadioPlugin.loadSuppliedStation }, 200);
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
			yourRadioPlugin.removeUserStream(clickedElement.attr("name"));
		}
	}

}

$(document).on('click', '[name="spikemilligan"]', yourRadioPlugin.loadSuppliedStation);
$(document).on("drop", "#anaconda", yourRadioPlugin.handleDropRadio);
clickRegistry.addClickHandlers('yourradio', yourRadioPlugin.handleClick);
clickRegistry.addMenuHandlers('yourradioroot', yourRadioPlugin.loadStations);
