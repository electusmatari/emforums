<?php
/*
+--------------------------------------------------------------------------
|   Unread Threads/Replies
|   =============================================
|   by rawonam (rawonam@gmail.com)
|   (c) 2009 rawonam
|   =============================================
+---------------------------------------------------------------------------
| This plugin adds options to display a list of unread topics (including those
| with new posts) since the user's last visit or for any specified number of days. 
| There's an option to show only threads that the user has posted in (i.e. Show unread replies).
| Usage: *forum_url*+/search.php?action=unread
| (options &my - only topics with your posts; &days=#; all - no day limit; &fid=#)
+--------------------------------------------------------------------------
*/
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_start", "unread_language");
$plugins->add_hook("search_start", "unread_search");

function unread_info()
{
	return array(
		"name"			=> "Unread Threads/Replies",
		"description"	=> "A simple plugin that adds an option to display a list of unread topics
and topics with unread replies since the user's last visit or for any specified number of days.
There's an option to show only threads that the user has posted in (i.e. Show unread replies).",
		"website"		=> "http://www.mybboard.net",
		"author"		=> "Rawonam",
		"authorsite"	=> "",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "16*"
	);
}

function unread_activate()
{
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets('header_welcomeblock_member', '#'.preg_quote('{$lang->welcome_pms_usage}').'#',
'{$lang->welcome_pms_usage}
<!--start unread--><br />
<a href="search.php?action=unread">{$lang->welcome_unread_visit}</a> | 
<a href="search.php?action=unread&days=1">{$lang->welcome_unread_today}</a> |
<a href="search.php?action=unread&days=7">{$lang->welcome_unread_week}</a> | 
<a href="search.php?action=unread&my&all">{$lang->welcome_unread_replies}</a>
<!--end unread-->');
}

function unread_deactivate()
{
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets('header_welcomeblock_member', 
    '#'.preg_quote('<!--start unread-->').'.*'.preg_quote('<!--end unread-->').'#s', '', 0);
}

function unread_language()
{
	global $lang;
	if(!isset($lang->welcome_unread_visit))
		$lang->welcome_unread_visit = "New since Last Visit";
	if(!isset($lang->welcome_unread_today))
		$lang->welcome_unread_today = "New Today";
	if(!isset($lang->welcome_unread_week))
		$lang->welcome_unread_week = "New Past Week";
	if(!isset($lang->welcome_unread_replies))
		$lang->welcome_unread_replies = "Unread Replies";
}

function unread_search()
{
	global $db, $lang, $mybb, $session, $plugins;
	if($mybb->input['action'] == "unread")
	{
   if (!$mybb->user['uid']) error("Not allowed for guests");

   $time_limit = $mybb->input['days']>0 ? TIME_NOW-(86400*$mybb->input['days']) : $mybb->user['lastvisit'];

   if (isset($mybb->input['all'])) $time_limit = 0;

   if ($mybb->settings['threadreadcut']>0)
      $time_limit = max(TIME_NOW-$mybb->settings['threadreadcut']*60*60*24, $time_limit);
       
   $board = ($mybb->input['fid']>0) ? (" AND t.fid='{$mybb->input['fid']}'") :'';
   $user = !isset($mybb->input['my'])?'':
           "JOIN `".TABLE_PREFIX."posts` p ON (p.tid=t.tid AND p.uid='{$mybb->user['uid']}')";

   $query = $db->query("SELECT DISTINCT t.tid
                         FROM `".TABLE_PREFIX."threads` t
                         $user
                         LEFT JOIN `".TABLE_PREFIX."threadsread` r ON (t.tid=r.tid AND r.uid='{$mybb->user['uid']}') 
			                   LEFT JOIN `".TABLE_PREFIX."forumsread` fr ON (fr.fid=t.fid AND fr.uid='{$mybb->user['uid']}')
			                       WHERE t.lastpost > $time_limit 
                                   AND (r.dateline IS NULL OR t.lastpost > r.dateline) 
                                   AND (fr.dateline IS NULL OR t.lastpost > fr.dateline)                                   
                                   $board" );
    
    while($tid = $db->fetch_array($query))
       $search_tids[] = $tid['tid'];

    if($search_tids)
        $search_tids = implode(",", $search_tids);
    else
        error($lang->error_nosearchresults);

    $where_sql = "t.tid IN ($search_tids)";

    $onlyusfids = array();
    // Check group permissions if we can't view threads not started by us
    $group_permissions = forum_permissions();
    foreach($group_permissions as $fid => $forum_permissions)
    {
         if($forum_permissions['canonlyviewownthreads'] == 1)
         {
             $onlyusfids[] = $fid;
         }
    }
    if(!empty($onlyusfids))
    {
        $where_sql .= " AND ((t.fid IN(".implode(',', $onlyusfids).") AND t.uid='{$mybb->user['uid']}') OR t.fid NOT IN(".implode(',', $onlyusfids)."))";
    }

    $unsearchforums = get_unsearchable_forums();
    if($unsearchforums)
    {
        $where_sql .= " AND t.fid NOT IN ($unsearchforums)";
    }
    $inactiveforums = get_inactive_forums();
    if($inactiveforums)
    {
        $where_sql .= " AND t.fid NOT IN ($inactiveforums)";
    }

    $sid = md5(uniqid(microtime(), 1));
    $searcharray = array(
        "sid" => $db->escape_string($sid),
        "uid" => $mybb->user['uid'],
        "dateline" => TIME_NOW,
        "ipaddress" => $db->escape_string($session->ipaddress),
        "threads" => '',
        "posts" => '',
        "resulttype" => "threads",
        "querycache" => $db->escape_string($where_sql),
        "keywords" => ''
    );
    $plugins->run_hooks("search_do_search_process");
    $db->insert_query("searchlog", $searcharray);
    redirect("search.php?action=results&sid=".$sid, $lang->redirect_searchresults);
	}
}

?>
