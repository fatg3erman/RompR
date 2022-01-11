var cantataImporter = function() {

	var cani = null;
	var polltimer = null;

	async function startPolling() {
		clearTimeout(polltimer);
		var data = await $.ajax({
			url: 'plugins/code/pollcani.php',
			type: 'GET',
			dataType: 'json'
		});
		debug.log('CANIMPORTER', data);
		var percent = (data.done / data.total) * 100;
		$('#caniprogress').rangechooser('setRange', {min: 0, max: percent});
		if (data.message) {
			$('#caniinfo').html(data.message);
		}
		if (percent < 100) {
			setTimeout(startPolling, 1000);
		}
	}

	return {

		open: function() {
			if (cani == null) {
				cani = browser.registerExtraPlugin("cani", language.gettext("label_cantataimporter"), cantataImporter, 'https://fatg3erman.github.io/RompR/Cantata-Importer');
				$("#canifoldup").append('<div class="noselection fullwidth" id="canimunger"></div>');
				$('#canimunger').append('<div class="textcentre">This will import all your ratings from Cantata into RompR. Existing ratings in RompR will be replaced</div>');
				$("#canimunger").append('<div style="height:1em;max-width:80%;margin:auto" id="caniprogress"></div>');
				$("#canimunger").append('<div style="padding:4px;max-width:80%;margin:auto;text-align:center;font-size:80%;margin-bottom:1em" id="caniinfo"></div>');
				$('#canimunger').append('<div class="textcentre"><button id="fuckboris" onclick="cantataImporter.go()">Start</button></div>');
				cani.show();
				browser.goToPlugin("cani");
			} else {
				browser.goToPlugin("cani");
			}

		},

		handleClick: function(element, event) {

		},

		close: function() {
			clearTimeout(polltimer);
			cani = null;
		},

		go: function() {
			$('#fuckboris').remove();
			$("#caniprogress").rangechooser({
				range: 100,
				interactive: false,
				startmax: 0,
			});
			$.ajax({
				url: 'plugins/code/canimporter.php',
				type: "POST",
				data: {action: 'start'}
			})
			.done(function(data) {
				debug.mark('CANIMPORTER', 'Done');
			})
			.fail(function() {
				infobar.error(language.gettext('label_general_error'));
			});
			setTimeout(startPolling, 1000);
		}

	}

}();

pluginManager.setAction(language.gettext("label_cantataimporter"), cantataImporter.open);
cantataImporter.open();
