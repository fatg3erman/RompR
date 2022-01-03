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
		$(ev.target).addClass("dropper-highlighted");
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
		$(ev.target).removeClass("dropper-highlighted");
		return false;
	}

	function handleDrop(ev) {
		evt = ev.originalEvent;
		$(evt.target).removeClass("dropper-highlighted");
		// If we have > max_file_uploads files, upload them in batches
		var max_files = $('input[name="max_file_uploads"]').val();
		var offset = 0;
		var formData = new FormData();
		while (offset < evt.dataTransfer.files.length) {
			formData.append('imagefile[]', evt.dataTransfer.files[offset]);
			offset++;
			if (offset == max_files || offset == evt.dataTransfer.files.length) {
				formData.append('currbackground', prefs.theme);
				formData.append('browser_id', $('#browser_id').val());
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
		var xhr = new XMLHttpRequest();
		xhr.open("POST", "api/userbackgrounds/");
		xhr.responseType = "json";
		xhr.onload = function () {
			switch (xhr.status) {
				case 200:
					debug.debug("BIMAGE", xhr.response);
					$('#bguploadspinner').removeClass('spinner').parent().fadeOut('fast');
					$('#wanglerbumface').html(language.gettext('label_choosefiles'));
					if (portCount == 0 && landCount == 0) {
						// If there wre previously no images we need to call setTheme to prefs starts
						// showing the new ones. That will call populate;
						prefs.setTheme();
					} else {
						// Otherwise just repopuluate, which is smoother for the user.
						backimagemanager.populate();
					}
					break;

				case 400:
					debug.warn("BIMAGE", "FAILED");
					infobar.error(language.gettext('error_toomanyimages'));
					// Fall Through

				default:
					debug.warn("BIMAGE", "FAILED");
					infobar.error(language.gettext('error_imageupload'));
					$('#bguploadspinner').removeClass('spinner').parent().fadeOut('fast');

			}
		};
		xhr.send(formData);
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
					'<input type="hidden" name="browser_id" />' +

					'<div class="filebutton textcentre" style="width:auto">'+
					'<input type="file" name="imagefile[]" id="imagefile" class="inputfile" multiple="multiple" />' +
					'<label id="wanglerbumface" for="imagefile">'+language.gettext('label_choosefiles')+'</label>' +
					'</div>' +
					'<input type="button" class="invisible" id="bgfileuploadbutton" value="'+language.gettext('albumart_uploadbutton')+'"></input>' +
					'<div class="textcenter invisible"><i class="icon-spin6 medicon" id="bguploadspinner"></i></div>' +

					'</form>'+

					'</div>' +

					'<div class="expand" style="margin-left:1em">' +

					'<div id="bg-drop-image" class="drop-images-here">Drop Images Here</div>' +

					'<div class="styledinputs clearfix">' +
					'<button class="tright" id="bg-removeall">'+language.gettext('label_remove_all')+'</button>' +
					'</div>' +

					'<div class="containerbox dropdown-container">' +
					'<div class="selectholder"><select id="thisbrowseronly">' +
					'<option value="0">'+language.gettext('label_bg_global')+'</option>' +
					'<option value="1">'+language.gettext('label_bg_only')+'</option>' +
					'</select>' +
					'</div>' +
					'</div>' +

					'</div>' +

					'<input type="hidden" id="browser_id" value="' + prefs.browser_id + '" />' +

					'</div>'
				);

				$('#backimunger').append(
					'<div class="albumsection">' +
					'<div class="tleft"><h2 id="bg-portrait-title"></h2></div>' +
					'</div>'
				);
				pholder = $('<div>', {class: 'containerbox wrap'}).appendTo('#backimunger');

				$('#backimunger').append(
					'<div class="albumsection">' +
					'<div class="tleft"><h2 id="bg-landscape-title"></h2></div>' +
					'</div>'
				);
				lholder = $('<div>', {class: 'containerbox wrap'}).appendTo('#backimunger');

				backimagemanager.populate();
				$('#bgfileuploadbutton').off('click').on('click', backimagemanager.uploadImages);
				$('#bg-removeall').off('click').on('click', backimagemanager.remove_all);
				$('#thisbrowseronly').off('change').on('change', backimagemanager.switch_browser_mode);
				// Hidden feature. To edit the images for this thmese on another browser, do $('browser_id').val('browser id to edit')
				// and then select 'this browser only' if it isn't already selected.
				$('#browser_id').off('change').on('change', backimagemanager.populate);
				$('#bg-drop-image').on('dragenter', dragEnter);
				$('#bg-drop-image').on('dragover', dragOver);
				$('#bg-drop-image').on('dragleave', dragLeave);
				$('#bg-drop-image').on('drop', handleDrop);

				backi.show();
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
				$.ajax({
					method: 'GET',
					url: 'api/userbackgrounds/?deleteimage='+url,
					dataType: 'json',
					cache: false
				});
				element.parent().parent().parent().fadeOut('fast');
				if (ori == 'portrait')
					portCount--;

				if (ori == 'landscape')
					landCount--;

				updateCounts();
			}
		},

		close: function() {
			backi = null;
		},

		uploadImages: function() {
			$('input[name="currbackground"]').val(prefs.theme);
			$('input[name="browser_id"]').val($('#browser_id').val());
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
				var images = await $.ajax({
					method: 'GET',
					url: 'api/userbackgrounds/?get_all_backgrounds='+prefs.theme+'&browser_id='+$('#browser_id').val(),
					dataType: 'json',
					cache: false
				});
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
			$.getJSON('api/userbackgrounds/?clearallbackgrounds='+prefs.theme+'&browser_id='+$('#browser_id').val(), function(data) {
				prefs.setTheme();
			});
		},

		switch_browser_mode: function() {
			if (portCount > 0 || landCount > 0) {
				$.getJSON('api/userbackgrounds/?switchbrowseronly='+prefs.theme+'&browser_id='+$('#browser_id').val()+'&thisbrowseronly='+$('#thisbrowseronly').val(), function(data) {
					prefs.setTheme();
				});
			}
		}

	}

}();

pluginManager.setAction(language.gettext("manage_bgs"), backimagemanager.open);
backimagemanager.open();
