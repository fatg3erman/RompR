var backimagemanager = function() {

	var backi = null;
	var portCount = 0;
	var landCount = 0;
	var pholder = null;
	var lholder = null;

	function make_image_holder(image, orientation, holder) {
		var outer = $('<div>', {class: 'fixed albumimg closet'}).appendTo(holder);
		var container = $('<div>', {class: 'covercontainer back-image'}).appendTo(outer);

		var pratt = $('<input>', {type: 'hidden', class: 'back-filename', value: image}).appendTo(container);
		var prott = $('<input>', {type: 'hidden', class: 'back-orientation', value: orientation}).appendTo(container);

		var url = 'getShrunkImage.php?url='+image+'&rompr_resize_size=smallish';
		var img = $('<img>', {class: 'lazy infoclick plugclickable bonk-image', 'data-src': url}).appendTo(container);
		var label = $('<div>').appendTo(container);
		// basename() in Javascript
		label.html(image.split('/').reverse()[0]);
		label.append($('<i>', {class: 'icon-cancel-circled smallicon back-image-delete infoclick plugclickable'}));
	}

	function observeImages(holder) {
		if (typeof(IntersectionObserver) == 'function') {
			holder.find("img.lazy").get().forEach(img => imageLoader.observe(img));
		} else {
 			holder.find("img.lazy").each(function() {
				var myself = $(this);
 				myself.attr('src', myself.attr('data-src')).removeAttr('data-src').removeClass('lazy');
			});
		}
	}

	function unobserveImages(holder) {
		if (typeof(IntersectionObserver) == 'function') {
			holder.find("img").get().forEach(img => imageLoader.unobserve(img));
		}
	}

	function updateCounts(gonk) {
		if (gonk) {
			$('#bg-portrait-title').html(portCount.toString()+' Portrait Images');
			$('#bg-landscape-title').html(landCount.toString()+' Landscape Images');
		} else {
			$('#bg-portrait-title').html('');
			$('#bg-landscape-title').html('');
		}
	}

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
		evt = ev.originalEvent;
		$(evt.target).removeClass("highlighted");
		// If we have > max_file_uploads files, upload them in batches
		var max_files = $('input[name="max_file_uploads"]').val();
		var offset = 0;
		var formData = new FormData();
		while (offset < evt.dataTransfer.files.length) {
			formData.append('imagefile[]', evt.dataTransfer.files[offset]);
			offset++;
			if (offset == max_files || offset == evt.dataTransfer.files.length) {
				formData.append('currbackground', prefs.theme);
				uploadFiles(formData);
				formData = new FormData();
			}
		}
		return false;
	}

	function uploadFiles(formData) {
		$('#bgfileuploadbutton').fadeOut('fast');
		$('#bguploadspinner').addClass('spinner').parent().fadeIn('fast');
		if ($('#thisbrowseronly').val() == 1) {
			formData.append('thisbrowseronly', 'on');
		}
		fetch(
			"api/userbackgrounds/",
			{
				signal: AbortSignal.timeout(1800000),
				body: formData,
				cache: 'no-store',
				method: 'POST',
				priority: 'low'
			}
		)
		.then(response => {
			switch (response.status) {
				case 200:
					debug.debug("BGIMAGE", response);
					if (portCount == 0 && landCount == 0) {
						// If there were previously no images we need to call setTheme again
						prefs.setTheme();
					} else {
						// Otherwise just repopuluate, which is smoother for the user.
						backimagemanager.populate();
					}
					break;

				case 400:
					throw new Error(language.gettext('error_toomanyimages'));
					break;

				default:
					throw new Error(language.gettext('error_imageupload')+'<br />'+response.statusText);
					break;
			}
		})
		.catch(err => {
			debug.error('BGIMAGE', err);
			infobar.error(err);
		})
		.finally(function() {
			$('#bguploadspinner').removeClass('spinner').parent().fadeOut('fast');
			$('#wanglerbumface').html(language.gettext('label_choosefiles'));
		});
	}

	function set_thisbrowseronly(t) {
		if (t) {
			$('#thisbrowseronly').val(1);
		} else {
			$('#thisbrowseronly').val(0);
		}
	}

	return {

		open: function() {
			if (backi == null) {
				backi = browser.registerExtraPlugin("backi", language.gettext("manage_bgs"), backimagemanager, null);
				$("#backifoldup").append('<div class="noselection fullwidth" id="backimunger"></div>');
				$('#backimunger').append('<div id="bgnotsupported" class="textcentre"><b>Custom background images are not supported with this theme</b></div>');
				$('#backimunger').append(
					'<div id="bguploader" class="containerbox">' +

					'<div class="expand" style="margin-right: 1em">' +

					'<form id="backimageform" enctype="multipart/form-data">' +
					'<input type="hidden" name="currbackground" />' +

					'<div class="filebutton textcentre" style="width:auto">'+
					'<input type="file" name="imagefile[]" id="imagefile" class="inputfile" multiple="multiple" />' +
					'<label id="wanglerbumface" for="imagefile">'+language.gettext('label_choosefiles')+'</label>' +
					'</div>' +
					'<input type="button" class="invisible" id="bgfileuploadbutton" value="'+language.gettext('albumart_uploadbutton')+'"></input>' +
					'<div class="textcenter invisible"><i class="icon-spin6 medicon" id="bguploadspinner"></i></div>' +

					'</form>'+

					'</div>' +

					'<div class="expand" style="margin-left:1em">' +

					'<div id="bg-drop-image" class="plugin_backi_drop-images">Drop Images Here</div>' +

					'<div class="styledinputs clearfix">' +
					'<button class="tright" id="bg-removeall">'+language.gettext('label_remove_all')+'</button>' +
					'</div>' +

					'<div class="containerbox vertical-centre">' +
					'<div class="selectholder"><select id="thisbrowseronly">' +
					'<option value="0">'+language.gettext('label_bg_global')+'</option>' +
					'<option value="1">'+language.gettext('label_bg_only')+'</option>' +
					'</select>' +
					'</div>' +
					'</div>' +

					'</div>' +

					'</div>'
				);

				$('#backimunger').append(
					'<div class="infobanner containerbox infosection">' +
					'<h2 class="expand" id="bg-portrait-title"></h2>' +
					'<div class="fixed alignmid"><i class="icon-menu svg-square infoclick plugclickable bg-hide-panel"></i></div>' +
					'</div>'
				);
				pholder = $('<div>', {class: 'containerbox wrap'}).appendTo('#backimunger');

				$('#backimunger').append(
					'<div class="infobanner containerbox infosection">' +
					'<h2 class="expand" id="bg-landscape-title"></h2>' +
					'<div class="fixed alignmid"><i class="icon-menu svg-square infoclick plugclickable bg-hide-panel"></i></div>' +
					'</div>'
				);
				lholder = $('<div>', {class: 'containerbox wrap'}).appendTo('#backimunger');

				backimagemanager.populate();
				$('#bgfileuploadbutton').off(prefs.click_event).on(prefs.click_event, backimagemanager.uploadImages);
				$('#bg-removeall').off(prefs.click_event).on(prefs.click_event, backimagemanager.remove_all);
				$('#thisbrowseronly').off('change').on('change', backimagemanager.switch_browser_mode);
				$('#bg-drop-image').on('dragenter', dragEnter);
				$('#bg-drop-image').on('dragover', dragOver);
				$('#bg-drop-image').on('dragleave', dragLeave);
				$('#bg-drop-image').on('drop', handleDrop);

				backi.show();
				browser.goToPlugin("backi");
			} else {
				browser.goToPlugin("backi");
			}

		},

		handleClick: function(element, event) {
			if (element.hasClass('bonk-image')) {
				var url = element.parent().children('input.back-filename').first().val();
				var orientation = element.parent().children('input.back-orientation').first().val();
				prefs.setBgImage(orientation, url);
			} else if (element.hasClass('back-image-delete')) {
				var url = element.parent().parent().children('input.back-filename').first().val();
				var ori = element.parent().parent().children('input.back-orientation').first().val();
				fetch('api/userbackgrounds/?deleteimage='+url);
				element.parent().parent().parent().fadeOut('fast');
				if (ori == 'portrait')
					portCount--;

				if (ori == 'landscape')
					landCount--;

				updateCounts();
			} else if (element.hasClass('bg-hide-panel')) {
				element.parent().parent().next().slideToggle('fast');
			}
		},

		close: function() {
			backi = null;
		},

		uploadImages: function() {
			$('input[name="currbackground"]').val(prefs.theme);
			var formElement = document.getElementById('backimageform');
			var formData = new FormData(formElement);
			uploadFiles(formData);
		},

		populate: async function() {

			unobserveImages(pholder);
			unobserveImages(lholder);
			pholder.empty();
			lholder.empty();
			portCount = 0;
			landCount = 0;

			if ($('#custombackground').css('display') == 'none') {
				$('#bgnotsupported').show();
				$('#bguploader').hide();
				updateCounts(false);
			} else {
				$('#bgnotsupported').hide();
				$('#bguploader').show();
				var response = await fetch('api/userbackgrounds/?get_all_backgrounds='+prefs.theme);
				var images = await response.json();
				set_thisbrowseronly(images.thisbrowseronly);
				debug.log('BACKIMAGE', images);
				if (images.images) {
					images.images.portrait.forEach(function(im) {
						make_image_holder(im, 'portrait', pholder);
					});
					observeImages(pholder);

					images.images.landscape.forEach(function(im) {
						make_image_holder(im, 'landscape', lholder);
					});
					observeImages(lholder);

					portCount = images.images.portrait.length;
					landCount = images.images.landscape.length;
				}
				updateCounts(true);
			}
		},

		remove_all: function() {
			$.getJSON('api/userbackgrounds/?clearallbackgrounds='+prefs.theme+'&browser_id=', function(data) {
				prefs.setTheme();
			});
		},

		switch_browser_mode: function() {
			if (portCount > 0 || landCount > 0) {
				$.getJSON('api/userbackgrounds/?switchbrowseronly='+prefs.theme+'&thisbrowseronly='+$('#thisbrowseronly').val(), function(data) {
					prefs.setTheme();
				});
			}
		}

	}

}();

pluginManager.setAction(language.gettext("manage_bgs"), backimagemanager.open);
backimagemanager.open();
