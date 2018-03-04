<?php /* Template Name: Close Voting Control Page */
/**
 * The template for custom page to close voting process for WP-ActivityPlanner project
 *
 * Adam T. Anthony
 * 3/2018
 *
 */

get_header(); ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

<?php
$postID=$_GET["closePostID"];
$apikey = "ffffffffffffffffffffffffffffffff";
$server = "us17.";

// Have voting already been closed?
$query="SELECT activity_vote_status FROM bitnami_wordpress.custom_activity WHERE activity_post_id=".$postID." LIMIT 1;";
$result=$wpdb->get_results($query);

foreach ($result as $voteStatus) {
	$vStatus=$voteStatus->activity_vote_status;
	//echo "<h1>Voting is ".$voteStatus->activity_vote_status."</h1></br>";
}

// Determine and Describe Action To Be Taken
$query="SELECT post_title, post_date FROM bitnami_wordpress.wp_posts WHERE ID=".$postID;	
$result=$wpdb->get_results($query);
	
foreach ($result as $postDetails) { 
	if(strcmp($vStatus,"OPEN")==0) {
		echo "<h1>Closing Voting For \"".$postDetails->post_title."\" </h1><p>(created on ".$postDetails->post_date.")</p></br>";				
	} 
	else if(strcmp($vStatus,"CLOSED")==0) {
		echo "<h1>Voting For \"".$postDetails->post_title."\" is already closed!</h1> <p>(post created on ".$postDetails->post_date.") </p>";		
	}
}


if(strcmp($vStatus,"OPEN")==0) {  // START - If Activity Is Still Open
	// Call Stored Procedure To Tally Votes, Close Voting, and return list of users/votes
	$query="CALL UpdateActivityVotes(".$postID.");";
	$result=$wpdb->get_results($query);
	//print_r($result);
		
	// Create MC Mailing Lists For Any Activities With Votes
	$activities = array_unique(array_column($result, 'activity_title'));
	foreach ($activities as $a) {
		$listID=mc_createlist($apikey, $a.'-'.date("m.d.y"), $server);
		//$listID="a068b9e6fb";
			
		// record activity_mc_list_id in SQL (example: 35737682a5)
		$query="UPDATE bitnami_wordpress.custom_activity SET activity_mc_list_id='".$listID."' WHERE activity_post_id=".$postID." AND activity_title='".$a."';";
		$wpdb->get_results($query);
			
		echo "<p>Activity <b>\"".$a."\"</b> has MailChimp list ID: <b>".$listID."</b></br>";
		// Add Users To This List If Appropriate
		foreach ($result as $subscription) {
			if(strcmp($a,$subscription->activity_title)==0) {
				echo '&nbsp;&nbsp;&nbsp;&nbsp;subscribing '.$subscription->user_email.' ('.$subscription->display_name.') to '.$listID;
				mc_subscribe($subscription->user_email, $subscription->display_name, $apikey, $listID, $server);			
			}
			echo "</p>";
		}		
	}
	// Close Comments For Post
	$query="UPDATE bitnami_wordpress.wp_posts SET comment_status='closed' WHERE ID=".$postID;	
	$result=$wpdb->get_results($query);
	
	// Updates Post with any additional messaging?
	
}	// END - If Activity Is Still Open

			
// Start of MailChimp function definitions

// mc_createlist() - creates a Mail Chimp LIST and returns the LIST'S ID
function mc_createlist($apikey, $listname, $server) {

	$auth = base64_encode( 'user:'.$apikey );
	$data = array(
		'apikey'	=> $apikey,
		'name' 		=> $listname,
		'contact'	=> array(
				'company' => 'Adam Anthony LLC',
				'address1' => '123 Meadow Lane',
				'address2' => 'Suite 7',
				'city' => 'Alton',
				'state' => 'NH',
				'zip' => '03809',
				'country' => 'US',
				'phone' => '603-555-1212'
			),
		'permission_reminder' => 'You signed up!',
		'campaign_defaults' => array(
				'from_name' => 'Adam Anthony',
				'from_email' => 'adam@anthony.net',
				'subject' => 'Weekend Activity Planning',
				'language' => 'en'
			),
		'email_type_option' => true
		);

		$json_data = json_encode($data);
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, 'https://'.$server.'api.mailchimp.com/3.0/lists/');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic '.$auth));
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

	
	$result = curl_exec($ch);
	//var_dump($result);
	
	// json to php array
	$phpObj =  json_decode($result);
	
	// list id
	return $phpObj->id;
		
}



// mc_subscribe() - adds a single subscriber to a Mail Chimp LIST
function mc_subscribe($email, $fname, $apikey, $listid, $server) {
	$auth = base64_encode( 'user:'.$apikey );
	$data = array(
		'apikey'        => $apikey,
		'email_address' => $email,
		'status'        => 'subscribed',
		'merge_fields'  => array(
			'FNAME' => $fname
			)
		);
	$json_data = json_encode($data);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://'.$server.'api.mailchimp.com/3.0/lists/'.$listid.'/members/');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
		'Authorization: Basic '.$auth));
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

	$result = curl_exec($ch);
	//var_dump($result);

	return 1;
}
// end of MC functions			
			
		
?>
		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
