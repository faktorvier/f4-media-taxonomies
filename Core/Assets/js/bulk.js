(function($){
	if(typeof f4MediaTaxonomy === 'undefined' || f4MediaTaxonomy.taxonomies.length === 0) {
		return;
	}

	setTimeout(function() {
		// Bulk actions
		var $bulk = jQuery('[name="action"], [name="action2"]');

		if($bulk.length) {
			for(var mediaTaxonomyName in f4MediaTaxonomy.taxonomies) {
				var mediaTaxonomy = f4MediaTaxonomy.taxonomies[mediaTaxonomyName];
				var $taxonomy = jQuery('<optgroup label="' + mediaTaxonomy.labels.bulk_title + '"></optgroup');

				mediaTaxonomy.terms.forEach(function(mediaTerm) {
					var $term = jQuery($term);
					var indent = Array(mediaTerm.level).join('&nbsp;&nbsp;&nbsp;');

					$taxonomy.append('<option value="' + f4MediaTaxonomy.bulk_action_prefix + mediaTerm.term_id + '">' + indent + mediaTerm.name + '</option>');
				});

				$bulk.append($taxonomy);
			}
		}
	}, 1000);
})(jQuery);
