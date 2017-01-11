<?php
  	$table_name = $wpdb->prefix . "news_articles_table";
    if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
?>