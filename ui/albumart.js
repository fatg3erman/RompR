var imagekey = '';
var imgobj = null;
var nosource = false;
var running = false;
var clickindex = null;
var wobblebottom;
var searchcontent;
var localimages;
var allshown = true;
var stream = "";
var progress;

if (typeof(IntersectionObserver) == 'function') {

	const imageLoadConfig = {
		rootMargin: '0px 0px 50px 0px',
		threshold: 0
	}

	var imageLoader = new IntersectionObserver(function(entries, self) {
	  entries.forEach(entry => {
	    if(entry.isIntersecting) {
	      preloadImage(entry.target);
	      self.unobserve(entry.target);
	    }
	  });
	}, imageLoadConfig);
}

function preloadImage(img) {
	$(img).attr('src', $(img).attr('data-src')).removeAttr('data-src').removeClass('lazy');
}

function getNewAlbumArt(div) {

	debug.log("ALBUMART","Getting art in",div);
	$.each($(div).find("img").filter(filterImages), function () {
			coverscraper.GetNewAlbumArt($(this));
		}
	);
	if (running == false) {
		running = true;
		progress.fadeIn('slow');
		$("#harold").off("click");
		$("#harold").on("click", reset );
		$("#harold").html("Stop Download");
		$('#doobag').off('click');
	}

}

// Does anybody ever read the comments in code?
// I hope they do, because most of the comments in my code are entirely useless.

function reset() {
	coverscraper.reset(-1);
}

// I like badgers

function start() {
	getNewAlbumArt('#wobblebottom');
}

function getsmall() {
	$('#doobag').html(language.gettext('label_searching')).makeFlasher();
	$("#doobag").off("click");
	$.ajax({
		type: 'GET',
		url: 'utils/findsmallimages.php',
		dataType: 'json',
		timeout: 300000
	})
	.done(function(data) {
		$('#doobag').stopFlasher().remove();
		debug.debug("SMALL IMAGES","Got List!",data);
		for (var i in data) {
			$('img[name="'+data[i]+'"]').attr('src', 'newimages/transparent.png').addClass('notexist');
		}
		coverscraper.reset($('.notexist:not(.notfound)').length + $('.notfound:not(.notexist)').length);
	})
	.fail(function() {
		$('#doobag').html("FAILED!").stopFlasher();
		debug.error("SMALL IMAGES","Big Wet Balls");
	});
}

function aADownloadFinished() {
	if (running == true) {
		running = false;
		$("#harold").off("click");
		$("#harold").on("click", start );
		$("#doobag").off("click");
		$("#doobag").on("click", getsmall );
		$("#harold").html("Get Missing Covers");
	}
	$("#status").html("");
	if (progress) {
		progress.fadeOut('slow');
		progress.rangechooser('setProgress', 0);
	}
}

function onWobblebottomClicked(event) {

	var clickedElement = findClickableElement(event);
	if (clickedElement.hasClass("clickalbumcover")) {
		event.stopImmediatePropagation();
		imageEditor.show(clickedElement);
	}
	if (clickedElement.hasClass('clickselectartist')) {
		event.stopImmediatePropagation();
		var a = clickedElement.attr("id");
		$(".clickselectartist").filter('.selected').removeClass('selected');
		clickedElement.addClass('selected');
		if (a == "allartists") {
			$(".albumart_artist_holder").show();
			if (!allshown) {
				boogerbenson();
				boogerbenson();
			}
		} else {
			$(".albumart_artist_holder").filter('[name!="'+a+'"]').hide();
			$('[name="'+a+'"]').show();
		}
	}
}

function findClickableElement(event) {

	var clickedElement = $(event.target);
	// Search upwards through the parent elements to find the clickable object
	while (!clickedElement.hasClass("clickable") &&
			clickedElement.prop("id") != "wobblebottom" &&
			clickedElement.prop("id") != "searchcontent") {
		clickedElement = clickedElement.parent();
	}
	return clickedElement;

}

// It's not raining

function boogerbenson() {
	if (allshown) {
		$("img", "#wobblebottom").filter( onlywithcovers ).parent().parent().hide();
		$("#finklestein").html(language.gettext("albumart_showall"));
		$(".albumsection").filter( emptysections ).hide();
		$(".bigholder").filter( emptysections2 ).hide();
	} else {
		$(".bigholder").show();
		$(".albumsection").show();
		$("img", "#wobblebottom").parent().parent().show();
		$("#finklestein").html(language.gettext("albumart_onlyempty"));
	}
	allshown = !allshown;
}

