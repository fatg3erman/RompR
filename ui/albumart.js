var running = false;
var wobblebottom;
var allshown = true;
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
		$("#harold").off(prefs.click_event);
		$("#harold").on(prefs.click_event, reset );
		$("#harold").html("Stop Download");
		$('#doobag').off(prefs.click_event);
	}

}

function get_image_newpos(where) {
	return where.parent().parent();
}

function create_imageeditor(newpos) {
	return $('<div>', {id: "imageeditor", class: "containerbox highlighted dropshadow"}).appendTo(newpos);
}

async function get_album_deets(imgobj) {
	return {
		phrase: decodeURIComponent(imgobj.parent().find('input[name="searchterm"]').val()),
		path: imgobj.parent().find('input[name="albumpath"]').val()
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
	$("#doobag").off(prefs.click_event);
	fetch(
		'utils/findsmallimages.php',
		{
			priority: 'low',
			cache: 'no-store',
			signal: AbortSignal.timeout(1800000)
		}
	)
	.then(response => {
		if (response.ok) {
			return response.json();
		} else {
			throw new Error('FindSmallImages : '+response.status+' '+response.statusText);
		}
	})
	.then(data => {
		$('#doobag').stopFlasher().remove();
		debug.debug("SMALL IMAGES","Got List!",data);
		for (var i in data) {
			$('img[name="'+data[i]+'"]').attr('src', 'newimages/transparent.png')
				.addClass('notexist').removeAttr('data-src').removeClass('lazy');
		}
		coverscraper.reset($('.notexist:not(.notfound)').length + $('.notfound:not(.notexist)').length);
	})
	.catch(err => {
		debug.error('ALBUMART', err);
		$('#doobag').html("FAILED!").stopFlasher();
	});
}

function aADownloadFinished() {
	if (running == true) {
		running = false;
		$("#harold").off(prefs.click_event);
		$("#harold").on(prefs.click_event, start );
		$("#doobag").off(prefs.click_event);
		$("#doobag").on(prefs.click_event, getsmall );
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
	$("#harold").on(prefs.click_event,  start );
	$("#doobag").on(prefs.click_event,  getsmall );
	$("#finklestein").on(prefs.click_event,  boogerbenson );
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
	wobblebottom.on(prefs.click_event, onWobblebottomClicked);
	$('.droppable').on('dragenter', dragEnter);
	$('.droppable').on('dragover', dragOver);
	$('.droppable').on('dragleave', dragLeave);
	$('.droppable').on('drop', imageEditor.handleDrop);
	$(document).on('mouseenter', '.clearbox', makeHoverWork);
	$(document).on('mouseleave', '.clearbox', makeHoverWork);
	$(document).on('mousemove', '.clearbox', makeHoverWork);
	$(document).on(prefs.click_event, '.clearbox.enter', makeClearWork);

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

function wobbleMyBottom() {
	var ws = getWindowSize();
	var newheight = ws.y - wobblebottom.offset().top;
	wobblebottom.css("height", newheight.toString()+"px");
	imageEditor.setWidth();
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
