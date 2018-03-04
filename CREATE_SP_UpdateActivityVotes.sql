DROP PROCEDURE UpdateActivityVotes
-----
#--UpdateActivityVotes Definition - tallies votes, closes voting (could close post for new suggestions, post summary, etc.)
DELIMITER $$
CREATE PROCEDURE UpdateActivityVotes
( IN current_post_id BIGINT(20) ) 
BEGIN 
	#--create temp table detailing user voting based on sweep of comments associated with active post
	CREATE TEMPORARY TABLE ActivityVotesTemp
	SELECT 
		bitnami_wordpress.wp_comments.user_id, 
		bitnami_wordpress.wp_comments.comment_post_id, 
		bitnami_wordpress.wp_users.display_name, 
		bitnami_wordpress.wp_users.user_email, 
		right(bitnami_wordpress.wp_comments.comment_content,length(bitnami_wordpress.wp_comments.comment_content)-16) as activity_title,
		bitnami_wordpress.custom_activity.activity_id
	FROM bitnami_wordpress.wp_comments
	RIGHT JOIN  bitnami_wordpress.wp_users ON bitnami_wordpress.wp_comments.user_id=bitnami_wordpress.wp_users.ID
	INNER JOIN  bitnami_wordpress.custom_activity ON right(bitnami_wordpress.wp_comments.comment_content,length(bitnami_wordpress.wp_comments.comment_content)-16)=bitnami_wordpress.custom_activity.activity_title
	WHERE 
		!strcmp(left(comment_content,16),'Count Me In For ') AND
		#--activity_post_id is passed as only parameter, current_post_id
		activity_post_id=current_post_id;
	
	#--create temp table summarizing voting
	CREATE TEMPORARY TABLE ActivityVotesTallyTemp
	SELECT activity_id,COUNT(user_id) AS activity_score FROM ActivityVotesTemp GROUP BY activity_id;
	
	#--update custom_activity table with  votes/scores
	UPDATE bitnami_wordpress.custom_activity
	INNER JOIN ActivityVotesTallyTemp ON bitnami_wordpress.custom_activity.activity_id = ActivityVotesTallyTemp.activity_id
	SET bitnami_wordpress.custom_activity.activity_score = IF(ActivityVotesTallyTemp.activity_score > 0, ActivityVotesTallyTemp.activity_score, bitnami_wordpress.custom_activity.activity_score)
	WHERE bitnami_wordpress.custom_activity.activity_vote_status='OPEN';
	
	#--close voting
	UPDATE bitnami_wordpress.custom_activity SET activity_vote_status='CLOSED' WHERE activity_post_id=current_post_id;
	
	#--data for mailChimp
	SELECT activity_id,activity_title,display_name,user_email FROM ActivityVotesTemp;
END
$$
DELIMITER ;