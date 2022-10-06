// The world's smallest jQuery plugin :)
jQuery.fn.reverse = [].reverse;
// http://www.mail-archive.com/discuss@jquery.com/msg04261.html

jQuery.fn.removeInlineCss = function(property) {

	if(property == null)
		return this.removeAttr('style');

	var proporties = property.split(/\s+/);

	return this.each(function(){
		var remover =
			this.style.removeProperty   // modern browser
			|| this.style.removeAttribute   // old browser (ie 6-8)
			|| jQuery.noop;  //eventual

		for(var i = 0 ; i < proporties.length ; i++)
			remover.call(this.style,proporties[i]);

	});
};

jQuery.fn.makeFlasher = function(options) {
	var settings = $.extend({
		flashtime: 4,
		easing: "ease",
		repeats: "infinite"
	}, options);

	return this.each(function() {
		if (!$(this).hasClass('flashing')) {
			var anistring = "pulseit "+settings.flashtime+"s "+settings.easing+" "+settings.repeats;
			$(this).css({"animation": "", "opacity": ""});
			$(this).hide().show();
			$(this).css({"animation": anistring});
			$(this).addClass('flashing');
		}
	});
}

jQuery.fn.stopFlasher = function() {
	return this.each(function() {
		if ($(this).hasClass('flashing')) {
			$(this).css({"animation": "","opacity": ""});
			$(this).removeClass('flashing');
		}
	});
}

jQuery.fn.switchToggle = function(state) {
	return this.each(function() {
		var st = (state == 0 || state == "off" || !state) ? "icon-toggle-off" : "icon-toggle-on";
		$(this).removeClass("icon-toggle-on icon-toggle-off").addClass(st);
	});
}

jQuery.fn.flowToggle = function(state) {
	return this.each(function() {
		$(this).removeClass("flow-on flow-off").addClass('flow-'+state);
	});
}

$.widget("rompr.trackdragger", $.ui.mouse, {
	options: {

	},

	_create: function() {
		this.dragging = false;
		this._mouseInit();
	},

	_mouseCapture: function() {
		return true;
	},

	_mouseStart: function(event) {
		var clickedElement = function(e) {
			while (!e.hasClass('draggable') && !e.is('body')) {
				e = e.parent();
			}
			return e;
		}($(event.target));
		if (!clickedElement.hasClass('draggable')) {
			return false;
		}
		this.dragging = true;
		if (!clickedElement.hasClass("selected")) {
			selectPlayable(event, clickedElement);
		}
		this.dragger = $('<div>', {id: 'dragger', class: 'draggable dragsort containerbox vertical dropshadow'}).appendTo('body');
		this.dragger.css('width', $('.selected').first().css('width'));
		if ($(".selected").length > 6) {
			this.dragger.append('<div class="containerbox menuitem playable_proxy">'+
				'<div class="smallcover fixed"><i class="icon-music svg-square"></i></div>'+
				'<div class="expand"><h3>'+$(".selected").not('.clickdisc').length+' Items</h3></div>'+
				'</div>');
		} else {
			$(".selected").clone().removeClass("selected").css('width', '100%').appendTo(this.dragger);
		}
		// Little hack to make dragging from the various tag/rating/playlist managers
		// look prettier
		this.dragger.find('tr').wrap('<table></table>');
		this.dragger.find('.icon-cancel-circled').remove();
		this.drag_x_offset = event.pageX - clickedElement.offset().left;
		var pos = {top: event.pageY - 12, left: event.pageX - this.drag_x_offset};
		this.dragger.css({top: pos.top+"px", left: pos.left+"px"});
		this.dragger.fadeIn('fast');
		$('.trackacceptor').acceptDroppedTracks('dragstart');
		return true;
	},

	_mouseDrag: function(event) {
		if (this.dragging) {
			var pos = {top: event.pageY - 12, left: event.pageX - this.drag_x_offset};
			this.dragger.css({top: pos.top+"px", left: pos.left+"px"});
		}
		// To cope with one trackacceptor covering another (eg the alarm editor covering
		// the play queue) we loop through them in reverse, which *should* put them in
		// z-index order. We set is_over to the first one that reports the dragger is
		// over it. Then we call each one with the value of is_over so it can check whether
		// the dragger is over it; sometimes they need to do something on dragOut, and they
		// need to remove any highlighted class they may have, so we do need to loop through
		// all of them.
		var is_over = null;
		$('.trackacceptor').reverse().each(function() {
			if ($(this).acceptDroppedTracks('checkMouseOver', event)) {
				is_over = this;
				return false;
			}
		});
		$('.trackacceptor').acceptDroppedTracks('check_is_over', is_over, event);
		return true;
	},

	_mouseStop: function(event) {
		this.dragging = false;
		this.dragger.remove();
		$('.trackacceptor').reverse().each(function() {
			if ($(this).acceptDroppedTracks('dragstop', event)) {
				return false;
			}
		});
		return true;
	}

});

$.widget("rompr.acceptDroppedTracks", {
	options: {
		ondrop: null,
		coveredby: null,
		scroll: false,
		scrollparent: '',
		started_sortable_drag: false,
		useclick: false,
		popup: null,
		notifier: null,
		hidepanel: null
	},

	_create: function() {
		if (!uiHelper.is_touch_ui) {
			this.element.addClass('trackacceptor');
		} else if (this.options.useclick) {
			this.element.on('click', $.proxy(this.useClick, this));
		}
		this.dragger_is_over = false;
	},

	dragstart: function() {
		this.dragger_is_over = false;
		// For custom scrollbars the bounding box needs to be the scrollparent
		var vbox = (this.options.scroll) ? $(this.options.scrollparent) : this.element;
		this.bbox = {
			left:   this.element.offset().left,
			top:    Math.max(vbox.offset().top, this.element.offset().top),
			right:  this.element.offset().left + this.element.width(),
			bottom: Math.min(vbox.offset().top + vbox.height(), this.element.offset().top + this.element.height())
		}
		if (this.options.coveredby !== null) {
			// ONLY works in playlist for sending correct events to correct targets
			this.bbox.top = $(this.options.coveredby).offset().top + $(this.options.coveredby).height();
		}
		if (this.element.hasClass('sortabletracklist')) {
			this.element.sortableTrackList('dragstart');
		}

	},

	dragstop: function(event) {
		debug.debug("UITHING","dragstop",this.element.attr("id"));
		if (this.dragger_is_over && this.options.ondrop !== null) {
			debug.log("UITHING","Dropped onto wotsit thingy",this.element.attr("id"));
			this.dragger_is_over = false;
			this.element.removeClass('highlighted');
			this.options.ondrop(event, this.element);

			if (this.options.notifier)
				infobar.removenotify(this.options.notifier);

			if (this.options.popup)
				this.options.popup.unhide();

			if (this.options.hidepanel)
				this.options.hidepanel.show();

			return true;
		}
		if (this.dragger_is_over && this.element.hasClass('sortabletracklist')) {
			debug.log("UITHING","Dropped onto sortable tracklist",this.element.attr("id"));
			this.dragger_is_over = false;
			this.element.removeClass('highlighted');
			this.element.sortableTrackList('dropped', event);
			return true;
		}
		this.dragger_is_over = false;
		this.element.removeClass('highlighted');
		return false;
	},

	checkMouseOver: function(event) {
		if (event.pageX > this.bbox.left && event.pageX < this.bbox.right &&
			event.pageY > this.bbox.top && event.pageY < this.bbox.bottom) {

			return true;

		} else {
			return false;
		}
	},

	check_is_over: function(is_over, event) {
		if (is_over == this.element[0]) {
			this.dragger_is_over = true;
			if (!this.element.hasClass('highlighted'))
				this.element.addClass('highlighted');

			if (this.element.hasClass('sortabletracklist'))
				this.element.sortableTrackList('do_intersect_stuff', event, $("#dragger"));

		} else if (this.dragger_is_over) {
			if (this.element.hasClass('sortabletracklist'))
				this.element.sortableTrackList('dragleave');

			this.element.removeClass('highlighted');
			this.dragger_is_over = false;
		}
	},

	useClick: function() {
		if (this.options.popup)
			this.options.popup.hide();

		if (this.options.hidepanel)
			this.options.hidepanel.hide();

		this.options.notifier = infobar.permnotify('Select an item to Play');
		playlist.addProxyCommand($.proxy(this.dragstop, this));
		this.dragger_is_over = true;
	}

 });