function onlywithcovers() {
	if ($(this).hasClass('notexist') || $(this).hasClass('notfound')) {
		return false;
	} else {
		return true;
	}
}

function filterImages() {
	if ($(this).hasClass('notexist') || $(this).hasClass('notfound')) {
		return true
	} else {
		return false;
	}
}

// This comment is useless

function emptysections() {
	var empty = true;
	$.each($(this).next().find('.albumimg'), function() { if (!$(this).is(':hidden')) { empty = false } });
	return empty;
}

function emptysections2() {
	var empty = true;
	$.each($(this).find('.albumimg'), function() { if (!$(this).is(':hidden')) { empty = false } });
	return empty;
}

function sections_without_missing_images() {
	var ne = $(this).find('img.notexist');
	var nf = $(this).find('img.notfound');
	if (ne.length + nf.length > 0) {
		return false;
	}
	return true;
}

function sections_with_missing_images() {
	var ne = $(this).find('img.notexist');
	var nf = $(this).find('img.notfound');
	if (ne.length + nf.length > 0) {
		return true;
	}
	return false;
}

$(document).ready(function () {
	prefs.loadPrefs(carry_on_loading);
});

function carry_on_loading() {
	debug.log("ALBUMART","Document is ready");
	prefs.rgbs = null;
	prefs.maxrgbs = null;
	prefs.setTheme(prefs.theme);
	progress = $('#progress');
	progress.rangechooser({range: 100, startmax: 0, interactive: false});
	$(window).on('resize', wobbleMyBottom );
	$("#harold").on('click',  start );
	$("#doobag").on('click',  getsmall );
	$("#finklestein").on('click',  boogerbenson );
	wobblebottom = $('#wobblebottom');
	wobbleMyBottom();
	$('#artistcoverslist').mCustomScrollbar({
		theme: "light",
		scrollInertia: 300,
		contentTouchScroll: 25,
		mouseWheel: {
			scrollAmount: 40,
		},
		advanced: {
			updateOnContentResize: true,
			updateOnImageLoad: false,
			autoScrollOnFocus: false,
			autoUpdateTimeout: 500,
		}
	});
	$('#coverslist').mCustomScrollbar({
		theme: "light",
		scrollInertia: 200,
		contentTouchScroll: 25,
		mouseWheel: {
			scrollAmount: parseInt(prefs.wheelscrollspeed),
		},
		advanced: {
			updateOnContentResize: true,
			updateOnImageLoad: false,
			autoScrollOnFocus: false,
			autoUpdateTimeout: 500,
		}
	});
	document.body.addEventListener('drop', function(e) {
		e.preventDefault();
	}, false);
	wobblebottom.on('click', onWobblebottomClicked);
	$('.droppable').on('dragenter', dragEnter);
	$('.droppable').on('dragover', dragOver);
	$('.droppable').on('dragleave', dragLeave);
	$('.droppable').on('drop', handleDrop);
	$(document).on('mouseenter', '.clearbox', makeHoverWork);
	$(document).on('mouseleave', '.clearbox', makeHoverWork);
	$(document).on('mousemove', '.clearbox', makeHoverWork);
	$(document).on('click', '.clearbox.enter', makeClearWork);

};

$(window).on('load', function () {
	debug.log("ALBUMART","Document has loaded");
	coverscraper = new coverScraper(1, true, true, true);
	var count = 0;
	$('.albumart_artist_holder').filter(sections_with_missing_images).each(function() {
		$(this).children('.albumsection').find('button').show();
	});
	$('#poobag').prop('checked', false);
	$('#dinkytoy').prop('checked', false);
	coverscraper.toggleScrolling(false);
	coverscraper.toggleLocal(false);
	$("#totaltext").html(numcovers+" "+language.gettext("label_albums"));
	coverscraper.reset(albums_without_cover);
	$("#status").html(language.gettext("albumart_instructions"));
	$('#dinkylabel').prop('disabled', false);
	$('#poobaglabel').prop('disabled', false);
	if (typeof(IntersectionObserver) == 'function') {
		$("img.lazy").get().forEach(img => imageLoader.observe(img));
	} else {
		$('img.lazy').not('.notexist').not('.notfound').each(function() {
			preloadImage(this);
		});
	}
});

function dragEnter(ev) {
	evt = ev.originalEvent;
	evt.stopPropagation();
	evt.preventDefault();
	$(ev.target).addClass("highlighted");
	return false;
}

function dragOver(ev) {
	evt = ev.originalEvent;
	evt.stopPropagation();
	evt.preventDefault();
	return false;
}

function dragLeave(ev) {
	evt = ev.originalEvent;
	evt.stopPropagation();
	evt.preventDefault();
	$(ev.target).removeClass("highlighted");
	return false;
}

