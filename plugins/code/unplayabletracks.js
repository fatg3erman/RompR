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
		if (trawler == null) {
			trawler = new faveFinder(true);
			trawler.setPriorities(['spotify', 'youtube']);
			trawler.setCheckDb(false);
			trawler.setExact(false);
		}
		databits[reqid] = {
			index: 0,
			data: [
				{
					Title: element.next().val(),
					trackartist: element.next().next().val(),
					key: reqid,
					reqid: reqid
				}
			]
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
		element.parent().parent().parent().parent().css({opacity: '0.2'});
		element.remove();
		doSqlStuff(databits[key].data[index], false);
	}

	function doSqlStuff(data, callback) {
		data.action = 'seturi';
		dbQueue.request([data], collectionHelper.updateCollectionDisplay,
			function(rdata) {
				infobar.error(language.gettext('label_general_error'));
				debug.warn("WISHLIST","Failure",rdata);
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
			debug.debug("UNPLAYABLE","Found A Track",results);
			databits[results[0].reqid].index = 0;
			databits[results[0].reqid].data = results;
			var element = $('.upsch_'+results[0].reqid);
			var trackDiv = element.parent().parent();
			var resultsDiv = $('<div>', {id: 'upsearch_'+results[0].key, class: 'toggledown'}).appendTo(trackDiv);
			if (results.length > 0 && results[0].file) {
				var dropper = $("<div>", {class: 'containerbox fixed', style: 'margin-top:1em'}).insertBefore(resultsDiv);
				dropper.append('<i class="openmenu icon-menu clickicon fixed inline-icon" name="upsearch_'+results[0].reqid+'"></i>');
				for (var i = 0; i < results.length; i++) {
					var data = results[i];
					var firstTrack = $('<div>', {class: 'containerbox vertical-centre underline', style: 'margin: 0'}).appendTo(resultsDiv);
					var trackDetails = $('<div>', {romprindex: i, romprkey: data.reqid, class: 'backhi plugclickable infoclick choosenew ninesix indent expand'}).html(trawler.trackHtml(data, true)).appendTo(firstTrack);
					firstTrack.append('<div class="fixed invisible importbutton"><button class="plugclickable infoclick importrow">Import</button></div>');
					firstTrack.prepend('<div class="fixed invisible playbutton"><i class="icon-no-response-playbutton clickicon playable inline-icon" name="'+data.file+'"></i></div>');
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
