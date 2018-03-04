CREATE TABLE `bitnami_wordpress`.`custom_activity` 
( 
	`activity_id` int NOT NULL AUTO_INCREMENT, 
	`activity_title` TEXT NOT NULL , 
	`activity_details` TEXT NOT NULL , 
	`activity_submit_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , 
	`activity_post_id` BIGINT(20) NOT NULL , 
	`activity_submitter_id` BIGINT(20) NOT NULL , 
	`activity_score` INT(11) NOT NULL DEFAULT '0' , 
	`activity_vote_status` VARCHAR(6) NOT NULL DEFAULT 'OPEN' , 
	`activity_mc_list_id` VARCHAR(12) NOT NULL , 
	PRIMARY KEY (`activity_id`)
)
	


