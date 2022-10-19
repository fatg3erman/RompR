var unplayabletracks = function() {

	var upl = null;
	var holder;
	var reqid = 0;
	var databits = new Array();

	function removeTrackFromDb(element, command) {
		debug.log("DB_TRACKS","Remove track from database",element.next().val());
		var trackDiv = element.parent().parent();
		metaHandlers.genericAction(
			[{action: command, ttid: element.next().val()}],
			collectionHelper.updateCollectionDisplay,
			function(data) {
				debug.error("DB TRACKS", "Failed to remove track",data);
				infobar.error(language.gettext('label_general_error'));
			}
		);
		trackDiv.fadeOut('fast');
	}

	function makeHolder() {
		holder = $('<div>', {id: 'unplayabletracks', class: 'holdingcell medium_masonry_holder helpfulholder noselection'}).appendTo('#uplfoldup');
	}

	function getUnplayableTracks() {
		holder.load('plugins/code/getunplayable.php');
	}

	function searchForTrack(element) {
		reqid = element.next().next().next().val();
		element.addClass('upsch_'+reqid).makeSpinner();
		$('<div>', {id: 'upresults_'+reqid, class: 'unplayable_results holdingcell medium_masonry_holder helpfulholder noselection'}).insertAfter(element.parent());

		metaHandlers.genericAction(
			[{
				action: 'findandreturnall',
				Title: element.next().val(),
				trackartist: element.next().next().val()
			}],
			function(data) {
				debug.log('WISHLIST', 'Fave Finder Results', data);
				$('.upsch_'+reqid).stopSpinner();
				if (data.length > 0) {
					$('#upresults_'+reqid).spotifyAlbumThing({
						classes: 'brick spotify_album_masonry selecotron',
						itemselector: 'brick',
						is_plugin: true,
						showanames: true,
						imageclass: 'jalopy',
						replace: reqid,
						data: data
					});
				} else {
					$('#upresults_'+reqid).html('<h3>No Tracks Found</h3>');
				}
			},
			function() {
				$('.upsch_'+reqid).stopSpinner();
				$('#upresults_'+reqid).html('<h3>No Tracks Found</h3>');
			}
		);

	}

	return {

		open: function() {
			if (upl == null) {
				upl = browser.registerExtraPlugin("upl", language.gettext("label_unplayabletracks"), unplayabletracks, 'https://fatg3erman.github.io/RompR/Unplayable-Tracks');
				makeHolder();
				getUnplayableTracks();
				upl.slideToggle('fast');
				browser.goToPlugin("upl");
			} else {
				browser.goToPlugin('upl');
			}
		},

		close: function() {
			holder.remove();
			upl = null;
		},

		handleClick: function(element, event) {
			if (element.hasClass('clickspotifywidget')) {
				let thing = $(event.target);
				while (!thing.hasClass('unplayable_results')) {
					thing = thing.parent();
				}
				thing.spotifyAlbumThing('handleClick', element);
			} else if (element.hasClass('clickremdb')) {
				removeTrackFromDb(element, 'deleteid');
			} else if (element.hasClass('clicksearchtrack')) {
				searchForTrack(element);
			}
		},

	}

}();

pluginManager.setAction(language.gettext("label_unplayabletracks"), unplayabletracks.open);
unplayabletracks.open();
