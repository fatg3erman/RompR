var unplayabletracks = function() {

    var upl = null;
    var holder;
    var reqid = 0;
    var databits = new Array();
    var trawler = null;

    function removeTrackFromDb(element, command) {
	    debug.log("DB_TRACKS","Remove track from database",element.next().val());
		var trackDiv = element.parent().parent();
	    metaHandlers.genericAction(
			[{action: command, ttid: element.next().val()}],
	       	collectionHelper.updateCollectionDisplay,
	        function() {
	            debug.log("DB TRACKS", "Failed to remove track");
	            infobar.error(language.gettext('label_general_error'));
	        }
	    );
		trackDiv.fadeOut('fast');
	}

    function makeHolder() {
        holder = $('<div>', {id: 'unplayabletracks', class: 'holdingcell masonified2 helpfulholder noselection'}).appendTo('#uplfoldup');
    }

    function getUnplayableTracks() {
        if (player.canPlay('spotify')) {
            holder.load('plugins/code/getunplayable.php');
        } else {
            holder.html('<h3>'+language.gettext('label_onlyspotify')+'</h3>');
        }
    }

    function searchForTrack(element) {
		reqid++;
		element.addClass('upsch_'+reqid).makeSpinner();
		if (trawler == null) {
			trawler = new faveFinder(true);
			trawler.setPriorities(['spotify']);
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
			]
		}

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

		trawler.findThisOne(databits[reqid].data[databits[reqid].index], unplayabletracks.updateDatabase);
	}

    function chooseNew(clickedElement) {
		var key = clickedElement.attr('romprkey');
		$('#upsearch_'+key).find('.importbutton, .playbutton').fadeOut('fast');
		clickedElement.next().fadeIn('fast');
		clickedElement.prev().fadeIn('fast');
	}

    function importRow(element) {
		var key = element.parent().prev().attr("romprkey");
		var index = element.parent().prev().attr('romprindex');
		debug.log("WISHLIST","Importing",databits[key], databits[key].data[index]);
		doSqlStuff(databits[key], databits[key].data[index], false);
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
			if (element.hasClass('clickremdb')) {
				removeTrackFromDb(element, 'deleteid');
			} else if (element.hasClass('clicksearchtrack')) {
				searchForTrack(element);
			} else if (element.hasClass('choosenew')) {
				chooseNew(element);
			} else if (element.hasClass('importrow')) {
				importRow(element);
			} else if (element.hasClass('dropchoices')) {
				$('#upchoices_'+element.attr('name')).slideToggle('fast');
			}
		},

        updateDatabase: function(results) {
			debug.log("UNPLAYABLE","Found A Track",results);
			databits[results[0].reqid].index = 0;
			databits[results[0].reqid].data = results;
			var element = $('.upsch_'+results[0].reqid);
			var trackDiv = element.parent().parent();
			var resultsDiv = $('<div>', {id: 'upsearch_'+results[0].key, class: 'toggledown'}).appendTo(trackDiv);
			if (results.length > 0 && results[0].uri) {
				var dropper = $("<div>", {class: 'containerbox fixed'}).insertBefore(resultsDiv);
				dropper.append('<i class="openmenu icon-menu clickicon fixed collectionicon" name="upsearch_'+results[0].reqid+'"></i>');
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
			element.removeClass('upsch_'+results[0].reqid).stopSpinner().remove();
			resultsDiv.find('.invisible.importbutton').first().fadeIn('fast');
			resultsDiv.find('.invisible.playbutton').first().fadeIn('fast');
		}

    }

}();

pluginManager.setAction(language.gettext("label_unplayabletracks"), unplayabletracks.open);
unplayabletracks.open();
