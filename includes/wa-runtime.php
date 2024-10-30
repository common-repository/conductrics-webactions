<?php
	class conductrics_wa_runtime {
		public function __construct() {
			$account_options = get_option('conductrics_wa_account');
			add_action("template_redirect", array($this, "include_script"));
			if ($account_options['wa_rocketscript_workaround'] == 1) {
				add_action("wp_print_scripts", array($this, "include_scripts_norocketscript"));
			}
		}

		/**
		* Workaround for Rocketscript (thanks to http://snippets.webaware.com.au/snippets/stop-cloudflare-rocketscript-breaking-wordpress-plugin-scripts/)
		*/
		function include_scripts_norocketscript() {
			global $wp_scripts;
			if (wp_script_is('conductrics-wa-script')) {
				// Manually include jQuery prerequisite first
				$script = $wp_scripts->query('jquery');
				if ($script) {
					$wp_scripts->print_scripts($script);
				}
				wp_dequeue_script('conductrics-wa-script');
				$this->enqueue_without_rocketscript('conductrics-wa-script');
			}
		}

		function enqueue_without_rocketscript($handle) {
			global $wp_scripts;

			$script = $wp_scripts->query($handle);
			$src = $script->src;
			if (!empty($script->ver)) {
				$src = add_query_arg('ver', $script->ver, $src);
			}
			$src = esc_url(apply_filters('script_loader_src', $src, $handle));
			echo "<script data-cfasync='false' type='text/javascript' src='$src'></script>\n";
		}

		function include_script( $post ) {
			$account_options = get_option('conductrics_wa_account');
			if ( empty($account_options) || $account_options['wa_global_enabled'] != 1 ) {
				return;
			}
			$agents = $this->get_relevant_agent_codes( $account_options );
			$agentCodes = implode(",", $agents);
			$this->include_script_for_agents( $agentCodes, $account_options );
		}

		function include_script_for_agents( $agentCodes, $account_options ) {
			# Bail early if required info missing
			if ( empty($agentCodes) || empty($account_options) ) {
				return;
			}
			$baseurl = $account_options['baseurl'];
			if ($baseurl === FALSE || $baseurl === '') {
				$baseurl = "http://api.conductrics.com";
			}
			$owner = urlencode($account_options['owner']);
			$apikey = urlencode($account_options['apikey']);
			$agentCodes = urlencode($agentCodes);
			$wa_script_url = "$baseurl/$owner/-/web-actions?apikey=$apikey&decisions-for-agents=$agentCodes";
			// Include the script for this web action
			wp_enqueue_script('jquery', false, array(), false, false);
			wp_enqueue_script(
				'conductrics-wa-script',
				$wa_script_url,
				array(),
				false, # version could be specified here
				false # we don't want to be placed in the footer
			);
		}

		function get_relevant_agent_codes( $account_options ) {
			# global agents
			# TODO - replace this concept with a widget that wp-admin user can place in sidebar?
			$globalAgents = explode( ",", $account_options['globalagent'] );
			# agents for this page/post
			$postAgents = explode( ",", get_post_meta( get_the_id(), '_wa_agent_code', true ) );
			return array_unique( array_merge( $globalAgents, $postAgents ) );
		}
	}

	new conductrics_wa_runtime();
?>
