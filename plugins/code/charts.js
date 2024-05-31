var charts = function() {

	var cha = null;
	var holders = new Array();
	var refreshtimer;

	const CHARTS_OPTIONS = [
		'Display Everyting',
		'Music Collection Only',
		'Spoken Word Only',
		'Internet Sources Only'
	];

	function putItems(holder, data, title) {
		var cols = 0;
		for (var i in data[0]) {
			if (i != 'Uri')
				cols++;
		}
		cols++;
		var html = '<table align="center" style="border-collapse:collapse;width:96%"><tr class="tagh"><th colspan="'+cols+'" align="center">'+title+'</th></tr>';
		html += '<tr class="chartheader">';
		html += '<td></td>';
		for (var i in data[0]) {
			if (i != 'Uri') {
				html += '<td><b>'+language.gettext(i)+'</b></td>';
			}
		}
		var maxplays = data[0].soundcloud_plays;
		debug.log("CHARTS","Max plays for",title,"is",maxplays);
		html += '</tr>';
		var count = 0;
		for (let item of data) {
			count++;
			if (item.Uri) {
				if (prefs.player_backend == "mpd" && item.Uri.match(/soundcloud:/)) {
					html += '<tr class="chart draggable clickcue playable backhi" name="'+rawurlncode(item.Uri)+'">';
				} else {
					html += '<tr class="chart draggable clicktrack playable backhi" name="'+rawurlencode(item.Uri)+'">';
				}
			} else {
				html += '<tr class="chart">';
			}
			html += '<td><i>'+count+'</i></td>';
			for (var j in item) {
				if (j != "Uri") {
					html += '<td>'+item[j]+'</td>';
				}
			}
			html += '</tr>';

			var percent = (item.soundcloud_plays/maxplays)*100;
			html += '<tr style="height:4px"><td class="chartbar" colspan="'+cols+'" style="background:linear-gradient(to right, '+getrgbs(percent,0)+'"></td></tr>';
			html += '<tr style="height:0.75em"><td colspan="'+cols+'"></td></tr>';

		}
		html += '</table>';
		holder.html(html);
	}

	function getCharts(success, failure) {
		metaHandlers.genericQuery('getcharts', success, failure);
	}

	return {

		open: function() {

			if (cha == null) {
				cha = browser.registerExtraPlugin("cha", language.gettext("label_charts"), charts);
				var h = $('<div>', {class: 'containerbox dropwdown-container'}).appendTo('#chafoldup');
				var sh = $('<div>', {class: 'selectholder'}).appendTo(h);
				var sl = $('<select>', {id: 'chartoptionselector', class: 'saveomatic'}).appendTo(sh);
				CHARTS_OPTIONS.forEach(function(v, i) {
					sl.append($('<option>', {value: i}).html(v));
				});
				prefs.setPrefs();
				$("#chafoldup").append('<div class="noselection fullwidth masonified" id="chamunger"></div>');
				getCharts(charts.firstLoad, charts.firstLoadFail);
				sl.on('change', charts.reloadAll);
			} else {
				browser.goToPlugin("cha");
			}

		},

		firstLoad: function(data) {
			setDraggable('#chafoldup');
			charts.doMainLayout(data);
		},

		firstLoadFail: function(data) {
			infobar.error(language.gettext('label_general_error'));
			cha.slideToggle('fast');
		},

		doMainLayout: function(data) {
			debug.debug("CHARTS","Got data",data);
			for (var i in data) {
				holders[i] = $('<div>', {class: 'masonry_general selecotron noselection', id: 'chaman_'+i}).appendTo($("#chamunger"));
				putItems(holders[i], data[i], i);
			}
			cha.slideToggle('fast', function() {
				browser.goToPlugin("cha");
				browser.rePoint($("#chamunger"), {itemSelector: '.masonry_general', percentPosition: true});
			});
		},

		close: function() {
			cha = null;
			holders = [];
		},

		reloadAll: function() {
			clearTimeout(refreshtimer);
			if (cha) {
				refreshtimer = setTimeout(function() {
					getCharts(charts.backgroundUpdate, metaHandlers.genericFail)
				},
				1000);
			}
		},

		backgroundUpdate: function(data) {
			for (var i in data) {
				holders[i].empty();
				putItems(holders[i],data[i],i);
			}
			browser.rePoint($("#chamunger"), {itemSelector: '.masonry_general', percentPosition: true});
		}

	}

}();

pluginManager.setAction(language.gettext("label_charts"), charts.open);
charts.open();