$.widget("rompr.sortableTrackList", $.ui.mouse, {
	options: {
		items: '',
		outsidedrop: null,
		insidedrop: null,
		scroll: false,
		scrollparent: '',
		bbox: false,
		scrollspeed: 0,
		scrollzone: 0,
		allowdragout: false
	},

	_create: function() {
		this.element.addClass('sortabletracklist');
		this.helper = null;
		this.dragger = null;
		this.dragging = false;
		this.draggingout = false;
		this._scrollcheck = null;
		this._mouseInit();
	},

	dragstart: function() {
		// For custom scrollbars the bounding box needs to be the scrollparent
		var vbox = (this.options.scroll) ? $(this.options.scrollparent) : this.element;
		this.bbox = {
			left:   this.element.offset().left,
			right:  this.element.offset().left + this.element.width(),
			top:    vbox.offset().top,
			bottom: vbox.offset().top + vbox.height()
		}
		if (this.helper) this.helper.remove();
		this.helper = null;
	},

	do_intersect_stuff: function(event, item) {
		// This is vertical sortable lists so we're only gonna care
		// about vertical sorting.
		var self = this;
		clearTimeout(this._scrollcheck);
		this._mouseEvent = event;
		this._item = item;
		var scrolled = this._checkScroll(event);
		this.element.find(this.options.items).each(function() {
			var jq = $(this);
			var bbox = {
				top: jq.offset().top,
				middle: jq.offset().top + jq.height()/2,
				bottom: jq.offset().top + jq.height()
			}
			if (event.pageY > bbox.top && event.pageY <= bbox.middle) {
				// Put a helper above the current item
				self._checkHelper.call(self, item);
				self.helper.detach().insertBefore(jq);
				return false;
			} else if (event.pageY > bbox.middle && event.pageY < bbox.bottom) {
				self._checkHelper.call(self, item);
				self.helper.detach().insertAfter(jq);
				return false;
			}
		});
		if (scrolled) {
			this._scrollcheck = setTimeout($.proxy(this._checkMouseHover, this), 100);
		}

	},

	_checkHelper: function(item) {
		if (this.helper) return true;
		if (this.element.find(this.options.items).first().is('tr')) {
			this.helper = $('<tr>', {
				id: this.element.attr('id')+'_sorthelper',
			});
		} else {
			this.helper = $('<div>', {
				id: this.element.attr('id')+'_sorthelper',
			});
		}
		this.helper.css('height', (item.height()+12)+"px");
		this.helper.attr('class', (item.children().first().hasClass('playable') || item.children().first().hasClass('playable_proxy')) ? 'draggable' : 'something');
		this.helper.empty();
	},

	_checkScroll: function(event) {
		// Custom Scrollbars ONLY
		var scrolled = false;
		if (this.options.scroll) {
			if (event.pageY < this.bbox.top + this.options.scrollzone) {
				$(this.options.scrollparent).mCustomScrollbar('scrollTo', '+='+this.options.scrollspeed, {scrollInertia: 100, scrollEasing: "easeOut"});
				scrolled = true;
			} else if (event.pageY > this.bbox.bottom - this.options.scrollzone) {
				$(this.options.scrollparent).mCustomScrollbar('scrollTo', '-='+this.options.scrollspeed, {scrollInertia: 100, scrollEasing: "easeOut"});
				scrolled = true;
			}
		}
		return scrolled;

	},

	_checkMouseHover: function() {
		this.do_intersect_stuff(this._mouseEvent, this._item);
	},

	dragleave: function() {
		if (this.helper) this.helper.remove();
		this.helper = null;
		clearTimeout(this._scrollcheck);
	},

	dropped: function(event) {
		// This is called when something from OUTSIDE the list has been dropped onto us
		debug.debug("STL","Dropped",event);
		clearTimeout(this._scrollcheck);
		if (this.helper) {
			this.options.outsidedrop(event, this.helper);
		}
	},

	// Local dragging functions

	_findDraggable: function(event) {
		var el = $(event.target);
		while (!el.hasClass(this.options.items.replace(/^\./,'')) && el != this.element) {
			el = el.parent();
		}
		if (el.hasClass('sortable')) {
			// Special case for playlist
			return el;
		}
		if (!el.hasClass('selected')) {
			el.addClass('selected');
		}
		return this.element.find('.selected');
	},

	_mouseStart: function(event) {
		debug.debug("SORTABLE","Mouse Start",event);
		var self = this;
		if (this.dragger) this.dragger.remove();
		var dragged = self._findDraggable(event);
		if (dragged.first().is('tr')) {
			this.dragger = $('<table>').appendTo('body');
		} else {
			this.dragger = $('<div>').appendTo('body');
		}
		this.dragger.css({
			position: 'absolute',
			top: dragged.first().offset().top + 'px',
			left: dragged.first().offset().left + 'px',
			width: dragged.first().width() + 'px'
			// 'z-index': 1500
		});
		self.dragged_elements = new Array();
		$.each(dragged, function() {
			var d = $(this);
			if (d.prev().length > 0) {
				self.dragged_elements.push({
					dragged_original_pos: d.prev(),
					dragged_original_type: 'after',
					dragged_original_element: d.clone()
				});
			} else if (d.next().length > 0) {
				self.dragged_elements.push({
					dragged_original_pos: d.next(),
					dragged_original_type: 'before',
					dragged_original_element: d.clone()
				});
			} else {
				self.dragged_elements.push({
					dragged_original_pos: d.parent(),
					dragged_original_type: 'into',
					dragged_original_element: d.clone()
				});
			}
			d.detach().removeClass('selected').appendTo(self.dragger);
		});
		this.drag_x_offset = event.pageX - this.dragger.offset().left;
		this.dragger.addClass('dropshadow').find('.icon-cancel-circled').remove();
		if (this.helper) this.helper.remove();
		this.helper = null;
		this._checkHelper(dragged);
		switch (this.dragged_elements[0].dragged_original_type) {
			case 'after':
				this.helper.detach().insertAfter(this.dragged_elements[0].dragged_original_pos);
				break;

			case 'before':
				this.helper.detach().insertBefore(this.dragged_elements[0].dragged_original_pos);
				break;

			case 'into':
				this.helper.detach().appendTo(this.dragged_elements[0].dragged_original_pos);
				break;

		}
		this.dragstart();
		this.dragging = true;
		return true;
	},

	_mouseDrag: function(event) {
		clearTimeout(this._scrollcheck);
		var self = this;
		if (this.dragging) {
			if ((event.pageX > this.bbox.right || event.pageX < this.bbox.left) &&
				this.options.allowdragout)
			{
				debug.trace('STR', 'Dragged Out');
				clearTimeout(this._scrollcheck);
				this.dragging = false;
				this.draggingout = true;
				var pos = {top: event.pageY - 12, left: event.pageX - this.drag_x_offset};
				this.dragger.css({top: pos.top+"px", left: pos.left+"px"});
				for (var i = this.dragged_elements.length; i > 0; i--) {
					var element = this.dragged_elements[i-1];
					switch (element.dragged_original_type) {
						case 'before':
							element.dragged_original_element.removeClass('selected').addClass('selected').insertBefore(self.element.find(element.dragged_original_pos));
							break;

						case 'after':
							element.dragged_original_element.removeClass('selected').addClass('selected').insertAfter(self.element.find(element.dragged_original_pos));
							break;

						case 'into':
							element.dragged_original_element.removeClass('selected').addClass('selected').appendTo(self.element.find(element.dragged_original_pos));
							break;
					}
				}
				if (this.helper) {
					this.helper.detach();
				}

				this.dragger.attr('id','dragger');
				// this.dragger.css('z-index', 1500);
				this.dragger.addClass('draggable dragsort');
				$('.trackacceptor').acceptDroppedTracks('dragstart');
			} else {
				var pos = {top: event.pageY - 12, left: event.pageX - this.drag_x_offset};
				if (pos.top > this.bbox.top && pos.top < this.bbox.bottom) {
					this.dragger.css('top',pos.top+'px');
					if (this.options.allowdragout) {
						this.dragger.css('left',pos.left+'px');
					}
					this.do_intersect_stuff(event, this.dragger);
				}
			}
		} else if (this.draggingout) {
			var pos = {top: event.pageY - 12, left: event.pageX - this.drag_x_offset};
			this.dragger.css({top: pos.top+"px", left: pos.left+"px"});
			var is_over = null;
			$('.trackacceptor').reverse().each(function() {
				if ($(this).acceptDroppedTracks('checkMouseOver', event)) {
					is_over = this;
					return false;
				}
			});
			$('.trackacceptor').acceptDroppedTracks('check_is_over', is_over, event);
		}
		return true;
	},

	_mouseStop: function(event) {
		clearTimeout(this._scrollcheck);
		var self = this;
		if (this.dragging) {
			this.dragger.remove();
			var marker = this.helper;
			for (var i = 0; i < this.dragged_elements.length; i++) {
				var element = this.dragged_elements[i];
				marker = element.dragged_original_element.insertAfter(marker);
			};
			this.helper.remove();
			this.helper = null;
			this.dragging = false;
			if (this.options.insidedrop) {
				this.options.insidedrop(event, marker);
			}
		} else if (this.draggingout) {
			debug.trace("STL","Dragged out and onto something else");
			this.dragger.remove();
			this.draggedout = false;
			if (this.helper) this.helper.remove();
			this.helper = null;
			$('.trackacceptor').reverse().each(function() {
				if ($(this).acceptDroppedTracks('dragstop', event)) {
					return false;
				}
			});
		}
		return true;
	}
});

