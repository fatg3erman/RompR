/*

To use searchManager to add a plugin to global search you need to call
searchManager.add_search_plugin(cls, search_func, categories)

cls is the class that the php supplied to add_search_entry()

search_func is the function to call to perform the search for your plugin

categories is an array of 'search domains' that are relevant to your plugin -
eg Mopidy URI schemes. Multiple plugins can use the same categories. The UI
will create a checkbox for each registered category.

search() will call your search_func IF there are any terms in use that your plugin
is interested in (as defined by $terms in the call to add_search_entry()) and IF
any of the categories you registered are selected.

search_func is called with
terms - a list of {term_name: [terms]}
domains - a list of [category, category,... ]

*/

var searchManager = function() {

	var plugins = [];
	var category_choosers = {};
	// Create a div to hold our checkboxes, since some of the plugins create their icons before the UI has loaded
	var cat_holder = $('<div>', {id: 'searchdomains', class: 'styledinputs'});

	return {

		add_search_plugin: function(cls, search_func, categories) {
			debug.log('SEARCHMANAGER', 'Adding Plugin', cls, categories.join(','));
			plugins.push({class: cls, func: search_func, terms: {}});
			// The reversing and prepending here is really all by way of a hack to make Local come first,
			// followed by any other Mopidy backends, then Radio, then Podcasts. Playersearch registers last
			// because it has to look up the supported URI schemes first.
			let cats = categories.reverse();
			for (let cat of cats) {
				if (!category_choosers.hasOwnProperty(cat)) {
					let unique = Object.keys(category_choosers).length.toString();
					category_choosers[cat] = $('<input>', {type: 'checkbox', name: cat, class: 'topcheck searchcategory', id: 'search_cat_'+unique})
						.prop('checked', prefs.get_special_value('searchcat_'+cat, true)).prependTo(cat_holder);
					$('<label>', {for: 'search_cat_'+unique, class: 'oneline search-category'}).html(cat.capitalize()).insertAfter(category_choosers[cat]);
				}
				category_choosers[cat].addClass(cls);
			}
			searchManager.setup_categories();
		},

		save_categories: function() {
			var cat = $(this).prev().attr('name');
			prefs.set_special_value('searchcat_'+cat, !$(this).prev().is(':checked'));
		},

		setup_categories: function() {
			$('#searchcategories').append(cat_holder);
		},

		search: function() {
			$('.search_result_box').clearOut().empty();
			$('.search-section').remove();
			uiHelper.prepareSearch();
			plugins.forEach(function(plugin) {
				plugin.terms = {};
				$("#collectionsearcher").find('.searchterm.'+plugin.class).each( function() {
					if ($(this).val() != '') {
						plugin.terms[$(this).attr('name')] = $(this).val().split(',');
					}
				});
				// Search this plugin if we have terms for it....
				if (Object.keys(plugin.terms).length > 0) {
					let doit = true;
					// ... but not if we have terms that it doesn't support
					$("#collectionsearcher").find('.searchterm').not('.'+plugin.class).each(function() {
						if ($(this).val() != '')
							doit = false;
					});
					if (doit) {
						let domains = [];
						$('input.searchcategory.'+plugin.class+':checked').each(function() {
							domains.push($(this).attr('name'));
						});
						if (domains.length > 0) {
							debug.mark('SEARCHMANAGER', 'Searching with class', plugin.class, plugin.terms);
							plugin.func(plugin.terms, domains);
						}
					}
				}
			});
		},

		make_search_title: function(holder, title) {
			if (!$('#'+holder).is(':empty')) {
				$('<div class="configtitle vertical-centre search-section is-coverable">'
					+'<i class="smallicon icon-menu clickicon fixed openmenu" name="'+holder+'"></i>'
					+'<div class="textcentre expand"><b>'+title+'</b></div>'
					+'</div>'
				).insertBefore($('#'+holder));

			}
		}
	}

}();
