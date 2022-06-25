var tuneinRadioPlugin = {

	loadBigRadio: function(clickedElement, menutoopen) {
		return "streamplugins/03_tuneinradio.php?populate=2";
	},

	search: async function(terms, domains) {
		// Note, terms.any is an array but encodeURIComponent will join them with a ,
		var uri = "streamplugins/03_tuneinradio.php?populate=2&search="+encodeURIComponent(terms.any)+'&domains='+domains.join(',');
		await clickRegistry.loadContentIntoTarget({
			target: $('#tunein_search'),
			clickedElement: $('button[name="globalsearch"]'),
			uri: uri
		});
		searchManager.make_search_title('tunein_search', 'Tunein Search');
	},

	browse: function(clickedElement, menutoopen) {
		var url = clickedElement.prev().prev().val();
		var title = clickedElement.prev().val();
		return "streamplugins/03_tuneinradio.php?populate=2&url="+url+'&title='+title+'&target='+menutoopen;
	}

}

// clickRegistry.addClickHandlers('tunein', tuneinRadioPlugin.handleClick);
clickRegistry.addMenuHandlers('tuneinroot', tuneinRadioPlugin.loadBigRadio);
clickRegistry.addMenuHandlers('tunein', tuneinRadioPlugin.browse);
searchManager.add_search_plugin('tuneinsearch', tuneinRadioPlugin.search, ['podcasts', 'radio']);
