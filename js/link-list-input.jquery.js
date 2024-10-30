(function( $ ) {
	var options;

	var methods = {
		enable : function(optionz) {
			var echoFn = function(val) {return val};

			options = $.extend({
				removeLabel: 'Remove',
				itemLabelFn: echoFn,
				optionLabelFn: echoFn,
				optionValueFn: echoFn,
				newItemHandlerFn: null,
				itemLinkFn: null,
				itemLinkClass: '',
				refresherFn: null,
				refreshLabel: 'refresh',
				newItemLabel: '(new item)',
				items: [],
				title: ''
			}, optionz);

			return this.each(function() {
				var $this = $(this);
				$this.each(function() {
					paintLinks(this);
				});
			});
		},

		refresh: function(callback) {
			refreshList(callback);
		},

		add: function(val) {
			el = this;
			addValue(el, val);
		}
	}

	refreshList = function(callback) {
		if (options.refresherFn) {
			options.refresherFn(callback);
		}
	}

	paintLinks = function(el) {
		var $el = $(el);
		var values = split($el.val());
		$el.parent().find('.link-list-container').remove();
		var refreshContent = options.refresherFn == null ? '' : '<a href="#" class="agent-list-refresh" title='+options.refreshLabel+'></a>';
		$('<div class="link-list-container"><p>'+options.title + refreshContent+'</p><div class="postbox"></div></div>').appendTo( $el.parent() );
		var container = $el.parent().find('.postbox');
		var listContainer = $('<ul></ul>').appendTo(container);
		for (var i in values) {
			var val = values[i]
			if (val == '') continue;
			$el.data('link-list-value', val);
			var itemContainer = $('<div class="reveal-on-hover misc-pub-section"></div>').appendTo(listContainer);
			itemContainer.append('<div class="link-list-item-label"><span>'+options.itemLabelFn(val)+'</span></div>');
			var linksContainer = $('<div class="link-list-item-links"><span class="revealed-on-hover"></span></div>').appendTo(itemContainer);
			if (options.itemLinksFn) {
				var links = options.itemLinksFn(val);
				for (var j in links) {
					linksContainer.append('<a href="'+links[j].href+'" class="'+(links[j].class || '') +'" target="'+(links[j].target || '_self')+'" title="'+(links[j].title || links[j].label)+'">'+links[j].label+'</a> | ');
				}
			}
			// link for user to remove item
			linksContainer.append('<a href="#" class="link-list-remove" data-link-list-value="'+val+'">'+options.removeLabel+'</a><br/>');
		}
		// Make drop-down for agents to add
		var content = [];
		content.push('<option value="" disabled selected>(agent to add)</option>'); // parameteritize
		/*if (options.refresherFn) {
			content.push('<option value="__refresh__">'+options.refreshLabel+'</option>');
		} */
		if (options.newItemHandlerFn) {
			content.push('<option value="__new__">'+options.newItemLabel+'</option>'); // parameteritize
		}
		content.push('<option value="" disabled />');
		for (var i in options.items) {
			var item = options.items[i];
			var val = options.optionValueFn(item);
			if (values.indexOf(val) == -1) {
				content.push('<option value="'+val+'">'+options.optionLabelFn(item)+'</option>');
			}
		}
		container.append('<div class="misc-pub-section">Add <select>'+content.join('')+'</select> ');
		$el.parent().find('.agent-list-refresh').click(function() {
			refreshList();
		});
		// When user clicks "remove" link for a value
		container.find('.link-list-remove').click(function() {
			var val = $(this).attr('data-link-list-value');
			var values = split($el.val());
			values.splice(values.indexOf(val), 1);
			$(el).val( join(values) );
			paintLinks(el);
		});
		container.find('select').change(function() {
			var values = split($el.val());
			var val = $(this).val()
			if (val == '__refresh__') {
				return refreshList();
			}
			if (val == '__new__') {
				return options.newItemHandlerFn()
			}
			addValue(el, val);
		});
	}
	split = function(str) {
		return str.split(',');
	}
	join = function(ar) {
		var result = []
		for (var i in ar) {
			if (ar[i] != '') result.push(ar[i]);
		}
		return result.join(',');
	}

	addValue = function(el, val) {
		var $el = $(el);
		var values = split($el.val());
		values.push(val);
		$(el).val( join(values) );
		paintLinks(el);
	}

	// Register plugin in its own namespace
	$.linklistinput = $.fn.linklistinput = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.linklistinput' );
		}
	};

})( jQuery );
