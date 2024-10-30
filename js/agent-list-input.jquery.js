(function( $ ) {
	var options = {};

	var methods = {
		init: function(optionz) {
			options = $.extend({
				account: {},
				selector: '.agent-code-list',
				title: null
			}, optionz);
			if ( hasAdminAccountInfo() ) {
				refreshAgentList();
			} else {
				showMessage('<strong>Plugin not set up.</strong> Please go to the Conductrics Account tab under Settings > Conductrics Actions and provide your account details.');
			}
			if (options.title == null) {
				options.title = $(options.selector).attr('title') || '';
			}
			$(options.selector).on('hover', tb_position_shim);
		}
	};

	showMessage = function(msg) {
		console.log(msg);
		$(options.selector).parent().html('<font color="#AA3333">' + msg + '</font>');
	};

	hasAdminAccountInfo = function() {
		return options.account
			&& options.account.owner && options.account.owner.length > 5
			&& options.account.adminkey && options.account.adminkey.length > 5
			&& options.account.adminurl && options.account.adminurl.length > 5 && options.account.adminurl.indexOf('http') == 0
	}

	// Simple wrapper around $.ajax
	doAjax = function(url, type, data, callback) {
		$.ajax({
			url: url,
			type: type,
			dataType: 'json',
			data: data,
			success: callback,
			timeout: 10000,
			error: function(jqXHR, textStatus, errorThrown) { callback(null, textStatus, jqXHR) },
			xhrFields: {
				withCredentials:true
			}
		})
	}

	refreshAgentList = top.refreshAgentList = function(callback) {
		var url = getApiUrl(['list-agents']);
		var data = {apikey:options.account.adminkey};
		doAjax(url, 'GET', data, function(response, status) {
			if (response && response.data.agents) {
				var map = {}
				for (var i in response.data.agents) {
					var item = response.data.agents[i];
					map[item.code] = item;
				}

				$(options.selector).linklistinput('enable', {
					items : response.data.agents,
					title: options.title,
					itemLinkClass: 'thickbox',
					itemLinksFn: function(val) {
						var item = map[val];
						if (item == null) {
							return [];
						}
						return [
							{ href: thickboxUrl(getConsoleUrl([val, 'web-actions'], true, true), 900), label: 'Web Actions', class: 'thickbox thickbox-conductrics' },
							{ href: thickboxUrl(getConsoleUrl([val, 'reporting-testing'], true), 900), label: 'Report', class: 'thickbox thickbox-conductrics', title:'Quick Report' },
							{ href: getConsoleUrl([val, 'home']), label: 'Console', target: 'conductrics-console' }
						]
					},
					itemLabelFn: function(val) {
						var item = map[val] || {};
						var itemName = item.name || val;
						var itemStatus = item.status || 'deleted';
						var str = "<strong>" + itemName + "</strong> "
							+ "<span class='agent-status agent-status-"+itemStatus+"'>"+itemStatus+"</span>";
						return str;
					},
					optionLabelFn: function(item) {return item.name},
					optionValueFn: function(item) {return item.code},
					newItemHandlerFn: function() { newAgentWorkflow() },
					refresherFn: refreshAgentList,
					newItemLabel: '(add new agent)'
				})

				tb_position_shim()

				if (callback) callback();
			} else {
				showMessage('<strong>Could not retrieve your Conductrics agents.</strong> Please check the Conductrics Account info you provided when setting up this plugin, and check your Internet connection.');
			}
		});
	}

	newAgentWorkflow = function() {
		var url = thickboxUrl(getConsoleUrl(['-', 'agent-create'], true), 900);
		$('#newagentlink').remove();
		$('<a id="newagentlink" class="thickbox" href="'+url+'" title="Create Agent">').appendTo('body');
		$('#newagentlink').trigger('click'); // open iframe for creating new agent
	}


	thickboxUrl = function(url, width, height) {
		if (url.indexOf('?') == -1) {
			url = url + '?'
		}
		return url + '&amp;KeepThis=true&amp;TB_iframe=true&amp;width='+(width || 900)+'&amp;height='+(height || 550);
	}

	getApiUrl = function(paths) {
		var url = [options.account.baseurl, options.account.owner].concat(paths).join('/');
		return url;
	}

	getConsoleUrl = function(paths, embeddedstyle, withLocation) {
		var url = [options.account.adminurl, options.account.owner].concat(paths).join('/') + '?';
		if (embeddedstyle) {
			url += "&amp;embeddedstyle=true";
		}
		if (withLocation) {
			// if provided by WP, pass along the current page's permalink as the "location" param to Conductrics
			if (options.account.permalink && options.account.permalink.indexOf('http') == 0) {
				url += "&amp;location=" + escape(options.account.permalink);
			// if not, pass along the home_url (assuming it is passed to us by WP) as the "location" param to Conductrics
			} else if (options.account.home_url && options.account.home_url.indexOf('http') == 0) {
				url += "&amp;location=" + escape(options.account.home_url);
			}
		}
		return url;
	}

	function receiveMessage(event) {
		console.log(event.data.message)
		if (event.data.message == 'agent-created') {
			// A new agent was created, let's refresh the list of available agents
			// and also add the new agent to the list of selected agents
			$(options.selector).linklistinput('refresh', function() {
				if (event.data.code) {
					$(options.selector).linklistinput('add', event.data.code);
				} else {
					closeThickbox(); // user bailed
				}
			});
		}
		if (event.data.message == 'agent-changed') {
			refreshAgentList();
		}
		if (event.data.message == 'web-actions-saved') {
			// Web actions were saved, close modal for that if we have one
			closeThickbox();
			refreshAgentList();
		}
	}

	// Hack/workaround for bug in WordPress - http://core.trac.wordpress.org/ticket/17249
	// See also http://binarybonsai.com/blog/using-thickbox-in-the-wordpress-admin
	var tb_position_original = null;
	tb_position_shim = function() {
		if (tb_position_original == null) {
			tb_position_original = window.tb_position;
			window.tb_position = tb_position_wrapper;
		}
	}
	tb_position_wrapper = function() {
		var ours = $('#TB_window > iframe').attr('src').indexOf(options.account.adminurl) == 0;
		if (!ours) {
			return tb_position_original(arguments);
		};
		// This is from the original Thickbox at http://thickbox.net/thickbox-code/thickbox.js
		$("#TB_window").css({marginLeft: '-' + parseInt((TB_WIDTH / 2),10) + 'px', width: TB_WIDTH + 'px'});
		if ( !(jQuery.browser.msie && jQuery.browser.version < 7)) { // take away IE6
			$("#TB_window").css({marginTop: '-' + parseInt((TB_HEIGHT / 2),10) + 'px'});
		}
	}

	function closeThickbox() {
		$('#TB_closeWindowButton').trigger('click')
	}

	// Register plugin in its own namespace
	$.agentlistinput = $.fn.agentlistinput = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.agentlistinput' );
		}
	};

	$(document).ready(function() {
		// see http://stackoverflow.com/questions/3076414/ways-to-circumvent-the-same-origin-policy
		if (window.attachEvent) {
			window.attachEvent('onmessage', receiveMessage);
		} else if (window.addEventListener) {
			window.addEventListener("message", receiveMessage, false);
		}
		$(document).agentlistinput('init', {
			account: window.conductrics_wa_account
		});
	});

})( jQuery );
