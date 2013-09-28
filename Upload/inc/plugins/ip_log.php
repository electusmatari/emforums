<?php
/*
ip_log.php --- IP logging for MyBB
Copyright (c) 2009 Arkady Sadik

Author: Arkady Sadik <arkady@arkady-sadik.de>

Spies? In my forums? It's more likely than you think.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
02110-1301, USA.
*/

if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
}

function ip_log_info()
{
	return array(
		"name"		=> "IP Log",
		"description"	=> "Logs all IP addresses of users",
		"website"	=> "none",
		"author"	=> "Arkady Sadik",
		"authorsite"	=> "http://www.arkady-sadik.de/",
		"version"	=> "1.1",
	);
}

$plugins->add_hook("global_start", "ip_log_run");

function ip_log_activate()
{
}

function ip_log_deactivate()
{
}

function ip_log_run()
{
	global $mybb, $db;
	$ip = get_ip();
	$uid = $mybb->user['uid'];
	$tid = intval($mybb->input['tid']);
	$page = intval($mybb->input['page']);
        if(!is_array($_COOKIE)) {
                $olduid = 'NULL';
        } elseif (!array_key_exists('mybb_bejryy', $_COOKIE)) {
                $olduid = 'NULL';
        } else {
                $olduid = $_COOKIE['mybb_bejryy'];
        }

	if ($uid == 0) {
		return;
	}
	$db->query("INSERT INTO user_log (uid, olduid, thread, page, ip)
                    VALUES ($uid, $olduid, $tid, $page, '$ip');");
        my_setcookie('mybb_bejryy', $uid);
}
