<?php
	/*
	Plugin Name: Conductrics Web Actions
	Description: Includes Conductrics Web Actions scripts in your pages, which makes it easy to test changes to your pages, track their success, and do dynamic targeting.
	Version: 0.3.5
	Author: Conductrics
	Author URI: http://conductrics.com
	License: GPL2
	*/

	# include normal web-actions functionality (for when pages are viewed by public)
	require_once( dirname(__FILE__) .'/includes/wa-runtime.php');
	# include admin functionality (menu items for plugin settings, etc)
	if ( is_admin() ) {
		# included only when the current user is in the WP admin
		require_once( dirname(__FILE__) .'/includes/wa-settings.php');
	}
?>
