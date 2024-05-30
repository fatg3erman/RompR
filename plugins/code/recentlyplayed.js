var recentlyPlayed = function() {

	var rpl = null;
	var holders = new Array();

	return {

		open: function() {

			if (rpl == null) {
				rpl = browser.registerExtraPlugin("rpl", language.gettext("label_recentlyplayed"), recentlyPlayed);

				$("#rplfoldup").append('<div class="noselection fullwidth masonry_general" id="rplmunger"></div>');
				fetch('plugins/code/recentlyplayed.php')
					.then((response) => response.text())
					.then(data => {
						setDraggable('#rplfoldup');
						recentlyPlayed.doMainLayout(data);
					})
					.catch(err => {
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
			fetch('plugins/code/recentlyplayed.php')
				.then((response) => response.text())
				.then(data => {
					$('#rplmunger').html(data);
				})
				.catch(err => {
					debug.error("RECENTLY PLAYED","Error reloading list",data);
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
