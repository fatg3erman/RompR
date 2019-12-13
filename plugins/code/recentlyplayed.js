var recentlyPlayed = function() {

	var rpl = null;
	var holders = new Array();

	return {

		open: function() {

			if (rpl == null) {
				rpl = browser.registerExtraPlugin("rpl", language.gettext("label_recentlyplayed"), recentlyPlayed);

				$("#rplfoldup").append('<div class="noselection fullwidth tagholder" id="rplmunger"></div>');
				$.ajax({
					url: 'backends/sql/recentlyplayed.php',
					type: "POST"
				})
				.done(function(data) {
					setDraggable('#rplfoldup');
					recentlyPlayed.doMainLayout(data);
				})
				.fail(function() {
					infobar.error(language.gettext('label_general_error'));
					rpl.slideToggle('fast');
				});
			} else {
				browser.goToPlugin("rpl");
			}

		},

		doMainLayout: function(data) {
			$('#rplmunger').html(data);
			rpl.slideToggle('fast', function() {
				browser.goToPlugin("rpl");
				infobar.markCurrentTrack();
			});
		},

		reloadAll: function() {
			$.ajax({
				url: 'backends/sql/recentlyplayed.php',
				type: "POST"
			})
			.done(function(data) {
				$('#rplmunger').html(data);
			})
			.fail(function() {
				debug.error("RECENTLY PLAYED","Error reloading list");
			});
		},

		handleClick: function(element, event) {

		},

		close: function() {
			rpl = null;
		}

	}
}();

pluginManager.setAction(language.gettext("label_recentlyplayed"), recentlyPlayed.open);
recentlyPlayed.open();