$.widget("rompr.resizeHandle", $.ui.mouse, {
	widgetEventPrefix: "resize",
	options: {
		side: 'left',
		offset: 0
	},

	_create: function() {
		this.dragging = false;
		this._mouseInit();
		this.element.css({cursor: "ew-resize"});
	},

	_mouseCapture: function(event) {
		this.dragging = true;
		this.startX = event.clientX;
		this.elementStartX = this.element.offset().left - this.options.offset;
		this.winsize = getWindowSize();
		this.widthadjust = this.element.outerWidth(true)/2;
		return true;
	},

	_mouseStart: function(event) {
		return true;
	},

	_mouseDrag: function(event) {
		if (this.dragging) {
			var distance = event.clientX - this.startX;
			if (this.options.side == 'left') {
				var size = Math.max(this.elementStartX + distance + this.widthadjust, 120);
				prefs.sourceswidthpercent = (size/this.winsize.x)*100;
			} else {
				var size = Math.max(this.winsize.x - (this.elementStartX + distance + this.widthadjust), 120);
				prefs.playlistwidthpercent = (size/this.winsize.x)*100;
			}
			if (prefs.sourceswidthpercent + prefs.playlistwidthpercent > 100 || prefs.hidebrowser) {
				if (this.options.side == 'left') {
					prefs.playlistwidthpercent = 100 - prefs.sourceswidthpercent;
				} else {
					prefs.sourceswidthpercent = 100 - prefs.playlistwidthpercent;
				}
			}
			this.options.donefunc();
		}
		return true;
	},

	_mouseStop: function(event) {
		this.dragging = false;
		browser.rePoint();
		prefs.save({sourceswidthpercent: prefs.sourceswidthpercent});
		prefs.save({playlistwidthpercent: prefs.playlistwidthpercent});
		return true;
	}

});

$.widget("rompr.rangechooser", $.ui.mouse, {

	options: {
		range: 1,
		ends: ['min','max'],
		allowed_min: 0,
		onstop: null,
		whiledragging: null,
		orientation: 'horizontal',
		startmin: 0,
		startmax: 1,
		interactive: true,
		animate: false
	},

	touch: null,

	_create: function() {
		this.dragging = false;
		var extraclass = (this.options.interactive) ? ' moveable' : '';
		switch (this.options.orientation) {
			case "horizontal":
				this.element.addClass('rangechooser progressbar'+extraclass);
				break;

			case "vertical":
				this.element.addClass('rangechooser progressbar_v'+extraclass);
				break;

			default:
				debug.error("RANGECHOOSER","Invalid orientation",this.options.orientation);
				break;


		}
		if (this.options.animate) {
		    this.element.addClass('animated');
		}
		this.min = this.options.startmin;
		this.max = this.options.startmax;
		if (this.options.interactive) {
			if (uiHelper.is_touch_ui) {
				// Use touch events if we're on a touch UI, otherwise
				// dragging doesn't work
				this.touch = null;
				this.element.on('touchstart', $.proxy(this._touchStart, this));
				this.element.on('touchmove', $.proxy(this._touchMove, this));
				this.element.on('touchend', $.proxy(this._touchEnd, this));
				this.element.on('touchcancel', $.proxy(this._touchEnd, this));
			} else {
				this._mouseInit();
			}
		}

		this.fill();
	},

	_mouseCapture: function(event) {
		this.dragging = true;
		this.dragWhich(event);
		this.update(event);
		if (this.options.onstop) {
			this.options.onstop(this.getRange());
		}
		return true;
	},

	_mouseDrag: function(event) {
		if (this.dragging) {
			this.update(event);
			if (this.options.whiledragging) {
				this.options.whiledragging(this.getRange());
			}
			return true;
		}
	},

	_mouseStop: function(event) {
		this.dragging = false;
		if (this.options.onstop) {
			this.options.onstop(this.getRange());
		}
		return true;
	},

	_touchStart: function(e) {
		if (e.touches.length == 1 && this.touch == null) {
			e.preventDefault();
			this.touch = e.touches[0].identifier;
			this._mouseCapture(e.touches[0]);
		}
	},

	_touchMove: function(e) {
		if (e.touches.length == 1 && this.touch == e.touches[0].identifier) {
			e.preventDefault();
			this._mouseDrag(e.touches[0]);
		}
	},

	_touchEnd: function(e) {
		if (e.changedTouches && typeof(e.changedTouches) == 'object') {
			for (let ct of e.changedTouches) {
				if (ct.identifier == this.touch) {
					e.preventDefault();
					this.touch = null;
					this._mouseStop(ct);
				}
			}
		}
	},

	update: function(event) {
		var position, fraction;
		if (this.options.orientation == "horizontal") {
			position = event.clientX - this.element.offset().left;
			fraction = position/this.element.width();
		} else {
			position = this.element.height()-(event.clientY - this.element.offset().top);
			fraction = position/this.element.height();
		}
		this[this.draggedElement] = fraction;
		if (this.max <= this.min) {
			this.max = this.min + 0.1;
		}
		if (this.min >= this.max) {
			this.min = this.max - 0.1;
		}
		this.min = Math.max(this.min, 0);
		this.max = Math.min(this.max, 1);
		this.max = Math.max(this.max, this.options.allowed_min);
		this.fill();
	},

	fill: function() {
		var gradients = new Array();
		if (this.options.animate) {
		    var rgbs = getrgbs(100,0);
		} else {
			var rgbs = getrgbs(this.max*100,this.min*100);
		}
		if (this.max == this.min || isNaN(this.min) || isNaN(this.max)) {
			gradients.push('transparent');
		} else if (this.options.orientation == "horizontal") {
			gradients.push("linear-gradient(to right, "+rgbs);
		} else {
			gradients.push("linear-gradient(to top, "+rgbs);
		}
		for (var i in gradients) {
			this.element.css("background", gradients[i]);
		}
		if (this.options.animate) {
		    if (this.max == this.min || isNaN(this.min) || isNaN(this.max) || this.max == 0) {
		        var pos = 0;
		    } else {
		        var pos = (this.element.width()*this.max)-this.element.width();
		    }
		    this.element.css({'background-position-x': pos+'px', 'background-repeat': 'no-repeat'});
		}
	},

	dragWhich: function(event) {
		var position, fraction;
		if (this.options.ends.length == 1) {
			this.draggedElement = this.options.ends[0];
			return true;
		}
		if (this.options.orientation == "horizontal") {
			position = event.clientX - this.element.offset().left;
			fraction = position/this.element.outerWidth(true);
		} else {
			position = event.clientY - this.element.offset().top;
			fraction = position/this.element.outerHeight(true);
		}

		var distanceFromMax = Math.abs(fraction - this.max);
		var distanceFromMin = Math.abs(fraction - this.min);

		if (distanceFromMax > distanceFromMin) {
			this.draggedElement = "min";
		} else {
			this.draggedElement = "max";
		}

	},

	getRange: function() {
		return {    min: this.min * this.options.range,
					max: this.max * this.options.range
				};
	},

	setRange: function(r) {
		var malarkey = {min: r.min / this.options.range, max: r.max / this.options.range}
		if (malarkey.min != this.min || malarkey.max != this.max) {
			this.min = malarkey.min;
			this.max = malarkey.max;
			this.fill();
		}
	},

	setOptions: function(o) {
		for (var i in o) {
			this.options[i] = o[i];
		}
	},

	setProgress: function(p) {
		var malarkey = {min: 0, max: p / this.options.range}
		if (malarkey.max != this.max) {
			this.min = 0;
			this.max = malarkey.max;
			this.fill();
		}
	}

});

