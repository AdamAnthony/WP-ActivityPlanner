<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.2
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php
	if ( is_sticky() && is_home() ) :
		echo twentyseventeen_get_svg( array( 'icon' => 'thumb-tack' ) );
	endif;
	?>
	<header class="entry-header">
		<?php
		if ( 'post' === get_post_type() ) {
			echo '<div class="entry-meta">';
				if ( is_single() ) {
					twentyseventeen_posted_on();
				} else {
					echo twentyseventeen_time_link();
					twentyseventeen_edit_link();
				};
			echo '</div><!-- .entry-meta -->';
		};

		if ( is_single() ) {
			the_title( '<h1 class="entry-title">', '</h1>' );
		} elseif ( is_front_page() && is_home() ) {
			the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' );
		} else {
			the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		}
		?>
<!-- / START WP-ActivityPlanner Modifications 1 of 2 -->	
				
<?php //echo "<br>user_id:".get_current_user_id()."<br><br>"; ?>
		
<!-- show submit button -->
<?php
// Has voting already been closed?
$query="SELECT activity_vote_status FROM bitnami_wordpress.custom_activity WHERE activity_post_id=".get_the_ID()." LIMIT 1;";
$result=$wpdb->get_results($query);

foreach ($result as $voteStatus) {
	$vStatus=$voteStatus->activity_vote_status;
}

// Only Display the 'Submit An Activity' button if voting is open AND a user is logged in.
if((!strcmp($vStatus,"CLOSED")==0) && (get_current_user_id()>>0)) {
	echo "<button onclick=\"document.getElementById('activityModal-".get_the_ID()."').style.display='block'\" style=\"width:auto;\">Submit An Activity</button>";
} 

	
?>

<!-- define modal - include the post ID in the modal ID in case multiple posts are being displayed (modal will be define multiple times) -->
<div id="activityModal-<?php the_ID(); ?>" class="modal">
  <form class="modal-content animate" name="activity-submit" action=<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?> method="POST">
    <div class="imgcontainer">
      <span onclick="document.getElementById('activityModal-<?php the_ID(); ?>').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container">
      <label for="activity_title"><b>Activity Title</b></label>
      <input class="activity_title" type="text" placeholder="Enter A Title For Your Activity" name="activity_title" required>

      <label for="activity_details"><b>Activity Details</b></label>

      <textarea row="10" class="activity_details" name="activity_details" placeholder="Enter Some Details About Your Activity" required="yes"></textarea>

		<input id="activity_post_id" name="activity_post_id" type="hidden" value="<?php the_ID(); ?>">
		<input id="activity_submit_date" name="activity_submit_date" type="hidden" value="<?php echo date("Y-m-d H:i:s"); ?>">
		<input id="activity_submitter_id" name="activity_submitter_id" type="hidden" value="<?php echo get_current_user_id(); ?>">
		
      <button type="submit">Submit Idea!</button>      
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('activityModal-<?php the_ID(); ?>').style.display='none'" class="cancelbtn">Cancel</button>
    </div>
  </form>
</div>

<script>
// Get the modal
var modal = document.getElementById('activityModal-<?php the_ID(); ?>');
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

      function insertToComment(elemID, text)
      {
        var elem = document.getElementById(elemID);
        elem.innerHTML += text;
      }
	
</script>
		
<?php 

// check for html-post data related to current wp-post and make sure a user is logged in
if (($_POST["activity_post_id"]==get_the_ID()) && (get_current_user_id()>>0)) {
		
	//define query checking for suggestion that matches existing one in the database
	
	// BAD - no mitigation for SQL injection risks
	//$query="SELECT activity_title FROM bitnami_wordpress.custom_activity WHERE activity_post_id=".get_the_ID()." AND activity_title='".$_POST["activity_title"]."';";
	
	// GOOD - mitigation for SQL injection risks
	$query = $wpdb->prepare(
		"SELECT activity_title FROM bitnami_wordpress.custom_activity WHERE activity_post_id=%s AND activity_title='%s'",get_the_ID(),$_POST["activity_title"]
		);
	
	$result=$wpdb->get_results($query);

	
	// insert the new suggestion if thee are no matches, but display an error if there ARE matches
	if (count($result)< 1){
		
		// GOOD - mitigation for SQL injection risks with use of wpdb->insert
		$wpdb->insert('custom_activity',array('activity_title'=>$_POST["activity_title"], 'activity_details'=>$_POST["activity_details"], 'activity_post_id'=>$_POST["activity_post_id"], 'activity_submitter_id'=>$_POST["activity_submitter_id"]));
				
	} else {
	echo "<p><br><b>*** Sorry \"".$_POST["activity_title"]."\" has already been suggested.  Please make another suggestion, or select an activity below. ***</b></p>";
	}
}
		
// allow administrators to close voting (provide them with a button)
if ((current_user_can('administrator')==1) && (!strcmp($vStatus,"CLOSED")==0)) {
	echo "<button onclick='window.location.href=\"\/close-voting\/?closePostID=".get_the_ID()."\"' style='width:auto;'>Close Submissions</button>";
}

// if voting is open but the user isn't logged in, provide a login button
if((!strcmp($vStatus,"CLOSED")==0) && (get_current_user_id()==0)) {
	echo "<button onclick='window.location.href=\"\/wp-login.php\"' style=\"width:auto;\">Login to Submit An Activity</button>";
} 
	
?>
		

<!-- END WP-ActivityPlanner Modifications 1 of 2 /-->	
	</header><!-- .entry-header -->

	<?php if ( '' !== get_the_post_thumbnail() && ! is_single() ) : ?>
		<div class="post-thumbnail">
			<a href="<?php the_permalink(); ?>">
				<?php the_post_thumbnail( 'twentyseventeen-featured-image' ); ?>
			</a>
		</div><!-- .post-thumbnail -->
	<?php endif; ?>

	<div class="entry-content">
		<?php
		/* translators: %s: Name of current post */
		the_content( sprintf(
			__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'twentyseventeen' ),
			get_the_title()
		) );

		wp_link_pages( array(
			'before'      => '<div class="page-links">' . __( 'Pages:', 'twentyseventeen' ),
			'after'       => '</div>',
			'link_before' => '<span class="page-number">',
			'link_after'  => '</span>',
		) );
		?>
	</div><!-- .entry-content -->

<!-- / START WP-ActivityPlanner Modifications 2 of 2 -->	
<!-- this block displays activities -->	
<?php

$query="SELECT activity_id, activity_title, activity_details, activity_submitter_id, activity_score FROM bitnami_wordpress.custom_activity WHERE activity_post_id=".get_the_ID().";";
$result=$wpdb->get_results($query);

foreach ($result as $activity) {

	echo '<div class="tooltip" onclick="insertToComment(\'comment\', \'Count Me In For '.stripslashes($activity->activity_title).'\n\');">'.stripslashes($activity->activity_title).'<span class="tooltiptext"><br><b>'.stripslashes($activity->activity_details).'</b><br><br></span></div><br>';
	
//	echo '<br>user id is:'.get_current_user_id();
//	echo '<br>activity_id is:'.$activity->activity_id;
	
    }

// if voting is closed - display a message
if(strcmp($vStatus,"CLOSED")==0) {
	echo "</br></br><p><b>**submissions and signups for this post have been closed**</b></p>";
} 

	
	
?>
<!-- END WP-ActivityPlanner Modifications 2 of 2 /-->	
	
	<?php
	if ( is_single() ) {
		twentyseventeen_entry_footer();
	}
	?>

</article><!-- #post-## -->
