var f4MediaTaxonomySelectizeFocus = '';

var f4MediaTaxonomySelectize = function(id, taxonomy) {
	var $selectize = jQuery(id);
	var options = [];

	if(typeof taxonomy.terms === 'object') {
		taxonomy.terms.forEach(function(term) {
			options.push({
				value: term.slug,
				text: term.name,
				parents: term.parents
			});
		});
	}

	$selectize.closest('tr').addClass('compat-field-selectize');

	$selectize.selectize({
		plugins: ['remove_button'],
		options: options,
		placeholder: taxonomy.labels.search,
		dropdownParent: 'body',
		preload: 'focus',
		create: function(input, callback) {
			jQuery.ajax({
				url: ajaxurl,
				cache: false,
				async: false,
				type: 'POST',
				data: {
					action: 'f4-media-taxonomies-add-term',
					taxonomy: taxonomy.slug,
					term_label: input
				},
				success: function(response) {
					try {
						f4MediaTaxonomy.taxonomies[taxonomy.slug].terms = response.all_terms;
					} catch(e) {};

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
				var label = (data.parents.length ? '<span class="parent-label">' + escape(data.parents.join(' / ')) + ' /</span> ' : '') + escape(data.text);

				return '<div>' + label + '</div>';
			},
			item: function(data, escape) {
				var isNewTerm = typeof data.parents === 'undefined';

				if(isNewTerm) {
					data.parents = [];
				}

				var sortStringArray = data.parents.slice(0);
				sortStringArray.push(data.text);
				var sort_string = sortStringArray.join('-').toLowerCase();
				var label = (data.parents.length ? '<span class="parent-label">' + escape(data.parents.join(' / ')) + ' /</span> ' : '') + escape(data.text);

				return '<div data-sort-string="' + escape(sort_string) + '">' + label + '</div>';
			}
		},
		onFocus: function() {
			f4MediaTaxonomySelectizeFocus = id;
		},
		onBlur: function() {
			f4MediaTaxonomySelectizeFocus = '';
		},
		onChange: function(value) {
			this.$dropdown.remove();
		},
		//closeAfterSelect: true,
		onItemAdd: function(value, $element) {
			$element.parent().children(':not(input)').sort(function(a, b) {
				var upA = jQuery(a).attr('data-sort-string');
				var upB = jQuery(b).attr('data-sort-string');
				return (upA < upB) ? -1 : (upA > upB) ? 1 : 0;
			}).removeClass('active').insertBefore($element.parent().children('input'));
		}
	});

	if(f4MediaTaxonomySelectizeFocus === id) {
		$selectize[0].selectize.focus();
	}
};
