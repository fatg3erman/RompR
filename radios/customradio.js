var customRadioManager = function() {

	const custom_radio_items = data_from_source('custom_radio_items');
	const radio_combine_options = data_from_source('radio_combine_options');

	var default_rule = {
		db_key: 'Nothing',
		option: 0,
		value: ''
	};

	var default_station = {
		name: language.gettext('label_createcustom'),
		combine_option: ' AND ',
		rules: [
			cloneObject(default_rule)
		]
	}

	var stations = new Array();

	var rule_counter = 0;

	function custom_radio_rule(table, params) {

		var self = this;
		var ui_row = $('<tr>').appendTo(table);
		var key_box = $('<td>').appendTo(ui_row);
		var option_box = $('<td>').appendTo(ui_row);
		var value_box = $('<td>').appendTo(ui_row);
		var delete_box = $('<td>').appendTo(ui_row);

		var key_selector;
		var option_selector;
		var delete_icon = $('<i>', {class: 'smallicon clickicon icon-cancel-circled'}).appendTo(delete_box);

		var enabled = false;
		var my_id = rule_counter;
		rule_counter++;

		function make_value_box(new_key) {
			switch (new_key) {
				case 'ta.Artistname':
					var dropper = $('<div>', {class: "containerbox expand spacer vertical-centre"}).
						appendTo(value_box).makeTagMenu({
							textboxname: 'rule_entry_'+my_id,
							placeholder: language.gettext('label_artists').capitalize(),
							labelhtml: '',
							populatefunction: starHelpers.populateArtistMenu,
							buttontext: null,
							buttonfunc: null
						});
						break;

				case 'aa.Artistname':
					var dropper = $('<div>', {class: "containerbox expand spacer vertical-centre"}).
						appendTo(value_box).makeTagMenu({
							textboxname: 'rule_entry_'+my_id,
							placeholder: language.gettext('label_artists').capitalize(),
							labelhtml: '',
							populatefunction: starHelpers.populateAlbumArtistMenu,
							buttontext: null,
							buttonfunc: null
						});
						break;

				case 'Genre':
					var dropper = $('<div>', {class: "containerbox expand spacer vertical-centre"}).
						appendTo(value_box).makeTagMenu({
							textboxname: 'rule_entry_'+my_id,
							placeholder: language.gettext('label_genres'),
							labelhtml: '',
							populatefunction: starHelpers.populateGenreMenu,
							buttontext: null,
							buttonfunc: null
						});
						break;

				case 'Tagtable.Name':
					var dropper = $('<div>', {class: "containerbox expand spacer vertical-centre"}).
						appendTo(value_box).makeTagMenu({
							textboxname: 'rule_entry_'+my_id,
							placeholder: language.gettext('label_tags'),
							labelhtml: '',
							populatefunction: tagAdder.populateTagMenu,
							buttontext: null,
							buttonfunc: null
						});
						break;

				case  'Title':
				case 'Albumname':
					value_box.append($('<input>', {type: 'text', name: 'rule_entry_'+my_id}));
					break;

				default:
					value_box.append($('<input>', {type: 'number', name: 'rule_entry_'+my_id}));
					break;
			}
			$('input[name="rule_entry_'+my_id+'"]').val(params.value);
		}

		this.initialise = function() {
			var key_div = $('<div>', {class: 'selectholder'}).appendTo(key_box);
			key_selector = $('<select>').appendTo(key_div);
			key_selector.append($('<option>').attr('value', 'Nothing').text(language.gettext('label_choose_one')).prop('disabled', true));
			custom_radio_items.forEach(function(item) {
				key_selector.append($('<option>').attr('value', item.db_key).text(language.gettext(item.name)));
			});
			key_selector.val(params.db_key);
			self.key_changed();
			key_selector.on('change', self.key_changed);
			delete_icon.on(prefs.click_event, self.delete);
		}

		this.key_changed = function(event) {
			var new_key = key_selector.val();
			debug.log('CUSTOMRADIO', 'Key Changed To', new_key);
			option_box.empty();
			value_box.empty();
			if (new_key != 'Nothing' && new_key !== null) {
				enabled = true;
				var option_div = $('<div>', {class: 'selectholder'}).appendTo(option_box);
				var options;
				custom_radio_items.forEach(function(item) {
					if (item.db_key == new_key) {
						options = item.options;
					}
				});
				option_selector = $('<select>').appendTo(option_div);
				$.each(options, function(i, v) {
					if (params.option == 0) {
						params.option = i;
					}
					option_selector.append($('<option>').attr('value', i).text(language.gettext(v)));
				});
				option_selector.val(params.option);
				self.option_changed();
				option_selector.on('change', self.option_changed);
				table.css('width', '100%');
			}
		}

		this.option_changed = function() {
			if (option_selector.val() == 45) {
				value_box.empty();
				value_box.append($('<input>', {type: 'hidden', name: 'rule_entry_'+my_id}).val('dummy'));
			} else {
				if (value_box.children('input[type="hidden"]').length > 0 || value_box.children('input').length == 0) {
					value_box.empty();
					make_value_box(key_selector.val());
				}
			}
		}

		this.delete = function() {
			enabled = false;
			ui_row.remove();
		}

		this.is_enabled = function() {
			return (enabled && $('input[name="rule_entry_'+my_id+'"]').val() != '');
		}

		this.get_params = function() {
			return {
				db_key: key_selector.val(),
				option: option_selector.val(),
				value: $('input[name="rule_entry_'+my_id+'"]').val()
			}
		}

	}

	function custom_radio_station(params) {

		var self = this;
		var my_id = rule_counter;
		rule_counter++;

		var holder = $('<div>', {class: 'menuitem fullwidth'}).appendTo('#pluginplaylists');
		var title = $('<div>', {class: "containerbox vertical-centre"}).appendTo(holder);
		title.append('<div class="svg-square fixed icon-wifi"></div>');
		title.append('<div class="expand drop-box-holder">'+params.name+'</div>');
		var editbutton = $('<button>', {class: 'fixed alignmid'}).html(language.gettext('label_edit')).appendTo(title);
		var playbutton = $('<button>', {class: 'fixed alignmid'}).html(language.gettext('button_playradio')).appendTo(title);

		var dropdown = $('<div>', {class: 'invisible indent'}).appendTo(holder);

		var combine_div = $('<div>', {class: 'containerbox vertical-centre'}).appendTo(dropdown);
		combine_div.append($('<div>', {class: 'fixed', style: 'margin-right: 1em'}).html(language.gettext('label_ruleoptions')));
		var combine_holder = $('<div>', {class: 'selectholder expand'}).appendTo(combine_div);
		var combine_selector = $('<select>').appendTo(combine_holder);
		$.each(radio_combine_options, function(i, v) {
			combine_selector.append($('<option>').attr('value', i).text(language.gettext(v)));
		});
		combine_selector.val(params.combine_option);

		var bum_div = $('<div>', {class: 'containerbox vertical-centre'}).appendTo(dropdown);
		bum_div.append($('<div>', {class: 'expand'}).html('Rules :'));

		var table = $('<table>').appendTo(dropdown);

		var arse_div = $('<div>', {class: 'containerbox vertical-centre'}).appendTo(dropdown);
		var add_button = $('<i>', {class: 'smallicon clickicon fixed icon-plus'}).appendTo(arse_div);
		arse_div.append($('<div>', {class: 'expand'}));
		if (params.name != language.gettext('label_createcustom')) {
			var delete_button = $('<i>', {class: 'smallicon clickicon fixed icon-cancel-circled'}).appendTo(arse_div);
		}
		var save_button = $('<i>', {class: 'smallicon clickicon fixed icon-floppy'}).appendTo(arse_div);

		var rules = new Array();

		function get_save_params(name) {
			var save_params = {
				name: name,
				combine_option: combine_selector.val(),
				rules: new Array()
			};
			for (let rule of rules) {
				if (rule.is_enabled()) {
					save_params.rules.push(rule.get_params());
				}
			}
			return save_params;
		}

		this.initialise = function() {
			params.rules.forEach(function(rule) {
				var r = new custom_radio_rule(table, rule);
				r.initialise();
				rules.push(r);
			});
			add_button.on(prefs.click_event, self.addRule);
			if (params.name != language.gettext('label_createcustom')) {
				delete_button.on(prefs.click_event, self.delete);
			}
			save_button.on(prefs.click_event, self.save);
			playbutton.on(prefs.click_event, self.play);
			editbutton.on(prefs.click_event, self.edit);
		}

		this.addRule = function() {
			var rule = cloneObject(default_rule);
			var r = new custom_radio_rule(table, rule);
			r.initialise();
			rules.push(r);
		}

		this.play = function() {
			self.save_to_backend(self.start_playing, params.name)
		}

		this.edit = function() {
			dropdown.slideToggle('fast');
		}

		this.save_to_backend = async function(callback, name) {
			var save_params = get_save_params(name);
			if (save_params.rules.length == 0) {
				infobar.notify(language.gettext('error_norules'));
				return;
			}
			debug.log('CUSTOMRADIO','Saving Station',save_params);
			try {
				var response = await fetch(
					'radios/api/savecustom.php',
					{
						signal: AbortSignal.timeout(5000),
						cache: 'no-store',
						method: 'POST',
						priority: 'high',
						body: JSON.stringify(save_params)
					}
				)
				if (response.ok) {
					callback.call();
				} else {
					var t = await response.text();
					msg = t ? t : response.statusText;
					throw new Error(msg);
				}
			} catch (err) {
				debug.error('CUSTOMRADIO', 'Failed to save station',err);
				infobar.error(language.gettext('label_general_error')+'<br>'+err);
			}
		}

		this.start_playing = function() {
			playlist.radioManager.load('starRadios', 'custom+'+params.name);
		}

		this.save = function(e) {
			var fnarkle = new popup({
				width: 400,
				title: language.gettext("button_createplaylist"),
				atmousepos: true,
				mousevent: e
			});
			var mywin = fnarkle.create();
			var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
			var e = $('<div>',{class: 'expand'}).appendTo(d);
			var i = $('<input>',{class: 'enter', id: 'gratuitous', type: 'text', size: '200'}).val(params.name).appendTo(e);
			var b = $('<button>',{class: 'fixed'}).appendTo(d);
			b.html(language.gettext('button_save'));
			fnarkle.useAsCloseButton(b, self.actually_save);
			fnarkle.open();
		}

		this.actually_save = function() {
			self.save_to_backend(customRadioManager.setup, $('#gratuitous').val());
			return true;
		}

		this.remove = function() {
			rules = new Array();
			holder.remove();
		}

		this.delete = function() {
			params.delete = 1;
			fetch(
				'radios/api/savecustom.php',
				{
					signal: AbortSignal.timeout(5000),
					cache: 'no-store',
					method: 'POST',
					priority: 'high',
					body: JSON.stringify(params)
				}
			);
			self.remove();
		}

	}

	function sort_stations(a, b) {
		if (a.name == language.gettext('label_createcustom')) {
			return 1;
		}
		if (b.name == language.gettext('label_createcustom')) {
			return -1;
		}
		var nameA = a.name.toUpperCase();
		var nameB = b.name.toUpperCase();
		if (nameA < nameB) {
		    return -1;
		}
		if (nameA > nameB) {
			return 1;
		}
		return 0;
	}

	return {
		setup: async function() {
			var sd = new Array();
			try {
				var response = await fetch(
					'radios/api/loadcustom.php',
					{
						signal: AbortSignal.timeout(5000),
						cache: 'no-store',
						method: 'GET',
						priority: 'low'
					}
				)
				if (response.ok) {
					sd = await response.json();
				} else {
					var t = await response.text();
					msg = t ? t : response.statusText;
					throw new Error(msg);
				}
			} catch (err) {
				debug.error('CUSTOMRADIO', 'Error loading stations', err);
			}
			// This code was written to sort the loaded stations and make sure the default one
			// comes last. Then I changed mym mind and I no lnger load the default one
			// but I didn't change this because I might change my mind again.
			sd.sort(sort_stations);
			for (let s of stations) {
				s.remove();
			}
			stations = new Array();
			var found_default = false;
			for (let station of sd) {
				if (station.name == language.gettext('label_createcustom')) {
					found_default = true;
				}
				var s = new custom_radio_station(station);
				s.initialise();
				stations.push(s);
			}
			if (!found_default) {
				var params = cloneObject(default_station);
				var s = new custom_radio_station(params);
				s.initialise();
				stations.push(s);
			}
		}

	}

}();

playlist.radioManager.register("customRadio", customRadioManager, null);
