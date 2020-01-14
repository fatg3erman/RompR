var tuneinRadioPlugin = {

	loadBigRadio: function(clickedElement, menutoopen) {
		return "streamplugins/03_tuneinradio.php?populate=2";
	},

	handleClick: function(event, clickedElement) {
		if (clickedElement.hasClass("tuneinsearchbutton")) {
			var term = $('[name="tuneinsearcher"]').val();
			if (term) {
				var uri = "streamplugins/03_tuneinradio.php?populate=2&search="+encodeURIComponent(term);
			} else {
				var uri = tuneinRadioPlugin.loadBigRadio();
			}
			clickRegistry.loadContentIntoTarget($('#tuneinlist'), $('i[name="tuneinlist"]'), true, uri);
		}
	},

	browse: function(clickedElement, menutoopen) {
		var url = clickedElement.prev().prev().val();
		var title = clickedElement.prev().val();
		return "streamplugins/03_tuneinradio.php?populate=2&url="+url+'&title='+title+'&target='+menutoopen;
	}

}

clickRegistry.addClickHandlers('tunein', tuneinRadioPlugin.handleClick);
clickRegistry.addMenuHandlers('tuneinroot', tuneinRadioPlugin.loadBigRadio);
clickRegistry.addMenuHandlers('tunein', tuneinRadioPlugin.browse);
