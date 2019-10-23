<?php
// Lotto-related functions

// Get actual round
function lotto_get_actual_round() {
	$actual_round=db_query_to_variable("SELECT `uid` FROM `lotto_rounds` WHERE `stop` IS NULL");
	return $actual_round;
}

// Get latest finished round
function lotto_get_finished_round() {
	$finished_round=db_query_to_variable("SELECT `uid` FROM `lotto_rounds` WHERE `stop` IS NOT NULL ORDER BY `stop` DESC LIMIT 1");
	return $finished_round;
}

// Close round and send rewards
function lotto_get_close_round() {
	$actual_round=db_query_to_variable("SELECT `uid` FROM `lotto_rounds` WHERE `stop` IS NULL");
	return $actual_round;
}

// Get current round tickets
function lotto_get_current_round_tickets($round_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$tickets=db_query_to_variable("SELECT SUM(`tickets`) FROM `lotto_tickets` WHERE `round_uid`='$round_uid'");
	return $tickets;
}

// Get current round user tickets
function lotto_get_current_round_tickets($round_uid,$user_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$user_uid_escaped=db_escape($user_uid);
	$tickets=db_query_to_variable("SELECT `tickets` FROM `lotto_tickets` WHERE `round_uid`='$round_uid' AND `user_uid`='$user_uid_escaped'");
	return $tickets;
}

// Get current round prize fund
function lotto_get_current_round_tickets($round_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$spent=db_query_to_variable("SELECT SUM(`spent`) FROM `lotto_tickets` WHERE `round_uid`='$round_uid'");
	return $spent;
}

// Buy tickets
function lotto_buy_tickets($user_uid,$amount) {
	
}

// Lotto close round
function lotto_close_round() {
	// Get current round uid
	$round_uid=lotto_get_actual_round();
	// Round could not exists (at zero round)
	if($round_uid) {
		$round_uid_escaped=db_escape($round_uid);

		// Mark round as closed
		db_query_to_variable("UPDATE `lotto_rounds` SET `stop`=NOW() WHERE `uid`='$round_uid'");

		// Calculate user's best hashes
		lotto_calc_all_users_best_hashes($round_uid);

		// Send rewards to winners

	}

	// Start new round
	$seed=bin2hex(random_bytes(32));
	$seed_escaped=db_escape($seed);
	db_query("INSERT INTO `lotto_rounds` (`seed`,`start`) VALUES ('$seed_escaped',NOW())");
}

// Lotto calculate all users best hashes
function lotto_calc_all_users_best_hashes($round_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$users_data=db_query_to_array("SELECT `user_uid` FROM `lotto_tickets` WHERE `round_uid`='$round_uid_escaped'");
	foreach($users_data as $users_row) {
		$user_uid=$users_row['user_uid'];
		lotto_calc_user_best_hash($round_uid,$user_uid);
	}
}

// Lotto get user best hash
function lotto_calc_user_best_hash($round_uid,$user_uid) {
	$user_tickets=lotto_get_current_round_tickets($round_uid,$user_uid);
	$user_seed=get_user_seed($user_uid);
	for($i=0;$i!=$user_tickets;$i++) {
		$hash=hash("sha256","$i.$server_seed.$user_seed");
		if(!isset($best_hash) || $hash<$best_hash) {
			$best_hash=$hash;
		}
	}

	$user_uid_escaped=db_escape($user_uid);
	$round_uid_escaped=db_escape($round_uid);
	$user_seed_escaped=db_escape($user_seed);
	$best_hash_escaped=db_escape($best_hash);

	db_query("UPDATE `lotto_tickets`
		SET `user_seed`='$user_seed_escaped',`best_hash`='$best_hash_escaped'
		WHERE `user_uid`='$user_uid_escaped' AND `round_uid`='$round_uid_escaped'");
}
?>
