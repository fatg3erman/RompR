function getPosition(e) {
	e = e || window.event;
	var cursor = {x:0, y:0};
	if (e.pageX || e.pageY) {
		cursor.x = e.pageX;
		cursor.y = e.pageY;
	}
	else {
		var de = document.documentElement;
		var b = document.body;
		cursor.x = e.clientX +
			(de.scrollLeft || b.scrollLeft) - (de.clientLeft || 0);
		cursor.y = e.clientY +
			(de.scrollTop || b.scrollTop) - (de.clientTop || 0);
	}
	return cursor;
}

function getWindowSize() {

	return {
		x: $(window).width(),
		y: $(window).height(),
		o: window.orientation
	};

}

// Turn an object into something that can be sent as POST data using fetch().
// Returns URLSearchParams()
// var opts = {
// 	command: 'search',
// 	resultstype: 'collection',
// 	domains: ['spotify', 'youtube'],
// 	dump: 'bartistroot',
// 	mpdsearch: {any: ['boobs']}
// }
// var formdata = object_to_postdata(opts);
// then use formdata.toString() as the body of the POST request
// command=search&resultstype=collection&domains%5B%5D=spotify&domains%5B%5D=youtube&dump=bartistroot&mpdsearch%5Bany%5D%5B%5D=boobs

// WON'T work if an array value contains an object value
// eg ['arse', 'cheese', {boobs: 'fruit'}]
// but I can't think of any time where that would ever crop up

function object_to_postdata(object, formdata, prevtag) {
	if (!formdata)
		formdata = new URLSearchParams();

	if (typeof object != 'object') {
		formdata.append(prevtag, object);
	} else if (Array.isArray(object)) {
		var key = prevtag + '[]';
		object.forEach(val => {
			formdata.append(key, val)
		});
	} else {
		$.each(object, (key, value) => {
			var newkey = (prevtag) ? prevtag+'['+key+']' : key;
			formdata = object_to_postdata(value, formdata, newkey)
		});
	}
	return formdata;
}

function zeroPad(num, count) {
	var numZeropad = num + '';
	while(numZeropad.length < count) {
		numZeropad = "0" + numZeropad;
	}
	return numZeropad;
}

function rawurlencode (str) {
	str = (str+'').toString();
	return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A');
}

function htmlspecialchars_decode(string) {
	if (string) {
		string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&#0*39;/g, "'").replace(/&quot;/g, '"');
		string = string.replace(/&amp;/g, '&');
	}
	return string;
}

function formatTimeString(duration) {
	if (duration > 0) {
		var secs=duration%60;
		var mins = (duration/60)%60;
		var hours = duration/3600;
		if (hours >= 1) {
			return parseInt(hours.toString()) + ":" + zeroPad(parseInt(mins.toString()), 2) + ":" + zeroPad(parseInt(secs.toString()),2);
		} else {
			return parseInt(mins.toString()) + ":" + zeroPad(parseInt(secs.toString()),2);
		}
	} else {
		return "&nbsp;";
	}
}

function getArray(data) {
	try {
		switch (typeof data) {
			case "object":
				if (data.length) {
					return data;
				} else {
					return [data];
				}
				break;
			case "undefined":
				return [];
				break;
			default:
				return [data];
				break;
		}
	} catch(err) {
		return [];
	}
}

function utf8_encode(s) {
  return unescape(encodeURIComponent(s));
}

