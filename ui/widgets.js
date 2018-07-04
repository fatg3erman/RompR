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
        var anistring = "pulseit "+settings.flashtime+"s "+settings.easing+" "+settings.repeats;
        $(this).css({"animation": "", "opacity": ""});
        $(this).hide().show();
        $(this).css({"animation": anistring});
    });
}

jQuery.fn.stopFlasher = function() {
    return this.each(function() {
        $(this).css({"animation": "","opacity": ""});
    });
}

jQuery.fn.switchToggle = function(state) {
    return this.each(function() {
        var st = (state == 0 || state == "off" || !state) ? "icon-toggle-off" : "icon-toggle-on";
        $(this).removeClass("icon-toggle-on icon-toggle-off").addClass(st);
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
        var clickedElement = findClickableElement(event);
        if (!clickedElement.hasClass('draggable')) {
            return false;
        }
        this.dragging = true;
        if (!clickedElement.hasClass("selected")) {
            if (clickedElement.hasClass("clickalbum") ||
                clickedElement.hasClass('clickalbumname') ||
                clickedElement.hasClass("clickloadplaylist")) {
                albumSelect(event, clickedElement);
            } else if (clickedElement.hasClass('clickdisc')) {
                discSelect(event, clickedElement);
            } else if (clickedElement.hasClass("clicktrack") ||
                        clickedElement.hasClass("clickcue") ||
                        clickedElement.hasClass('clickstream')) {
                trackSelect(event, clickedElement);
            }
        }
        this.dragger = $('<div>', {id: 'dragger', class: 'draggable dragsort containerbox vertical dropshadow'}).appendTo('body');
        this.dragger.css('width', $('.selected').first().css('width'));
        if ($(".selected").length > 6) {
            this.dragger.append('<div class="containerbox menuitem">'+
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
        $('.trackacceptor').each(function() {
            if ($(this).acceptDroppedTracks('checkMouseOver', event)) {
                // DON'T Break out of the each loop, as it prevents checkMouseOver
                // from removing the 'highlighted' class from things we've previously dragged over
                // if they would be the nxt one in the loop.
                // return false;
            }
        });
        return true;
    },

    _mouseStop: function(event) {
        this.dragging = false;
        this.dragger.remove();
        $('.trackacceptor').each(function() {
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
        started_sortable_drag: false
    },

    _create: function() {
        this.element.addClass('trackacceptor');
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
        debug.log("UITHING","dragstop",this.element.attr("id"));
        if (this.dragger_is_over && this.options.ondrop !== null) {
            debug.log("UITHING","Dropped onto wotsit thingy",this.element.attr("id"));
            this.dragger_is_over = false;
            this.element.removeClass('highlighted');
            this.options.ondrop(event, this.element);
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
            if (!this.dragger_is_over) {
                this.dragger_is_over = true;
                this.element.addClass('highlighted');
            }
            if (this.dragger_is_over && this.element.hasClass('sortabletracklist')) {
                this.element.sortableTrackList('do_intersect_stuff', event, $("#dragger"));
            }
            return true;
        } else {
            if (this.dragger_is_over) {
                debug.log("UITHING","Dragger is NOT over",this.element.attr("id"));
                this.element.removeClass('highlighted');
                if (this.element.hasClass('sortabletracklist')) {
                    this.element.sortableTrackList('dragleave');
                }
                this.dragger_is_over = false;
            }
            return false;
        }
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
            top:    Math.max(vbox.offset().top, this.element.offset().top),
            right:  this.element.offset().left + this.element.width(),
            bottom: Math.min(vbox.offset().top + vbox.height(), this.element.offset().top + this.element.height())
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
        this.helper.attr('class', item.hasClass('draggable') ? 'draggable' : 'something');
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
        debug.log("STL","Dropped",event);
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
        return el;
    },

    _mouseStart: function(event) {
        debug.log("SORTABLE","Mouse Start",event);
        var dragged = this._findDraggable(event);
        if (dragged.prev().length == 0) {
            this.dragged_original_before = dragged.next();
            this.dragged_original_after = false;
        } else {
            this.dragged_original_before = false;
            this.dragged_original_after = dragged.prev();
        }
        this.dragged_original_pos = dragged.prev();
        if (this.dragger) this.dragger.remove();
        this.dragger = dragged.clone().appendTo('body');
        this.dragger.find('.icon-cancel-circled').remove();
        if (this.dragger.is('tr')) {
            this.dragger.wrap('<table></table>');
        }
        this.dragger.css({
            position: 'absolute',
            top: dragged.offset().top + 'px',
            left: dragged.offset().left + 'px',
            width: dragged.width() + 'px',
            'z-index': 1500
        });
        this.drag_x_offset = event.pageX - this.dragger.offset().left;
        this.dragger.addClass('dropshadow');
        if (this.helper) this.helper.remove();
        this.helper = null;
        this._checkHelper(dragged);
        this.helper.detach().insertAfter(dragged);
        this.original = dragged.detach();
        this.dragstart();
        this.dragging = true;
        return true;
    },

    _mouseDrag: function(event) {
        clearTimeout(this._scrollcheck);
        if (this.dragging) {
            if ((event.pageX > this.bbox.right || event.pageX < this.bbox.left) &&
                this.options.allowdragout)
            {
                clearTimeout(this._scrollcheck);
                this.dragging = false;
                this.draggingout = true;
                var pos = {top: event.pageY - 12, left: event.pageX - this.drag_x_offset};
                this.dragger.css({top: pos.top+"px", left: pos.left+"px"});
                if (!this.dragged_original_after) {
                    this.original.insertBefore(this.dragged_original_before);
                } else {
                    this.original.insertAfter(this.dragged_original_after);
                }
                this.original.addClass('selected');
                if (this.helper) {
                    this.helper.detach();
                }
                this.dragger.attr('id','dragger');
                this.dragger.css('z-index', 1500);
                this.dragger.addClass('draggable');
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
            $('.trackacceptor').each(function() {
                if ($(this).acceptDroppedTracks('checkMouseOver', event)) {
                    // Break out of the each loop
                    return false;
                }
            });
        }
        return true;
    },

    _mouseStop: function(event) {
        clearTimeout(this._scrollcheck);
        if (this.dragging) {
            this.dragger.remove();
            this.original.insertAfter(this.helper);
            this.helper.remove();
            this.helper = null;
            this.dragging = false;
            if (this.options.insidedrop) {
                this.options.insidedrop(event, this.original);
            }
        } else if (this.draggingout) {
            debug.log("STL","Dragged out and onto something else");
            this.dragger.remove();
            this.draggedout = false;
            if (this.helper) this.helper.remove();
            this.helper = null;
            $('.trackacceptor').each(function() {
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
        adjusticons: [],
        side: 'left'
    },

    _create: function() {
        this.dragging = false;
        this._mouseInit();
        this.element.css({cursor: "move"});
    },

    _mouseCapture: function(event) {
        this.dragging = true;
        this.startX = event.clientX;
        this.elementStartX = this.element.offset().left;
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
        interactive: true
    },

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
        this.min = this.options.startmin;
        this.max = this.options.startmax;
        if (this.options.interactive) {
            this._mouseInit();
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
        var rgbs = getrgbs(this.max*100,this.min*100);
        var gradients = new Array();
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
        this.min = malarkey.min;
        this.max = malarkey.max;
        this.fill();
    }

});

$.widget("rompr.floatingMenu", $.ui.mouse, {
    options: {
        handleClass: null,
        addClassTo: null,
        siblings: '',
        handleshow: true
    },

    _create: function() {
        var self = this;
        this.dragging = false;
        this._mouseInit();
        if (this.options.addClassTo) {
            this.element.find('.'+this.options.addClassTo).first().addClass(this.options.handleClass)
                .append('<i class="icon-cancel-circled playlisticonr tright clickicon closemenu"></i>');
            var hl = this.element.find('input.helplink');
            if (hl.length > 0) {
                this.element.find('.'+this.options.addClassTo).first().append('<a href="'+hl.first().val()+'" target="_blank"><i class="icon-info-circled playlisticonr tright"></i></a>');
            }
            
        }
        if (self.options.handleshow) {
            this._parent = this.element.parent();
            this.element.find('.closemenu').click($.proxy(self.toggleMenu, self));
            this._parent.click(function(event) {
                debug.log("FRUITBAT",event);
                if (!event.target.className.match('progressbar')) {
                    $.proxy(self.toggleMenu, self)();
                }
            });
        }
    },

    _mouseCapture: function() {
        return true;
    },

    _findSourceElement: function(event) {
        var el = $(event.target);
        while (!el.hasClass(this.options.handleClass) && !el.hasClass('topdropmenu') && el != this.element)
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

$.widget('rompr.spotifyAlbumThing', {

    options: {
        classes: 'tagholder2 selecotron',
        itemselector: 'tagholder2',
        swapclass: 'tagholder2',
        sub: '',
        showbiogs: false,
        layoutcallback: null,
        maxwidth: 640,
        is_plugin: false,
        imageclass: 'masochist',
        masonified: false,
        data: []
    },

    _create: function() {
        var self = this;
        var ids = new Array();
        this.options.id = this.element.attr('id');
        this.element.empty();
        this.element.append('<div class="sizer"></div>');
        for (var i in this.options.data) {
            var a = this.options.data[i];
            if (this.options.sub) {
                a = a[this.options.sub];
            }
            if (ids.indexOf(a.id) > -1) {
                debug.mark("SPALBUM","Duplicate album ID",a.id);
                continue;
            }
            ids.push(a.id);
            var x = $('<div>', {class: this.options.classes+' clearfix'}).appendTo(this.element);
            var img = '';
            if (a.images && a.images[0]) {
                img = 'getRemoteImage.php?url='+a.images[0].url
                for (var j in a.images) {
                    if (a.images[j].width <= this.options.maxwidth) {
                        img = 'getRemoteImage.php?url='+a.images[j].url;
                        break;
                    }
                }
            } else {
                img = 'newimages/spotify-icon.png';
            }
            var clickclass = (this.options.is_plugin) ? ' plugclickable' : '';
            var trackclass = (player.canPlay('spotify')) ? ' infoclick clickable draggable' : '';
            var cx = (this.options.showbiogs) ? ' tleft' : '';
            var y = $('<div>', {class: 'helpfulalbum fullwidth notthere'+cx}).appendTo(x);
            var html;
            var appendto;
            if (layoutProcessor.openOnImage) {
                var t = $('<div>').appendTo(y);
                t.append('<img class="'+this.options.imageclass+' menu infoclick'+clickclass+' clickopenalbum" src="'+img+'"  name="'+a.id+'"/>');
                html = '<div class="tagh albumthing sponklick relpos">'+
                    '<span class="title-menu'+trackclass+' clicktrack" name="'+a.uri+'">';
                appendto = t;
            } else {
                y.append('<img class="'+this.options.imageclass+trackclass+' clicktrack" '+'src="'+img+'" name="'+a.uri+'"/>');
                html = '<div class="tagh albumthing sponklick">'+
                    '<i class="icon-toggle-closed menu infoclick'+clickclass+' clickopenalbum" name="'+a.id+'"></i>'+
                    '<span class="title-menu'+trackclass+' clicktrack" name="'+a.uri+'">';
                appendto = y;
            }
            if (this.options.showbiogs) {
                var an = new Array();
                for (var i in a.artists) {
                    an.push(a.artists[i].name);
                }
                html += '<span class="artistnamething">'+concatenate_artist_names(an)+'</span><br />';
            }
            html += a.name+'</span>';
            if (!player.canPlay('spotify')) {
                html += '<a href="'+a.external_urls['spotify']+'" target="_blank"><i class="icon-spotify-circled playlisticonr"></i></a>';
            }
            if (layoutProcessor.openOnImage && player.canPlay('spotify')) {
                html += '<div class="playdiv'+trackclass+' clicktrack" name="'+a.uri+'"></div>';
            }
            html += '</div>';
            appendto.append(html);
            y.append('<div class="tagh albumthing invisible" id="'+a.id+'"></div>');
            if (this.options.showbiogs) {
                y.append('<input type="hidden" value="'+encodeURIComponent(concatenate_artist_names(an))+'" />');
                x.append('<span class="minwidthed" id="'+self.options.id+'bio_'+a.id+'"></span>');
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
        var self = this;
        var id = element.attr("name");
        var dropper = $('#'+id);
        if (element.isOpen()) {
            self.element.find('#'+self.options.id+'bio_'+id).hide();
            element.toggleClosed();
            if (self.options.showbiogs) {
                dropper.parent().parent().removeClass('tagholder_wide dropshadow').addClass(self.options.swapclass);
                dropper.parent().parent().children('.helpfulalbum').addClass('fullwidth');
            }
            dropper.hide();
            browser.rePoint();
        } else {
            element.toggleOpen();
            if (dropper.hasClass("filled")) {
                self._openAlbum(dropper);
                dropper.show();
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
    },

    _openAlbum: function(e) {
        var self = this;
        if (self.options.showbiogs) {
            e.parent().parent().removeClass(self.options.swapclass).addClass('tagholder_wide dropshadow');
            e.parent().parent().children('.helpfulalbum').removeClass('fullwidth');
            self.element.find('#'+self.options.id+'bio_'+e.attr('id')).show();
            browser.rePoint();
            if (!e.hasClass('biogd')) {
                var aname = decodeURIComponent(e.next().val());
                if (aname != "Various Artists"){
                    e.addClass('biogd');
                    lastfm.artist.getInfo({artist: decodeURIComponent(e.next().val())},
                        $.proxy(self.artistInfo, self),
                        $.proxy(self.lfmError, self),
                        e.attr('id')
                    );
                }
            }
        }
        infobar.markCurrentTrack();
    },

    spotifyAlbumResponse: function(data) {
        if (layoutProcessor.openOnImage) {
            $('[name="'+data.id+'"]').parent().parent().stopSpinner();
        } else {
            $('[name="'+data.id+'"]').stopSpinner();
        }
        var e = this.element.find("#"+data.id);
        e.show();
        this._openAlbum(e);
        e.addClass("filled").html(spotifyTrackListing(data));
        infobar.markCurrentTrack();
        browser.rePoint();
    },

    spotiError: function(data) {
        infobar.notify(infobar.ERROR, "Error Getting Data From Spotify");
    },

    artistInfo: function(data, reqid) {
        var self = this;
        debug.log("MONKEYSPANNER","Got LastFM Info for reqid",data,reqid);
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

$.widget('rompr.spotifyArtistThing', {

    options: {
        classes: 'tagholder2',
        itemselector: 'tagholder2',
        swapclass: 'tagholder2',
        sub: '',
        layoutcallback: null,
        maxwidth: 640,
        is_plugin: false,
        imageclass: 'masochist',
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
                img = 'getRemoteImage.php?url='+a.images[0].url;
                for (var j in a.images) {
                    if (a.images[j].width <= self.options.maxwidth) {
                        img = 'getRemoteImage.php?url='+a.images[j].url;
                        break;
                    }
                }
            } else {
                img = 'newimages/artist-icon.png';
            }
            var clickclass = (this.options.is_plugin) ? ' plugclickable' : '';
            var trackclass = (player.canPlay('spotify')) ? ' infoclick clickable draggable' : '';
            var y = $('<div>', {class: 'helpfulalbum fullwidth tleft notthere'}).appendTo(x);
            var html;
            var appendto;
            if (layoutProcessor.openOnImage) {
                var t = $('<div>').appendTo(y);
                t.append('<img class="'+this.options.imageclass+' menu infoclick'+clickclass+' clickopenartist" src="'+img+'"  name="'+a.id+'"/>');
                html = '<div class="tagh albumthing sponklick relpos">'+
                    '<span class="title-menu'+trackclass+' clicktrack" name="'+a.uri+'">'+a.name+'</span>';
                appendto = t;
            } else {
                y.append('<img class="'+this.options.imageclass+trackclass+' clicktrack" src="'+img+'" name="'+a.uri+'"/>');
                var html = '<div class="tagh albumthing">'+
                            '<i class="icon-toggle-closed menu infoclick clickopenartist" name="'+a.id+'"></i>'+
                            '<span class="title-menu '+trackclass+' clicktrack" name="'+a.uri+'">'+a.name+'</span>';
                appendto = y;
            }
            if (!player.canPlay('spotify')) {
                html += '<a href="'+a.external_urls['spotify']+'" target="_blank"><i class="icon-spotify-circled playlisticonr"></i></a>';
            }
            if (layoutProcessor.openOnImage && player.canPlay('spotify')) {
                html += '<div class="playdiv'+trackclass+' clicktrack" name="'+a.uri+'"></div>';
            }
            html += '</div>';
            appendto.append(html)

            x.append('<span class="minwidthed" id="'+self.options.id+'bio_'+a.id+'"></span>');
            // The inline styles make Masonry lay it out without a big vertical gap between elements
            // Don't know why
            var twat = $('<div>', { class: "selecotron holdingcell masonified5", id : a.id, style: "height: 0px; display: none"}).appendTo(x);

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
        var self = this;
        var id = element.attr("name");
        var dropper = $('#'+id);
        if (element.hasClass('clickopenartist')) {
            if (element.isOpen()) {
                self.element.find('#'+self.options.id+'bio_'+id).hide();
                element.toggleClosed();
                dropper.parent().removeClass('tagholder_wide dropshadow').addClass(self.options.swapclass);
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
        } else if (element.hasClass('clickopenalbum')) {
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
            classes: 'tagholder5',
            itemselector: 'tagholder5',
            sub: false,
            layoutcallback: browser.rePoint,
            imageclass: 'masochist',
            data: data.items
        });
    },

    spotiError: function(data) {
        infobar.notify(infobar.ERROR, "Error Getting Data From Spotify");
        debug.error("MONKEYBALLS", "Spotify Error", data);
    },

    _openArtist: function(e) {
        var self = this;
        e.parent().removeClass(self.options.swapclass).addClass('tagholder_wide dropshadow');
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
        sources_not_to_choose: {
                file: 1,
                http: 1,
                https: 1,
                mms: 1,
                rtsp: 1,
                somafm: 1,
                spotifytunigo: 1,
                rtmp: 1,
                rtmps: 1,
                sc: 1,
                yt: 1,
                m3u: 1,
                spotifyweb: 1,
                'podcast+http': 1,
                'podcast+https': 1,
                'podcast+ftp': 1,
                'podcast+file': 1,
                'podcast+itunes': 1,
                'podcast+gpodder.net': 1,
                'podcast+gpodder': 1
        }
    },

    _create: function() {
        var self = this;
        this.options.holder = $('<div>', {class: 'containerbox wrap'}).appendTo(this.element);
        // var p = this.options.default_domains;
        // // p.reverse();
        // for (var i in p) {
        //     if (player.canPlay(p[i])) {
        //         var makeunique = $("[id^='"+p[i]+"_import_domain']").length+1;
        //         var id = p[i]+'_import_domain_'+makeunique;
        //         this.options.holder.append('<div class="fixed brianblessed styledinputs">'+
        //             '<input type="checkbox" class="topcheck" id="'+id+'"><label for="'+id+'">'+
        //             p[i].capitalize()+'</label></div>');
        //     }
        // }
        for (var i in player.urischemes) {
            if (!this.options.sources_not_to_choose.hasOwnProperty(i)) {
                var makeunique = $("[id^='"+i+"_import_domain']").length+1;
                var id = i+'_import_domain_'+makeunique;
                this.options.holder.append('<div class="fixed brianblessed styledinputs">'+
                    '<input type="checkbox" class="topcheck" id="'+id+'"><label for="'+id+'">'+
                    i.capitalize()+'</label></div>');
            }
        }
        this.options.holder.find('.topcheck').each(function() {
            var n = $(this).attr("id");
            var d = n.substr(0, n.indexOf('_'));
            if (self.options.default_domains.indexOf(d) > -1) {
                $(this).prop("checked", true);
            }
        });
        this.options.holder.disableSelection();
    },

    _setOption: function(key, value) {
        this.options[key] = value;
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

    var options = {
        width: 100,
        height: 100,
        title: "Popup",
        helplink: null,
        xpos: null,
        ypos : null,
        id: null,
        toggleable: false,
        hasclosebutton: true
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
        win = $('<div>', { id: winid, class: "popupwindow dropshadow noselection" }).appendTo($('body'));
        titlebar = $('<div>', { class: "cheese dragmenu" }).appendTo(win);
        var tit = $('<div>', { class: "configtitle textcentre"}).appendTo(titlebar)
        tit.html('<b>'+options.title+'</b>');
        if (options.hasclosebutton) {
            tit.append('<i class="icon-cancel-circled playlisticonr clickicon tright"></i>');
        }
        if (options.helplink !== null) {
            tit.append('<a href="'+options.helplink+'" target="_blank"><i class="icon-info-circled playlisticonr clickicon tright"></i></a>');
        }
        contents = $('<div>',{class: 'popupcontents'}).appendTo(win);
        titlebar.find('.icon-cancel-circled').click( function() {self.close(false)});
        var winsize = getWindowSize();
        var maxsizeallowed = layoutProcessor.maxPopupSize(winsize);
        initialsize = { width: Math.min(options.width, maxsizeallowed.width),
                            height: Math.min(options.height, maxsizeallowed.height)};
        if (options.xpos == null) {
            var x = (winsize.x - initialsize.width)/2;
            var y = (winsize.y - initialsize.height)/2;
        } else {
            options.xpos += 4;
            options.ypos += 4;
            var x = Math.min(options.xpos, (winsize.x - initialsize.width));
            var y = Math.min(options.ypos, (winsize.y - initialsize.height));
        }
        if (x < 0) {
            x = 0;
        }
        
        win.css({width: initialsize.width+'px',
                height: initialsize.height+'px',
                top: y+'px',
                left: x+'px'});

        win.floatingMenu({handleshow: false, handleclass: 'cheese'});

        return contents;

    }

    this.open = function() {
        win.show();
        // Space in the outer window
        var h = win.outerHeight(true) - titlebar.outerHeight(true) - 16;
        // Height of the contents. Fudge factor allows for custom scrollbar disappearing or reappearing
        var a = contents.outerHeight(true) + titlebar.outerHeight(true);
        if (a < h) {
            win.css('height',a.toString()+'px');
        } else {
            contents.css("height", h.toString() + 'px');
        }
        var winsize = getWindowSize();
        if (options.ypos != null) {
            var y = Math.min(options.ypos, (winsize.y - win.outerHeight(true)));
        } else {
            var y = Math.max((winsize.y - win.outerHeight(true))/2, 0);
        }
        win.css({top: y+'px'});
        layoutProcessor.addCustomScrollBar(contents);
    }

    this.close = function(callback) {
        if (callback) {
            callback();
        }
        win.remove();
    }

    this.addCloseButton = function(text, func) {
        var button = $('<button>',{class: 'tright'}).appendTo(contents);
        button.html(text);
        button.click(function() { self.close(func) });
    }

    this.useAsCloseButton = function(elem, func) {
        elem.click(function() { self.close(func) });
    }

    this.setContentsSize = function() {
        var h = win.outerHeight(true) - titlebar.outerHeight(true) - 16;
        contents.css("height", h.toString() + 'px');
    },
    
    this.setWindowToContent = function() {
        var h= contents.outerHeight(true) + titlebar.outerHeight(true);
        win.css('height', h+'px');
    }
    
}
