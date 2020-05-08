(function($){
	if(typeof f4MediaTaxonomy === 'undefined' || f4MediaTaxonomy.taxonomies.length === 0) {
		return;
	}

	// Overlay and grid filters
	if(typeof wp !== 'undefined' && typeof wp.media !== 'undefined') {
		var media = wp.media;

		if(typeof media.view.AttachmentFilters === 'undefined') {
			return;
		}

		var attachmentsBrowser = media.view.AttachmentsBrowser;

		var attachmentFilter = media.view.AttachmentFilters.extend({
			createFilters: function() {
				var filters = {};
				var that = this;

				_.each(that.options.taxonomy.terms, function(mediaTerm, index) {
					var indent = Array(mediaTerm.level).join('&nbsp;&nbsp;&nbsp;');

					filters[index] = {
						text: indent + mediaTerm.name,
						props: {}
					};

					filters[index].props[that.options.taxonomy.query_var] = mediaTerm.slug;
				});

				filters.all = {
					text:  that.options.taxonomy.labels.all_items,
					props: {},
					priority: 10
				};

				filters.all.props[that.options.taxonomy.query_var] = '';

				this.filters = filters;
			}
		});

		media.view.AttachmentsBrowser = media.view.AttachmentsBrowser.extend({
			createToolbar: function() {
				var that = this;

				attachmentsBrowser.prototype.createToolbar.call(that);

				for(var mediaTaxonomyName in f4MediaTaxonomy.taxonomies) {
					var mediaTaxonomy = f4MediaTaxonomy.taxonomies[mediaTaxonomyName];

					that.toolbar.set('f4-media-taxonomy-' + mediaTaxonomy.slug + '-label', new media.view.Label({
						value: mediaTaxonomy.labels.singular,
						attributes: {
							for: 'f4-media-taxonomy-' + mediaTaxonomy.slug + '-filter'
						},
						priority: (-75)
					}).render());

					that.toolbar.set('f4-media-taxonomy-' + mediaTaxonomy.slug + '-filter', new attachmentFilter({
						controller: that.controller,
						model: that.collection.props,
						priority: (-75),
						taxonomy: mediaTaxonomy,
						id: 'f4-media-taxonomy-' + mediaTaxonomy.slug + '-filter',
						className: 'f4-media-taxonomy-filter attachment-filters'
					}).render());
				}
			}
		});
	}
})(jQuery);
