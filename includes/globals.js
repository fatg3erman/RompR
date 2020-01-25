var sources = new Array();
var last_selected_element = null;
var resizeTimer = null;
const masonry_gutter = 8;
var coverscraper;
var lastfm;
var albumart_update = true;
var visibilityHidden, visibilityChange;
if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
  visibilityHidden = "hidden";
  visibilityChange = "visibilitychange";
} else if (typeof document.msHidden !== "undefined") {
  visibilityHidden = "msHidden";
  visibilityChange = "msvisibilitychange";
} else if (typeof document.webkitHidden !== "undefined") {
  visibilityHidden = "webkitHidden";
  visibilityChange = "webkitvisibilitychange";
}
