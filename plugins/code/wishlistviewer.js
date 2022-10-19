var wishlistViewer = function() {

	var wlv = null;
	var reqid = 0;
	var attributes = [];

	function removeTrackFromWl(element, command) {
		debug.log("DB_TRACKS","Remove track from database",element.next().val());
		var trackDiv = element.parent().parent();
		metaHandlers.genericAction(
			[{action: command, wltrack: element.next().val()}],
			collectionHelper.updateCollectionDisplay,
			function(data) {
				debug.error("DB TRACKS", "Failed to remove track",data);
				infobar.error(language.gettext('label_general_error'));
			}
		);
		trackDiv.fadeOut('fast');
	}

	function clearWishlist() {
		metaHandlers.genericAction(
			'clearwishlist',
			function(rdata) {
				debug.mark("DB TRACKS","Wishlist Cleared");
				loadWishlist(false);
			},
			function(data) {
				debug.error("DB TRACKS","Failed to clear wishlist for some reason",data);
				infobar.error(language.gettext('label_general_error'));
			}
		);
	}

	function searchForTrack(element) {
		reqid++;
		element.addClass('wlsch_'+reqid).makeSpinner();
		$('<div>', {id: 'wlresults_'+reqid, class: 'wishlist_results holdingcell medium_masonry_holder helpfulholder noselection'}).insertAfter(element.parent());

		attributes[reqid] = [];

		var rat = element.parent().find('.rating-icon-small').first();
		if (rat.hasClass('icon-1-stars')) {
			debug.log("WISHLIST","1 star");
			attributes[reqid].push({attribute: 'Rating', value:  1});
		} else if (rat.hasClass('icon-2-stars')) {
			debug.log("WISHLIST","2 star");
			attributes[reqid].push({attribute: 'Rating', value:  2});
		} else if (rat.hasClass('icon-3-stars')) {
			debug.log("WISHLIST","3 star");
			attributes[reqid].push({attribute: 'Rating', value:  3});
		} else if (rat.hasClass('icon-4-stars')) {
			debug.log("WISHLIST","4 star");
			attributes[reqid].push({attribute: 'Rating', value:  4});
		} else if (rat.hasClass('icon-5-stars')) {
			debug.log("WISHLIST","5 star");
			attributes[reqid].push({attribute: 'Rating', value:  5});
		}
		var tag = element.parent().find('.tracktags').first();
		if (tag.length > 0) {
			debug.info("WISHLIST","Setting Tags Attribute");
			attributes[reqid].push({attribute: 'Tags', value: tag.text().split(", ")});
		}
		metaHandlers.genericAction(
			[{
				action: 'findandreturnall',
				reqid: reqid,
				Title: element.next().val(),
				trackartist: element.next().next().val()
			}],
			function(data) {
				debug.log('WISHLIST', 'Fave Finder Results', data);
				$('.wlsch_'+reqid).stopSpinner();
				if (data.length > 0) {
					$('#wlresults_'+reqid).spotifyAlbumThing({
						classes: 'brick spotify_album_masonry selecotron',
						itemselector: 'brick',
						is_plugin: true,
						showanames: true,
						imageclass: 'jalopy',
						attributes: attributes[reqid],
						data: data
					});
				} else {
					$('#wlresults_'+reqid).html('<h3>No Tracks Found</h3>');
				}
			},
			function() {
				$('.wlsch_'+reqid).stopSpinner();
				$('#wlresults_'+reqid).html('<h3>No Tracks Found</h3>');
			}
		);
	}

	function loadWishlist(display) {
		$("#wishlistlist").load("plugins/code/getwishlist.php?sortby="+prefs.sortwishlistby, function() {
			$('[name="sortwishlistby"][value="'+prefs.sortwishlistby+'"]').prop('checked', true);
			$('[name="sortwishlistby"]').on('click', reloadWishlist);
			infobar.markCurrentTrack();
			if (display && !wlv.is(':visible')) {
				wlv.slideToggle('fast', function() {
					browser.goToPlugin("wlv");
				});
			}
		});
	}

	function reloadWishlist() {
		prefs.save({sortwishlistby: $('[name="sortwishlistby"]:checked').val()});
		loadWishlist(false);
	}

	return {

		open: function() {

			if (wlv == null) {
				wlv = browser.registerExtraPlugin("wlv", language.gettext("label_wishlist"), wishlistViewer, 'https://fatg3erman.github.io/RompR/The-Wishlist');
				$("#wlvfoldup").append('<div id="wishlistlist"></div>');
				loadWishlist(true);
			} else {
				browser.goToPlugin("wlv");
			}

		},

		handleClick: function(element, event) {
			if (element.hasClass('clickspotifywidget')) {
				let thing = $(event.target);
				while (!thing.hasClass('wishlist_results')) {
					thing = thing.parent();
				}
				thing.spotifyAlbumThing('handleClick', element);
			} else if (element.hasClass('clickremdb')) {
				removeTrackFromWl(element, 'deletewl');
			} else if (element.hasClass('clicksearchtrack')) {
				searchForTrack(element);
			} else if (element.hasClass('clickclearwishlist')) {
				clearWishlist();
			}
		},

		close: function() {
			wlv = null;
		},

		update: function() {
			if (wlv !== null) {
				loadWishlist(false);
			}
		}

	}

}();

pluginManager.setAction(language.gettext("label_viewwishlist"), wishlistViewer.open);
wishlistViewer.open();
