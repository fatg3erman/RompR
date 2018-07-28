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
	            debug.log("DB TRACKS", "Failed to remove track");
	            infobar.notify(infobar.ERROR, "Failed to remove track!");
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
				infobar.notify(infobar.ERROR, "Failed. Sorry.");
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
			debug.mark("WISHLIST","Setting Tags Attribute");
			databits[reqid].attributes.push({attribute: 'Tags', value: tag.text().split(", ")});
		}
		trawler.findThisOne(databits[reqid].data[databits[reqid].index], wishlistViewer.updateDatabase);
	}

	function chooseNew(clickedElement) {
		var key = clickedElement.parent().attr("id");
		key = key.replace(/wlchoices_/, "");
		var index = clickedElement.attr("name");
		clickedElement.html(trawler.trackHtml(databits[key].data[databits[key].index]), false);
		clickedElement.attr("name", databits[key].index);
		databits[key].index = index;
		var html = trawler.trackHtml(databits[key].data[index], false) +
		'<br /><span class="clickicon tiny plugclickable dropchoices infoclick" name="'+key+'"> '+
			language.gettext("label_moreresults", [(databits[key].data.length - 1)]) + '</span></div>';
		$("#wltrackfound"+key).html(html);
	}

	function importRow(element) {
		var clickedElement = element.prev().attr("id");
		debug.log("WISHLIST","Import row",clickedElement);
		var key = parseInt(clickedElement.replace('wltrackfound',''));
		debug.log("WISHLIST","Importing",databits[key], databits[key].data[databits[key].index]);
		doSqlStuff(databits[key], databits[key].data[databits[key].index], false);
	}

	function doSqlStuff(parentdata, data, callback) {
		data.action = 'add';
		data.attributes = parentdata.attributes;
		dbQueue.request([data], collectionHelper.updateCollectionDisplay,
            function(rdata) {
	            infobar.notify(infobar.ERROR,"Track Import Failed");
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
					// wlv.find('.tooltip').tipTip({delay: 500, edgeOffset: 8});
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
			} else if (element.hasClass('clickstream')) {
				onSourcesDoubleClicked(event);
			}
		},

		close: function() {
			wlv = null;
		},

		updateDatabase: function(results) {
			debug.log("WISHLIST","Found A Track",results);
			var data = results[0];
			databits[data.reqid].index = 0;
			databits[data.reqid].data = results;
			var element = $('.wlsch_'+data.reqid);
			var trackDiv = element.parent().parent();
			var html;
			var choicesDiv;
			if (data.uri) {
				temphtml = trawler.trackHtml(data, false);
				if (results.length > 1) {
					temphtml += '<br /><span class="clickicon tiny plugclickable dropchoices infoclick" name="'+data.key+'"> '+
								language.gettext("label_moreresults", [(results.length - 1)]) +
								'</span>';
					choicesDiv = $('<div>', {id: 'wlchoices_'+data.key, class: "invisible ninesix indent padright"});
					for (var i = 1; i < results.length; i++) {
						choicesDiv.append('<div class="backhi plugclickable infoclick choosenew" name="'+i+'" style="margin-bottom:4px">'+
											trawler.trackHtml(results[i], false))+'</div>';
					}
				}
				html = '<div class="containerbox expand"><div id="wltrackfound'+data.key+'" class="indent expand invisible">'+temphtml+'</div>'+'<button class="fixed plugclickable infoclick importrow">Import</button></div>';
			} else {
				html = '<div id="wltrackfound'+data.key+'" class="expand invisible"><b><i>'+language.gettext("label_notfound")+'</i></b></div>';
			}
			trackDiv.append(html);
			if (choicesDiv) {
				trackDiv.append(choicesDiv);
			}
			element.removeClass('wlsch_'+data.reqid).stopSpinner().remove();
			trackDiv.find('.invisible').first().fadeIn('fast');

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