$.widget("rompr.floatingMenu", $.ui.mouse, {
	options: {
		handleClass: null,
		addClassTo: null,
		siblings: '',
		handleshow: true,
		movecallback: null
	},

	resizetimer: null,

	_create: function() {
		var self = this;
		this.dragging = false;
		this._mouseInit();
		if (this.options.addClassTo) {
			var act = this.element.find('.'+this.options.addClassTo).first();
			var close = act.find('.right-icon').addClass('icon-cancel-circled clickicon closemenu');
			act.addClass(this.options.handleClass);
			var hl = this.element.find('input.helplink');
			if (hl.length > 0) {
				act.prepend('<i class="icon-blank smallicon"></i>');
				$('<a href="'+hl.first().val()+'" target="_blank"><i class="icon-info-circled smallicon tright"></i></a>').insertBefore(close);
			}
		}

		if (self.options.handleshow) {
			this._parent = this.element.parent();
			this.element.find('.closemenu').on('click', $.proxy(self.toggleMenu, self));
			this._parent.on('click', function(event) {
				debug.debug("FRUITBAT",event);
				if (!event.target.className.match('progressbar')) {
					$.proxy(self.toggleMenu, self)();
				}
			});
		}
	},

	_mouseCapture: function(event) {
		// Seemingly this is crucial to stop the event bubbling up the tree
		// to the parent icon and closing the menu. Didn't used to be a problem.
		if ($(event.target).hasClass('openmenu')) {
			$.proxy(clickRegistry.doMenu, $(event.target), event).call();
			return false;
		} else {
			event.stopPropagation();
			return true;
		}
	},

	_findSourceElement: function(event) {
		var el = $(event.target);
		while (!el.hasClass(this.options.handleClass) && !el.hasClass('top_drop_menu') && el != this.element)
		{
			el = el.parent();
		}
		if (el.hasClass(this.options.handleClass)) {
			return true;
		} else {
			return false;
		}
	},

	_mouseStart: function(event) {
		if (this.options.handleClass && this._findSourceElement(event) === false) {
			return false;
		}
		this.dragging = true;
		this.drag_x_offset = event.pageX - this.element.offset().left;
		this.drag_y_offset = event.pageY - this.element.offset().top;
		this.element.detach().appendTo('body');
		this._mouseDrag(event);
		return true;
	},

	_mouseDrag: function(event) {
		if (this.dragging) {
			var pos = {top: event.pageY - this.drag_y_offset, left: event.pageX - this.drag_x_offset};
			this.element.css({top: pos.top+"px", left: pos.left+"px"});
			if (this.options.movecallback) {
				this.options.movecallback(pos);
			}
		}
		return true;
	},

	_mouseStop: function(event) {
		this.dragging = false;
		return true;
	},

	toggleMenu: function() {
		var self = this;
		if (this.element.is(':visible')) {
			this.element.slideToggle('fast', function() {
				self.element.css({left: "", top: ""}).detach().appendTo(self._parent);
			});
		} else {
			if (this.options.handleClass == null) {
				var height = self._parent.height()+2;
				self.element.css({top: height+"px"});
			}
			$(self.options.siblings).each(function() {
				if ($(this).is(':visible') && $(this) != self.element && !$(this).parent().is('body')) {
					$(this).slideToggle('fast');
				}
			});

			if (this.element.hasClass('useasfixed')) {
				var top = parseInt(this.element.parent().offset().top) + this.element.parent().outerHeight(true);
				this.element.css({
					top: top+'px',
					left: this.element.parent().offset().left+'px'
				});
				this.element.detach().appendTo('body');
			}
			this.element.slideToggle('fast', function() {
				$(this).fanoogleMenus();
			});
		}
	}

});

// VVVVVVV IMPORTANT!!!!!
// DO NOT use fancy effects eg fades or slidetoggle or any of that
// on things where masonry is in use, as they fuck up Masonry's size
// calculations big time. Just use hide() and show()
// Spent many hours seeking this out, so tkae note!

// The parent container on which this is called ought to have an id attribute
// as it's used to separate things if there are more than one of these

