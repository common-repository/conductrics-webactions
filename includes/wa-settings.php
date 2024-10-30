<?php
	class conductrics_wa_settings {
		public function __construct() {
			if(is_admin()) {
				add_action( 'admin_menu', array($this, 'add_page') );
				add_action( 'admin_init', array($this, 'page_init') );
				add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts') );

				add_filter(  'plugin_action_links_conductrics-webactions/conductrics-webactions.php', array($this, 'add_action_link') );

				# Meta boxes
				# Display meta box in admin right rail
				add_action( 'add_meta_boxes', array($this, 'wa_meta_box_add') );
				# Persist meta value when post saved
				add_action( 'save_post', array($this, 'wa_meta_box_save') );
			}
		}

		// Add settings link on plugin page
		function add_action_link( $links ) {
			$settings_link = '<a href="options-general.php?page=conductrics-wa-settings">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		# callback - fires when it's time to add meta boxes to wp-admin page
		function wa_meta_box_add( $post ) {
			$agent_code = get_post_meta( $post->ID, '_wa_agent_code', true );
			$screens = array('post', 'page'); # what type of posts/pages the meta box should show on in wp-admin
			foreach ( $screens as $screen ) {
				add_meta_box(
					'conductrics-wa-meta', # html id for the meta box
					'Conductrics Web Actions', # title
					array($this, 'wa_meta_box_show'), # callback
					$screen,
					'side', # show in right margin in wp-admin
					'core' # priority
				);
			}
		}

		# callback - fires when it's time to compose the html our meta box
		function wa_meta_box_show( $post ) {
			$agent_code = get_post_meta( $post->ID, '_wa_agent_code', true );
			$admin_page = "options-general.php?page=conductrics-wa-settings";
			echo "<div><input type='hidden' name='wa_agent_code' value='$agent_code' class='agent-code-list' title='Agents to use on this page:' title='Agents to use on this page:'/></div>";
			echo "See also: <a href='$admin_page'>Agents to Use on Every Page</a>";
		}

		# callback - fires when it's time to save the post (and thus our meta value)
		function wa_meta_box_save( $post_id ) {
			if ( isset( $_POST['wa_agent_code'] ) ) { # was our form field posted?
				update_post_meta(
					$post_id,
					'_wa_agent_code',
					strip_tags($_POST['wa_agent_code'])
				);
			}
		}

		function add_page() {
			add_options_page(
				'Conductrics Web Actions',
				'Conductrics Actions',
				'manage_options',
				'conductrics-wa-settings',
				array($this, 'options_page')
			);
		}

		function options_page() {
			$account_options = $this->admin_get_options(true);
			$console_url = $account_options['adminurl'] . "/" . $account_options['owner'] . "/agent-list";

			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'a';
			$section = 'conductrics-wa-settings-' . $current_tab;
			$active_a = ($current_tab === 'a') ? "nav-tab-active" : "";
			$active_b = ($current_tab === 'b') ? "nav-tab-active" : "";

			?>
				<div class="wrap">
					<?php screen_icon(); ?>
					<h2 class="nav-tab-wrapper">
						<a id="tab-a" href="?page=conductrics-wa-settings&amp;tab=a" class="nav-tab <?php echo $active_a ?>">Global Web Actions</a>
						<a id="tab-b" href="?page=conductrics-wa-settings&amp;tab=b" class="nav-tab <?php echo $active_b ?>">Conductrics Account</a>
					</h2>
					<form method="post" action="options.php">
						<?php
							settings_fields('account'); // print out all hidden setting fields
							do_settings_sections($section);
						?>
						<?php submit_button(); ?>
					</form>
				</div>
			<?php
			if ($current_tab === 'a' && !empty($account_options['owner']) ) {
				echo 'You can also visit the <a href="'.$console_url.'" target="conductrics-console">Conductrics Console</a> to enable targeting, additional reporting, and other account options.';
			}
		}

		function admin_scripts() {
			wp_enqueue_script(
				'conductrics-jquery-link-list',
				plugins_url('js/link-list-input.jquery.js', dirname(__FILE__)),
				array('jquery'), # WP will make sure jQuery is included for us
				false, # version
				true # we want to be placed in the footer
			);
			wp_enqueue_script(
				'conductrics-jquery-agent-list',
				plugins_url('js/agent-list-input.jquery.js', dirname(__FILE__)),
				array('jquery'),
				false, # version
				true # we want to be placed in the footer
			);
			wp_localize_script(
				'conductrics-jquery-agent-list',
				'conductrics_wa_account',
				array_merge(array(), $this->admin_get_options(true), array( "permalink" => get_permalink(), "home_url" => home_url() ))
			);
			wp_enqueue_style(
				'wa-settings',
				plugins_url('css/wa-settings.css', dirname(__FILE__))
			);
			add_thickbox();
		}

		function page_init() {
			register_setting('account', 'conductrics_options', array($this, 'admin_check_options'));

			# Section: Web Actions
			add_settings_section(
			    'conductrics_wa_globalagent',
			    'Web Actions',
			    array($this, 'global_section_info'),
			    'conductrics-wa-settings-a'
			);
			# globalagent
			add_settings_field(
				'conductrics_globalagent',
				'Global Agents',
				array($this, 'create_input_globalagent'),
				'conductrics-wa-settings-a',
				'conductrics_wa_globalagent'
			);
			# wa_global_enabled
			add_settings_field(
				'conductrics_wa_global_enabled',
				'Global Enable/Disable',
				array($this, 'create_input_wa_global_enabled'),
				'conductrics-wa-settings-a',
				'conductrics_wa_globalagent'
			);
			add_settings_field(
				'conductrics_wa_rocketscript_workaround',
				'Rocketscript',
				array($this, 'create_input_wa_rocketscript_workaround'),
				'conductrics-wa-settings-a',
				'conductrics_wa_globalagent'
			);
			# section: Account
			add_settings_section(
			    'conductrics_wa_account',
			    'Account Settings',
			    array($this, 'account_section_info'),
			    'conductrics-wa-settings-b'
			);
			# owner
			add_settings_field(
				'conductrics_owner',
				'Account Owner Code',
				array($this, 'create_input_owner'),
				'conductrics-wa-settings-b',
				'conductrics_wa_account'
			);
			# apikey
			add_settings_field(
				'conductrics_apikey',
				'Runtime API Key',
				array($this, 'create_input_apikey'),
				'conductrics-wa-settings-b',
				'conductrics_wa_account'
			);
			# adminkey
			add_settings_field(
				'conductrics_adminkey',
				'Admin API Key',
				array($this, 'create_input_adminkey'),
				'conductrics-wa-settings-b',
				'conductrics_wa_account'
			);

			# Section: Advanced
			add_settings_section(
			    'conductrics_wa_advanced',
			    'Advanced Settings',
			    array($this, 'global_section_advanced'),
			    'conductrics-wa-settings-b'
			);
			# baseurl
			add_settings_field(
				'conductrics_baseurl',
				'API Server',
				array($this, 'create_input_baseurl'),
				'conductrics-wa-settings-b',
				'conductrics_wa_advanced'
			);
			# adminurl
			add_settings_field(
				'conductrics_adminurl',
				'Console URL',
				array($this, 'create_input_adminurl'),
				'conductrics-wa-settings-b',
				'conductrics_wa_advanced'
			);
		}

		function admin_check_options($input) {
			$account_options = $this->admin_get_options(false);
			if ( isset( $input['owner'] )) {
				$account_options['owner'] = $this->sanitize_code( $input['owner'] );
				$account_options['apikey'] = $this->sanitize_code( $input['apikey'] );
				$account_options['adminkey'] = $this->sanitize_code( $input['adminkey'] );
				$account_options['baseurl'] = esc_url_raw($input['baseurl'], array('http', 'https'));
				$account_options['adminurl'] = esc_url_raw($input['adminurl'], array('http', 'https'));
			} else {
				$account_options['wa_global_enabled'] = $input['wa_global_enabled'];
				$account_options['wa_rocketscript_workaround'] = $input['wa_rocketscript_workaround'];
				$account_options['globalagent']	= $this->sanitize_codes( $input['globalagent'] );
			}
			update_option('conductrics_wa_account', $account_options);
		}

	    function create_input_owner($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['owner'];
	        ?><input type="text" id="conductrics_owner" name="conductrics_options[owner]" value="<?php echo $value; ?>" placeholder="your owner code" class='regular-text'/> <em>(required, starts with "owner")</em><?php
	    }
	    function create_input_apikey($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['apikey'];
	        ?><input type="text" id="conductrics_apikey" name="conductrics_options[apikey]" value="<?php echo $value; ?>" placeholder="your API key" class='regular-text'/> <em>(required, starts with "api")</em><?php
	    }
	    function create_input_adminkey($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['adminkey'];
	        ?><input type="text" id="conductrics_adminkey" name="conductrics_options[adminkey]" value="<?php echo $value; ?>" placeholder="your API key" class='regular-text'/> <em>(required, starts with "admin")</em><?php
	    }
	    function create_input_baseurl($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['baseurl'];
	        ?><input type="text" id="conductrics_baseurl" name="conductrics_options[baseurl]" value="<?php echo $value; ?>" placeholder="http://api.conductrics.com" class='regular-text'/> <em>(optional)</em><?php
	    }
	    function create_input_adminurl($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['adminurl'];
	        ?><input type="text" id="conductrics_adminurl" name="conductrics_options[adminurl]" value="<?php echo $value; ?>" placeholder="http://console.conductrics.com" class='regular-text'/> <em>(optional)</em><?php
	    }
	    function create_input_globalagent($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['globalagent'];
	        ?><input type="hidden" id="conductrics_globalagent" name="conductrics_options[globalagent]" value="<?php echo $value; ?>" placeholder="agent code(s)" class='regular-text agent-code-list' title='Agents to use on every page:'/> <?php
	        /* ?><div><em>Use commas for multiple agents, like so:</em> agent-1,agent-2</div><?php */
	    }
	    function create_input_wa_global_enabled($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['wa_global_enabled'];
	        ?><label><input type="checkbox" id="conductrics_wa_global_enabled" name="conductrics_options[wa_global_enabled]" <?php checked( $value == 1 ); ?> value="1" /> Enable Web Actions</label><?php
	        ?><div><em>Uncheck to disable all Web Actions, even if set on individual pages.</em></div><?php
	    }
	    function create_input_wa_rocketscript_workaround($args) {
	    	$account_options = $this->admin_get_options(false);
	    	$value = $account_options['wa_rocketscript_workaround'];
	        ?><label><input type="checkbox" id="conductrics_wa_rocketscript_workaround" name="conductrics_options[wa_rocketscript_workaround]" <?php checked( $value == 1 ); ?> value="1" /> Enable Rocketscript Workaround</label><?php
	        ?><div><em>Check if you are using Rocketscript.</em></div><?php
	    }

		function admin_get_options($apply_defaults) {
			$options = get_option('conductrics_wa_account');
			if ($apply_defaults) {
				$options['baseurl'] = empty($options['baseurl']) ? 'http://api.conductrics.com' : $options['baseurl'];
				$options['adminurl'] = empty($options['adminurl']) ? 'http://console.conductrics.com' : $options['adminurl'];
			}
			return $options;
		}
		function account_section_info() {
			print '<p>You can find this information in the Conductrics admin console, under Account > Keys and Password.</p>';
	    }
		function global_section_info() {
			print "<p>If you have have a Web Actions agent that should be included on every page, enter it here.</p>";
	    }
		function global_section_advanced() {
			print "<p>If you've been given specific settings from Conductrics, please provide them here. Just leave them blank otherwise.</p>";
	    }
	    function sanitize_code($str) {
	    	return preg_replace('/[^A-Za-z0-9\-\_]/', '', $str);
	    }
	    function sanitize_codes($str) { # same as above, but allows commas
	    	return preg_replace('/[^A-Za-z0-9\-\_\,]/', '', $str);
	    }
	}

	new conductrics_wa_settings();
?>
