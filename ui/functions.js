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
