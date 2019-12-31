var collectionHelper = function() {

	var monitortimer = null;
	var monitorduration = 1000;
	var update_load_timer = null;
	var returned_data = new Array();
	var return_callback = null;
	var update_timer = null;
	var notify = false;

	function scanFiles(cmd) {
		debug.log('GENERAL','Scanning Files');
		collectionHelper.disableCollectionUpdates();
		collectionHelper.prepareForLiftOff(language.gettext("label_updating"));
		collectionHelper.markWaitFileList(language.gettext("label_updating"));
		uiHelper.prepareCollectionUpdate();
		debug.log("PLAYER","Scanning Files",cmd,prefs.player_backend);
		debug.shout("PLAYER","Scanning using",cmd);
		player.controller.do_command_list([[cmd]], function() {
			update_load_timer = setTimeout( pollAlbumList, 2000);
			player.controller.checkProgress();
		});
	}

	function pollAlbumList() {
		clearTimeout(update_load_timer);
		debug.log('GENERAL','Polling Collection Rebuild')
		$.getJSON("player/mpd/postcommand.php", checkPoll);
	}

	function checkPoll(data) {
		if (data.updating_db) {
			debug.log('GENERAL','Still updating collection');
			update_load_timer = setTimeout( pollAlbumList, 1000);
		} else {
			debug.log('GENERAL','Player rescan is complete');
			refreshCollection();
			loadFileBrowser();
		}
	}

	function refreshCollection() {
		// Collection updates are done completely asynchronously. All this does is start it off, it does not wait for
		// it to complete, since browsers retry automatically after some time (2 minutes seems to be the norm)
		// and there's no way to stop that.
		// We rely on the update monitor to keep polling the server until it gets 'RompR Is Done'
		// at which point it will load the collection.
		debug.mark('GENERAL', 'Initiating Collection Rebuild');
		var albums = 'albums.php?rebuild=yes';
		$.ajax({
			type: "GET",
			url: albums,
			timeout: prefs.collection_load_timeout,
			dataType: "html",
			cache: false
		})
		.done(function() {
			debug.mark('GENERAL','Collection Rebuild has Started. Polling From Here.');
			monitortimer = setTimeout(checkUpdateMonitor,monitorduration);
		})
		.fail(function(data) {
			debug.error('GENERAL','Collection Rebuild Did Not Work!',data);
			var msg = language.gettext('error_collectionupdate');
			if (data.responseText) {
				msg += ' - '+data.responseText;
			}
			infobar.error(msg);
			infobar.removenotify(notify);
			loadCollection();
		});
	}

	function checkUpdateMonitor() {
		$.ajax({
			type: "GET",
			url: 'utils/checkupdateprogress.php',
			dataType: 'json',
		})
		.done(function(data) {
			debug.trace("UPDATE",data);
			if (data.current == 'RompR Is Done') {
				debug.mark('GENERAL', 'Collection Update Finished');
				infobar.notify(language.gettext('label_updatedone'));
				infobar.removenotify(notify);
				loadCollection();
			} else {
				$('#updatemonitor').html(data.current);
				if (player.updatingcollection) {
					monitortimer = setTimeout(checkUpdateMonitor,monitorduration);
				}
			}
		})
		.fail(function(data) {
			debug.log("UPDATE","ERROR",data);
			if (player.updatingcollection) {
				monitortimer = setTimeout(checkUpdateMonitor,monitorduration);
			}
		});
	}

	function loadCollection() {
		if (!prefs.hide_albumlist) {
			var albums = 'albums.php?item='+collectionHelper.collectionKey('a');
			debug.log("GENERAL","Loading Collection from URL",albums);
			$.ajax({
				type: "GET",
				url: albums,
				timeout: prefs.collection_load_timeout,
				dataType: "html",
				cache: false
			})
			.done(function(data) {
				debug.log('GENERAL','Collection Loaded');
				$("#collection").html(data);
				// player.collectionLoaded = true;
				if ($('#emptycollection').length > 0) {
					player.collection_is_empty = true;
					$('#collectionbuttons').show();
					prefs.save({ collectioncontrolsvisible: true });
					$('[name="donkeykong"]').makeFlasher({flashtime: 0.5, repeats: 3});
				} else {
					player.collection_is_empty = false;
				}
				data = null;
				collectionHelper.scootTheAlbums($("#collection"));
				uiHelper.doThingsAfterDisplayingListOfAlbums($('#collection'));
				collectionHelper.enableCollectionUpdates();
				loadAudiobooks();
			})
			.fail(function(data) {
				collectionHelper.disableCollectionUpdates();
				var html = '<p align="center"><b><font color="red">Failed To Generate Collection</font></b></p>';
				if (data.responseText) {
					html += '<p align="center">'+data.responseText+'</p>';
				}
				if (data.statusText) {
					html += '<p align="center">'+data.statusText+'</p>';
				}
				html += '<p align="center"><a href="https://fatg3erman.github.io/RompR/Troubleshooting#very-large-collections" target="_blank">Read The Troubleshooting Docs</a></p>';
				$("#collection").html(html);
				debug.error("PLAYER","Failed to generate collection",data);
				infobar.error(language.gettext('error_collectionupdate'));
			})
			.always(function() {
				debug.log('GENERAL','In always callback');
				player.updatingcollection = false;
			});
		} else {
			loadAudiobooks();
		}
	}

	function check_init_tasks() {
		if (!player.collectionLoaded) {
			player.collectionLoaded = true;
			startBackgroundInitTasks.doNextTask();
		}
	}

	function loadAudiobooks() {
		if (!prefs.hide_audiobooklist) {
			$('#audiobooks').load(
				'albums.php?item='+collectionHelper.collectionKey('z'),
				function() {
					collectionHelper.scootTheAlbums($("#audiobooks"));
					uiHelper.doThingsAfterDisplayingListOfAlbums($('#audiobooks'));
					loadSearchResults();
				}
			);
		} else {
			loadSearchResults();
		}
	}

	function loadSearchResults() {
		// if (!prefs.hide_searcher && prefs.displayresultsas != 'tree') {
		// 	// We can't re-load Directory Tree results so we don't bother
		// 	$('#searchresultholder').load(
		// 		'albums.php?item='+collectionHelper.collectionKey('b'),
		// 		function() {
		// 			collectionHelper.scootTheAlbums($("#searchresultholder"));
		// 			uiHelper.doThingsAfterDisplayingListOfAlbums($('#searchresultholder'));
		// 		}
		// 	);
		// }
		check_init_tasks();
	}

	function loadFileBrowser() {
		if (prefs.hide_filelist) {
			return false;
		}
		var files = 'dirbrowser.php';
		debug.log("GENERAL","Loading File Browser from URL",files);
		$("#filecollection").load(files);
	}

	function updateUIElements() {

		if (dbQueue.queuelength() > 0) {
			debug.log("UI","Deferring updates due to outstanding requests");
			clearTimeout(update_timer);
			setTimeout(updateUIElements, 1000);
			return;
		}

		returned_data.forEach(function(rdata, index) {

			if (rdata && rdata.hasOwnProperty('deletedalbums')) {
				$.each(rdata.deletedalbums, function(i, v) {
					debug.log("REMOVING", v);
					uiHelper.removeAlbum(v);
				});
			}

			if (rdata && rdata.hasOwnProperty('deletedartists')) {
				$.each(rdata.deletedartists, function(i, v) {
					debug.log("REMOVING", v);
					uiHelper.removeArtist(v);
				});
			}

			if (rdata && rdata.hasOwnProperty('modifiedalbums')) {
				$('#emptycollection').remove();
				$.each(rdata.modifiedalbums, function(i,v) {
					// We remove and replace any modified albums, as they may have a new date or albumartist which would cause
					// them to appear elsewhere in the collection. First remove the dropdown if it exists and replace its contents
					debug.log("MODIFIED",v.id);
					uiHelper.insertAlbum(v);
					if (prefs.clickmode == 'single') {
						$('#'+v.id).find('.invisibleicon').removeClass('invisibleicon');
					}
				});
			}

			if (rdata && rdata.hasOwnProperty('modifiedartists')) {
				$('#emptycollection').remove();
				$.each(rdata.modifiedartists, function(i,v) {
					// The only thing to do with artists is to add them in if they don't exist
					// NOTE. Do this AFTER inserting new albums, because if we're doing albumbyartist with banners showing
					// then the insertAfter logic will be wrong if we've already inserted the artist banner. We also need
					// to remove and replace the banner when that sort option is used, because we only insertAfter an album ID
					var x = uiHelper.findArtistDisplayer(v.id);
					if (x.length == 0) {
						debug.log('MODIFIED', v.id);
						uiHelper.insertArtist(v);
					}
				});
			}

			if (rdata && rdata.hasOwnProperty('addedtracks') && rdata.addedtracks.length > 0) {
				$.each(rdata.addedtracks, function(i, v) {
					if (v.albumindex !== null && v.trackuri != '' && prefs.chooser != 'searcher') {
						// (Ignore if it went into the wishlist) Also don't do it if we're looking
						// at the search pane as it's just annoying to tag tracks from there and have it keep
						// switching to the collection
						debug.log("INSERTED",v);
						layoutProcessor.displayCollectionInsert(v);
					}
				});
			} else {
				infobar.markCurrentTrack();
			}

			if (rdata && rdata.hasOwnProperty('stats')) {
				// stats is another html fragment which is the contents of the
				// statistics box at the top of the collection
				$("#fothergill").html($(rdata.stats).children());
			}

			if (rdata && rdata.hasOwnProperty('bookstats')) {
				// stats is another html fragment which is the contents of the
				// statistics box at the top of the collection
				$("#mingus").html($(rdata.bookstats).children());
			}

			returned_data[index] = null;

		});

		collectionHelper.scootTheAlbums($("#collection"));
		if (return_callback) {
			return_callback();
			return_callback = null;
		}

	}

	return {

		rejigDoodahs: function(panel, visible) {
			if (visible) {
				switch (panel) {
					case 'albumlist':
						loadCollection();
						break;

					case 'filelist':
						loadFileBrowser();
						break;

					case 'audiobooklist':
						loadAudiobooks();
						break;
				}
			}
		},

		doUpdateCollection: function() {
			collectionHelper.checkCollection(true, false);
		},

		doURescanCollection: function() {
			collectionHelper.checkCollection(true, true);
		},

		disableCollectionUpdates: function() {
			$('button[name="donkeykong"]').off('click').css('opacity', '0.2');
			$('button[name="dinkeyking"]').off('click').css('opacity', '0.2');
		},

		enableCollectionUpdates: function() {
			$('button[name="donkeykong"]').off('click').on('click', collectionHelper.doUpdateCollection).css('opacity', '');
			$('button[name="dinkeyking"]').off('click').on('click', collectionHelper.doRescanCollection).css('opacity', '');
		},

		forceCollectionReload: function() {
			debug.log("COLLECTION", "Forcing Collection reload");
			collection_status = 0;
			collectionHelper.displayCollection();
			if (notify) {
				infobar.removenotify(notify);
			}
		},

		prepareForLiftOff: function(text) {
			notify = infobar.permnotify(text);
			$("#collection").empty();
			$("#audiobooks").empty();
			doSomethingUseful('collection', text);
			var x = $('<div>',{ id: 'updatemonitor', class: 'tiny', style: 'padding-left:1em;margin-right:1em'}).insertAfter($('#spinner_collection'));
		},

		markWaitFileList: function(text) {
			$("#filecollection").empty();
			doSomethingUseful("filecollection", text);
		},

		collectionKey: function(w) {
			switch (w) {
				case 'b':
					return w+prefs.sortresultsby+'root';
					break;
				default:
					return w+prefs.sortcollectionby+'root';
					break;
			}
		},

		checkCollection: function(forceup, rescan) {
			debug.log("COLLECTION", "checking collection. collection_status is",collection_status);
			if (forceup && player.updatingcollection) {
				infobar.error(language.gettext('error_nocol'));
				return;
			}
			var update = forceup;
			if (prefs.updateeverytime && prefs.player_backend == prefs.collection_player) {
				debug.mark("COLLECTION","Updating Collection due to preference");
				update = true;
			} else {
				if (!prefs.hide_albumlist && collection_status == 1 && prefs.player_backend == prefs.collection_player) {
					debug.mark("COLLECTION","Updating Collection because it is out of date");
					collection_status = 0;
					update = true;
				}
			}
			if (update) {
				debug.log('GENERAL','We are going to update the collection');
				player.updatingcollection = true;
				$("#searchresultholder").html('');
				scanFiles(rescan ? 'rescan' : 'update');
			} else {
				collectionHelper.displayCollection();
			}
		},

		displayCollection: function() {
			loadCollection();
			loadFileBrowser();
		},

		scootTheAlbums: function(jq) {
			if (prefs.downloadart) {
				debug.log("COLLECTION", "Scooting albums in",jq.attr('id'));
				$.each(jq.find("img.notexist"), function() {
					coverscraper.GetNewAlbumArt($(this));
				});
			}
		},

		updateCollectionDisplay: function(rdata, callback) {
			// rdata contains HTML fragments to insert into the collection
			// Otherwise we would have to reload the entire collection panel every time,
			// which would cause any opened dropdowns to be mysteriously closed,
			// which would just look shit.
			debug.trace("COLLECTION","Update Display",rdata);
			if (rdata) {
				returned_data.push(rdata);
				return_callback = callback;
				clearTimeout(update_timer);
				setTimeout(updateUIElements, 1000);
			}
		}
	}
}();
