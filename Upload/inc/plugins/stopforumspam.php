<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("member_do_register_start", "stopforumspam");

function stopforumspam_info()
{
	/**
	 * Array of information about the plugin.
	 * name: The name of the plugin
	 * description: Description of what the plugin does
	 * website: The website the plugin is maintained at (Optional)
	 * author: The name of the author of the plugin
	 * authorsite: The URL to the website of the author (Optional)
	 * version: The version number of the plugin
	 * guid: Unique ID issued by the MyBB Mods site for version checking
	 * compatibility: A CSV list of MyBB versions supported. Ex, "121,123", "12*". Wildcards supported.
	 */
	return array(
		"name"			=> "Stop forum spam",
		"description"	=> "Prevents users who are listed at http://www.stopforumspam.com from registering.",
		"website"		=> "https://github.com/Tim-B",
		"author"		=> "Tim B.",
		"authorsite"	=> "https://github.com/Tim-B",
		"version"		=> "1.2",
		"guid" 			=> "cd4d9e2f4a6975562887ee6edffb984e",
		"compatibility" => "16*"
	);
}

function stopforumspam_install(){
	global $db, $mybb;

	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_user'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_email'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_ip'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_mode'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_log'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_fail'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='stopforumspam'"); 

	$stopforumspam_group = array(
		"gid"	 => "NULL",
		"name"	 => "stopforumspam",
		"title" => "Stopforumspam.com check.",
		"description"	=> "Checks new registrations against www.stopforumspam.com",
		"disporder"	 => 5,
		"isdefault"	 => 0,
	);

	$group['gid'] = $db->insert_query("settinggroups", $stopforumspam_group);
	$mybb->smallquote_insert_gid = $group['gid'];

	$stopforumspam_setting_1 = array(
		"sid"	 => "NULL",
		"name"	 => "sp_user",
		"title"	 => "Check username",
		"description"	=> "Do you want to check the username against www.stopforumspam.com?",
		"optionscode"	=> "yesno", 
		"value"	 => 1,
		"disporder"	 => 1,
		"gid"	 => $group['gid'],
	);

	$db->insert_query("settings", $stopforumspam_setting_1);

	$stopforumspam_setting_2 = array(
		"sid"	 => "NULL",
		"name"	 => "sp_email",
		"title"	 => "Check email",
		"description"	=> "Do you want to check the email address against www.stopforumspam.com?",
		"optionscode"	=> "yesno",
		"value"	 => 1,
		"disporder"	 => 2,
		"gid"	 => $group['gid'],
	);

	$db->insert_query("settings", $stopforumspam_setting_2);

	$stopforumspam_setting_3 = array(
		"sid"	 => "NULL",
		"name"	 => "sp_ip",
		"title"	 => "Check IP",
		"description"	=> "Check IP address against www.stopforumspam.com?",
		"optionscode"	=> "yesno",
		"value"	 => 1,
		"disporder"	 => 3,
		"gid"	 => $group['gid'],
	);

	$db->insert_query("settings", $stopforumspam_setting_3);

	$stopforumspam_setting_4 = array(
		"sid"	 => "NULL",
		"name"	 => "sp_mode",
		"title"	 => "All criteria must be met to deny registration?",
		"description"	=> "it is recommended that this is set to no.",
		"optionscode"	=> "yesno",
		"value"	 => 0,
		"disporder"	 => 4,
		"gid"	 => $group['gid'],
	);

	$db->insert_query("settings", $stopforumspam_setting_4);
	
	$stopforumspam_setting_5 = array(
		"sid"	 => "NULL",
		"name"	 => "sp_log",
		"title"	 => "Log denials in sfs_log.php?",
		"description"	=> "Do you want all users denied registration to be logged in the plugin log file?",
		"optionscode"	=> "yesno",
		"value"	 => 1,
		"disporder"	 => 5,
		"gid"	 => $group['gid'],
	);

	$db->insert_query("settings", $stopforumspam_setting_5);
	
	$stopforumspam_setting_6 = array(
		"sid"	 => "NULL",
		"name"	 => "sp_fail",
		"title"	 => "Fail safe or fail secure?",
		"description"	=> "If there is an error loading StopForumSpam.com and yes is selected the user will be automatically allowed (fail safe) otherwise the user will be automatically rejected (fail secure).",
		"optionscode"	=> "yesno",
		"value"	 => 1,
		"disporder"	 => 6,
		"gid"	 => $group['gid'],
	);

	$db->insert_query("settings", $stopforumspam_setting_6);

	rebuild_settings();
	
}

