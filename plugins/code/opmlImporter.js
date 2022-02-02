var opmlImporter = function() {

	var opmlv = null;

	return {

		open: function() {

			if (opmlv == null) {
				opmlv = browser.registerExtraPlugin("opmlv", language.gettext("label_opmlimporter"), opmlImporter, 'https://fatg3erman.github.io/RompR/OPML-Importer');
				$('#opmlvfoldup').append(
					'<div class="fullwidth">'+
					'<form id="opmluploader" action="plugins/code/opmluploader.php" method="post" enctype="multipart/form-data">'+
					'<div class="filebutton textcentre">'+
					'<input class="inputfile" name="opmlfile" id="opmlfile" type="file" />'+
					'<label for="opmlfile">'+language.gettext('label_choosefile')+'</label>'+
					'</div>'+
					'<input class="invisible" id="opmlsubmit" value="'+language.gettext("albumart_uploadbutton")+'" type="button" />'+
					'</form>'+
					'</div>'
				);
				$('#opmlvfoldup').append('<div id="opmllist"></div>');
				$('#opmlvfoldup').append('<h2>'+language.gettext('label_opmlexp')+'</h2>')
				$('#opmlvfoldup').append(
					'<div class="fullwidth">'+
					'<a href="plugins/code/opmlexport.php" download="podcasts.opml">'+
					'<button>'+language.gettext('button_opmlexp')+'</button>'+
					'</a>'+
					'</div>'
				);
				$('#opmlsubmit').on('click', opmlImporter.uploadFile);
				opmlv.slideToggle('fast', function() {
					browser.goToPlugin("opmlv");
				});
			} else {
				browser.goToPlugin("opmlv");
			}

		},

		handleClick: function(element, event) {

		},

		close: function() {
			opmlv = null;
		},

		uploadFile: function() {
			var formElement = document.getElementById('opmluploader');
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "plugins/code/opmluploader.php");
			xhr.responseType = "json";
			xhr.onload = function () {
				if (xhr.status === 200) {
					opmlImporter.gotData(xhr.response);
				} else {
					infobar.error(language.gettext('label_general_error'));
				}
			};
			xhr.send(new FormData(formElement));
		},

		gotData: function(data) {
			debug.debug("OPML IMPORTER", "File Parsed",data);
			var html = '';
			html += '<div class="configtitle"><div class="expand textcentre">'+language.gettext('label_opmlimporter')+'</div></div>';
			html += '<div class="containerbox fullwidth">';
			html += '<button class="fixed" name="opml_selectall">'+language.gettext('button_selectall')+'</button>';
			html += '<button class="fixed" name="opml_selectnone">'+language.gettext('button_selectnone')+'</button>';
			html += '<div class="expand"></div>';
			html += '<button class="fixed" name="opml_import">'+language.gettext('button_import')+'</button>';
			html += '</div>';
			html += '<table width="100%">';
			for (var i in data) {
				html += '<tr>';
				html += '<td>';
				if (data[i].subscribed) {
					html += '<i class="icon-tick smallicon"></i>';
				} else {
					html += '<div class="styledinputs">';
					html += '<input type="hidden" value="'+data[i].feedURL+'" />';
					html += '<input id="opml_'+i+'" class="topcheck" type="checkbox" />';
					html += '<label for="opml_'+i+'">&nbsp;</label>';
					html += '</div>';
				}
				html += '</td>';
				html += '<td>'+data[i].Title+'</td>';
				html += '<td><a href="'+data[i].htmlURL+'" target="_blank">'+data[i].htmlURL+'</a></td>';
				html += '</tr>';
			}
			html += '</table>';
			$('#opmllist').html(html);
			$('[name="opml_selectall"]').on('click', opmlImporter.selectAll);
			$('[name="opml_selectnone"]').on('click', opmlImporter.selectNone);
			$('[name="opml_import"]').on('click', opmlImporter.Import);
			opmlImporter.selectAll();
		},

		selectAll: function() {
			$('#opmllist input[type="checkbox"]').prop('checked', true);
		},

		selectNone: function() {
			$('#opmllist input[type="checkbox"]').prop('checked', false);
		},

		Import: function() {
			$('[name="opml_import"]').off('click');
			var s = $('#opmllist input[type="checkbox"]:checked');
			if (s.length > 0) {
				opmlImporter.subscribeToNext(s.first());
			} else {
				$('[name="opml_import"]').on('click', opmlImporter.Import);
				podcasts.doNewCount();
			}
		},

		subscribeToNext: async function(c) {
			var feedUrl = c.prev().val();
			var s = $('<i>', {class: 'spinable smallicon'}).insertBefore(c);
			c.next().remove();
			c.remove();
			debug.log("OPML IMPORTER","Importing Podcast",feedUrl);
			await podcasts.getFromUrl(feedUrl, s);
				// if (flag) {
					debug.debug("OPML Importer", "Success?");
					s.replaceWith('<i class="icon-tick smallicon"></i>');
					opmlImporter.Import();
				// } else {
				// 	debug.warn("OPML Importer", "Failed to import",feedUrl);
				// 	s.replaceWith('<i class="icon-attention-1 smallicon"></i>');
				// 	opmlImporter.Import();
				// }
			// });
		}

	}

}();

pluginManager.setAction(language.gettext("label_opmlimporter"), opmlImporter.open);
opmlImporter.open();