function handleDrop(ev) {
	debug.log("ALBUMART","Dropped",ev);
	evt = ev.originalEvent;
	$(ev.target).removeClass("highlighted");
	imgobj = $(ev.target);
	imagekey = imgobj.attr("name");
	nosource = (imgobj.hasClass('notfound') || imgobj.hasClass('notexist'));
	clickindex = null;
	dropProcessor(ev.originalEvent, imgobj, coverscraper, uploadComplete, searchFail);
}

var imageEditor = function() {

	var offset = 0;
	var position = null;
	var bigdiv = null;
	var bigimg = new Image();
	var currparent = null;
	var currhighlight = null;
	var currname = null;
	var current = "g";
	bigimg.onload = function() {
		imageEditor.displayBigImage();
	}

	return {

		show: function(where) {
			var newpos = where.parent().parent();
			if (where.attr('name') == currname) {
				imageEditor.close();
				return true;
			}
			if (currparent !== null) {
				imageEditor.close();
			}
			currname = where.attr('name');
			bigdiv = $('<div>', {id: "imageeditor", class: "containerbox highlighted dropshadow"}).appendTo(newpos);
			bigdiv.on('click', imageEditor.onGoogleSearchClicked);
			offset = 0;
			currhighlight = where.parent();
			currhighlight.addClass('highlighted');
			currparent = newpos;
			currparent.addClass('imageeditor-opened');

			bigimg.src = "";
			bigdiv.empty();
			imgobj = where;
			imagekey = imgobj.attr('name');
			nosource = (imgobj.hasClass('notfound') || imgobj.hasClass('notexist'));
			var phrase = decodeURIComponent(imgobj.parent().find('input[name="searchterm"]').val());
			var path = imgobj.parent().find('input[name="albumpath"]').val();
			debug.trace('ALBUMART','Local Path Is',path);

			bigdiv.append($('<div>', { id: "searchcontent" }));
			bigdiv.append($('<div>', { id: "origimage"}).append($("<img>", { id: 'browns' })));

			$("#searchcontent").append( $('<div>', {id: "editcontrols", class: "clearfix fullwidth"}),
										$('<div>', {id: "gsearch", class: "noddy fullwidth invisible"}),
										$('<div>', {id: "fsearch", class: "noddy fullwidth invisible"}),
										$('<div>', {id: "usearch", class: "noddy fullwidth invisible"}));

			$("#"+current+"search").removeClass("invisible");

			$("#gsearch").append(       $('<div>', {id: "brian", class: "fullwidth"}),
										$('<div>', {id: "searchresultsholder", class: "fullwidth"}));

			$("#searchresultsholder").append($('<div>', {id: "searchresults", class: "containerbox fullwidth wrap"}));

			$("#fsearch").append(		$('<div>', {id: "localresultsholder", class: "fullwidth"}));

			$("#localresultsholder").append($('<div>', {id: "localresults", class: "containerbox fullwidth wrap"}));

			var fdiv =                  $('<div>', {class: "fullwidth"}).appendTo('#usearch');
			var uform =                 $('<form>', { id: 'uform', action: 'utils/getalbumcover.php', method: 'post', enctype: 'multipart/form-data' }).appendTo(fdiv);
			uform.append(               $('<input>', { id: 'uploadkey', type: 'hidden', name: 'key', value: '' }),
										$('<input>', { id: 'uploadartist', type: 'hidden', name: 'artist', value: '' }),
										$('<input>', { id: 'uploadalbum', type: 'hidden', name: 'album', value: '' }),
						);
			var fb =                    $('<div>', {class: 'filebutton textcentre'}).appendTo(uform);
			var inp =                   $('<input>', { name: 'ufile', type: 'file', id: 'ufile', class: 'inputfile'}).appendTo(fb);
			inp.on('change', function() {
				var filename = $(this).val().replace(/.*(\/|\\)/, '');
				$(this).next().html(filename);
				$(this).parent().next('input[type="button"]').fadeIn('fast');
			});
			var lab =                   $('<label>', { for: 'ufile' }).appendTo(fb);
			lab.html(language.gettext('label_choosefile'));
			var but =                   $('<input>', { type: 'button', class: 'invisible fixed', value: language.gettext("albumart_uploadbutton") }).appendTo(uform);
			but.on('click', imageEditor.uploadFile);

			$("#usearch").append(      '<div class="holdingcell"><p>'+language.gettext("albumart_dragdrop")+'</p></div>');

			$("#editcontrols").append(  '<div id="g" class="tleft bleft clickable clickicon bmenu">'+language.gettext("albumart_googlesearch")+'</div>');
			if (path && path != '.') {
				$("#editcontrols").append( '<div id="f" class="tleft bleft bmid clickable clickicon bmenu">'+language.gettext("albumart_local")+'</div>');
			}
			$("#editcontrols").append(  '<div id="u" class="tleft bleft bmid clickable clickicon bmenu">'+language.gettext("albumart_upload")+'</div>'+
										'<div class="tleft bleft bmid clickable clickicon"><a href="http://www.google.com/search?q='+phrase+'&hl=en&site=imghp&tbm=isch" target="_blank">'+language.gettext("albumart_newtab")+'</a></div>');

			$("#editcontrols").append(  $('<i>', { class: "icon-cancel-circled smallicon tright clickicon", onclick: "imageEditor.close()"}));

			$("#"+current).addClass("bsel");

			$("#brian").append('<div class="containerbox"><div class="expand"><input class="enter clearbox" type="text" id="searchphrase" /></div><button class="fixed" onclick="imageEditor.research()">Search</button></div>');

			$("#searchphrase").val(phrase);

			if (imgobj.attr("src")) {
				var aa = new albumart_translator(imgobj.attr("src"));
				bigimg.src = aa.getSize('asdownloaded');
			}

			imageEditor.search();
			if (path && path != '.') {
				$.getJSON("utils/findLocalImages.php?path="+encodeURIComponent(path), imageEditor.gotLocalImages)
			}

			var searchparams = coverscraper.getImageSearchParams(imgobj);
			$('input#uploadkey').val(searchparams.key);
			$('input#uploadartist').val(searchparams.artist);
			$('input#uploadalbum').val(searchparams.album);
			$('#searchphrase').on('keyup', imageEditor.bumblefuck);
			wobbleMyBottom();
			$('#coverslist').mCustomScrollbar('scrollTo', $('#imageeditor').parent());
		},

		setWidth: function() {
			if (bigdiv) {
				var l = Math.max(currparent.position().left - 4, 0);
				var w = Math.max((currparent.width() + currparent.position().left - l), (currparent.parent().width() - 8));
				bigdiv.css({
					width: w+"px",
					left: "-"+l+"px"
				});

			}
		},

		close: function() {
			bigdiv.remove();
			bigdiv = null;
			currhighlight.removeClass('highlighted');
			currparent.removeClass('imageeditor-opened');
			currhighlight = null;
			currparent = null;
			currname = null;
			curval = null;
		},

		displayBigImage: function() {
			if (bigdiv) {
				$('#browns').attr('src', bigimg.src).css('opacity', 1);
			}
		},

		research: function() {
			$("#searchresults").empty();
			offset = 0;
			imageEditor.search();
		},

		search: function() {
			bing.image.search(
				$("#searchphrase").val(),
				offset,
				imageEditor.bingSearchComplete,
				imageEditor.bingSearchComplete,
			);
		},

		bingSearchComplete: function(data) {
			debug.debug("IMAGEEDITOR","Bing Search Results", data);
			$("#morebutton").remove();
			if (data.value) {
				data.value.forEach(function(image) {
					$('#searchresults').append(imageEditor.imageResult(
						{
							thumbnail: image.thumbnailUrl,
							dimensions: image.width.toString()+'x'+image.height.toString(),
							hostpage: image.hostPageDomainFriendlyName,
							name: image.name,
							id: image.imageId,
							fullurl: image.contentUrl
						}
					));
				});
				if (data.nextOffset) {
					offset = data.nextOffset;
					$("#searchresultsholder").append('<div id="morebutton" class="fullwidth"><button onclick="imageEditor.search()">'+language.gettext("albumart_showmore")+'</button></div>');
				}
			} else if (data.error) {
				$('#searchresults').append('<h3>'+data.error+'</h3>');
			}

		},

		imageResult: function(options) {
			var holder = $('<div>', {class: 'fixed albumimg closet'});
			var container = $('<div>', {class: 'covercontainer'}).appendTo(holder);
			container.append($('<img>', {class: 'clickable clickicon clickgimage', src: options.thumbnail, id: options.id}));
			container.append($('<input>', {type: 'hidden', value: options.fullurl}));
			if (options.name)
				container.append($('<div>', {class: 'playlistrow2'}).html(options.name));
			if (options.dimensions)
				container.append($('<div>', {class: 'playlistitem'}).html(options.dimensions));
			if (options.hostpage)
				container.append($('<div>', {class: 'playlistrow2'}).html(options.hostpage));
			return holder;
		},

		onGoogleSearchClicked: function(event) {
			var clickedElement = findClickableElement(event);
			if (clickedElement.hasClass("clickgimage")) {
				debug.trace("ALBUMART","Search Result clicked :",clickedElement.next().val(), clickedElement.prop('id'));
				event.stopImmediatePropagation();
				updateImage(clickedElement.next().val(), clickedElement.prop('id'));
			} else if (clickedElement.hasClass("bmenu")) {
				var menu = clickedElement.attr("id");
				$(".noddy").filter(':visible').fadeOut('fast', function() {
					$("#"+menu+"search").fadeIn('fast');
				});
				$(".bleft").removeClass('bsel');
				clickedElement.addClass('bsel');
				current = menu;
			}
		},

		updateBigImg: function(url) {
			$("#browns").css('opacity', 0);
			if (typeof url == "string") {
				bigimg.src = url;
			}
		},

		showError: function(message) {
			debug.warn("IMAGEEDITOR","Error - ",message);
			$("#morebutton").remove();
			$("#searchresults").append('<h3>'+language.gettext("albumart_googleproblem")+' "'+message+'"</h3>');
		},

		gotLocalImages: function(data) {
			debug.debug("ALBUMART","Retreived Local Images: ",data);
			if (data && data.length > 0) {
				data.forEach(function(image) {
					$("#localresults").append(imageEditor.imageResult({
						thumbnail: image,
						dimensions: false,
						hostpage: false,
						name: false,
						id: hex_md5(image),
						fullurl: image
					}));
				});
			}
		},

		bumblefuck: function(e) {
			if (e.keyCode == 13) {
				imageEditor.research();
			}
		},

		uploadFile: function() {
			imgobj.removeClass('notfound notexist').addClass('notfound');
			imageEditor.updateBigImg(true);
			startAnimation();
			var formElement = document.getElementById("uform");
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "utils/getalbumcover.php");
			xhr.responseType = "json";
			xhr.onload = function () {
				if (xhr.status === 200) {
					uploadComplete(xhr.response);
				} else {
					searchFail();
				}
			};
			xhr.send(new FormData(formElement));
		}

	}

}();

