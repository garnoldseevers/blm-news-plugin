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
  na_article_url text NOT NULL,
  na_image_url text NOT NULL,
  na_publication tinytext NOT NULL,
  na_title tinytext NOT NULL,
  na_publish_date date NOT NULL,
  na_author tinytext NOT NULL,
  na_blurb text NOT NULL,
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
	//insert_data();
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
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">News Articles</h1>
		<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-add-page" class="page-title-action">Add New</a>
		<?php
		global $wpdb;
	  	$table_name = $wpdb->prefix . "news_articles_table";
		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		$limit = 20; // number of rows in page
		$offset = ( $pagenum - 1 ) * $limit;
		$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM {$wpdb->prefix}news_articles_table" );
		$num_of_pages = ceil( $total / $limit );
		$entries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}news_articles_table ORDER BY na_feature DESC, na_order ASC, na_publish_date DESC LIMIT $offset, $limit" );
		?>
		<table class="wp-list-table widefat fixed striped pages">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-title column-primary">Title</th>
					<th scope="col" class="manage-column" style="width: 20%">Publication</th>
					<th scope="col" class="manage-column date column-date">Published</th>
					<th scope="col" class="manage-column" style="width: 10%">Featured</th>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php
				foreach($entries as $entry){
					?>
					<tr>
						<td class="iedit hentry title has-row-actions column-title column-primary page-title">
							<?php echo $entry->na_title; ?>
							<!--<div class="row-actions">
								<span class="edit">
									<a href="">Edit</a> | 
								<span class="trash">
									<a href="">Delete</a>
								</span>
							</div>-->
						</td>
						<td class="hentry" style="width: 20%;">
							<?php echo $entry->na_publication; ?>
						</td>
						<td class="hentry date column-date">
							<?php echo $entry->na_publish_date; ?>
						</td>
						<td class="hentry" style="width: 10%; text-align: center;">
							<?php 
								if($entry->na_feature == 1){
									echo "yes";
								}else{
									echo "no";
								} 
							?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
			<tfoot>
				<th scope="col" class="manage-column column-title column-primary">Title</th>
					<th scope="col" class="manage-column" style="width: 20%">Publication</th>
				<th scope="col" class="manage-column">Published</th>
					<th scope="col" class="manage-column" style="width: 10%">Featured</th>
			</tfoot>
			<?php	
			$page_links = paginate_links( array(
			    'base' => add_query_arg( 'pagenum', '%#%' ),
			    'format' => '',
			    'prev_text' => __( '&laquo;', 'text-domain' ),
			    'next_text' => __( '&raquo;', 'text-domain' ),
			    'total' => $num_of_pages,
			    'current' => $pagenum
			) );

			if ( $page_links ) {
			    echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
			}
			?>
		</table>
	</div>
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
	$blm_news_articles_parameter = filter_input(INPUT_GET, 'blm_news_articles');
	if($blm_news_articles_parameter == "all"){
		display_all_articles();
	}else if($blm_news_articles_parameter == "both"){
		display_featured_articles();
		display_all_articles();
	}else{
		display_featured_articles();
	}
}


function display_featured_articles(){ 
	?> 
	<h2>Featured</h2>
	<?php
	global $wpdb;
  	$table_name = $wpdb->prefix . "news_articles_table";
	$news_articles = $wpdb->get_results( 
		"
		SELECT * 
		FROM $table_name
		ORDER BY na_order ASC, na_publish_date DESC
		"
	);
	foreach ( $news_articles as $news_article ){
		if($news_article->na_article_url != "" && $news_article->na_feature == 1){
		?>
			<article class="news-article featured" itemscope itemtype="http://schema.org/Article">
				<a href="<?php echo $news_article->na_article_url; ?>" class="clearfix" itemprop="url">
					<?php if($news_article->na_image_url != ""){
						?>
						<img src="<?php echo $news_article->na_image_url; ?>" class="publication" alt="<?php echo $news_article->na_publication; ?>" itemprop="image"/>
						<?php 
					}else{
						?>
						<span class="publication" itemprop="publisher">
							<?php echo $news_article->na_publication; ?>
						</span>
						<?php
					}
					?>
					<span class="news-article-content">
						<span class="publish-date" itemprop="datePublished">
							<?php 
								$article_date = date_create($news_article->na_publish_date);
								echo date_format($article_date,"F j, Y"); 
							?>
						</span>
						<span class="title" itemprop="headline">
							<?php echo $news_article->na_title; ?>
						</span>
						<?php if($news_article->na_author != ""){
							?>
							<span class="author" itemprop="author">
								<?php echo "by: ".$news_article->na_author; ?>
							</span>
							<?php
						}
						?>
					</span>
				</a>
			</article>
			<?php
		}
	}
}


function display_all_articles(){
	/* use ob_start to convert HTML content to string */
	?> 
	<h2>
		Everything
	</h2>
	<?php
	global $wpdb;
  	$table_name = $wpdb->prefix . "news_articles_table";
	$news_articles = $wpdb->get_results( 
		"
		SELECT * 
		FROM $table_name
		ORDER BY na_order ASC, na_publish_date DESC
		"
	);
	foreach ( $news_articles as $news_article ){
		if($news_article->na_article_url != ""){
		?>
			<article class="news-article" itemscope itemtype="http://schema.org/Article">
				<a href="<?php echo $news_article->na_article_url; ?>" itemprop="url">
					<span class="title" itemprop="headline">
						<?php 
							$news_article_title = $news_article->na_title;
							$condensed_title = strlen($news_article_title) > 70 ? substr($news_article_title,0,70)."..." : $news_article_title;
							echo $condensed_title; 
						?>
					</span>
					<span class="publication" itemprop="publisher">
						<?php echo " - ".$news_article->na_publication; ?>
					</span>
					<span class="publication-date" itemprop="datePublished">
						<?php 
							$article_date = date_create($news_article->na_publish_date);
							echo date_format($article_date,"n/j/Y"); 
						?>
					</span>
				</a>
			</article>
			<?php
		}
	}
	/* display result of ob_start */
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