$.widget('rompr.spotifyAlbumThing', {

	options: {
		classes: 'spotify_album_masonry selecotron',
		itemselector: 'spotify_album_masonry',
		swapclass: 'spotify_album_masonry',
		sub: '',
		showbiogs: false,
		layoutcallback: null,
		maxwidth: 640,
		is_plugin: false,
		imageclass: 'spotify_album_image',
		masonified: false,
		showlistenlater: true,
		showremovebutton: false,
		removecallback: null,
		data: []
	},

	_create: function() {
		var self = this;
		var ids = new Array();
		this.options.id = this.element.attr('id');
		this.element.empty();
		this.element.append('<div class="sizer"></div>');
		for (var i in this.options.data) {
			var an = [];
			var a = this.options.data[i];
			debug.debug("SPOTIALBUM","Index is",i,"data is",a);
			if (this.options.sub && a.hasOwnProperty(this.options.sub)) {
				if (a.rompr_index) {
					var b = a.rompr_index;
				}
				a = a[this.options.sub];
				a.rompr_index = b;
			}
			if (ids.indexOf(a.id) > -1) {
				debug.debug("SPALBUM","Duplicate album ID",a.id);
				continue;
			}
			ids.push(a.id);

			// Create the HTML for the album
			var x = $('<div>', {class: this.options.classes+' clearfix albumwidget'}).appendTo(this.element);

			var clickclass = (this.options.is_plugin) ? ' plugclickable' : '';
			var trackclass = (player.canPlay('spotify')) ? ' playable draggable' : '';
			var cx = (this.options.showbiogs) ? ' tleft' : '';
			var y = $('<div>', {class: 'helpfulalbum fullwidth notthere'+cx}).appendTo(x);

			var html;
			var appendto;
			if (layoutProcessor.openOnImage) {
				appendto = $('<div>').appendTo(y);
				appendto.append($('<img>', {
					class: this.options.imageclass+clickclass+' menu infoclick clickopenalbum clickspotifywidget',
					src: self._getImage(a),
					name: self.options.id+'dropper_'+a.id
				}));
				html = '<div class="tagh albumthing sponklick relpos">'+
					'<span class="title-menu'+trackclass+' clicktrack" name="'+rawurlencode(a.uri)+'">';
			} else {
				y.append($('<img>', {
					class: this.options.imageclass+trackclass+' clicktrack',
					src: self._getImage(a),
					name: a.uri
				}));
				html = '<div class="tagh albumthing sponklick">'+
					'<i class="icon-toggle-closed menu infoclick'+clickclass+' clickopenalbum clickspotifywidget" name="'+self.options.id+'dropper_'+a.id+'"></i>'+
					'<span class="title-menu'+trackclass+' clicktrack" name="'+rawurlencode(a.uri)+'">';
				appendto = y;
			}
			if (this.options.showbiogs) {
				for (var ai in a.artists) {
					an.push(a.artists[ai].name);
				}
				html += '<span class="artistnamething">'+concatenate_artist_names(an)+'</span><br />';
			}
			html += a.name+'</span>';
			if (!player.canPlay('spotify')) {
				html += '<a href="'+a.external_urls['spotify']+'" target="_blank"><i class="icon-spotify-circled smallicon"></i></a>';
			}
			if (layoutProcessor.openOnImage && player.canPlay('spotify')) {
				html += '<div class="playdiv'+trackclass+' clicktrack" name="'+rawurlencode(a.uri)+'"></div>';
			}
			html += '</div>';
			appendto.append(html);
			var con = $('<div>', {class: 'tagh albumthing clearfix'}).appendTo(appendto);
			if (self.options.showlistenlater) {
				con.append($('<i>', {
					class: 'tleft icon-headphones smallicon infoclick'+clickclass+' clickaddtolistenlater clickspotifywidget tooltip',
					title: language.gettext('label_addtolistenlater'),
					name: i
				}));
			} else if (self.options.showremovebutton) {
				con.append($('<i>', {
					class: 'tleft icon-cancel-circled smallicon infoclick'+clickclass+' clickremovefromll clickspotifywidget tooltip',
					title: language.gettext('label_removefromlistenlater'),
					name: a.rompr_index
				}));
			}
			if (player.canPlay('spotify')) {
				con.append($('<i>', {
					class: 'tright icon-music smallicon infoclick'+clickclass+' clickaddtocollection clickspotifywidget tooltip',
					title: language.gettext('label_addtocollection'),
					name: i
				}));
			}
			y.append($('<div>', {
				class: 'tagh albumthing invisible',
				id: self.options.id+'dropper_'+a.id
			}));
			if (this.options.showbiogs) {
				y.append($('<input>', {
					type: 'hidden',
					value: encodeURIComponent(concatenate_artist_names(an))
				}));
				x.append($('<span>', {
					class: 'minwidthed',
					id: self.options.id+'bio_'+a.id
				}));
			}
		}
		this.element.imagesLoaded(function() {
			if (self.options.itemselector !== null) {
				browser.rePoint(self.element, {
					itemSelector: '.'+self.options.itemselector,
					columnWidth: '.sizer',
					percentPosition: true
				});
				self.element.find('.notthere').removeClass('notthere');
				self.options.masonified = true;
			}
			if (self.options.layoutcallback) {
				self.options.layoutcallback();
			}
		});
	},

	_setOption: function(key, value) {
		this.options[key] = value;
	},

	handleClick: function(element) {
		debug.trace('SPOTIFYALBUMTHING', 'Handling Click');
		var self = this;
		var id = element.attr("name").replace(self.options.id+'dropper_', '');
		if (element.hasClass('clickopenalbum')) {
			var dropper = $('#'+element.attr("name"));
			if (element.isOpen()) {
				self.element.find('#'+self.options.id+'bio_'+id).hide();
				element.toggleClosed();
				if (self.options.showbiogs) {
					dropper.parent().parent().removeClass('masonry_opened dropshadow').addClass(self.options.swapclass);
					dropper.parent().parent().children('.helpfulalbum').addClass('fullwidth');
				}
				dropper.hide();
				browser.rePoint();
			} else {
				element.toggleOpen();
				if (dropper.hasClass("filled")) {
					self._openAlbum(dropper);
					dropper.show();
					self.element.find('#'+self.options.id+'bio_'+id).show();
					browser.rePoint();
				} else {
					if (layoutProcessor.openOnImage) {
						element.parent().parent().makeSpinner();
					} else {
						element.makeSpinner();
					}
					spotify.album.getInfo(id, $.proxy(self.spotifyAlbumResponse, self), self.spotiError, true);
				}
			}
		} else if (element.hasClass('clickaddtolistenlater')) {
			debug.trace("SPALBUMTHING","Add To Listen Later",self.options.data[id]);
			metaHandlers.addToListenLater(self.options.data[id]);
		} else if (element.hasClass('clickremovefromll')) {
			while (!(element.hasClass('albumwidget'))) {
				element = element.parent();
			}
			element.fadeOut('fast', browser.rePoint);
			if (self.options.removecallback) {
				self.options.removecallback(id);
			}
		} else if (element.hasClass('clickaddtocollection')) {
			var ad = this.options.data[id];
			if (this.options.sub && ad.hasOwnProperty(this.options.sub)) {
				ad = ad[this.options.sub];
			}
			var artnames = [];
			for (var art in ad.artists) {
				artnames.push(ad.artists[art].name);
			}
			spotify.album.getInfo(ad.id, function(data) {
				metaHandlers.fromSpotifyData.addAlbumTracksToCollection(data, concatenate_artist_names(artnames))
			}, function() {
				debug.error("SPOTIALBUM", "Failed To Add Album To Collection");
			})
		}
	},

	_openAlbum: function(e) {
		var self = this;
		if (self.options.showbiogs) {
			e.parent().parent().removeClass(self.options.swapclass).addClass('masonry_opened dropshadow');
			e.parent().parent().children('.helpfulalbum').removeClass('fullwidth');
			self.element.find('#'+self.options.id+'bio_'+e.attr('id').replace(self.options.id+'dropper_')).show();
			browser.rePoint();
			if (!e.hasClass('biogd')) {
				var aname = decodeURIComponent(e.next().val());
				if (aname != "Various Artists"){
					e.addClass('biogd');
					lastfm.artist.getInfo({artist: decodeURIComponent(e.next().val())},
						$.proxy(self.artistInfo, self),
						$.proxy(self.lfmError, self),
						e.attr('id').replace(self.options.id+'dropper_', '')
					);
				}
			}
		}
		infobar.markCurrentTrack();
	},

	spotifyAlbumResponse: function(data) {
		if (layoutProcessor.openOnImage) {
			$('[name="'+this.options.id+'dropper_'+data.id+'"]').parent().parent().stopSpinner();
		} else {
			$('[name="'+this.options.id+'dropper_'+data.id+'"]').stopSpinner();
		}
		var e = $("#"+this.options.id+'dropper_'+data.id);
		e.show();
		this._openAlbum(e);
		e.addClass("filled").html(spotifyTrackListing(data));
		infobar.markCurrentTrack();
		browser.rePoint();
	},

	spotiError: function(data) {
		infobar.error(language.gettext('label_general_error'));
	},

	artistInfo: function(data, reqid) {
		var self = this;
		debug.debug("MONKEYSPANNER","Got LastFM Info for reqid",data,reqid);
		if (data) {
			if (data.error) {
				self.element.find('#'+self.options.id+'bio_'+reqid).html(language.gettext('label_noartistinfo'));
			} else {
				var lfmdata = new lfmDataExtractor(data.artist);
				self.element.find('#'+self.options.id+'bio_'+reqid).hide().html(lastfm.formatBio(lfmdata.bio(), lfmdata.url())).fadeIn('fast');
				browser.rePoint();
			}
		}
	},

	lfmError: function(data, reqid) {
		this.element.find('#'+this.options.id+'bio_'+reqid).html(language.gettext('label_noartistinfo'));
	},

	_getImage: function(a) {
		var self = this;
		var img = 'newimages/spotify-icon.png';
		if (a.images && a.images[0]) {
			debug.debug("SPOTIALBUM","Images",a.images);
			var img = 'getRemoteImage.php?url='+rawurlencode(a.images[0].url);
			for (var j in a.images) {
				if (a.images[j].width <= this.options.maxwidth) {
					img = 'getRemoteImage.php?url='+rawurlencode(a.images[j].url);
					break;
				}
			}
			if (self.options.showbiogs) {
				img += '&rompr_resize_size=medium';
			} else {
				img += '&rompr_resize_size=smallish';
			}
		}
		return img;
	},

	_destroy: function() {
		if (this.options.masonified) {
			this.element.masonry('destroy');
			this.element.empty();
			this.options.masonified = false;
			browser.rePoint();
		}
	}

});