function wobbleMyBottom() {
	var ws = getWindowSize();
	var newheight = ws.y - wobblebottom.offset().top;
	wobblebottom.css("height", newheight.toString()+"px");
	imageEditor.setWidth();
}

// Ceci n'est pas une commentaire

function updateImage(url, index) {
	clickindex = index;
	imgobj.removeClass('notfound notexist').addClass('notfound');
	imageEditor.updateBigImg(true);
	startAnimation();
	var options = coverscraper.getImageSearchParams(imgobj);
	options.source = url;
	$.ajax({
		url: "utils/getalbumcover.php",
		type: "POST",
		data: options,
		cache:false
	})
	.done(uploadComplete)
	.fail(searchFail);
}

function startAnimation() {
	imgobj.removeClass('nospin').removeAttr('src').addClass('spinner');
}

function animationStop() {
	imgobj.removeClass('spinner').addClass('nospin');
}

function searchFail() {
	debug.info("ALBUMART","No Source Found");
	$('#'+clickindex).attr('src', 'newimages/imgnotfound.svg');
	imgobj.removeClass('notfound notexist').addClass('notexist');
	imageEditor.updateBigImg(false);
	animationStop();
}

function uploadComplete(data) {
	debug.log("ALBUMART","Upload Complete");
	if (data.small) {
		animationStop();
		debug.trace("ALBUMART","Success for",imagekey);
		if (nosource) {
			coverscraper.updateInfo(1);
			nosource = false;
		}
		imgobj.removeClass("notexist notfound");
		var firefoxcrapnesshack = Math.floor(Date.now());
		imgobj.attr('src', data.medium+'?version='+firefoxcrapnesshack.toString());
		imageEditor.updateBigImg(data.asdownloaded+'?version='+firefoxcrapnesshack.toString());
		sendLocalStorageEvent(imagekey, data);
	} else {
		searchFail();
	}
}

function toggleScrolling() {
	if ($('#poobag').is(':checked')) {
		debug.log("COVERS","Disabling Scrolling");
		coverscraper.toggleScrolling(false);
	} else {
		debug.log("COVERS","Enabling Scrolling");
		coverscraper.toggleScrolling(true);
	}
}

function toggleLocal() {
	if ($('#dinkytoys').is(':checked')) {
		debug.log("COVERS","Enabling Local Images");
		coverscraper.toggleLocal(false);
	} else {
		debug.log("COVERS","Ignoring Local Images");
		coverscraper.toggleLocal(true);
	}
}

function fakeClickOnInput() {

}
