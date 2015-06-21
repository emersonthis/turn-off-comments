<?php 
/*
Plugin Name: Turn off all comments
Plugin URI:  http://secretdesignproject.com
Description: Turn off comments for all posts and pages with one click.
Version:     1.0
Author:      Emerson This
Author URI:  http://emersonthis.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: toac
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

#register the menu
add_action( 'admin_menu', 'toac_plugin_menu' );

#add it to the tools panel
function toac_plugin_menu() {
	add_submenu_page( 'tools.php', 'Turn off all comments', 'Turn off comments', 'delete_others_posts', 'toac', 'toac_plugin_options');
}

function toac_count_open_comments() {
	global $wpdb;
	return $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE comment_status ='open' AND post_status='publish' " );
}

function toac_count_open_pings() {
	global $wpdb;
	return $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE ping_status ='open' AND post_status='publish' " );
}

#print the markup for the page
function toac_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$comment_count = toac_count_open_comments();
	$ping_count = toac_count_open_pings();

	echo '<div class="wrap">';

	echo '<h2>Turn off comments for all posts</h2>';

	if (isset($_GET['status']) && $_GET['status']=='success') { 
	?>
		<div id="message" class="updated notice is-dismissible">
			<p>Posts updated: <?php echo $_GET['rows']; ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
		</div>
	<?php
	}

	echo "<p>{$comment_count} posts/pages with comments turned on.</p>";
	echo "<p>{$ping_count} posts/pages with pings turned on.</p>";

	?>
		<form method="post" action="/wp-admin/admin-post.php">

			<input type="hidden" name="action" value="update_posts_comment_status" />
			<input class="" type="hidden" name="update_comment_status" value="0" />
			<input class="" type="hidden" name="update_ping_status" value="0" />
			<p>
			<label>
				<input class="" type="checkbox" name="update_comment_status" value="1" />
				Turn off comments on all posts/pages
			</label><br />
			<label>
				<input class="" type="checkbox" name="update_ping_status" value="1" />
				Turn off pings on all posts/pages
			</label>
			</p>
			<input class="button button-primary" type="submit" value="Do it now" />
		</form>

		<p>If you do not want new comments, remember to <a href="/wp-admin/options-discussion.php">update the default article settings</a> so that comments and pingbacks are off.</p>
	<?php
	echo '</div>';
}

#callback for handling the request
function toac_handle_request() {

	#check which options were sent
	$comments = $_POST['update_comment_status'];
	$pings = $_POST['update_ping_status'];

	#call DB update function
	$rows = toac_update_posts( $comments, $pings );

	#redirect back to page
	$redirect_url = get_bloginfo('url') . "/wp-admin/tools.php?page=toac&status=success&rows={$rows}";
    header("Location: ".$redirect_url);
    exit;
}

#handles the actual updating of the DB
function toac_update_posts($comments_off=FALSE, $pings_off=FALSE) {

	#No updates means we dont do anything
	if (!$comments_off && !$pings_off)
		return;

	#have to globalize $wpdb;
	global $wpdb;

	#build query in segments depending on options
	$query = "UPDATE $wpdb->posts SET "; 

    if ($comments_off)
    	$query.=" comment_status = 'closed'";

    #optionally add commma to prevent syntax errors
    if ($pings_off)
    	$query.= ($comments_off) ? ", ping_status = 'closed'" : " ping_status = 'closed'";
    
    $query.=" WHERE 1";

    #return number of records updated
	return $wpdb->query($query);

}

#register the action that the form submits to
add_action( 'admin_post_update_posts_comment_status', 'toac_handle_request' );

//if you want to  not logged in users submitting, you have to add both actions!
// add_action( 'admin_post_nopriv_update_posts_comment_status', 'toac_handle_request' );
