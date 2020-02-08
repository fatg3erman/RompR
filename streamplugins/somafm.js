var somaFmPlugin = {

	loadSomaFM: function() {
		return "streamplugins/01_somafm.php?populate";
	}

}

clickRegistry.addMenuHandlers('somafmroot', somaFmPlugin.loadSomaFM);
