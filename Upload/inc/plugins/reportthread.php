<?php
/**
Report to Thread 0.1.3
----------------------

This program is free software: you can redistribute it and/or modify it under the terms of the 
GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. 
If not, see <http://www.gnu.org/licenses/>.
**/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('report_do_report_end', 'reportthread_dopost');

function reportthread_info()
{
	return array(
		"name" => "Report To Thread",
		"description" => "Opens a discussion thread about the reported item when a user reports a post.",
		"website" => "http://forumgods.com/",
		"author" => "Tony Hudgins",
		"authorsite" => "http://forumgods.com/forums/member.php?160-Syke",
		"version" => "0.1.3",
		"compatibility" => "16*",
		"guid" => "8624478eb178c6eb37a503e960c267ec"
	);
}

function reportthread_install()
{
	global $db;
	
	$reported_group = array('gid' => 'NULL',
							'name' => 'reportthread',
							'title' => 'Report to Thread',
							'description' => 'Configure the Report to Thread plugin.',
							'disporder' => '1');
							
	$db->insert_query("settinggroups", $reported_group);
	$gid = $db->insert_id();
	
	$reported_setting1 = array('sid' => 'NULL',
							   'name' => 'rtt_enabled',
							   'title' => 'Enabled',
							   'description' => 'Whether or not to create new discussion threads for reported items.',
							   'optionscode' => 'yesno',
							   'value' => 'yes',
							   'disporder' => '1',
							   'gid' => intval($gid));
								   
	$reported_setting2 = array('sid' => 'NULL',
							   'name' => 'rtt_fid',
							   'title' => 'Forum ID',
							   'description' => 'The forum to post report threads to.',
							   'optionscode' => 'text',
							   'value' => '1',
							   'disporder' => '2',
							   'gid' => intval($gid));
								   
	$db->insert_query('settings', $reported_setting1);
	$db->insert_query('settings', $reported_setting2);
	
	rebuild_settings();
}

function reportthread_is_installed()
{
	global $db;
	
	$group = $db->query("SELECT `name` FROM `" . TABLE_PREFIX . "settinggroups` WHERE `name` = 'reportthread' LIMIT 1;");
	$s1 = $db->query("SELECT `name` FROM `" . TABLE_PREFIX . "settings` WHERE `name` = 'rtt_enabled' LIMIT 1;");
	$s2 = $db->query("SELECT `name` FROM `" . TABLE_PREFIX . "settings` WHERE `name` = 'rtt_fid' LIMIT 1;");
	
	if( $db->num_rows($group) >= 1 && $db->num_rows($s1) >= 1 && $db->num_rows($s2) )
		return true;
	else
		return false;
}

function reportthread_uninstall()
{
	global $db;
	
	$db->query("DELETE FROM `" . TABLE_PREFIX . "settinggroups` WHERE `name` = 'reportthread' LIMIT 1;");
	$db->query("DELETE FROM `" . TABLE_PREFIX . "settings` WHERE `name` = 'rtt_enabled' LIMIT 1;");
	$db->query("DELETE FROM `" . TABLE_PREFIX . "settings` WHERE `name` = 'rtt_fid' LIMIT 1;");
	
	rebuild_settings();
}

function reportthread_activate()
{
	global $db;
	
	$db->query("UPDATE `" . TABLE_PREFIX . "settings` SET `value` = 'yes' WHERE `name` = 'rtt_enabled' LIMIT 1;");
}

function reportthread_deactivate()
{
	global $db;
	
	$db->query("UPDATE `" . TABLE_PREFIX . "settings` SET `value` = 'no' WHERE `name` = 'rtt_enabled' LIMIT 1;");
}

function reportthread_dopost()
{
	require_once(MYBB_ROOT . "inc/datahandlers/post.php");
	global $db, $mybb;
	
	if(intval($mybb->settings['rtt_enabled']) == 1 || preg_replace("/[^a-z]/i", "", $mybb->settings['rtt_enabled']) == "yes")
	{
		$post = get_post($mybb->input['pid']);
		$thread = get_thread($post['tid']);
		$forum = get_forum($thread['fid']);
		
		$tlink = get_thread_link($thread['tid']);
		$flink = get_forum_link($thread['fid']);
		
		$post_data = $mybb->user['username'] . " has reported a post.

Original Thread: [url=" . $mybb->settings['bburl'] . "/$tlink]" . $thread['subject'] . "[/url]
Forum: [url=" . $mybb->settings['bburl'] . "/$flink]" . $forum['name'] . "[/url]

Reason Given:
[quote=\"" . $mybb->user['username'] . "\" dateline=\"" . time() . "\"]" . $mybb->input['reason'] . "[/quote]

Post Content:
[quote=\"" . $post['username'] . "\" pid=\"" . $post['pid'] . "\" dateline=\"" . $post['dateline'] . "\"]" . $post['message'] . "[/quote]";
		
		$new_thread = array(
			"fid" => $mybb->settings['rtt_fid'],
			"prefix" => 0,
			"subject" => "Reported Post By " . $mybb->user['username'],
			"icon" => 0,
			"uid" => $mybb->user['uid'],
			"username" => $mybb->user['username'],
			"message" => $post_data,
			"ipaddress" => get_ip(),
			"posthash" => md5($mybb->user['uid'] . random_str()),
		);

		$posthandler = new PostDataHandler("insert");
		$posthandler->action = "thread";
		$posthandler->set_data($new_thread);
		
		if($posthandler->validate_thread())
			$thread_info = $posthandler->insert_thread();
	}
}
?>