function escapeHtml(text) {
	if (!text) return '';
	return text
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

function unescapeHtml(text) {
	if (!text) return '';
  	return text
		.replace(/&amp;/g, "&")
	  	.replace(/&lt;/g, "<")
	  	.replace(/&gt;/g, ">")
	  	.replace(/&quot;/g, '"')
	  	.replace(/&#039;/g, "'");
}

String.prototype.capitalize = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
}

String.prototype.initcaps = function() {
	return this.charAt(0).toUpperCase() + this.slice(1).toLowerCase();
}

String.prototype.removePunctuation = function() {
	var punctRE = /[\u2000-\u206F\u2E00-\u2E7F\\'!"#\$%&\(\)\*\+,\-\.\/:;<=>\?@\[\]\^_`\{\|\}~]/g;
	var nopunc = this.replace(/\s*\&\s*/, ' and ').replace(punctRE,'').replace(/\s+/g, ' ');
	return nopunc.toLowerCase();
}

String.prototype.fixDodgyLinks = function() {
	var regexp = /([^"])(https*:\/\/.*?)([<|\n|\r|\s|\)])/g;
	return this.replace(regexp, '$1<a href="$2" target="_blank">$2</a>$3');
}

String.prototype.isArtistOrAlbum = function() {
	if (this.indexOf(':artist:') > -1
		|| this.indexOf(':album:') > -1
	) {
		return true;
	} else {
		return false;
	}
}

function setCookie(cname, cvalue, exdays) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = "expires="+d.toUTCString();
	document.cookie = cname + "=" + cvalue + "; " + expires + '; path=/;SameSite=Lax';
}

function getCookie(cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
	}
	return "";
}

function getLocale() {
	if (navigator.browserLanguage) {
		return navigator.browserLanguage;
	}
	if (navigator.languages && navigator.languages.length) {
		return navigator.languages[0];
	}
	if (navigator.language) {
		return navigator.language;
	}
	return 'en-GB';
}

$.fn.hasAttr = function(name) {
   return this.attr(name) !== undefined;
}

function openAlbumArtManager() {
	window.open('albumart.php');
}

function reloadWindow() {
	var a = window.location.href;
	if (a.match(/index.php/)) {
		location.assign(a.replace(/index.php/,''));
	} else {
		location.reload(true);
	}
}

function arraycompare(a, b) {
	if (a.length != b.length) {
		return false;
	}
	for (var i in a) {
		if (a[i] != b[i]) {
			return false;
		}
	}
	return true;
}

function onStorageChanged(e) {

	debug.debug("GENERAL","Storage Event",e);

	if (e.key == "key" && e.newValue != "Blerugh") {
		var key = e.newValue;
		debug.trace("GENERAL","Updating album image for key",key);
		debug.debug('GENERAL', e);
		if (key.substring(0,1) == "!") {
			key = key.substring(1,key.length);
			update_failed_ui_images(key)
		} else {
			var images = JSON.parse(localStorage.getItem('albumimg_'+key));
			update_ui_images(key, images);
			localStorage.removeItem('albumimg_'+key);
		}
	}
}

function update_ui_images(key, images) {
	$.each(images, function(i,v) {
		if (i != 'delaytime') {
			images[i] = images[i]+'?version='+Date.now();
		}
	});
	$('img[name="'+key+'"]').removeClass("notexist notfound").attr("src", "").hide().show();
	$('img[name="'+key+'"]').not('.jalopy').not('.droppable').attr("src", images.small);
	$('img[name="'+key+'"].jalopy').attr("src", images.medium);
	$('img[name="'+key+'"].droppable').attr("src", images.medium);
	if (typeof(IntersectionObserver) == 'function') {
		$('img[name="'+key+'"].lazy').get().forEach(img => imageLoader.unobserve(img));
		$('img[name="'+key+'"].lazy').removeClass('lazy');
	}
	if (typeof(infobar) != 'undefined') {
		infobar.albumImage.setSource({images: images, ImgKey: key});
	}
}

function update_failed_ui_images(key) {
	$('img.notexist[name="'+key+'"]').removeClass("notexist").removeClass('notfound').addClass("notfound").removeAttr('src');
}

// function preventDefault(ev) {
// 	evt = ev.originalEvent;
// 	evt.stopPropagation();
// 	evt.preventDefault();
// 	return false;
// }

function joinartists(ob) {
	if (typeof(ob) != "object") {
		return ob;
	} else {
		if (typeof(ob[0]) == "string") {
			// As returned by MPD in its Status ie for Performer
			// However these are returned as an Object rather than as an Array and we need an array
			// (Yes, arrays and object are the same, more or less, but Objects don't have a slice method)
			var a = new Array();
			for (var i in ob) {
				a.push(ob[i]);
			}
			return concatenate_artist_names(a);
		} else {
			var t = new Array();
			for (var i in ob) {
				var flub = ""+ob[i].name;
				t.push(flub);
			}
			return concatenate_artist_names(t);
		}
	}
}

function concatenate_artist_names(t) {
	var shitte = structuredClone(t);
	var f = shitte.pop();
	if (shitte.length == 0) {
		return f;
	} else {
		return [shitte.join(', '), f].join(' & ');
	}
}

function randomsort(a,b) {
	if (Math.random() > 0.5) {
		return 1;
	} else {
		return -1;
	}
}

function lastIndexOf(a) {
	var retval = 0;
	a.forEach(function(value, index) {
		retval = index;
	});
	return retval;
}

function get_file_extension(filename) {
	if (filename.lastIndexOf('.') == -1)
		return 'Unknown';

	let poop = filename.substring(filename.lastIndexOf('.')+1, filename.length) || 'Unknown';

	let poop2 = poop.substring(0, poop.indexOf('?')) || poop;

	return poop2;
}

function uiLoginBind() {
	if (!prefs.lastfm_logged_in) {
		$('.lastfmlogin-required').removeClass('notenabled').addClass('notenabled');
		$('input[name="lfmuser"]').val('');
		$('#lastfmloginbutton').off(prefs.click_event).on(prefs.click_event, lastfm.startlogin).html(language.gettext('config_loginbutton')).removeClass('notenabled').addClass('notenabled');
	} else {
		$('.lastfmlogin-required').removeClass('notenabled');
		$('#lastfmloginbutton').off(prefs.click_event).on(prefs.click_event, lastfm.logout).html(language.gettext('button_logout')).removeClass('notenabled');
	}
}

function get_css_variable(name) {
	return getComputedStyle(document.documentElement).getPropertyValue(name);
}

function set_css_variable(name, value) {
	document.documentElement.style.setProperty(name, value);
}

function unset_css_variable(name) {
	document.documentElement.style.removeProperty(name);
}

function data_from_source(script_name) {
    return JSON.parse($('script[name="'+script_name+'"]').text());
}

// Warn if overriding existing method
if(Array.prototype.equals)
    debug.warn('INIT', "Overriding existing Array.prototype.equals.");
// attach the .equals method to Array's prototype to call it on any array
Array.prototype.equals = function (array) {
    // if the other array is a falsy value, return
    if (!array)
        return false;

    // compare lengths - can save a lot of time
    if (this.length != array.length)
        return false;

    for (var i = 0, l=this.length; i < l; i++) {
        // Check if we have nested arrays
        if (this[i] instanceof Array && array[i] instanceof Array) {
            // recurse into the nested arrays
            if (!this[i].equals(array[i]))
                return false;
        }
        else if (this[i] != array[i]) {
            // Warning - two different object instances will never be equal: {x:20} != {x:20}
            return false;
        }
    }
    return true;
}
// Hide method from for-in loops
Object.defineProperty(Array.prototype, "equals", {enumerable: false});

var imageEditor = function() {

	var offset = 0;
	var position = null;
	var bigdiv = null;
	var bigimg = new Image();
	var imgobj = null;
	var imagekey = '';
	var currparent = null;
	var currhighlight = null;
	var currname = null;
	var current = "g";
	var clickindex = null;
	var nosource = false;
	var searchcontent;
	var localimages;
	bigimg.onload = function() {
		imageEditor.displayBigImage();
	}

	function startAnimation() {
		imgobj.removeClass('nospin').removeAttr('src').addClass('spinner');
	}

	function animationStop() {
		imgobj.removeClass('spinner').addClass('nospin');
	}

	// Ceci n'est pas une commentaire

	function updateImage(url, index) {
		clickindex = index;
		imgobj.removeClass('notfound notexist').addClass('notfound');
		imageEditor.updateBigImg(true);
		startAnimation();
		var formData = coverscraper.getImageFormParams(imgobj);
		formData.append('source', url);
		fetch(
			"utils/getalbumcover.php",
			{
				method: 'POST',
				body: formData,
				signal: AbortSignal.timeout(30000),
				priority: 'low'
			}
		)
		.then(response => {
			if (response.ok) {
				return response.json();
			} else {
				throw new Error('Ah balls');
			}
		})
		.then(data => { imageEditor.uploadComplete(data) })
		.catch(imageEditor.searchFail);
	}

	return {

		show: async function(where) {
			var newpos = get_image_newpos(where);
			if (where.attr('name') == currname) {
				imageEditor.close();
				return true;
			}
			if (currparent !== null) {
				imageEditor.close();
			}
			currname = where.attr('name');
			bigdiv = create_imageeditor(newpos);
			bigdiv.on(prefs.click_event, imageEditor.onGoogleSearchClicked);
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
			var deets = await get_album_deets(imgobj);
			var phrase = deets.phrase;
			var path = deets.path;
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
			but.on(prefs.click_event, imageEditor.uploadFile);

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
				fetch(
					"utils/findLocalImages.php?path="+encodeURIComponent(path),
					{
						priority: 'low',
						cache: 'no-store',
						signal: AbortSignal.timeout(10000)
					}
				)
				.then(response => {
					if (response.ok) {
						return response.json();
					} else {
						throw new Error(response.statusText);
					}
				})
				.then(data => { imageEditor.gotLocalImages(data) })
				.catch(err => { debug.error('LOCALIMAGES', err) });
			}

			var searchparams = coverscraper.getImageSearchParams(imgobj);
			$('input#uploadkey').val(searchparams.key);
			$('input#uploadartist').val(searchparams.artist);
			$('input#uploadalbum').val(searchparams.album);
			$('#searchphrase').on('keyup', imageEditor.bumblefuck);
			wobbleMyBottom();
			if (prefs.has_custom_scrollbars && $('#coverslist').length > 0)
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

		setHeight: function() {
			var t = imgobj.offset().top;
			var ws = getWindowSize();
			var h = ws.y - t - 16;
			if (h < 400) {
				t = Math.max(0, t-(400-h));
				h = ws.y - t - 16;
			}
			bigdiv.css({top: t+'px', height: h+'px'});
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
			debug.log("BRAVE", "Searching with offset", offset);
			brave.image.search(
				$("#searchphrase").val(),
				offset,
				imageEditor.braveSearchComplete,
				imageEditor.braveSearchComplete,
			);
		},

		braveSearchComplete: function(data) {
			debug.debug("IMAGEEDITOR","Brave Search Results", data);
			$("#morebutton").remove();
			if (data.results) {
				var i = 0;
				data.results.forEach(function(image) {
					if (!image.properties.width)
						image.properties.width = '?';
					if (!image.properties.height)
						image.properties.height = '?';
					$('#searchresults').append(imageEditor.imageResult(
						{
							thumbnail: image.thumbnail.src,
							dimensions: image.properties.width.toString()+'x'+image.properties.height.toString(),
							hostpage: image.source,
							title: image.title,
							name: image.name,
							id: i,
							fullurl: image.properties.url
						}
					));
					i++;
				});
				if (data.more_results_available && data.more_results_available == 'true') {
					offset += 20;
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
				container.append($('<div>', {class: 'playlistrow2 breakall'}).html(options.name));
			if (options.dimensions)
				container.append($('<div>', {class: 'playlistitem'}).html(options.dimensions));
			if (options.title)
				container.append($('<div>', {class: 'playlistitem'}).html(options.title));
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
			if (data && data.error) {
				$("#localresults").html('<h3>'+data.error+'</h3>');
			} else if (data && data.length > 0) {
				if (data.hasOwnProperty('error')) {
				} else {
					data.forEach(function(image) {
						$("#localresults").append(imageEditor.imageResult({
							thumbnail: image,
							dimensions: false,
							hostpage: false,
							name: image.split(/[\\/]/).pop(),
							id: hex_md5(image),
							fullurl: image
						}));
					});
				}
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
			var formData = new FormData(formElement);
			fetch(
				"utils/getalbumcover.php",
				{
					method: 'POST',
					signal: AbortSignal.timeout(20000),
					priority: 'low',
					body: formData
				}
			)
			.then(response => {
				if (response.ok) {
					return response.json();
				} else {
					throw new Error('Balls '+response.status+' '+response.statusText);
				}
			})
			.then(data => { imageEditor.uploadComplete(data) })
			.catch(err => {
				debug.error('ALBUMART', 'Upload Failed '+err);
				imageEditor.searchFail();
			});
		},

		uploadComplete: function(data) {
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
		},

		searchFail: function() {
			debug.info("ALBUMART","No Source Found");
			$('#'+clickindex).attr('src', 'newimages/imgnotfound.svg');
			imgobj.removeClass('notfound notexist').addClass('notexist');
			imageEditor.updateBigImg(false);
			animationStop();
		},

		handleDrop: function(ev) {
			debug.log("ALBUMART","Dropped",ev);
			evt = ev.originalEvent;
			$(ev.target).removeClass("highlighted");
			imgobj = $(ev.target);
			imagekey = imgobj.attr("name");
			nosource = (imgobj.hasClass('notfound') || imgobj.hasClass('notexist'));
			clickindex = null;
			dropProcessor(ev.originalEvent, imgobj, coverscraper, imageEditor.uploadComplete, imageEditor.searchFail);
		}


	}

}();

function findClickableElement(event) {

	var clickedElement = $(event.target);
	// Search upwards through the parent elements to find the clickable object
	while (!clickedElement.hasClass("clickable") &&
			clickedElement.prop("id") != "wobblebottom" &&
			clickedElement.prop("id") != "imageeditor" &&
			clickedElement.prop("id") != "searchcontent") {
		clickedElement = clickedElement.parent();
	}
	return clickedElement;

}