$.widget('rompr.spotifyArtistThing', {

	options: {
		classes: 'spotify_album_masonry',
		itemselector: 'spotify_album_masonry',
		swapclass: 'spotify_album_masonry',
		sub: '',
		layoutcallback: null,
		maxwidth: 640,
		maxalbumwidth: 640,
		is_plugin: false,
		imageclass: 'spotify_album_image',
		masonified: false,
		data: []
	},

	_create: function() {
		var self = this;
		this.options.id = this.element.attr('id');
		this.element.empty();
		// Sizing element for Masonry, empty, and first so that expanding the first item
		// doesn't break the layout
		this.element.append('<div class="sizer"></div>');
		for (var i in this.options.data) {
			var a = this.options.data[i];
			var x = $('<div>', {class: this.options.classes+' clearfix'}).appendTo(this.element);
			var img = '';
			if (a.images[0]) {
				img = 'getRemoteImage.php?url='+rawurlencode(a.images[0].url);
				for (var j in a.images) {
					if (a.images[j].width <= self.options.maxwidth) {
						img = 'getRemoteImage.php?url='+rawurlencode(a.images[j].url);
						break;
					}
				}
				img += '&rompr_resize_size=medium';
			} else {
				img = 'newimages/artist-icon.png';
			}
			var clickclass = (this.options.is_plugin) ? ' plugclickable' : '';
			var trackclass = (player.canPlay('spotify')) ? ' playable clickable draggable' : '';
			var y = $('<div>', {class: 'helpfulalbum fullwidth tleft notthere'}).appendTo(x);
			var html;
			var appendto;
			if (layoutProcessor.openOnImage) {
				var t = $('<div>').appendTo(y);
				t.append('<img class="'+this.options.imageclass+' menu infoclick'+clickclass+' clickopenartist clickspotifywidget" src="'+img+'"  name="'+a.id+'"/>');
				html = '<div class="tagh albumthing sponklick relpos">'+
					'<span class="title-menu'+trackclass+' clicktrack" name="'+rawurlencode(a.uri)+'">'+a.name+'</span>';
				appendto = t;
			} else {
				y.append('<img class="'+this.options.imageclass+trackclass+' clicktrack" src="'+img+'" name="'+rawurlencode(a.uri)+'"/>');
				var html = '<div class="tagh albumthing">'+
							'<i class="icon-toggle-closed menu infoclick clickopenartist clickspotifywidget" name="'+a.id+'"></i>'+
							'<span class="title-menu '+trackclass+' clicktrack" name="'+rawurlencode(a.uri)+'">'+a.name+'</span>';
				appendto = y;
			}
			if (!player.canPlay('spotify')) {
				html += '<a href="'+a.external_urls['spotify']+'" target="_blank"><i class="icon-spotify-circled smallicon"></i></a>';
			}
			if (layoutProcessor.openOnImage && player.canPlay('spotify')) {
				html += '<div class="playdiv'+trackclass+' clicktrack" name="'+rawurlencode(a.uri)+'"></div>';
			}
			html += '</div>';
			appendto.append(html)

			x.append('<span class="minwidthed" id="'+self.options.id+'bio_'+a.id+'"></span>');
			// The inline styles make Masonry lay it out without a big vertical gap between elements
			// Don't know why
			var twat = $('<div>', { class: "selecotron holdingcell spotify_artist_albums medium_masonry_holder", id : a.id, style: "height: 0px; display: none"}).appendTo(x);

		}
		this.element.imagesLoaded(function() {
			self.element.find('.notthere').removeClass('notthere');
			browser.rePoint(self.element,
				{   itemSelector: '.'+self.options.itemselector,
					columnWidth: '.sizer',
					percentPosition: true
				}
			);
			if (self.options.layoutcallback) {
				self.options.layoutcallback();
			}
			self.options.masonified = true;
		});
	},

	_setOption: function(key, value) {
		this.options[key] = value;
	},

	handleClick: function(element) {
		debug.trace('SPOTIFYARTISTTHING', 'Handling Click');
		var self = this;
		var id = element.attr("name");
		var dropper = $('#'+id);
		if (element.hasClass('clickopenartist')) {
			if (element.isOpen()) {
				self.element.find('#'+self.options.id+'bio_'+id).hide();
				element.toggleClosed();
				dropper.parent().removeClass('masonry_opened dropshadow').addClass(self.options.swapclass);
				dropper.parent().children('.helpfulalbum').addClass('fullwidth');
				dropper.hide();
				browser.rePoint();
			} else {
				element.toggleOpen();
				if (dropper.hasClass("filled")) {
					self._openArtist(dropper);
					dropper.show();
					browser.rePoint();
				} else {
					if (layoutProcessor.openOnImage) {
						element.parent().parent().makeSpinner();
					} else {
						element.makeSpinner();
					}
					spotify.artist.getAlbums(id, 'album', $.proxy(self._gotAlbumsForArtist, self), self.spotiError, true);
				}
			}
		} else if (element.hasClass('clickopenalbum') || element.hasClass('clickaddtolistenlater') || element.hasClass('clickaddtocollection')) {
			debug.log('SPOTIFYARTISTTHING', 'Is a pass-up-to-album thing');
			$('#'+element.parent().parent().parent().parent().attr('id')).spotifyAlbumThing('handleClick', element);
		}
	},

	_gotAlbumsForArtist: function(data) {
		var self = this;
		var e = self.element.find('#'+data.reqid);
		if (layoutProcessor.openOnImage) {
			$('[name="'+data.reqid+'"]').parent().parent().stopSpinner();
		} else {
			$('[name="'+data.reqid+'"]').stopSpinner();
		}
		e.show();
		self._openArtist(e)
		e.addClass('filled');
		e.spotifyAlbumThing({
			classes: 'spotify_album_masonry',
			itemselector: 'spotify_album_masonry',
			sub: false,
			layoutcallback: browser.rePoint,
			imageclass: 'spotify_album_image',
			maxwidth: self.options.maxalbumwidth,
			data: data.items
		});
	},

	spotiError: function(data) {
		infobar.error(language.gettext('label_general_error'));
		debug.error("MONKEYBALLS", "Spotify Error", data);
	},

	_openArtist: function(e) {
		var self = this;
		e.parent().removeClass(self.options.swapclass).addClass('masonry_opened dropshadow');
		e.parent().children('.helpfulalbum').removeClass('fullwidth');
		self.element.find('#'+self.options.id+'bio_'+e.attr('id')).show();
		browser.rePoint();
		if (!e.hasClass('biogd')) {
			var aname = e.parent().find('span.title-menu').first().html();
			if (aname != 'Various Artists') {
				e.addClass('biogd');
				lastfm.artist.getInfo({artist: e.parent().find('span.title-menu').first().html()},
					$.proxy(self.artistInfo, self),
					$.proxy(self.lfmError, self),
					e.attr('id')
				);
			}
		}
	},

	artistInfo: function(data, reqid) {
		var self = this;
		if (data) {
			if (data.error) {
				self.element.find('#'+self.options.id+'bio_'+reqid).html(language.gettext('label_noartistinfo'));
			} else {
				var lfmdata = new lfmDataExtractor(data.artist);
				self.element.find('#'+self.options.id+'bio_'+reqid).hide().html(lastfm.formatBio(lfmdata.bio(), lfmdata.url())).fadeIn('fast');
				browser.rePoint();
			}
		}
	},

	lfmError: function(data, reqid) {
		this.element.find('#'+this.options.id+'bio_'+reqid).html(language.gettext('label_noartistinfo'));
	},

	_destroy: function() {
		if (this.options.masonified) {
			this.element.masonry('destroy');
			this.element.empty();
			this.options.masonified = false;
			browser.rePoint();
		}
	}

});

