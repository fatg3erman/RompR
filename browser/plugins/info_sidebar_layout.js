function info_sidebar_layout(options) {

	var self = this;
	debug.trace('SBLAYOUT', options);

	var settings = $.extend({
		expand: false,
		expandid: null,
		title: 'There is no title',
		withbannerid: true
	}, options);

	function make_expand_icon(id) {
		$('<i>', {class: 'icon-expand-up medicon clickexpandbox infoclick tleft', name: id}).prependTo(self.mainbit);
	}

	this.everything = $('<div>');
	this.banner = browser.info_banner({name: settings.title, withfoldup: settings.withbannerid}, settings.source, false).appendTo(this.everything);
	this.foldup = $('<div>', {class: 'foldup infobanner'}).appendTo(this.everything);
	if (settings.withbannerid) {
		this.foldup.prop('id', settings.type+'foldup');
	}
	this.html = $('<div>').appendTo(this.foldup);
	this.holder = $('<div>', {class: 'containerbox info-detail-layout'}).appendTo(this.html);
	this.sidebar = $('<div>', {class: 'info-box-fixed info-box-list info-border-right'}).appendTo(this.holder);
	this.widebit = $('<div>', {class: 'info-box-expand stumpy'}).appendTo(this.holder);
	this.mainbit = $('<div>', {class: 'holdingcell'}).appendTo(this.widebit);
	if (settings.expand)
		make_expand_icon(settings.expandid);

	this.clear_out = function() {
		self.sidebar.empty();
		self.html.find('.spotchoices').empty();
		self.mainbit.empty();
		self.widebit.children().not('.holdingcell').remove();
		self.html.children().not('.info-detail-layout').remove();
	}

	this.remove_expand_icon = function() {
		self.mainbit.find('i.icon-expand-up').remove();
	}

	this.get_title = function() {
		return settings.title;
	}

	this.check_expand_icon = function(id) {
		if (self.mainbit.find('i.icon-expand-up').length == 0)
			make_expand_icon(id);
	}

	this.finish = function(uri, name) {
		var icon = self.banner.find('.icon-spin6.spinner');
		icon.removeClass('icon-spin6 spinner').addClass(browser.get_icon(settings.source));
		if (uri)
			icon.attr({title: language.gettext('info_newtab')}).wrap($('<a>', {href: uri, target: '_blank'}));

		if (name)
			self.banner.find('h2').html(name);
	}

	this.make_possibility_chooser = function(possibilities, currentposs, name) {
		if (possibilities && possibilities.length > 1) {
			let tbl = $('<table>').appendTo($('<div>', {class: 'spotchoices clearfix'}).prependTo(self.html));
			let tr = $('<tr>').appendTo(tbl);
			$('<td>').append(
				$('<div>', {class: 'bleft tleft spotthing'}).append(
					$('<span>', {class: 'spotpossname'}).html('All possibilities for '+name)
				)
			).appendTo(tr);
			let td = $('<td>').appendTo(tr);
			possibilities.forEach(function(poss, index) {
				let c = $('<div>', {class: 'tleft infoclick bleft clickchooseposs', name: index}).appendTo(td);
				if (index == currentposs)
					c.addClass('bsel');
				let i = $('<img>', {class: 'spotpossimg title-menu'}).appendTo(c);
				i.attr('src', poss.image ? 'getRemoteImage.php?url='+rawurlencode(poss.image) : 'newimages/artist-icon.png');
				c.append($('<span>', {class: 'spotpossname'}).html(poss.name));
			});
		}
	}

	this.display_error = function(error) {
		self.holder.remove();
		self.html.append($('<h3>', {align: 'center'}).html(error));
	}

	this.add_main_image = function(image) {
		$('<input>', {type: 'hidden'}).val('getRemoteImage.php?url='+rawurlencode(image)).insertAfter(
			$('<img>', {class: 'standout infoclick clickzoomimage cshrinker stright', src: 'getRemoteImage.php?url='+rawurlencode(image)}).prependTo(self.mainbit)
		);
	}

	this.add_masonry_images = function(images) {
		var holder = $('<div>', {class: 'fullwidth masonified2'}).appendTo(self.widebit);
		holder.imageMasonry({images: images});
	}

	this.add_playable_images = function(spotidata) {
		var holder = $('<div>', {class: 'holdingcell selecotron masonified4'}).appendTo(self.html);
		holder.playableMasonry({spotidata: spotidata});
	}

	this.add_sidebar_image = function(thumb, image) {
		$('<input>', {type: 'hidden'}).val('getRemoteImage.php?url='+rawurlencode(image)).insertAfter(
			$('<img>', {class: 'infoclick clickzoomimage', src: 'getRemoteImage.php?url='+rawurlencode(thumb)}).appendTo(self.sidebar)
		);
	}

	this.add_profile = function(profile) {
		$('<p>', {class: 'minwidthed'}).html(profile).appendTo(self.mainbit);
	}

	this.add_sidebar_list = function(label, value) {
		var list = $('<ul>').appendTo(self.sidebar);
		value = value ? ' '+value : '';
		list.append($('<li>').html('<b>'+label+'</b>'+value));
		return list;
	}

	this.append_to_list = function(list, label, value) {
		list.append($('<li>').html('<b>'+label+'</b>&nbsp;'+value));
	}

	this.add_flow_box_header = function(options) {
		var settings = $.extend({
			wide: false,
			title: ''
		}, options);

		var mbbox = $('<div>', {class: 'mbbox underline'}).html('<b>'+settings.title+'</b>').appendTo(self.mainbit);
		if (settings.wide) {
			mbbox.addClass('minwidthed3');
		} else {
			mbbox.addClass('minwidthed');
		}
		return mbbox;
	}

	this.add_flow_box = function(content) {
		return $('<div>', {class: 'mbbox'}).html(content).appendTo(self.mainbit);
	}

	this.add_non_flow_box_header = function(options) {
		var settings = $.extend({
			wide: false,
			title: ''
		}, options);

		var mbbox = $('<div>', {class: 'mbbox underline'}).html('<b>'+settings.title+'</b>').appendTo(self.html);
		if (settings.wide) {
			mbbox.addClass('minwidthed3');
		} else {
			mbbox.addClass('minwidthed');
		}
		return mbbox;
	}

	this.add_non_flow_box = function(content) {
		return $('<div>', {class: 'mbbox'}).html(content).appendTo(self.html);
	}

	this.add_dropdown_box = function(target, cls, name, dropname, title) {
		target.empty();
		target.append($('<i>', {class: 'icon-toggle-closed menu infoclick '+cls, name: name}));
		target.append($('<span>', {class: 'title-menu'}).html(title));
		$('<div>', {class: 'invisible', name: dropname}).insertAfter(target);
	}

	this.getHTML = function() {
		return self.everything.html();
	}

	this.get_contents = function() {
		self.everything.find('div.foldup').css({opacity: 1});
		return self.everything;
	}

	this.detach_contents = function() {
		self.everything.detach();
	}

}