function stopforumspam_uninstall(){
	global $db, $mybb, $lang;
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_user'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_email'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_ip'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_mode'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_log'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='sp_fail'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='stopforumspam'"); 
	
	rebuild_settings();
}

function stopforumspam_is_installed()
{
	global $db;
	$query = $db->simple_select("settinggroups", "COUNT(*) as rows", "name = 'stopforumspam'");
	$sfscnt = $db->fetch_field($query, "rows");

	if($sfscnt == 1)
	{
		return true;
	}
	
	return false;
}

function stopforumspam(){
	global $mybb, $session, $username, $email, $ip;

	//$sp_xml = simplexml_load_file('http://www.stopforumspam.com/api?ip='. $session->ipaddress .'&email='. $mybb->input['email'] .'&username='.$mybb->input['username']);

	$username = $mybb->input['username'];
	
	$email = $mybb->input['email'];
	
	$ip = $session->ipaddress;
	
	$url = 'http://www.stopforumspam.com/api?ip='. $ip .'&email='. $email .'&username=' . $username .'&f=json';
	
	$data = @file_get_contents($url);

    $data = json_decode($data);
	
	function sp_log($log)
	{
	
		$logfile = 'sfs_log.php';
		
		$logmessage = '';
		
		if(!file_exists($logfile))
		{
		
			$fileoption = 'w';
			$logmessage = '<?php die(); ?>';
			
		}else{
		
			$fileoption = 'a';
			
		}
		
		$logmessage .= "\n";
		
		$logmessage .= $log;
		
		$fhandle = fopen($logfile, $fileoption) or die("can't open file");
		
		fwrite($fhandle, $logmessage);
	
		fclose($fhandle);
		
	}
	
	if((isset($data->error)) or ($data == Null))
	{
	
		if($mybb->settings['sp_log'])
		{
			
			if($data == Null)
			{
				$error = 'StopForumSpam.com could not be reached';
			}else{
				$error = $data->error;
			}
			
			$logstring = 'Error: '. $error;
			$logstring .= " / Time: ".date(DATE_RSS, time());
			$logstring .= ' / Username: '.$username;
			$logstring .= ' / Email: '.$email;
			$logstring .= ' / IP: '.$ip;
			
			sp_log($logstring);
			
		}
		
		if($mybb->settings['sp_fail'])
		{
		
			return;
			
		}else{
		
			error('There was an error validating your registration. Please contact the administrator or try again later');
			
		}
	
	}
	
	
	function sp_spamerror(){
	
		global $username, $email, $ip, $mybb;
		
		if($mybb->settings['sp_log'])
		{
			
			$logstring = "Time: ".date(DATE_RSS, time());
			$logstring .= ' / Username: '.$username;
			$logstring .= ' / Email: '.$email;
			$logstring .= ' / IP: '.$ip;
			
			sp_log($logstring);
			
		}

		error('Your details match those of a known spammer, therefore you have been disallowed registration.');
	}

	$sp_checkarray = array('ip'=>false, 'user'=>false, 'email'=>false);

	
	if(($mybb->settings['sp_ip'] == 1) && ($data->ip->appears)){
		$sp_checkarray['ip'] = true;
	}

	if(($mybb->settings['sp_user'] == 1) && ($data->username->appears)){
		$sp_checkarray['user'] = true;
	}


	if(($mybb->settings['sp_email'] == 1) && ($data->email->appears)){
		$sp_checkarray['email'] = true;
	}
	

	if($mybb->settings['sp_mode'] == 1){

			$sp_count = 0;
			$sp_rescount = 0;

			if($mybb->settings['sp_email'] == 1){
			$sp_count++;
		
			if($sp_checkarray['email']){
				$sp_rescount++;
			}
		}
		
	if($mybb->settings['sp_ip'] == 1){
		
		$sp_count++;
		if($sp_checkarray['ip']){
			$sp_rescount++;
		}
	}
	
	if($mybb->settings['sp_user'] == 1){
		
		$sp_count++;
		if($sp_checkarray['user']){
			$sp_rescount++;
		}
		
	}
	
	if($sp_rescount == $sp_count){
		sp_spamerror();
	}

		}else{
			if(in_array(1, $sp_checkarray)){
			sp_spamerror();
		}
	}
}

?>