$.widget("rompr.makeDomainChooser", {

	options: {
		default_domains: [],
	},

	_create: function() {
		var self = this;
		this.options.holder = $('<div>', {class: 'containerbox wrap'}).appendTo(this.element);
		for(var i of player.get_search_uri_schemes()) {
			var makeunique = $("[id^='"+i+"_import_domain']").length+1;
			var id = i+'_import_domain_'+makeunique;
			this.options.holder.append('<div class="fixed brianblessed styledinputs">'+
				'<input type="checkbox" class="topcheck" id="'+id+'"><label for="'+id+'">'+
				i.capitalize()+'</label></div>');
		}
		this.setSelection(this.options.default_domains);
		this.options.holder.disableSelection();
	},

	_setOption: function(key, value) {
		this.options[key] = value;
	},

	setSelection: function(domains) {
		this.options.holder.find('.topcheck').each(function() {
			var n = $(this).attr("id");
			var d = n.substr(0, n.indexOf('_'));
			if (domains && domains.indexOf(d) > -1) {
				$(this).prop("checked", true);
			} else {
				$(this).prop("checked", false);
			}
		});
	},

	getSelection: function() {
		var result = new Array();
		this.options.holder.find('.topcheck:checked').each( function() {
			var n = $(this).attr("id");
			result.push(n.substr(0, n.indexOf('_')));
		});
		return result;
	}

});

function popup(opts) {

	var self = this;
	var initialsize;
	var win;
	var titlebar;
	var contents;
	var contentholder;
	var modal_screen = null;

	var options = {
		css: {
			width: 100,
			height: 100
		},
		title: "Popup",
		helplink: null,
		atmousepos: false,
		mousevent: null,
		mouseside: 'left',
		id: null,
		toggleable: false,
		hasclosebutton: true,
		fitheight: false,
		hasscrollbar: false,
		closecallbacks: {},
		buttons: null,
		button_min_width: '1em',
		modal: false
	}

	for (var i in opts) {
		options[i] = opts[i];
	}

	this.create = function() {
		var winid;
		if (options.id) {
			winid = options.id;
		} else {
			winid = hex_md5(options.title);
		}
		if ($('#'+winid).length > 0) {
			$('#'+winid).remove();
			if (options.toggleable) {
				return false;
			}
		}

		if (options.modal)
			modal_screen = $('<div>', {class: 'modal-blackout'}).appendTo($('body'));

		win = $('<div>', { id: winid, class: "popupwindow dropshadow noselection" }).appendTo($('body'));
		var container = $('<div>', {class: 'containerbox vertical popupcontentcontainer'}).appendTo(win);
		var tit_options = {
			title_class: 'dragmenu',
			label_text: options.title,
			icon_size: 'smallicon'
		}

		container.append(uiHelper.ui_config_header(tit_options));
		titlebar = container.children('.configtitle');
		if (options.helplink)
			container.append($('<input>', {class: 'helplink', value: options.helplink, type: 'hidden'}));
		contentholder = $('<div>', {class: 'popupcontentholder expand'}).appendTo(container);
		contents = $('<div>',{class: 'popupcontents clearfix'}).appendTo(contentholder);

		win.floatingMenu({
			handleshow: false,
			handleclass: 'configtitle',
			movecallback: self.moved,
			addClassTo: (options.hasclosebutton) ? 'configtitle' : false
		});
		titlebar.find('.closemenu').on('click',  function() {self.close(false)});
		return contents;
	}

	this.open = function() {
		win.css({opacity: 1});
		self.adjustCSS(true, true);
		self.setCSS();
		win.css({opacity: 1});
	}

	this.close = function(event) {
		var result = true;
		if (event !== null) {
			var button = $(event.target).html();
			debug.log("POPUP","Button",button,"was clicked");
			if (options.closecallbacks.hasOwnProperty(button) && options.closecallbacks[button] !== false) {
				result = options.closecallbacks[button]();
			}
		}

		if (result !== false)
			win.remove();

		if (modal_screen != null)
			modal_screen.remove();
	}

	this.moved = function(pos) {
		options.css.top = pos.top;
		options.css.left = pos.left;
	}

	this.adjustCSS = function(setleft, settop) {
		var contentheight = contents.outerHeight(true) + titlebar.outerHeight(true);
		if (options.fitheight) {
			options.css.height = contentheight+8;
		} else if (contentheight < options.css.height) {
			options.css.height = contentheight+8;
		}
		var w = getWindowSize();
		options.css.width = Math.min(w.x-16, options.css.width);
		options.css.height = Math.min(w.y-16, options.css.height);
		if (options.atmousepos) {
			options.css.top = Math.min(options.mousevent.clientY+8, w.y - options.css.height);
			switch (options.mouseside) {
				case 'left':
					options.css.left = Math.min(options.mousevent.clientX-8, w.x - options.css.width);
					break;

				case 'right':
					options.css.right = Math.max(options.mousevent.clientX+8, options.css.width);
					break;
			}
		} else {
			if (setleft) {
				options.css.left = Math.max(0, (w.x/2 - options.css.width/2));
			}
			if (settop) {
				options.css.top =  Math.max(0, (w.y/2 - options.css.height/2));
			}
			options.css.height = Math.min(options.css.height, (w.y - options.css.top));
		}
		if (!options.hasscrollbar && (options.css.height - titlebar.outerHeight(true)) < contents.outerHeight(true)) {
			contentholder.addCustomScrollBar();
			options.hasscrollbar = true;
		}
	}

	this.setCSS = function() {
		for (var i in options.css) {
			debug.debug("POPUP","Setting CSS",i,'to',options.css[i]);
			win.css(i, options.css[i]+'px');
		}
	}

	this.addCloseButton = function(text, callback) {
		var button = $('<button>',{class: 'tright'}).appendTo(contents);
		button.html(text);
		options.closecallbacks[text] = callback;
		button.on('click', self.close);
	}

	this.useAsCloseButton = function(elem, callback) {
		options.closecallbacks[elem.html()] = callback;
		elem.on('click', self.close);
	}

	this.setWindowToContentsSize = function() {
		self.adjustCSS(false, false);
		self.setCSS();
	}

	this.add_button = function(side, label) {
		if (options.buttons == null)
			options.buttons = $('<div>', {class: 'clearfix'}).appendTo(contents);

		return $('<button>', {class: 't'+side, style: 'min-width: '+options.button_min_width}).html(language.gettext(label)).appendTo(options.buttons);
	}

	this.hide = function() {
		win.css({display: 'none'});
	}

	this.unhide = function() {
		win.css({display: ''});
	}

}

/*
	Note that for continuous dragging to work, options.command
	needs to take 2 parameters, the first being a volume value
	and the second being a callback
*/

$.widget('rompr.volumeControl', {

	vtimer: null,
	sliderclamps: 0,

	options: {
		orientation: 'vertical',
		command: null
	},

	_create: function() {
		this.element.rangechooser({
			range: 100,
			ends: ['max'],
			onstop: $.proxy(this.onstop, this),
			whiledragging: $.proxy(this.whiledragging, this),
			orientation: this.options.orientation
		});
	},

	onstop: function(v) {
		clearTimeout(this.vtimer);
		this.sliderclamps = 0;
		debug.trace("VOLUMECONTROL","Setting volume",v.max);
		this.options.command(v.max);
	},

	releaseTheClamps: function() {
		this.sliderclamps--;
	},

	whiledragging: function(v) {
		if (this.sliderclamps == 0) {
			// Double interlock to prevent hammering mpd:
			// We don't send another volume request until two things happen:
			// 1. The previous volume command returns
			// 2. The timer expires
			this.sliderclamps = 2;
			debug.trace("VOLUMECONTROL2","Setting volume",v.max);
			this.options.command(v.max, $.proxy(this.releaseTheClamps, this));
			clearTimeout(this.vtimer);
			this.vtimer = setTimeout($.proxy(this.releaseTheClamps, this), 500);
		}
	},

	displayVolume: function(v) {
		this.element.rangechooser("setRange", {min: 0, max: v});
	}

});

