var ratingManager = function() {

	var rmg = null;
	var sortby;
	var loaded = false;
	var current_album = null;
	var current_albumholder = null;
	var current_letter = '';
	var to_refresh = new Array();
	var updating_section = false;

	function startNewSection(title, section, atstart) {
		debug.log("RATMAN","Starting Section",title);
		var a;
		if (atstart === true) {
			a = $('<div>').prependTo('#ratmunger');
		} else {
			a = $('<div>').insertAfter(atstart.parent());
		}
		var x = $('<div>', { class: "pluginsection textunderline containerbox" }).appendTo(a);
		x.append('<i class="icon-toggle-closed fixed menu infoclick plugclickable clickopensection"></i><span class="fixed rattitle">'+title+'</span><div class="expand filterinfo"></div></div>');
		if (sortby == "Tag") {
			x.append('<i class="fixed icon-trash topimg infoclick plugclickable clickdeletetag"></i>');
		}
		var b = $('<div>', {class: 'thebigholder fullwidth notthere', name: encodeURIComponent(section)}).appendTo(a);
		b.append('<div class="sizer"></div>');
		if (sortby != 'AlbumArtist') {
			a.acceptDroppedTracks({
				ondrop: ratingManager.dropped
			});
		}
	}

	function putTracksInSection(section) {
		updating_section = true;
		debug.log("RATMAN","Putting Tracks In Section",section);
		var dropper = $('.thebigholder[name="'+section+'"]');
		dropper.prev().children('.clickopensection').makeSpinner();
		if (dropper.hasClass('notthere')) {
			
		} else {
			dropper.addClass('notthere');
			dropper.masonry('destroy').empty().append('<div class="sizer"></div>');
		}
		metaHandlers.genericAction(
			[{action: 'ratentries', sortby: sortby, value: decodeURIComponent(section)}],
			function(tracks) {
				debug.trace("RATMAN","Got Tracks",tracks);
				current_letter = '';
				current_album = '';
				for (var i in tracks) {
					putNewAlbumTrack(dropper, tracks[i]);
				}
				dropper.imagesLoaded(function() {
					debug.log("RATMAN","Images Loaded In",section);
					browser.rePoint(dropper, { itemSelector: '.slaphead', columnWidth: '.sizer', percentPosition: true });
					dropper.prev().children('.clickopensection').stopSpinner().toggleOpen();
					dropper.removeClass('notthere');
		            infobar.markCurrentTrack();
				});
				updating_section = false;
				checkSectionRefresh();
			},
			function() {
				infobar.notify(infobar.ERROR, "Failed to get data!");
				rmg.slideToggle('fast');
			}
		);
	}

	function putNewAlbumTrack(holder, data) {
		if (filterTrack(data)) {
			return false;
		}
		if (data.Albumname != current_album) {
			current_album = data.Albumname;
			if (sortby == 'AlbumArtist') {
				var tit = '<b class="artistnamething">'+data.Albumname+'</b>';
				if (data.AlbumArtist != data.Artistname) {
					tit += '<br><i>'+data.AlbumArtist+'</i>';
				}
				tit += '</div>';
				var nl = '';
			} else {
				var tit = '<b class="artistnamething">'+data.AlbumArtist+'</b><br><b>'+data.Albumname+'</b>';
				var nl = data.SortLetter;
			}
			if (prefs.ratman_showletters && nl != current_letter) {
				holder.append('<div class="slaphead highlighted fullwidth letterholder">'+nl+'</div>');
				current_letter = nl;
			}
			var b = $('<div>', {class: "slaphead pluginitem"}).appendTo(holder);
			var c = $('<div>', {class: "helpfulalbum fullwidth selecotron"}).appendTo(b);
			var src = data.Image;
			if (src) {
				if (!prefs.ratman_smallart) {
					src = src.replace(/albumart\/small/, 'albumart/asdownloaded');
				}
				c.append('<img class="masochist" src="'+src+'" />');
			}
			c.append('<div class="tagh albumthing sponclick clickable infoclick draggable clickalbumname" name="dummy">'+tit+'</div>');
			current_albumholder = $('<div>', {class: "tagh albumthing"}).appendTo(c);
		}
		var setdata = encodeURIComponent(JSON.stringify({title: data.Title, artist: data.Artistname, trackno: data.TrackNo, album: data.Albumname, albumartist: data.AlbumArtist}));
		var html = '<div class="ntu infoclick clickable draggable clicktrack fullwidth" name="'+encodeURIComponent(data.Uri)+'">';
		html += '<div class="containerbox line">';
		html += '<div class="tracknumber fixed">'+data.TrackNo+'</div>';
		html += '<div class="expand containerbox vertical">';
		html += '<div class="fixed tracktitle"><b>'+data.Title+'</b></div>';
		if (data.AlbumArtist != data.Artistname) {
			html += '<div class="fixed playlistrow2 artistname">'+data.Artistname+'</div>';
		}
		html += '<div class="fixed playlistrow2 trackrating"><i class="icon-'+data.Rating+'-stars rating-icon-small infoclick plugclickable clicksetrat"></i></div>';
		html += '<div class="carol fixed playlistrow2 tracktags">';
		if (data.Tags && data.Tags != "No Tags") {
			var tags = data.Tags.split(', ');
			for (var i in tags) {
				html += '<span class="tag">'+tags[i]+'<i class="icon-cancel-circled infoclick plugclickable clickicon tagremover playlisticon"></i></span> ';
			}
		}
		html += '</div>';
		html += '</div>';
		html += '<div class="fixed playlistrow2 tracktime">'+formatTimeString(data.Duration)+'</div>';
		html += '</div>';
		html += '<div class="containerbox line"><i class="fixed icon-plus infoclick plugclickable clickicon playlisticon clickaddtags"></i></div>';
		html += '<input type="hidden" class="setdata" value="'+setdata+'" />';
		html += '</div>';
		current_albumholder.append(html);
	}

	function filterTrack(data) {
		var term = $('[name=filterinput]').val();
		term = term.replace(/\s+/g, '\\s+');
		var re = new RegExp(term, "i");
		var tests = ['Title','Artistname','AlbumName','AlbumArtist'];
		for (var i in tests) {
			if (re.test(data[tests[i]])) {
				return false;
			}
		}
		if (data.Tags && data.Tags != "No Tags") {
			var tags = data.Tags.split(' ,');
			for (var i in tags) {
				if (re.test(tags[i])) {
					return false;
				}
			}
		}
		return true;
	}

	function do_action(setdata, callback) {
		metaHandlers.genericAction(
			[setdata],
			function(rdata) {
				updateCollectionDisplay(rdata);
				callback();
				update_rest_of_ui();
			},
			function() {
				infobar.notify(infobar.ERROR, "Oh dear, that didn't work");
			}
		);
	}

	function update_rest_of_ui() {
    	nowplaying.refreshUserMeta();
    	// We need to do this if we're pre-populating the playlist using get_extra_track_info
    	// but we're not currently, because it's too slow
    	// playlist.repopulate();
	}
	
	function refreshSection(section) {
		if (to_refresh.indexOf(section) == -1 && !($('.thebigholder[name="'+section+'"]').hasClass('notthere'))) {
			to_refresh.push(section);
		}
		checkSectionRefresh();
	}
	
	function checkSectionRefresh() {
		if (!updating_section) {
			var section = to_refresh.shift();
			if (section) {
				putTracksInSection(section);
			}
		}
	}

	return {

		open: function() {

        	if (rmg == null) {
	        	rmg = browser.registerExtraPlugin("rmg", language.gettext("config_tagrat"), ratingManager, "https://fatg3erman.github.io/RompR/Managing-Ratings-And-Tags");

	        	$("#rmgfoldup").append('<div class="containerbox padright ratinstr" name="ratman_dragRat">'+
	        		'<div class="expand"><b>'+language.gettext("label_ratingmanagertop")+'</b></div>'+
	        		'</div>');
	        	$("#rmgfoldup").append('<div class="containerbox padright ratinstr" name="ratman_dragTag">'+
	        		'<div class="expand"><b>'+language.gettext("label_tagmanagertop")+'</b></div>'+
	        		'</div>');

	        	$("#rmgfoldup").append('<div class="containerbox padright wrap ratsoptions">'+
	        		'<div class="fixed"><b>Sort By :&nbsp; </b></div>'+
	        		'<div class="fixed brianblessed styledinputs"><input type="radio" class="topcheck" name="ratman_sortby" id="ratsortrat" value="Rating"><label for="ratsortrat">Rating</label></div>'+
	        		'<div class="fixed brianblessed styledinputs"><input type="radio" class="topcheck" name="ratman_sortby" id="ratsorttag" value="Tag"><label for="ratsorttag">Tag</label></div>'+
	        		'<div class="fixed brianblessed styledinputs"><input type="radio" class="topcheck" name="ratman_sortby" id="ratsortlis" value="Tags"><label for="ratsortlis">Tag List</label></div>'+
	        		'<div class="fixed brianblessed styledinputs"><input type="radio" class="topcheck" name="ratman_sortby" id="ratsortart" value="AlbumArtist"><label for="ratsortart">Artist</label></div>'+
	        		'</div>');

    			$("#rmgfoldup").append('<div class="containerbox padright noselection ratinstr" name="ratman_dragTag">'+
        			'<div class="expand">'+
            		'<input class="enter inbrowser" name="newtagnameinput" type="text" />'+
        			'</div>'+
					'<button class="fixed" onclick="ratingManager.createTag()">'+language.gettext("button_createtag")+'</button>'+
    				'</div>');

	        	$("#rmgfoldup").append('<div class="containerbox padright wrap ratsoptions">'+
	        		'<div class="fixed"><b>Display Options :&nbsp; </b></div>'+
	        		'<div class="fixed brianblessed styledinputs"><input type="checkbox" class="topcheck" id="ratman_showletters"><label for="ratman_showletters">Show Letter Headers</label></div>'+
	        		'<div class="fixed brianblessed styledinputs"><input type="checkbox" class="topcheck" id="ratman_smallart"><label for="ratman_smallart">Small Album Art</label></div>'+
	        		'</div>');

    			$("#rmgfoldup").append('<div class="containerbox padright noselection ratsoptions">'+
        			'<div class="expand">'+
            		'<input class="enter inbrowser clearbox" name="filterinput" type="text" />'+
        			'</div>'+
					'<button class="fixed" onclick="ratingManager.filter()">'+language.gettext("button_filter")+'</button>'+
    				'</div>');

	        	$("#rmgfoldup").append('<div class="containerbox padright" name="ratman_loading"><h3>Loading List....</h3></div>');

			    $("#rmgfoldup").append('<div class="noselection fullwidth masonified" id="ratmunger"></div>');
			    $('[name="filterinput"]').click(function(ev){
		            ev.preventDefault();
		            ev.stopPropagation();
		            var position = getPosition(ev);
		            var elemright = $('[name="filterinput"]').width() + $('[name="filterinput"]').offset().left;
		            if (position.x > elemright - 24) {
		            	$('[name="filterinput"]').val("");
		            	ratingManager.filter();
		            }
			    });
			    $('[name="filterinput"]').hover(makeHoverWork);
			    $('[name="filterinput"]').mousemove(makeHoverWork);
				$('.ratinstr').hide();
				rmg.show();
				$('[name="ratman_sortby"][value="'+prefs.ratman_sortby+'"]').prop('checked', true);
	            $('#ratman_showletters').prop('checked', prefs.ratman_showletters ? true : false );
	            $('#ratman_smallart').prop('checked', prefs.ratman_smallart ? true : false );
	        	browser.goToPlugin("rmg");
			    ratingManager.reloadEntireRatList();
	            $('#rmgfoldup .enter').keyup(onKeyUp);
	            $('[name="ratman_sortby"]').on('click', ratingManager.reloadEntireRatList );
	            $('#ratman_showletters').on('click', ratingManager.reloadEntireRatList );
	            $('#ratman_smallart').on('click', ratingManager.reloadEntireRatList );
	        } else {
	        	browser.goToPlugin("rmg");
	        }
		},

		doMainLayout: function(alldata) {
			debug.log("RATINGMANAGER","Got data",alldata);
			var secdiv = true;
			for (var i in alldata) {
				var section = alldata[i];
				debug.log("RATMAN","Doing Section",section);
				if ($('.thebigholder[name="'+encodeURIComponent(section)+'"]').length == 0 && section != 'No Tags') {
					if (sortby == 'Rating') {
						startNewSection('<i class="icon-'+section+'-stars rating-icon-big"></i>', section, secdiv);
					} else {
						startNewSection(section, section, secdiv);
					}
				}
				secdiv = $('.thebigholder[name="'+encodeURIComponent(section)+'"]');
			}
			if (!loaded) {
				setDraggable('#rmgfoldup');
				loaded = true;
			}
		},

		handleClick: function(element, event) {
			if (element.hasClass('clickopensection')) {
				var dropper = element.parent().next();
				if (element.isClosed()) {
					var fi = element.next().next();
					var term = $('[name=filterinput]').val();
					if (term == '') {
						fi.html('')
					} else {
						fi.html("Filtered By '"+term+"'");
					}
					var section = dropper.attr('name');
					putTracksInSection(section);
				} else {
					element.toggleClosed();
					dropper.addClass('notthere').masonry('destroy').empty().append('<div class="sizer"></div>');
				}
			} else if (element.hasClass('clickdeletetag')) {
				var tag = element.parent().next().attr('name');
				do_action({action: 'deletetag', value: tag}, function() {
    				element.parent().next().remove();
    				element.parent().remove();
				});
			} else if (element.hasClass('tagremover')) {
				var tag = element.parent().text();
				var setstring = element.parent().parent().parent().parent().parent().children('input').val();
				var setdata = JSON.parse(decodeURIComponent(setstring));
				var uri = decodeURIComponent(element.parent().parent().parent().parent().parent().attr('name'));
				setdata.uri = uri;
				setdata.action = 'remove';
				setdata.attributes = [{attribute: "Tags", value: tag}];
				debug.log("RATING MANAGER","Removing",tag,"from",setdata);
				do_action(setdata, function() {
					$('input.setdata').each(function() {
						if ($(this).val() == setstring) {
							$(this).parent().find('.carol').children('span').each(function() {
								if ($(this).text() == tag) {
									$(this).remove();
								}
							});
						}
					});
    				browser.rePoint();
				});
			} else if (element.hasClass('clicksetrat')) {
				var setstring = element.parent().parent().parent().parent().children('input').val();
				var setdata = JSON.parse(decodeURIComponent(setstring));
                var position = getPosition(event);
				var width = element.width();
				var starsleft = element.offset().left;
				var rating = Math.ceil(((position.x - starsleft - 6)/width)*5);
				var uri = decodeURIComponent(element.parent().parent().parent().parent().attr('name'));
				setdata.uri = uri;
				setdata.action = "set";
				setdata.attributes = [{attribute: "Rating", value: rating}];
				debug.log("RATING MANAGER","Setting Rating to",rating,"on",setdata);
				do_action(setdata, function() {
					$('input.setdata').each(function() {
						if ($(this).val() == setstring) {
							$(this).parent().find('.trackrating').html('<i class="icon-'+rating+'-stars rating-icon-small infoclick plugclickable clicksetrat"></i>');
						}
					});
				});
			} else if (element.hasClass('clickaddtags')) {
				tagAdder.show(event, null, ratingManager.addTags);
			}
		},

		addTags: function(element, toadd) {
			var setstring = element.parent().parent().children('input').val();
			var setdata = JSON.parse(decodeURIComponent(setstring));
			var uri = decodeURIComponent(element.parent().parent().attr('name'));
			setdata.uri = uri;
			setdata.action = 'set';
			var tagarr = toadd.split(',');
			setdata.attributes = [{attribute: 'Tags', value: tagarr}];
			debug.log("RATING MANAGER","Adding Tags",setdata);
			do_action(setdata, function() {
				$('input.setdata').each(function() {
					if ($(this).val() == setstring) {
    					for (var i in tagarr) {
							$(this).parent().find('.carol').append('<span>'+tagarr[i].trim()+'<i class="icon-cancel-circled infoclick plugclickable clickicon tagremover playlisticon"></i></span>');
						}
					}
				});
				browser.rePoint();
				for (var i in tagarr) {
					refreshSection(encodeURIComponent(tagarr[i]));
				}
				ratingManager.reloadRatList();
			});
		},

		reloadRatList: function() {
			metaHandlers.genericAction(
				[{action: 'ratlist', sortby: sortby}],
				function(data) {
					$('[name="ratman_loading"]').hide();
            		ratingManager.doMainLayout(data);
		        	if (layoutProcessor.supportsDragDrop) {
						$('[name="ratman_drag'+sortby.substr(0,3)+'"]').fadeIn('fast');
					}
					checkSectionRefresh();
            	},
            	function() {
            		infobar.notify(infobar.ERROR, "Failed to get data!");
            		rmg.slideToggle('fast');
            	}
            );
		},
		
		reloadEntireRatList: function() {
			$('.ratinstr').hide();
			$('[name="ratman_loading"]').show();
		    sortby = $('[name="ratman_sortby"]:checked').val();
		    prefs.save({ratman_sortby: sortby, ratman_showletters: $('#ratman_showletters').is(':checked'), ratman_smallart: $('#ratman_smallart').is(':checked')});
	    	$('#ratmunger').empty();
			ratingManager.reloadRatList();
		},

		dropped: function(event, element) {
	        event.stopImmediatePropagation();
	        var value = decodeURIComponent(element.children('.thebigholder').attr('name'));
	        switch (sortby) {
	        	case 'Rating':
	        		var attributes = {attribute: 'Rating', value: value};
	        		break;

	        	case 'Tag':
	        	case 'Tags':
	        		var attributes = {attribute: 'Tags', value: value.split(',')};
	        		break;

	        	default:
	        		debug.error("RATMAN","Unknown sortby type",sortby);
	        		return false;
	        		break;
	        }
	        metaHandlers.fromUiElement.doMeta('set', value, [attributes], function() {
				refreshSection(encodeURIComponent(value));
	        	update_rest_of_ui();
	       	});
		},

		close: function() {
			$('#ratmunger').empty();
			loaded = false;
			rmg = null;
		},

		filter: function() {
			var term = $('[name=filterinput]').val();
			if (term == '') {
				$('.filterinfo').html('')
			} else {
				$('.icon-toggle-open').next().next().html("Filtered By '"+term+"'");
			}
			ratingManager.reloadRatList(true);
		},

		createTag: function() {
			var name = $('[name=newtagnameinput]').val();
			if ($('.thebigholder[name="'+name+'"]').length > 0) {
				infobar.notify(infobar.ERROR, "That tag already exists");
			} else {
				name = name.replace(/\s*,\s*/, ', ');
				startNewSection(name, name, true);
			}
		}
	}

}();

pluginManager.setAction(language.gettext("config_tagrat"), ratingManager.open);
ratingManager.open();
