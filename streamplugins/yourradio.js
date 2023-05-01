var yourRadioPlugin = {

	reloadStations: function() {
		if (!prefs.hide_radiolist) {
			clickRegistry.loadContentIntoTarget({
				target: $('#yourradiolist'),
				clickedElement: $('i[name="yourradiolist"]'),
				uri: 'api/yourradio/?populate'
			});
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
		return 'api/yourradio/?populate';
	},

	updateStreamName: async function(streamid, name, uri) {
		await $.ajax({
			type: 'POST',
			data: { updatename: 1, streamid: streamid, name: name, uri: uri },
			url: "api/yourradio"
		});
		yourRadioPlugin.reloadStations();
	},

	addFave: async function(data) {
		data.addfave = 1;
		await $.ajax({
			type: 'POST',
			data: data,
			url: "api/yourradio/"
		});
		yourRadioPlugin.reloadStations();
		infobar.notify(language.gettext('label_addedradio'));
	},

	removeUserStream: async function(name) {
		await $.ajax({
			type: 'POST',
			data: {remove: name},
			url: "api/yourradio/"
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
				url: 'api/yourradio/',
				data: {'order[]': radioOrder}
		});
	},

	handleDropRadio: function() {
		setTimeout(function() { yourRadioPlugin.loadSuppliedStation }, 200);
	},

	loadSuppliedStation: function() {
		if ($("#yourradioinput").val() != '') {
			player.controller.addTracks([{
				type: "stream",
				url: $("#yourradioinput").val(),
				image: null,
				station: null
			}], playlist.playFromEnd(), null, false);
		}
	},

	handleClick: function(event, clickedElement) {
		if (clickedElement.hasClass("clickradioremove")) {
			yourRadioPlugin.removeUserStream(clickedElement.attr("name"));
		}
	}

}

$(document).on(prefs.click_event, '[name="spikemilligan"]', yourRadioPlugin.loadSuppliedStation);
$(document).on("drop", "#anaconda", yourRadioPlugin.handleDropRadio);
clickRegistry.addClickHandlers('yourradio', yourRadioPlugin.handleClick);
clickRegistry.addMenuHandlers('yourradioroot', yourRadioPlugin.loadStations);
