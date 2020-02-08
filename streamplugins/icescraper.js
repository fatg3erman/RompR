var icecastPlugin = {

	loadIcecastRoot: function() {
		return "streamplugins/85_iceScraper.php?populate";
	},

	loadIcecastSearch: function() {
		return "streamplugins/85_iceScraper.php?populate=1&searchfor="+encodeURIComponent($('input[name="searchfor"]').val());
	},

	iceSearch: function() {
		clickRegistry.loadContentIntoTarget({
			target: $('#icecastlist'),
			clickedElement: $('.openmenu[name="icecastlist"]'),
			uri: "streamplugins/85_iceScraper.php",
			data: {populate: 1, searchfor: encodeURIComponent($('input[name="searchfor"]').val())}
		});
	},

	handleClick: function(event, clickedElement) {
		if (clickedElement.hasClass('clickicepager')) {
			clickRegistry.loadContentIntoTarget({
				target: $('#icecastlist'),
				clickedElement: $('.openmenu[name="icecastlist"]'),
				uri: "streamplugins/85_iceScraper.php",
				data: {populate: 1, path: clickedElement.attr('name')}
			});
		}
	}

}

$(document).on('click', '[name="cornwallis"]', icecastPlugin.iceSearch);
clickRegistry.addClickHandlers('icescraper', icecastPlugin.handleClick);
clickRegistry.addMenuHandlers('icecastroot', icecastPlugin.loadIcecastRoot);