function info_html_layout(options) {
	var self = this;
	debug.trace('HTLAYOUT', options);

	var settings = $.extend({
		title: 'There is no title',
		withbannerid: true
	}, options);

	this.everything = $('<div>');
	this.banner = browser.info_banner({name: settings.title, withfoldup: settings.withbannerid}, settings.source, false).appendTo(this.everything);
	this.foldup = $('<div>', {class: 'foldup infobanner'}).appendTo(this.everything);
	if (settings.withbannerid) {
		this.foldup.prop('id', settings.type+'foldup');
	}
	this.html = $('<div>').appendTo(this.foldup);

	this.clear_out = function() {
		self.html.empty();
	}

	this.get_title = function() {
		return settings.title;
	}

	this.finish = function(uri, name, html) {

		self.html.html(html);

		var icon = self.banner.find('.icon-spin6.spinner');
		icon.removeClass('icon-spin6 spinner').addClass(browser.get_icon(settings.source));
		if (uri)
			icon.attr({title: language.gettext('info_newtab')}).wrap($('<a>', {href: uri, target: '_blank'}));

		if (name)
			self.banner.find('h2').html(name);

	}

	this.get_contents = function() {
		return self.everything;
	}

	this.detach_contents = function() {
		self.everything.detach();
	}

}

function info_special_layout(options) {
	var self = this;

	var settings = $.extend({
		title: 'There is no title'
	}, options);

	this.everything = $('<div>');
	this.banner = browser.info_banner({name: settings.title, link: settings.link, withfoldup: true}, settings.source, false).appendTo(this.everything);
	this.foldup = $('<div>', {class: 'foldup infobanner', id: settings.type+'foldup'}).appendTo(this.everything);

	this.html = $(settings.html).appendTo(this.foldup);

	this.clear_out = function() {
		self.html.remove();
	}

	this.remove_expand_icon = function() {
		self.html.find('i.icon-expand-up').first().remove();
	}

	this.make_possibility_chooser = function() {

	}

	this.get_title = function() {
		return settings.title;
	}

	this.get_contents = function() {
		self.everything.find('div.foldup').css({opacity: 1});
		return self.everything;
	}

	this.detach_contents = function() {
		self.everything.detach();
	}

}

