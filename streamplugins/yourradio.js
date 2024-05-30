var yourRadioPlugin = {

	reloadStations: function() {
		if (!prefs.hide_radiolist) {
			clickRegistry.loadContentIntoTarget({
				target: $('#yourradiolist'),
				clickedElement: $('i[name="yourradiolist"]'),
				uri: 'api/yourradio//?populate'
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
		return 'api/yourradio//?populate';
	},

	updateStreamName: function(streamid, name, uri) {
		var data = { updatename: 1, streamid: streamid, name: name, uri: uri };
		fetch(
			"api/yourradio/",
			{
				signal: AbortSignal.timeout(5000),
				body: JSON.stringify(data),
				cache: 'no-store',
				method: 'POST',
				priority: 'low'
			}
		)
		.then(yourRadioPlugin.reloadStations)
		.catch(function(err) { debug.warn('YOURRADIO', 'Failed to update stream name', err) });
	},

	addFave: function(data) {
		data.addfave = 1;
		fetch(
			"api/yourradio/",
			{
				signal: AbortSignal.timeout(5000),
				body: JSON.stringify(data),
				cache: 'no-store',
				method: 'POST',
				priority: 'low'
			}
		)
		.then(function() {
			yourRadioPlugin.reloadStations();
			infobar.notify(language.gettext('label_addedradio'));
		})
		.catch(function(err) { debug.warn('YOURRADIO', 'Failed to add fave', err) });
	},

	removeUserStream: function(name) {
		var data = {remove: name};
		fetch(
			"api/yourradio/",
			{
				signal: AbortSignal.timeout(5000),
				body: JSON.stringify(data),
				cache: 'no-store',
				method: 'POST',
				priority: 'low'
			}
		)
		.then(yourRadioPlugin.reloadStations)
		.catch(function(err) { debug.warn('YOURRADIO', 'Failed to remove stream ', err) });
	},

	saveRadioOrder: function() {
		var radioOrder = new Array();
		$("#yourradiolist").find(".clickradioremove").each( function() {
			radioOrder.push($(this).attr('name'));
		});
		debug.log('YOURRADIO', 'Saving radio order',radioOrder);
		data = {order: radioOrder};
		fetch(
			"api/yourradio/",
			{
				signal: AbortSignal.timeout(5000),
				body: JSON.stringify(data),
				cache: 'no-store',
				method: 'POST',
				priority: 'low'
			}
		)
		.then(yourRadioPlugin.reloadStations)
		.catch(function(err) { debug.warn('YOURRADIO', 'Failed to save order ', err) });
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
