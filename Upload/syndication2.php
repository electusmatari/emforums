<?php
/**
 * MyBB 1.4
 * Copyright © 2008 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/about/license
 */

define("IN_MYBB", 1);
define("IGNORE_CLEAN_VARS", "fid");
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'syndication.php');

require_once "./global.php";

// Load global language phrases
$lang->load("syndication");

// Load syndication class.
require_once MYBB_ROOT."inc/class_feedgeneration.php";
$feedgenerator = new FeedGenerator();

// Load the post parser
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Find out the post limit.
$post_limit = intval($mybb->input['limit']);
if($post_limit > 50)
{
	$post_limit = 50;
}
else if(!$post_limit || $post_limit < 0)
{
	$post_limit = 20;
}

// Syndicate a specific forum or all viewable?
if(isset($mybb->input['fid']))
{
	$forumlist = $mybb->input['fid'];
	$forumlist = explode(',', $forumlist);
}
else
{
	$forumlist = "";
}

// Get the forums the user is not allowed to see.
$unviewableforums = get_unviewable_forums(true);
$inactiveforums = get_inactive_forums();

$unviewable = '';

// If there are any, add SQL to exclude them.
if($unviewableforums)
{
	$unviewable .= " AND fid NOT IN($unviewableforums)";
}

if($inactiveforums)
{
	$unviewable .= " AND fid NOT IN($inactiveforums)";
}

// If there are no forums to syndicate, syndicate all viewable.
if(!empty($forumlist))
{
    $forum_ids = "'-1'";
    foreach($forumlist as $fid)
    {
        $forum_ids .= ",'".intval($fid)."'";
    }
    $forumlist = "AND fid IN ($forum_ids) $unviewable";
}
else
{
    $forumlist = $unviewable;
    $all_forums = 1;
}

// Find out which title to add to the feed.
$title = $mybb->settings['bbname'];
$query = $db->simple_select("forums", "name, fid, allowhtml, allowmycode, allowsmilies, allowimgcode", "1=1 ".$forumlist);
$comma = " - ";
while($forum = $db->fetch_array($query))
{
    $title .= $comma.$forum['name'];
    $forumcache[$forum['fid']] = $forum;
    $comma = ", ";
}

// If syndicating all forums then cut the title back to "All Forums"
if($all_forums)
{
    $title = $mybb->settings['bbname']." - ".$lang->all_forums;
}

// Set the feed type.
$feedgenerator->set_feed_format($mybb->input['type']);

// Set the channel header.
$channel = array(
    "title" => $title,
    "link" => $mybb->settings['bburl']."/",
    "date" => time(),
    "description" => $mybb->settings['bbname']." - ".$mybb->settings['bburl']
);
$feedgenerator->set_channel($channel);

    $query = $db->simple_select("posts", "subject, dateline, message, edittime, tid, fid, pid, username", "visible='1' ".$forumlist, array('order_by' => 'dateline', 'order_dir' => 'desc', 'limit' => $post_limit));
    while($post = $db->fetch_array($query))
    {
		$items[$post['tid']] = array(
			"title" => $post['subject'],
			"link" => $channel['link'].get_post_link($post['pid'], $post['tid'])."#pid".$post['pid'],
			"date" => $post['dateline'],
                        "author" => $post['username'],
		);
		$parser_options = array(
			"allow_html" => $forumcache[$post['fid']]['allowhtml'],
			"allow_mycode" => $forumcache[$post['fid']]['allowmycode'],
			"allow_smilies" => $forumcache[$post['fid']]['allowsmilies'],
			"allow_imgcode" => $forumcache[$post['fid']]['allowimgcode'],
			"filter_badwords" => 1
		);

        $items[$post['tid']]['description'] = $parser->parse_message($post['message'], $parser_options);
        $items[$post['tid']]['updated'] = $post['edittime'];
        $feedgenerator->add_item($items[$post['tid']]);
    }

// Then output the feed XML.
$feedgenerator->output_feed();
?>
