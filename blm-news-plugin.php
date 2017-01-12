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
	global $wpdb;
  	$table_name = $wpdb->prefix . "news_articles_table";
  	/*
  		Confirm Delete Page
  	*/
	if($_GET['delete'] && $_GET['delete'] != ""){
		$id_to_delete = filter_input(INPUT_GET, 'delete');
		$news_articles = $wpdb->get_results( 
			"
			SELECT * 
			FROM $table_name
			WHERE id = $id_to_delete
			ORDER BY na_order ASC, na_publish_date DESC
			"
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Delete News Articles</h1>
		</div>
		<?php
		foreach ( $news_articles as $news_article ){
			?>
			<p>
				Are you sure that you want to delete <?php echo $news_article->na_title; ?>
			</p>
			<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-admin-page&confirmed_delete=<?php echo $news_article->id; ?>">Delete</a>
			<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-admin-page" class="page-title-action">Cancel</a>
			<?php
		}
		?>
		</div><!-- .wrap -->
		<?php
		exit();
	}
	?>
	<?php
  	/*
  		Delete Page
  	*/
	if($_GET['confirmed_delete'] && $_GET['confirmed_delete'] != ""){
		$id_to_delete = filter_input(INPUT_GET, 'confirmed_delete');
		$news_articles = $wpdb->get_results( 
			"
			SELECT * 
			FROM $table_name
			WHERE id = $id_to_delete
			ORDER BY na_order ASC, na_publish_date DESC
			"
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">News Article Deleted</h1>
		</div>
		<?php
		foreach ( $news_articles as $news_article ){
			?>
			<p>
				<?php echo $news_article->na_title; ?> has been deleted
			</p>
			<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-admin-page" class="page-title-action">View All Articles</a>
			<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-add-page" class="page-title-action">Add Another</a>
			<?php
			$wpdb->delete( $table_name, array( 'id' => $news_article->id ) );
		}
		?>
		</div><!-- .wrap -->
		<?php
		exit();
	}
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">News Articles</h1>
		<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-add-page" class="page-title-action">Add New</a>
		<?php
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
							<div class="row-actions">
								<!--<span class="edit">
									<a href="">Edit</a> | 
								</span>-->
								<span class="trash">
									<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-admin-page&delete=<?php echo $entry->id; ?>">Delete</a>
								</span>
							</div>
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
	<?php
}

/** 

	Add New Page
	
*/
function display_add_new_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	global $wpdb;
  	$table_name = $wpdb->prefix."news_articles_table";
	if( !empty($_POST)){
		$na_article_url = sanitize_text_field( $_POST['na_article_url'] );
		$na_image_url = sanitize_text_field( $_POST['na_image_url'] );
		$na_publication = sanitize_text_field( $_POST['na_publication'] );
		$na_title = sanitize_text_field( $_POST['na_title'] );
		$na_publish_date = sanitize_text_field( $_POST['na_publish_date'] );
		$na_author = sanitize_text_field( $_POST['na_author'] );
		$na_blurb = sanitize_text_field( $_POST['na_blurb'] );
		$na_feature = sanitize_text_field( $_POST['na_feature'] );
		$na_order = sanitize_text_field( $_POST['na_order']);
		$wpdb->insert($table_name, array('na_article_url' => $na_article_url,'na_image_url' => $na_image_url,'na_publication' => $na_publication,'na_title' => $na_title,'na_publish_date' => $na_publish_date,'na_author' => $na_author,'na_blurb' => $na_blurb,'na_feature' => $na_feature,'na_order' => $na_order,));
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">News Article Added</h1>
		</div>
		<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-add-page" class="page-title-action">Add Another</a>
		<a href="<?php echo admin_url(); ?>admin.php?page=news-articles-admin-page" class="page-title-action">View All Articles</a>
		<?php
	}else{
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Add New News Article</h1>
			<form name="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=news-articles-add-page" method="post" id="post">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="na_article_url">Article URL</label>
							</th>
							<td>
								<input name="na_article_url" type="text" class="regular-text" />
								<p class="description">Enter the entire url of the article, complete with http://</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="na_publication">Publication</label>
							</th>
							<td>
								<input name="na_publication" type="text" class="regular-text" />
								<p class="description">Enter the name of the blog where the article is hosted</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="na_title">Title</label>
							</th>
							<td>
								<input name="na_title" type="text" class="regular-text" />
								<p class="description">Enter the title of the article</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="na_publish_date">Publication Date</label>
							</th>
							<td>
								<input name="na_publish_date" type="date" />
								<p class="description">Enter the date that the article was published</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="na_author">Author</label>
							</th>
							<td>
								<input name="na_author" type="text" class="regular-text" />
								<p class="description">Enter the name of the person who wrote the article</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="na_blurb">Excerpt</label>
							</th>
							<td>
								<textarea name="na_blurb"/></textarea>
								<p class="description">Enter an eexcerpt of the article to display</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="na_feature">Feature this Article</label>
							</th>
							<td>
								<input type='hidden' value='0' name='na_feature'>
								<input type='checkbox' name='na_feature' value='1'>
								<p class="description">Place this article in the "featured" section</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="na_order">Order</label>
							</th>
							<td>
								<input name="na_order" type="text" class="regular-text" />
								<p class="description">influence the order of the article by giving it a higher number</p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
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

/* Shortcode output if featured articles are selected */
function display_featured_articles(){ 
	?> 
	<div class="na-selector">
		<span class="na-selector-title">
			View:
		</span>
		<span class="na-tab na-tab-active">Featured</span>
		<a href="<?php echo $_SERVER['PHP_SELF']; ?>?blm_news_articles=all" class="na-tab">All</a>
	</div>
	<div id="na-news-articles">
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
				<a href="<?php echo $news_article->na_article_url; ?>" class="clearfix" target="_blank" itemprop="url">
					<?php if($news_article->na_image_url != ""){
						?>
						<span class="publication">
							<img src="<?php echo $news_article->na_image_url; ?>" alt="<?php echo $news_article->na_publication; ?>" itemprop="image"/>
						</span>
						<?php 
					}else{
						?>
						<span class="publication" itemprop="publisher">
							<?php echo str_replace("\\","",$news_article->na_publication); ?>
						</span>
						<?php
					}
					?>
					<span class="news-article-content">
						<?php if($news_article->na_publish_date != "0000-00-00"){
							?>
							<span class="publish-date" itemprop="datePublished">
								<?php 
									$article_date = date_create($news_article->na_publish_date);
									echo date_format($article_date,"F j, Y"); 
								?>
							</span>
							<?php
						}
						?>
						<span class="title" itemprop="headline">
							<?php echo str_replace("\\","",$news_article->na_title); ?>
						</span>
						<?php if($news_article->na_author != ""){
							?>
							<span class="author" itemprop="author">
								<?php echo "by: ".str_replace("\\","",$news_article->na_author); ?>
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
	?>
	</div> <!-- #na-news-articles -->
	<?php
}

/* Shortcode output if all articles is selected */
function display_all_articles(){
	?>
	<div class="na-selector">
		<span class="na-selector-title">
			View:
		</span>
		<a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="na-tab">Featured</a>
		<span class="na-tab na-tab-active">All</span>
	</div>
	<div id="na-news-articles">
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
				<a href="<?php echo $news_article->na_article_url; ?>" target="_blank" itemprop="url">
					<span class="title" itemprop="headline">
						<?php 
							$news_article_title = str_replace("\\","",$news_article->na_title);
							$condensed_title = strlen($news_article_title) > 70 ? substr($news_article_title,0,70)."..." : $news_article_title;
							echo $condensed_title; 
						?>
					</span>
					<span class="publication" itemprop="publisher">
						<?php echo " - ".str_replace("\\","",$news_article->na_publication); ?>
					</span>
					<span class="publication-date" itemprop="datePublished">
						<?php 
							$article_date = date_create($news_article->na_publish_date);
							echo "(".date_format($article_date,"n/j/Y").")"; 
						?>
					</span>
				</a>
			</article>
			<?php
		}
	}
	?>
	</div> <!-- #na-news-articles -->
	<?php
}

?>