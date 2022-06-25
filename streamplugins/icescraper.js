var icecastPlugin = {

	// loadIcecastRoot: function() {
	// 	return "streamplugins/85_iceScraper.php?populate";
	// },

	// loadIcecastSearch: function() {
	// 	return "streamplugins/85_iceScraper.php?populate=1&searchfor="+encodeURIComponent($('input[name="searchfor"]').val());
	// },

	iceSearch: async function(terms, domains) {
		// Note, terms.any is an array but encodeURIComponent will join them with a ,
		await clickRegistry.loadContentIntoTarget({
			target: $('#icecastlist'),
			clickedElement: $('#globalsearch'),
			uri: "streamplugins/85_iceScraper.php",
			data: {populate: 1, searchfor: encodeURIComponent(terms.any)}
		});
		searchManager.make_search_title('icecastlist', 'Icecast Radio');
	},

	handleClick: function(event, clickedElement) {
		if (clickedElement.hasClass('clickicepager')) {
			clickRegistry.loadContentIntoTarget({
				target: $('#icecastlist'),
				clickedElement: $('#globalsearch'),
				uri: "streamplugins/85_iceScraper.php",
				data: {populate: 1, path: clickedElement.attr('name')}
			});
		}
	}

}

// $(document).on('click', '[name="cornwallis"]', icecastPlugin.iceSearch);
clickRegistry.addClickHandlers('icescraper', icecastPlugin.handleClick);
// clickRegistry.addMenuHandlers('icecastroot', icecastPlugin.loadIcecastRoot);
searchManager.add_search_plugin('icecastsearch', icecastPlugin.iceSearch, ['radio']);
