var f4MediaTaxonomySelectizeFocus = '';

Selectize.define('silent_remove', function(options){
    var self = this;

    // defang the internal search method when remove has been clicked
    this.on('item_remove', function(){
        this.plugin_silent_remove_in_remove = true;
    });

    this.search = (function() {
        var original = self.search;
        return function() {
            if (typeof(this.plugin_silent_remove_in_remove) != "undefined") {
                // re-enable normal searching
                delete this.plugin_silent_remove_in_remove;
                return {
                        items: {},
                        query: [],
                        tokens: []
                    };
            }
            else {
                return original.apply(this, arguments);
            }
        };
    })();
});

var f4MediaTaxonomySelectize = function(id, taxonomy) {
	var $selectize = jQuery(id);

	$selectize.closest('tr').addClass('compat-field-selectize');

	$selectize.selectize({
		plugins: ['remove_button', 'silent_remove'],
		placeholder: taxonomy.labels.search,
		dropdownParent: null,
		preload: 'focus',
		closeAfterSelect: true,
		load: function(query, callback) {
			if(!query.length) {
				//this.addOption({'text': taxonomy.labels.search_hint, 'value': 'f4-media-searchhint', 'searchhint': true, 'disabled': true});
				//this.refreshOptions(false);
				return callback();
			}

			this.removeOption('f4-media-searchhint');
			this.refreshOptions(false);

			jQuery.ajax({
				url: ajaxurl,
				cache: false,
				data: {
					action: 'f4-media-taxonomies-search-terms',
					taxonomy: taxonomy.slug,
					query: query
				},
				success: function(response) {
					callback(response.data);
				}
			});
		},
		create: function(input, callback) {
			jQuery.ajax({
				url: ajaxurl,
				cache: false,
				data: {
					action: 'f4-media-taxonomies-add-term',
					taxonomy: taxonomy.slug,
					term_label: input
				},
				success: function(response) {
					if(typeof response.new_term !== 'undefined') {
						callback({
							value: response.new_term.slug,
							text: response.new_term.name
						});
					} else {
						callback(false);
					}
				}
			});
		},
		render: {
			option_create: function(data, escape) {
				return '<div class="create">' + taxonomy.labels.add + ': <strong>' + escape(data.input) + '</strong></div>';
			},
			option: function(data, escape) {
				var label = (typeof data.parents !== 'undefined' && data.parents.length ? '<span class="parent-label">' + escape(data.parents.join(' / ')) + ' /</span> ' : '') + escape(data.text);

				if(typeof data.searchhint === 'undefined') {
					return '<div>' + label + '</div>';
				} else {
					return '<div class="searchhint">' + label + '</div>';
				}
			},
			item: function(data, escape) {
				var isNewTerm = typeof data.parents === 'undefined';

				if(isNewTerm) {
					data.parents = [];

					let selectedItems = JSON.parse(this.$input.attr('data-selected-items'));
					let termSlug = data.text;

					if(typeof selectedItems[termSlug] !== 'undefined') {
						data.text = selectedItems[termSlug].name;
						data.parents = selectedItems[termSlug].parents || [];
					}
				}

				var sortStringArray = data.parents.slice(0);
				sortStringArray.push(data.text);
				var sort_string = sortStringArray.join('-').toLowerCase();
				var label = (data.parents.length ? '<span class="parent-label">' + escape(data.parents.join(' / ')) + ' /</span> ' : '') + escape(data.text);

				return '<div data-sort-string="' + escape(sort_string) + '">' + label + '</div>';
			}
		},
		onFocus: function() {
			let items = [];

			if(this.currentResults) {
				items = this.currentResults.items;
			}

			if(!items.length) {
				this.addOption({'text': taxonomy.labels.search_hint, 'value': 'f4-media-searchhint', 'searchhint': true, 'disabled': true});
			}

			f4MediaTaxonomySelectizeFocus = id;
		},
		onItemRemove: function() {
			f4MediaTaxonomySelectizeFocus = '';
		},
		onBlur: function() {
			this.removeOption('f4-media-searchhint');
			this.refreshOptions(false);
			f4MediaTaxonomySelectizeFocus = '';
		},
		onItemAdd: function(value, $element) {
			$element.parent().children(':not(input)').sort(function(a, b) {
				var upA = jQuery(a).attr('data-sort-string');
				var upB = jQuery(b).attr('data-sort-string');

				return upA.localeCompare(upB, undefined, {
					numeric: true,
					sensitivity: 'base'
				});
			}).removeClass('active').insertBefore($element.parent().children('input'));
		}
	});

	if(f4MediaTaxonomySelectizeFocus === id) {
		$selectize[0].selectize.focus();
	}
};
