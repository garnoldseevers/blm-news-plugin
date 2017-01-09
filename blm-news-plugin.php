<?php
	/*
	Plugin Name: blooom - News Plugin
	Description: A custom plugin that displays quotes from news sources and links to the original article
	Version:     1.0
	Author:      blooom inc.
	Author URI:  https://blooom.com
	*/

	/** 
	Block direct access to plugin files
	*/
	
	defined( 'ABSPATH' ) or die( 'access denied: ABSPATH' );

	/**
	Create a table in the WordPress database to house news article data
	*/

	function create_news_articles_table() {
		/** access WordPress database */
		global $wpdb;

		/** set name for table */
	  	$table_name = $wpdb->prefix . "news_articles_table";

	  	/**
	  	use dbDelta to ensure table is created and updated correctly
	  	*/

	  	$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			author tinytext NOT NULL,
			contact tinytext NOT NULL,
			url varchar DEFAULT '' NOT NULL,
			image varchar DEFAULT '' NOT NULL,
			featured tinyint NOT NULL,
			order smallint NOT NULL,
			PRIMARY KEY  (id)
		) 
		$charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		/** 
		End dbDelta 
		*/
		
		/** Add version number to WP Database in case needed for later reference */
		add_option( "news_article_db_version", "1.0" );
	}
	
	/**
	Activation Hooks 
	*/

	/** Call create_news_articles_table function when plugin is activated */
	register_activation_hook( __FILE__, 'create_news_articles_table' );

	/**
	Admin Menu
	*/

	/** Call create_admin_page function in when in admin menu */
	add_action( 'admin_menu', 'create_admin_page' );

	/** Register Admin Menu in WordPress and call display_admin_page function */
	function create_admin_page() {
		add_menu_page( 'News Articles Options', 'News Articles', 'manage_options', 'news-articles-admin-page', 'display_admin_page'/*,icon_url */ );
		add_submenu_page( 'news-articles-admin-page', "Add New News Article", "Add New", 'manage_options', 'news-articles-add-page', 'display_add_new_page');
	}

	/** The code that displays the admin page */
	function display_admin_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<p>NEWS ARTICLE ENTRY FORM</p>';
		echo '</div>';
	}

	/** The code that displays the add new page */
	function display_add_new_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<p>NEWS ARTICLE ENTRY FORM</p>';
		echo '</div>';
	}

	/** 
	Shortcode 
	*/

	/** Register the shortcode with WordPress */
	add_shortcode( 'news_articles', 'news_articles_shortcode' );

	/** The Shortcode Function */
	function news_articles_shortcode() {

		/* use ob_start to convert HTML content to string */
		ob_start();
		?> 
			<p>
				News Articles
			</p>
		<?php
		/* display result of ob_start */
		return ob_get_clean();
	}

?>