var customRadioManager = function() {

	var jsonNode = document.querySelector("script[name='custom_radio_items']");
	var jsonText = jsonNode.textContent;
	const custom_radio_items = JSON.parse(jsonText);

	jsonNode = document.querySelector("script[name='radio_combine_options']");
	jsonText = jsonNode.textContent;
	const radio_combine_options = JSON.parse(jsonText);

	var default_rule = {
		db_key: 'Nothing',
		option: 0,
		value: ''
	};

	var default_station = {
		name: 'Create Custom Radio Station',
		combine_option: ' OR ',
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
		var delete_icon = $('<i>', {class: 'playlisticon clickicon icon-cancel-circled'}).css('display', 'none').appendTo(delete_box);

		var enabled = false;
		var my_id = rule_counter;
		rule_counter++;

		function make_value_box(new_key) {
			switch (new_key) {
				case 'Artistname':
					var dropper = $('<div>', {class: "containerbox expand spacer dropdown-container"}).
						appendTo(value_box).makeTagMenu({
							textboxname: 'rule_entry_'+my_id,
							placeholder: 'Artist Name(s)',
							labelhtml: '',
							populatefunction: starRadios.populateArtistMenu,
							buttontext: null,
							buttonfunc: null
						});
						break;

				case 'Genre':
					var dropper = $('<div>', {class: "containerbox expand spacer dropdown-container"}).
						appendTo(value_box).makeTagMenu({
							textboxname: 'rule_entry_'+my_id,
							placeholder: 'Genre(s)',
							labelhtml: '',
							populatefunction: starRadios.populateGenreMenu,
							buttontext: null,
							buttonfunc: null
						});
						break;

				case 'Tagtable.Name':
					var dropper = $('<div>', {class: "containerbox expand spacer dropdown-container"}).
						appendTo(value_box).makeTagMenu({
							textboxname: 'rule_entry_'+my_id,
							placeholder: 'Tag(s)',
							labelhtml: '',
							populatefunction: tagAdder.populateTagMenu,
							buttontext: null,
							buttonfunc: null
						});
						break;

				default:
					value_box.append($('<input>', {type: 'number', name: 'rule_entry_'+my_id}));
					break;
			}
		}

		this.initialise = function() {
			var key_div = $('<div>', {class: 'selectholder'}).appendTo(key_box);
			key_selector = $('<select>').appendTo(key_div);
			key_selector.append($('<option>').attr('value', 'Nothing').text(language.gettext('label_choose_one')).prop('disabled', true));
			custom_radio_items.forEach(function(item) {
				key_selector.append($('<option>').attr('value', item.db_key).text(language.gettext(item.name)));
			});
			key_selector.on('change', self.key_changed);
			key_selector.val(params.db_key);
		},

		this.key_changed = function(event) {
			var new_key = key_selector.val();
			debug.log('CUSTOMRADIO', 'Key Changed To', new_key);
			option_box.empty();
			value_box.empty();
			delete_icon.off('click').css('display', 'none');
			if (new_key != 'Nothing') {
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
					option_selector.append($('<option>').attr('value', i).text(language.gettext(v)));
				});
				option_selector.on('change', self.option_changed);
				make_value_box(new_key);
				delete_icon.css('display', '').on('click', self.delete);
				table.css('width', '100%');
			}
		}

		this.option_changed = function() {
			if (option_selector.val() == 45) {
				value_box.empty();
				value_box.append($('<input>', {type: 'hidden', name: 'rule_entry_'+my_id}).val('dummy'));
			} else {
				if (value_box.children('input[type="hidden"]').length > 0) {
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
			return enabled;
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

		var holder = $('<div>', {class: 'menuitem fullwidth'}).appendTo('#pluginplaylists');
		var title = $('<div>', {class: "containerbox dropdown-container"}).appendTo(holder);
		title.append('<div class="svg-square fixed icon-wifi"></div>');
		title.append('<div class="expand dropdown-holder">'+params.name+'</div>');
		var playbutton = $('<button>', {class: 'fixed alignmid'}).html(language.gettext('button_playradio')).appendTo(title);

		var combine_div = $('<div>', {class: 'containerbox dropdown-container'}).appendTo(holder);
		combine_div.append($('<div>', {class: 'fixed', style: 'margin-right: 1em'}).html('Rule Options'));
		var combine_holder = $('<div>', {class: 'selectholder expand'}).appendTo(combine_div);
		var combine_selector = $('<select>').appendTo(combine_holder);
		$.each(radio_combine_options, function(i, v) {
			combine_selector.append($('<option>').attr('value', i).text(language.gettext(v)));
		});
		combine_selector.val(params.combine_option);

		var bum_div = $('<div>', {class: 'containerbox dropdown-container'}).appendTo(holder);
		bum_div.append($('<div>', {class: 'expand'}).html('Rules :'));

		var table = $('<table>').appendTo(holder);

		var arse_div = $('<div>', {class: 'containerbox dropdown-container'}).appendTo(holder);
		var add_button = $('<i>', {class: 'smallicon clickicon fixed icon-plus'}).appendTo(arse_div);
		arse_div.append($('<div>', {class: 'expand'}));

		var rules = new Array();

		this.initialise = function() {
			params.rules.forEach(function(rule) {
				var r = new custom_radio_rule(table, rule);
				r.initialise();
				rules.push(r);
			});
			add_button.on('click', self.addRule);
			playbutton.on('click', self.play);
		}

		this.addRule = function() {
			var rule = cloneObject(default_rule);
			var r = new custom_radio_rule(table, rule);
			r.initialise();
			rules.push(r);
		}

		this.play = async function() {
			var save_params = {
				name: params.name,
				combine_option: combine_selector.val(),
				rules: new Array()
			};
			for (let rule of rules) {
				if (rule.is_enabled()) {
					save_params.rules.push(rule.get_params());
				}
			}
			if (save_params.rules.length == 0) {
				infobar.notify('You must create some rules');
				return;
			}
			debug.log('CUSTOMRADIO','Playing Station',save_params);
			try {
				var s = await $.ajax({
					type: 'POST',
					url: 'radios/code/savecustom.php',
					data: JSON.stringify(save_params),
					contentType: false
				});
				playlist.radioManager.load('starRadios', 'custom+'+save_params.name);
			} catch (err) {
				debug.error('CUSTOMRADIO', 'Failed to play station',err);
				infobar.error(language.gettext('label_general_error'));
			}
		}

	}

	return {
		setup: function() {
			var params = cloneObject(default_station);
			var s = new custom_radio_station(params);
			s.initialise();
			stations.push(s);
		}
	}

}();

playlist.radioManager.register("customRadio", customRadioManager, null);
