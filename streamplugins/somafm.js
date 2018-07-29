var somaFmPlugin = {

	loadSomaFM: function() {
	    if ($("#somafmlist").hasClass('notfilled')) {
	    	$('i[name="somafmlist"]').makeSpinner();
	        $("#somafmlist").load("streamplugins/01_somafm.php?populate", function( ) {
				$('i[name="somafmlist"]').stopSpinner();
				$('#somafmlist').removeClass('notfilled');
				layoutProcessor.postAlbumActions();
	        });
		}
	}

}

menuOpeners['somafmlist'] = somaFmPlugin.loadSomaFM;
