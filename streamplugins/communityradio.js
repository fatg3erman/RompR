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

	search: function(event, clickedElement) {
		var uri = 'streamplugins/04_communityradio.php?populate=3&order='+prefs.communityradioorderby;
		var foundterm = false;
		$('.comm_radio_searchterm').each(function() {
			if ($(this).val() != '') {
				foundterm = true;
				uri += '&'+$(this).attr('name')+'='+encodeURIComponent($(this).val());
			}
		});
		if (!foundterm) {
			uri = 'streamplugins/04_communityradio.php?populate=4';
		}
		clickRegistry.loadContentIntoTarget({
			target: $('#communitystations'),
			clickedElement: $('i[name="communityradiolist"]'),
			uri: uri
		});
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
clickRegistry.addClickHandlers('commradiosearch', communityRadioPlugin.search);
clickRegistry.addMenuHandlers('commradioroot', communityRadioPlugin.loadCommRadioRoot);
clickRegistry.addMenuHandlers('commradio', communityRadioPlugin.browse);
