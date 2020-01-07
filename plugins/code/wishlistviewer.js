var wishlistViewer = function() {

	var wlv = null;
	var trawler = null;
	var databits = new Array();
	var reqid = 0;

	function removeTrackFromWl(element, command) {
		debug.log("DB_TRACKS","Remove track from database",element.next().val());
		var trackDiv = element.parent().parent();
		metaHandlers.genericAction(
			[{action: command, wltrack: element.next().val()}],
			collectionHelper.updateCollectionDisplay,
			function() {
				debug.error("DB TRACKS", "Failed to remove track");
				infobar.error(language.gettext('label_general_error'));
			}
		);
		trackDiv.fadeOut('fast');
	}

	function clearWishlist() {
		metaHandlers.genericAction(
			'clearwishlist',
			function(rdata) {
				debug.log("DB TRACKS","Wishlist Cleared");
				loadWishlist(false);
			},
			function() {
				debug.log("DB TRACKS","Failed to clear wishlist for some reason");
				infobar.error(language.gettext('label_general_error'));
			}
		);
	}

	function searchForTrack(element) {
		reqid++;
		element.addClass('wlsch_'+reqid).makeSpinner();
		if (trawler == null) {
			trawler = new faveFinder(true);
			trawler.setPriorities([]);
			trawler.setCheckDb(false);
			trawler.setExact(false);
		}
		databits[reqid] = {
			index: 0,
			data: [
				{
					title: element.next().val(),
					artist: element.next().next().val(),
					key: reqid,
					reqid: reqid
				}
			],
			attributes: new Array()
		}

		// We need to ensure the ratings and tags get added if the track already exists
		// or we create a new one.
		// We also need to ensure that the wishlist version gets removed from the database
		databits[reqid].attributes = new Array();
		var rat = element.parent().find('.rating-icon-small').first();
		if (rat.hasClass('icon-1-stars')) {
			debug.log("WISHLIST","1 star");
			databits[reqid].attributes.push({attribute: 'Rating', value:  1});
		} else if (rat.hasClass('icon-2-stars')) {
			debug.log("WISHLIST","2 star");
			databits[reqid].attributes.push({attribute: 'Rating', value:  2});
		} else if (rat.hasClass('icon-3-stars')) {
			debug.log("WISHLIST","3 star");
			databits[reqid].attributes.push({attribute: 'Rating', value:  3});
		} else if (rat.hasClass('icon-4-stars')) {
			debug.log("WISHLIST","4 star");
			databits[reqid].attributes.push({attribute: 'Rating', value:  4});
		} else if (rat.hasClass('icon-5-stars')) {
			debug.log("WISHLIST","5 star");
			databits[reqid].attributes.push({attribute: 'Rating', value:  5});
		}
		var tag = element.parent().find('.tracktags').first();
		if (tag.length > 0) {
			debug.info("WISHLIST","Setting Tags Attribute");
			databits[reqid].attributes.push({attribute: 'Tags', value: tag.text().split(", ")});
		}
		trawler.findThisOne(databits[reqid].data[databits[reqid].index], wishlistViewer.updateDatabase);
	}

	function doSqlStuff(parentdata, data, callback) {
		data.action = 'add';
		data.attributes = parentdata.attributes;
		dbQueue.request([data], collectionHelper.updateCollectionDisplay,
			function(rdata) {
				infobar.error(language.gettext('label_general_error'));
				debug.warn("WISHLIST","Failure");
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

	function chooseNew(clickedElement) {
		var key = clickedElement.attr('romprkey');
		$('#wlsearch_'+key).find('.importbutton, .playbutton').fadeOut('fast');
		clickedElement.next().fadeIn('fast');
		clickedElement.prev().fadeIn('fast');
	}

	function importRow(element) {
		var key = element.parent().prev().attr("romprkey");
		var index = element.parent().prev().attr('romprindex');
		debug.log("WISHLIST","Importing",databits[key], databits[key].data[index]);
		doSqlStuff(databits[key], databits[key].data[index], false);
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
			if (element.hasClass('clickremdb')) {
				removeTrackFromWl(element, 'deletewl');
			} else if (element.hasClass('clicksearchtrack')) {
				searchForTrack(element);
			} else if (element.hasClass('choosenew')) {
				chooseNew(element);
			} else if (element.hasClass('importrow')) {
				importRow(element);
			} else if (element.hasClass('clickclearwishlist')) {
				clearWishlist();
			} else if (element.hasClass('dropchoices')) {
				$('#wlchoices_'+element.attr('name')).slideToggle('fast');
			}
		},

		close: function() {
			wlv = null;
		},

		updateDatabase: function(results) {
			debug.log("WISHLIST","Found A Track",results);
			databits[results[0].reqid].index = 0;
			databits[results[0].reqid].data = results;
			var element = $('.wlsch_'+results[0].reqid);
			var trackDiv = element.parent().parent();
			var resultsDiv = $('<div>', {id: 'wlsearch_'+results[0].key, class: 'toggledown'}).appendTo(trackDiv);
			if (results.length > 0 && results[0].uri) {
				var dropper = $("<div>", {class: 'containerbox fixed'}).insertBefore(resultsDiv);
				dropper.append('<i class="openmenu icon-menu clickicon fixed collectionicon" name="wlsearch_'+results[0].reqid+'"></i>');
				for (var i = 0; i < results.length; i++) {
					var data = results[i];
					var firstTrack = $('<div>', {class: 'containerbox dropdown-container'}).appendTo(resultsDiv);
					var trackDetails = $('<div>', {romprindex: i, romprkey: data.reqid, class: 'backhi plugclickable infoclick choosenew ninesix indent padright expand'}).html(trawler.trackHtml(data, false)).appendTo(firstTrack);
					firstTrack.append('<div class="fixed invisible importbutton"><button class="plugclickable infoclick importrow">Import</button></div>');
					firstTrack.prepend('<div class="fixed invisible playbutton"><i class="icon-no-response-playbutton clickicon playable collectionicon" name="'+data.uri+'"></i></div>');
				}
			} else {
				resultsDiv.append('<div class="expand"><b><i>'+language.gettext("label_notfound")+'</i></b></div>');
			}
			element.removeClass('wlsch_'+results[0].reqid).stopSpinner().remove();
			// resultsDiv.find('.invisible').first().fadeIn('fast');
			resultsDiv.find('.invisible.importbutton').first().fadeIn('fast');
			resultsDiv.find('.invisible.playbutton').first().fadeIn('fast');
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
