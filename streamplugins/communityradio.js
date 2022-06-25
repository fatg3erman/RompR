var communityRadioPlugin = {

	pagenum: 0,

	loadCommRadioRoot: function() {
		return 'streamplugins/04_communityradio.php?populate=1&page=0&order=none';
	},

	page: function(clickedElement) {
		var target = clickedElement;
		while (!(target.hasClass('is-albumlist') || target.hasClass('invisible'))) {
			target = target.parent();
		}
		target.children('input').first().clone().insertBefore(clickedElement);
		target.children('input').last().clone().insertBefore(clickedElement);
		communityRadioPlugin.pagenum = clickedElement.attr('name');
		clickRegistry.loadContentIntoTarget({
			target: target,
			clickedElement: clickedElement
		});
	},

	search: async function(terms, domains) {
		var uri = 'streamplugins/04_communityradio.php?populate=3&order='+prefs.communityradioorderby;
		$.each(terms, function(i, v) {
			let term = (i == 'any') ? 'name' : i;
			// Note, terms.whatever is an array but encodeURIComponent will join them with a ,
			uri += '&'+term+'='+encodeURIComponent(v);
		});
		await clickRegistry.loadContentIntoTarget({
			target: $('#commradio_search'),
			clickedElement: $('button[name="globalsearch"]'),
			uri: uri
		});
		searchManager.make_search_title('commradio_search', 'Community Radio Browser');
	},

	browse: function(clickedElement, menutoopen) {
		var url = clickedElement.prev().prev().val();
		var title = clickedElement.prev().val();
		var uri = "streamplugins/04_communityradio.php?populate=2&url="+url+'&order='+prefs.communityradioorderby+'&page='+communityRadioPlugin.pagenum+'&title='+title;
		communityRadioPlugin.pagenum = 0;
		return uri;
	},

	handleClick: function(event, clickedElement) {
		if (clickedElement.hasClass('clickcommradiopager')) {
			communityRadioPlugin.page(clickedElement);
		}
	}
}

clickRegistry.addClickHandlers('commradio', communityRadioPlugin.handleClick);
// clickRegistry.addClickHandlers('commradiosearch', communityRadioPlugin.search);
clickRegistry.addMenuHandlers('commradioroot', communityRadioPlugin.loadCommRadioRoot);
clickRegistry.addMenuHandlers('commradio', communityRadioPlugin.browse);
searchManager.add_search_plugin('commradiosearch', communityRadioPlugin.search, ['radio']);
