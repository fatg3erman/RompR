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

function zeroPad(num, count)
{
    var numZeropad = num + '';
    while(numZeropad.length < count) {
        numZeropad = "0" + numZeropad;
    }
    return numZeropad;
}

function cloneObject(obj) {
    return JSON.parse(JSON.stringify(obj));
}

function rawurlencode (str) {
    str = (str+'').toString();
    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A');
}

function htmlspecialchars_decode(string) {
    string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&#0*39;/g, "'").replace(/&quot;/g, '"');
    string = string.replace(/&amp;/g, '&');
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
        return "";
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
    return this.replace(/\s*\&\s*/, ' and ').replace(punctRE,'').replace(/\s+/g, ' ');
}

String.prototype.fixDodgyLinks = function() {
    var regexp = /([^"])(https*:\/\/.*?)([<|\n|\r|\s|\)])/g;
    return this.replace(regexp, '$1<a href="$2" target="_blank">$2</a>$3');
}

String.prototype.isArtistOrAlbum = function() {
    if (this.indexOf(':artist:') > -1 || this.indexOf(':album:') > -1) {
        return true;
    } else {
        return false;
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires + '; path=/';
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

    debug.log("GENERAL","Storage Event",e);

    if (e.key == "key" && e.newValue != "Blerugh") {
        var key = e.newValue;
        debug.log("GENERAL","Updating album image for key",key,e);
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
    $('img[name="'+key+'"]').not('.jalopy').attr("src", images.small);
    $('img[name="'+key+'"].jalopy').attr("src", images.medium);
    if (typeof(infobar) != 'undefined') {
        infobar.albumImage.setSource({images: images, key: key});
    }
}

function update_failed_ui_images(key) {
    $('img.notexist[name="'+key+'"]').removeClass("notexist").removeClass('notfound').addClass("notfound").removeAttr('src');
}

function preventDefault(ev) {
    evt = ev.originalEvent;
    evt.stopPropagation();
    evt.preventDefault();
    return false;
}

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
    if (t.length == 0) {
        return "";
    } else if (t.length == 1) {
        return t[0];
    } else if (t.length == 2) {
        return t.join(" & ");
    } else {
        var f = t.slice(0, t.length-1);
        return f.join(", ") + " & " + t[t.length-1];
    }
}

function randomsort(a,b) {
    if (Math.random() > 0.5) {
        return 1;
    } else {
        return -1;
    }
}
