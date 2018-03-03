
##WP-ActivityPlanner			
Group Activity Suggestion and Sign-Up Blog

Adam T. Anthony (adam@anthony.net) - 3/2018		
			
#Overview:			
		These WordPress modifications provide a means for users to make suggestions and vote on those suggestions.	
		The modifications include routines to close the suggestions process, tally votes, and create MailChimp lists	
		for each suggestion with votes (adding users who voted for a suggestions to the respective MailChimp list).	

#Specification:			
			
1	must use WordPress / MySQL instance
			primary point of modification is in 'template-parts/post/content.php'
			
2	must include 'Submit Activity' button under every blog title:
			title is written by content-php.html, so included HTML/PHP for button here after title display
			button is functional, including on pages with multiple posts present
			
3	must include tool allowing users to submit activities
			wrote simple modal pop-up with post form
			modal code (JS/HTML/PHP) included in content-php.html, styling in 'Appearance>Customize>Additional CSS'
			submit button only appears for logged in users - login button appears in place of it for guest visitors
			submit idea button is gone when voting is closed
			
	3.1	each submission must update the post body with the suggestion
			list of activities is displayed immediately after <div class="entry-content">…</div>
			error checking for an identical activity name in place - warning displayed
			checking 'get_current_user_id() >>0' before allowing activity add (must be logged in to suggest)
			
	3.2	must add a row to a database table (defined below)
			modal form posts data back to current page, if page sees 'activity_title' post value, the activity is inserted into the database
			
	3.3	submit activity button could lead here
			'Submit Activity' button triggers the modal
			
4	must require login to comment
			set WordPress option “Users must be registered and logged in to comment"
			checking 'get_current_user_id() >>0' before allowing activity add (must be logged in to suggest activity)
			
5	must not require comment approval
			'Comment must be manually approved' option set to no / unchecked
			
6	must use comments as the basis for signing up for an activity
			comments starting with 'Count Me In For ' are evaluated as votes during the vote tally
			included JS to update the comment input with 'Count Me In For activity' when an activity is clicked to accelerate testing
			
7	must allow for open-for-voting and closed-for-sign-up states
			voting in each post is closed with PHP, stored procedure, MySql stored values
			comments are turned off for post with PHP
			
8	must use a custom table
			activity data stored here: `bitnami_WordPress`.`custom_activity` 
			
9	must use / create a tool to close voting
			created php-based tool which is accessible through WordPress, could be hit with CRON for scheduling
			provided button/link to this tool, passing post ID, for anyone who is a WordPress admin for site
			
10	tool must make use of a stored procedure:
			the procedure name is 'UpdateActivityVotes'
			
	10.1	can only run once per post
				" WHERE bitnami_wordpress.custom_activity.activity_vote_status='OPEN' " statement in SP prevents it
					from updating records, and PHP prevents creating more MailChimp lists when post has previously been set to 'CLOSED'
				close submissions button appears only for admins, and disappears if voting is closed
			
	10.2	tallies and stores activity scores
				bitnami_wordpress.custom_activity.activity_score' is updated by the SP
	10.3	returns list of users signed up for each activity
				final SELECT statement in SP returns the user to activity mapping
	10.4	could close post for votes
				each activity record is closed by the SP by setting 'bitnami_wordpress.custom_activity.activity_vote_status' to 'CLOSED'
			
11	tool could close post for votes is SP did not
				SP covers this requirement
			
12	tool must create MailChimp lists for all activities users have voted for
			the PHP tool executes the SP, and uses an unique values for the 'activity_title' to create a MailChimp List
			communication with the MailChimp /lists/ API is handled by custom function mc_createlist()
			mc_createlist() takes 1)API Key, 2)List Name, 3)server/API link, and returns the list's identifier
			
13	tool must add users to their respective MailChimp lists
			the PHP tool also adds users to the appropriate MailChimp List immediately after the list is created
			communication with the MailChimp /lists/id/members/ API is handled by custom function mc_subscribe()
			
14	security considerations / steps taken
			set WordPress option “Users must be registered and logged in to comment"
			to protect against SQL injection, ensured use of $wpdb->prepare for any custom sql queries in PHP
			checking 'get_current_user_id() >>0' before allowing activity add (must be logged in to suggest activity)
			clear sensitive data from any documentation, shared code, communications
			     closeVoting.php is the only file in the project which contains sensitive data, MailChimp API Key
			
			
#Implementing:			
	1	from WP Dashboard,  go to 'Appearance>Customize>Additional CSS'	overwrite of append content with content from project file Appearance-Customize-Additional_CSS.css
	2	use WP editor to modify the content.php source	overwrite content with content from project file content.php
	3	use phpMyAdmin to create the custom table and SP	queries defined in CREATE_custom_activity_TABLE.sql and CREATE_SP_UpdateActivityVotes.sql
	4	copy page.php to closeVoting.php (by SFTP, etc.)	/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/twentyseventeen
	5	make sure closeVoting.php is read-writeable (by SFTP, etc.)	
	6	create a WP page with link /close-voting/	
	7	use WP editor to modify the closeVoting.php source	overwrite content with content from project file closeVoting.php
	8	set the $apikey variable to your MailChimp API key on line 17 of the closeVoting.php file with the WP editor	
	9	set the $server variable to your MailChimp server on line 18 of the closeVoting.php file with the WP editor	
			
			
#Testing:			
		create a post	
		verify 'submit an activity' button and (if logged in as an admin) 'close submissions' buttons appear under title of post	
		submit multiple suggestions with the 'submit and activity button'	
		     repeat with multiple users accounts	
		     ensure this can't be done if you're not signed in	
		sign up for multiple activities by commenting "Count Me In For " + the activity name (click activity name to auto populate the comment field	
		     repeat with multiple user accounts	
		with an WP admin account, click the 'close submissions' button on the test post	
		revisit the post and attempt to suggest and activity 	
		revisit the post and ensure commenting is closed	
		login to MailChimp and ensure your mailing lists have been created	https://us17.api.mailchimp.com/playground/