function info_layout_empty() {

	this.clear_out = function() { }

	this.make_possibility_chooser = function() { }

	this.get_title = function() {
		return '';
	}

	this.get_contents = function() {
		return '';
	}

	this.detach_contents = function() { }

}

function info_panel_expand_box(source, element, event, name, me, link) {
	var expandingframe = element.parent().parent().parent().parent();
	// We use a special layout and just clone the html contents. This avoids getting into the
	// horrble situation where you try to create a JQuery object where the child contains the parent.
	// JQuery does not like that (inifinite recursion), and there isn't a good way to prevent it - apart from this.
	// While this is not as memory efficient as I'd like (there is duplicate HTML data now) the alternative
	// involves scanning through for potential circular references and unrolling them in some sort of
	// appalling minority report scenario.
	var layout = new info_special_layout({
		html: expandingframe.html(),
		title: name,
		type: 'artist',
		source: me,
		link: link
	});
	layout.remove_expand_icon();
	var pos = expandingframe.offset();
	var target = $("#artistfoldup").length == 0 ? me : "artist";
	var targetpos = $("#"+target+"foldup").offset();
	var animator = expandingframe.clone();
	animator.css('position', 'absolute');
	animator.css('top', pos.top+"px");
	animator.css('left', pos.left+"px");
	animator.css('width', expandingframe.width()+"px");
	animator.appendTo($('body'));
	$("#"+target+"foldup").css({opacity: 0});
	animator.animate(
		{
			top: targetpos.top+"px",
			left: targetpos.left+"px",
			width: $("#artistinformation").width()+"px"
		},
		'fast',
		'swing',
		function() {
			nowplaying.special_update(
				me,
				'artist',
				layout
			);
			animator.remove();
		}
	);
}

$.widget('rompr.imageMasonry', {

	options: {
		images: [],
		class: 'tagholder2',
		id: 'buggery_'+Date.now()
	},

	_create: function() {
		var self = this;
		this.element.empty();
		this.element.attr('id', self.options.id)

		this.options.images.forEach(function(image) {
			$('<input>', {type: 'hidden'}).val('getRemoteImage.php?url='+rawurlencode(image)).insertAfter(
				$('<img>', {class: 'infoclick clickzoomimage float-img', src: 'getRemoteImage.php?url='+rawurlencode(image)})
				.appendTo($('<div>', {class: self.options.class})
				.appendTo(self.element))
			);
		});
		this.element.imagesLoaded(function() {
			// Masonry doesn't work if I use self.element here?????
			browser.rePoint($('#'+self.options.id), {itemSelector: '.'+self.options.class, percentPosition: true});
		});

	},

	_destroy: function() {
		this.element.masonry('destroy');
		this.element.empty();
		browser.rePoint();
	}

});

$.widget('rompr.playableMasonry', {

	options: {
		spotidata: [],
		class: 'tagholder4',
		id: 'baggery_'+Date.now()
	},

	_create: function() {
		var self = this;
		this.element.empty();
		this.element.attr('id', self.options.id)
		this.options.spotidata.tracks.forEach(function(track) {
			var img = (track.album.images && track.album.images.length > 0) ?
				'getRemoteImage.php?url='+rawurlencode(track.album.images[0].url)+'&rompr_resize_size=smallish' : 'newimages/spotify-icon.png';
			var x = $('<div>', {class: 'arsecandle tagholder4 clickable draggable clicktrack playable notthere', name: rawurlencode(track.uri)}).appendTo(self.element);
			x.append($('<img>', {class: 'cheeseandfish', src: img}));
			var an = track.artists.map(a => a.name);
			x.append($('<div>').html(track.name+'<br /><b>'+concatenate_artist_names(an)+'</b>'));
		});
		this.element.imagesLoaded(function() {
			// Masonry doesn't work if I use self.element here?????
			$('#'+self.options.id).find('.notthere').removeClass('notthere');
			setDraggable('#'+self.options.id);
			browser.rePoint($('#'+self.options.id), {itemSelector: '.arsecandle', columnWidth: '.arsecandle', percentPosition: true});
		});
	},

	_destroy: function() {
		this.element.masonry('destroy');
		this.element.empty();
		browser.rePoint();
	}

});
