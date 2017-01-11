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
	/** give function access WordPress database */
	global $wpdb;

	/** set name for table */
  	$table_name = $wpdb->prefix . "news_articles_table";

  	/**
  	use dbDelta to ensure table is created and updated correctly
  	*/


$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  na_article_url varchar(55) DEFAULT '' NOT NULL,
  na_image_url varchar(55) DEFAULT '' NOT NULL,
  na_publication tinytext NOT NULL,
  na_title tinytext NOT NULL,
  na_blurb text NOT NULL,
  na_publish_date date DEFAULT NOT NULL,
  na_author tinytext NOT NULL,
  na_feature mediumint(9) NOT NULL,
  na_order mediumint(9) NOT NULL,
  PRIMARY KEY  (id)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
	
	/** 
	End dbDelta 
	*/
	
	/** Add version number to WP Database in case needed for later reference */
	add_option( "news_article_db_version", "1.0" );
	insert_data();
}
function insert_data(){
	/** give function access WordPress database */
	global $wpdb;
  	$table_name = $wpdb->prefix . "news_articles_table";
	$publication = "The Wall Street Journal";
	$author = "me";
	$wpdb->insert( 
		$table_name, 
		array(
			'na_publication' => $publication,
			'na_author' => $author,
		) 
	);
}
/**
Activation Hooks - occur when plugin is activated
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
	?>
	<div class="wrap">
	<p>NEWS ARTICLE ENTRY FORM</p>
	</div>
	<?php
}

/** 
Shortcode 
*/

/** Register the shortcode with WordPress */
add_shortcode( 'news_articles', 'news_articles_shortcode' );

/** The Shortcode Function */
function news_articles_shortcode() {
	global $wpdb;
  	$table_name = $wpdb->prefix . "news_articles_table";
	$news_articles = $wpdb->get_results( 
		"
		SELECT * 
		FROM $table_name
		ORDER BY na_order, na_publish_date
		"
	);

	/* use ob_start to convert HTML content to string */
	ob_start();
	?> 
	<p>
		News Articles
	</p>
	<h2>Featured</h2>
	<?php
	foreach ( $news_articles as $news_article ){
		if($news_article->na_article_url != "" && $news_article->na_feature == 1){
		?>
			<article class="news-article featured" itemscope itemtype="http://schema.org/Article">
				<a href="<?php echo $news_article->na_article_url; ?>" itemprop="url">
					<?php if($news_article->na_image_url != ""){
						?>
						<img src="<?php echo $news_article->na_image_url; ?>" alt="<?php echo $news_article->na_publication; ?>" itemprop="image"/>
						<?php 
					}else{
						?>
						<span class="news-article-publication" itemprop="publisher">
							<?php echo $news_article->na_publication; ?>
						</span>
						<?php
					}
					?>
					<span class="news-article-publish-date" itemprop="datePublished">
						<?php echo $news_article->na_publish_date; ?>
					</span>
					<span class="news-article-title" itemprop="headline">
						<?php echo $news_article->na_title; ?>
					</span>
					<span class="news-article-author" itemprop="author">
						<?php echo $news_article->na_author; ?>
					</span>
				</a>
			</article>
			<?php
		}
	}
	?>
	<h2>
		Everything
	</h2>
	<?php
	foreach ( $news_articles as $news_article ){
		if($news_article->na_article_url != ""){
		?>
			<article class="news-article featured" itemscope itemtype="http://schema.org/Article">
				<a href="<?php echo $news_article->na_article_url; ?>" itemprop="url">
					<?php if($news_article->na_image_url != ""){
						?>
						<img src="<?php echo $news_article->na_image_url; ?>" alt="<?php echo $news_article->na_publication; ?>" itemprop="image"/>
						<?php 
					}else{
						?>
						<span class="news-article-publication" itemprop="publisher">
							<?php echo $news_article->na_publication; ?>
						</span>
						<?php
					}
					?>
					<span class="publication-date" itemprop="datePublished">
						<?php echo $news_article->na_publish_date; ?>
					</span>
					<span class="news-article-title" itemprop="headline">
						<?php echo $news_article->na_title; ?>
					</span>
					<span class="news-article-author" itemprop="author">
						<?php echo $news_article->na_author; ?>
					</span>
				</a>
			</article>
			<?php
		}
	}
	/* display result of ob_start */
	return ob_get_clean();
}

/**
Deactivation Hooks - occur when plugin is deactivated
*/

/**
Uninstall Hooks - occur when plugin is deactivated
*/
register_uninstall_hook( __FILE__, 'uninstall_news_articles' );

function uninstall_news_articles(){
	/*
	Drop $prefix>news_articles table
	*/
}

function dev_alert($message){
	?>
	<script type="text/javascript">
		alert("<?php echo $message; ?>");
	</script>
	<?php
}

?>