/* Touchwipe for playlist only, based on the more general jquery touchwipe */
/*! jquery.touchwipe - v1.3.0 - 2015-01-08
* Copyright (c) 2015 Josh Stafford; Licensed MIT */

/* This version ignores all vertical swipes but adds a long press function
   and uses the touchend event instead of a timer to make the action happen */

jQuery.fn.playlistTouchWipe = function(settings) {

	var config = {
		min_move_x: 20,
		min_move_y: 20,
		swipeSpeed: 300,
		swipeDistance: 120,
		longPressTime: 1000,
		preventDefaultEvents: false, // prevent default on swipe
		preventDefaultEventsX: true, // prevent default is touchwipe is triggered on horizontal movement
		preventDefaultEventsY: true // prevent default is touchwipe is triggered on vertical movement
	};

	if (settings) {
		$.extend(config, settings);
	}

	this.each(function() {
		var startX;
		var startY;
		var isMoving = false;
		var touchesX = [];
		var touchesY = [];
		var self = this;
		var starttime = 0;
		var longpresstimer = null;
		var pressing = false;

		function cancelTouch() {
			clearTimeout(longpresstimer);
			this.removeEventListener('touchmove', onTouchMove);
			this.removeEventListener('touchend', onTouchEnd);
			startX = null;
			startY = null;
			isMoving = false;
			pressing = false;
		}

		function onTouchEnd(e) {
			var time = Date.now();
			clearTimeout(longpresstimer);
			if (pressing) {
				e.stopImmediatePropagation();
				e.stopPropagation();
				e.preventDefault();
				pressing = false;
				setTimeout(bindPlaylistClicks, 500);
			} else if (isMoving) {
				var dx = touchesX.pop();
				touchesX.push(dx);
				if (time - starttime < config.swipeSpeed && dx > config.swipeDistance) {
					touchesX.push($(self).outerWidth(true));
					if ($(self).hasClass('item')) {
						$(self).next().animate({left: 0 - $(self).outerWidth(true)}, 'fast', 'swing');
					}
					$(self).animate({left: 0 - $(self).outerWidth(true)}, 'fast', 'swing', doAction);
				} else {
					doAction();
				}
			}
		}

		function doAction() {
			var dxFinal, dyFinal;
			cancelTouch();
			dxFinal = touchesX.pop();
			touchesX = [];
			if (dxFinal > ($(self).outerWidth(true)*0.75)) {
				if ($(self).hasClass('track')) {
					playlist.delete($(self).attr('romprid'));
				} else if ($(self).hasClass('item')) {
					playlist.deleteGroup($(self).attr('name'));
				}
			} else {
				$(self).animate({left: 0}, 'fast', 'swing');
				if ($(self).hasClass('item')) {
					$(self).next().animate({left: 0}, 'fast', 'swing');
				} else {
					$(self).removeClass('highlighted');
				}
			}
		}

		function onTouchMove(e) {
			clearTimeout(longpresstimer);
			if(config.preventDefaultEvents) {
				e.preventDefault();
			}

			if (isMoving) {
				var x = e.touches[0].pageX;
				var y = e.touches[0].pageY;
				var dx = startX - x;
				var dy = startY - y;

				if (Math.abs(dx) >= config.min_move_x) {
					if (config.preventDefaultEventsX) {
						e.preventDefault();
					}
					var newpos = 0 - dx;
					if (newpos < 0) {
						$(self).css('left', newpos.toString()+'px');
						if ($(self).hasClass('item')) {
							$(self).next().css('left', newpos.toString()+'px');
						} else {
							$(self).addClass('highlighted');
						}
						touchesX.push(dx);
					}
				}
			}
		}

		function longPress() {
			debug.info("TOUCHWIPE","Long Press");
			pressing = true;
			// Unbind click handler from playlist, otherwise the touchend
			// event makes it start playing the clicked track.
			// Don't seem to be able to prevent the event propagating.
			$(self).addBunnyEars();
			playlist.startBunnyTimeout($(self));
			unbindPlaylistClicks();
		}

		function onTouchStart(e) {
			starttime = Date.now();
			if (e.touches.length === 1) {
				startX = e.touches[0].pageX;
				startY = e.touches[0].pageY;
				isMoving = true;
				this.addEventListener('touchmove', onTouchMove, false);
				this.addEventListener('touchend', onTouchEnd, false);
				longpresstimer = setTimeout(longPress, config.longPressTime);
			}
		}

		this.addEventListener('touchstart', onTouchStart, false);

	});

	return this;
};

jQuery.fn.touchStretch = function(settings) {

	var config = {
		is_double_panel_skin: false
	};

	if (settings) {
		$.extend(config, settings);
	}

	this.each(function() {
		var start_touch_width;
		var start_width;
		var percentage= null;
		var otherpcercentage = null;
		var self;
		var handlingtouch = [null, null];
		var panel;
		var otherpanel;

		function onStretchTouchEnd(e) {
			debug.debug('TOUCHEND', e);
			if (e.changedTouches && typeof(e.changedTouches) == 'object') {
				for (let ct of e.changedTouches) {
					if (ct.identifier == handlingtouch[0]) {
						debug.trace('TOUCHEND', 'Touch 0 finixhed');
						e.preventDefault();
						handlingtouch[0] = null;
					}
					if (ct.identifier == handlingtouch[1]) {
						debug.trace('TOUCHEND', 'Touch 1 finixhed');
						e.preventDefault();
						handlingtouch[1] = null;
					}
				}
			}
			if (handlingtouch[0] == null && handlingtouch[1] == null && percentage != null) {
				let pts = {};
				pts[panel+'widthpercent'] = parseFloat(percentage);
				if (config.is_double_panel_skin)
					pts[otherpanel+'widthpercent'] = parseFloat(otherpercentage);
				prefs.save(pts);
			}
		}

		function onStretchTouchMove(e) {
			if (e.touches.length != 2)
				return;
			var ids = [];
			for (let t of e.touches) {
				ids.push(t.identifier);
			}
			// This checks to see if either of the arrays have any elements in common
			// No, I don't know how it works.
			if (handlingtouch.some(item => ids.includes(item))) {
				e.preventDefault();
				var touch_width = Math.abs(e.touches[0].clientX - e.touches[1].clientX);
				var width_change = touch_width - start_touch_width;
				var new_panel_width = start_width + width_change;
				var ws = getWindowSize();
				percentage = ((new_panel_width/ws.x) * 100).toFixed(2);
				let widths = {};
				widths[panel] = percentage;
				if (config.is_double_panel_skin) {
					if (percentage + otherpcercentage > 100 || prefs.hidebrowser) {
						otherpercentage = 100 - percentage;
						widths[otherpanel] = otherpercentage;
					}
				}
				layoutProcessor.setPanelCss(widths);
				// debug.log('TOUCH',touch_width,width_change,new_panel_width,percentage);
			}
		}

		function onStretchTouchStart(e) {
			if (e.touches.length == 2 && handlingtouch[0] == null && handlingtouch[1] == null) {
				var target = $(event.target);
				while (!target.hasClass('resizable') && !target.is('body')) {
					target = target.parent();
				}
				if (target.is('body'))
					return;

				e.preventDefault();
				self = target;
				panel = self.prop('id');
				otherpanel = (panel == 'sources') ? 'playlist' : 'sources';
				percentage = null;
				otherpercentage = prefs[otherpanel+'widthpercent'];
				start_width = self.width();
				start_touch_width = Math.abs(e.touches[0].clientX - e.touches[1].clientX);
				handlingtouch[0] = e.touches[0].identifier;
				handlingtouch[1] = e.touches[1].identifier;
			}
		}

		this.ontouchstart = onStretchTouchStart;
		this.ontouchmove = onStretchTouchMove;
		this.ontouchend = onStretchTouchEnd;
		this.ontouchcancel = onStretchTouchEnd;

	});

	return this;

}
