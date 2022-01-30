var somaFmPlugin = {

	loadSomaFM: function() {
		return "streamplugins/01_somafm.php?populate";
	},

	loadCallback:function() {
		$('#somafm_qualityselector').val(prefs.somafm_quality);
		somaFmPlugin.setQuality();
	},

	setQuality: function() {
		var quality = $('#somafm_qualityselector').val();
		debug.log('SOMAFM', 'Changing Quality To',quality);
		$('#somafmlist div.soma-fm').each(function() {
			var self = $(this);
			var inp = self.find('input[name="'+quality+'"]');
			if (inp.length > 0) {
				debug.log('SOMAFM', 'Using', inp.val());
				self.attr('name', inp.val());
				self.removeClass('stream-disabled').addClass('clickstream playable');
			} else {
				debug.log('SOMAFM', 'Could not find that quality');
				self.removeClass('clickstream playable').addClass('stream-disabled');
			}
		});
	}

}

clickRegistry.addMenuHandlers('somafmroot', somaFmPlugin.loadSomaFM);
clickRegistry.addLoadCallback('somafmroot', somaFmPlugin.loadCallback);
$(document).on('change', '#somafm_qualityselector', somaFmPlugin.setQuality);