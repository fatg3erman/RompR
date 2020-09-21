function info_sidebar_layout(options) {

	var self = this;
	debug.mark('SBLAYOUT', options);

	var settings = $.extend({
		expand: false,
		expandid: null
	}, options);

	this.html = $('<div>');
	this.holder = $('<div>', {class: 'containerbox info-detail-layout'}).appendTo(this.html);
	this.sidebar = $('<div>', {class: 'info-box-fixed info-box-list info-border-right'}).appendTo(this.holder);
	this.mainbit = $('<div>', {class: 'holdingcell'}).appendTo($('<div>', {class: 'info-box-expand stumpy'}).appendTo(this.holder));
	if (settings.expand)
		$('<i>', {class: 'icon-expand-up medicon clickexpandbox infoclick tleft', name: settings.expandid}).appendTo(this.mainbit);

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

	this.add_main_image = function(image) {
		$('<input>', {type: 'hidden'}).val('getRemoteImage.php?url='+rawurlencode(image)).insertAfter(
			$('<img>', {class: 'standout infoclick clickzoomimage cshrinker stright', src: 'getRemoteImage.php?url='+rawurlencode(image)}).prependTo(self.mainbit)
		);
	}

	this.add_masonry_images = function(images) {
		var holder = $('<div>', {class: 'fullwidth masonified2'}).appendTo(self.mainbit);
		holder.imageMasonry({images: images});
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
		if (this.sidebar.is(':empty'))
			this.sidebar.remove();
		return self.html.html();
	}

	this.get_contents = function() {
		if (this.sidebar.is(':empty'))
			this.sidebar.remove();
		return self.html;
	}

}

function info_panel_expand_box(source, element, event, name, me) {
	var expandingframe = element.parent().parent().parent().parent();
	var content = expandingframe.html();
	content=content.replace(/<i class="icon-expand-up.*?\/i>/, '');
	var pos = expandingframe.offset();
	var target = $("#artistfoldup").length == 0 ? me : "artist";
	var targetpos = $("#"+target+"foldup").offset();
	var animator = expandingframe.clone();
	animator.css('position', 'absolute');
	animator.css('top', pos.top+"px");
	animator.css('left', pos.left+"px");
	animator.css('width', expandingframe.width()+"px");
	animator.appendTo($('body'));
	$("#"+target+"foldup").animate(
		{
			opacity: 0
		},
		'fast',
		'swing',
		function() {
			animator.animate(
				{
					top: targetpos.top+"px",
					left: targetpos.left+"px",
					width: $("#artistinformation").width()+"px"
				},
				'fast',
				'swing',
				function() {
					browser.speciaUpdate(
						me,
						'artist',
						{
							name: name,
							link: null,
							data: content
						}
					);
					animator.remove();
				}
			);
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
