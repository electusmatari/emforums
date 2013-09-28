<?php
/*
operations_notify.php --- Operations Notify for MyBB
Copyright (c) 2009, 2010, 2011 Arkady Sadik

Author: Arkady Sadik <arkady@arkady-sadik.de>

Idea taken from Calendar Warner by Online Urbanus

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

function operations_notify_info()
{
    return array("name"        => "Operations Notify",
                 "description" => "Notifies of ongoing events from an operations forum",
                 "website"     => "none",
                 "author"      => "Arkady Sadik",
                 "authorsite"  => "http://www.arkady-sadik.de/",
                 "version"     => "1.5",
           );
}

$plugins->add_hook("pre_output_page", "operations_notify_run");

function operations_notify_activate()
{
}

function operations_notify_deactivate()
{
}

function operations_notify_run($page)
{
    global $mybb, $db;

    $wanted_fids = array();
    $operation_fids = array(111, 144, 145, 149);
    $fid_suffix = array(144 => ' <span class="grdforum">GRD</span>',
                        145 => ' <span class="grdforum">LUTI</span>',
                        149 => ' <span class="grdforum">Allies</span>');
    foreach ($operation_fids as $fid)
    {
        $fpermissions = forum_permissions($fid);
        if($fpermissions['canview'] == 1)
        {
            $wanted_fids[] = $fid;
        }
    }
    if (count($wanted_fids) == 0)
    {
        return $page;
    }

    $expander_begin_end = opnotify_expander();
    $expander = $expander_begin_end[0];
    $begin = $expander_begin_end[1];
    $end = $expander_begin_end[2];

    $html = ('<table border="0" cellspacing="1" cellpadding="4" class="tborder"><tr><td class="thead">' .
             '<span class="smalltext"><strong><span id="opcurrenttime"></span>Upcoming Operations</strong> '.
             $expander);

    $html .= '<span id="opnotifications" style="float: right"></span>';

    $html .= '</td></tr>';

    $html .= opnotify_upcoming_operations($wanted_fids, $fid_suffix,
                                          $begin, $end);

    $page = str_replace("<div id=\"content\">",
                        "<div id=\"content\">" . $html,
                        $page);
    return $page;
}

function opnotify_upcoming_operations ($wanted_fids, $fid_suffix,
                                       $begin, $end) {
    global $mybb, $db;

    $html = "";
    $query = $db->query("
SELECT fid, subject, tid, prefix
FROM ".TABLE_PREFIX."threads
WHERE fid IN (" . join(", ", $wanted_fids) . ")
  AND sticky = 0
  AND subject REGEXP '[0-9][0-9][0-9]\\.[0-9][0-9]\\.[0-9][0-9]'
ORDER BY subject ASC
;
");
    while ($thread = $db->fetch_array($query))
    {
        if (preg_match("/^([0-9][0-9][0-9])\\.([0-9][0-9])\\.([0-9][0-9]).(.*)/",
                       $thread['subject'],
                       $matches))
        {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            $shorttitle = $matches[4];
            if (preg_match("/.*([0-9][0-9]):([0-9][0-9]).*/",
                           $shorttitle,
                           $matches))
            {
                $hour = $matches[1];
                $minute = $matches[2];
            }
            else
            {
                $hour = 23;
                $minute = 59;
            }

            $timestamp = mktime($hour, $minute, 0, $month, $day,
                                $year + 1898);
            if ($timestamp > $begin && $timestamp < $end)
            {
                if ($thread['prefix'] == 9)
                {
                    $style = " style=\"color: #FF0000; font-weight: bold;\"";
		}
                else
                {
                    $style = "";
                }
                if ($mybb->input['opexpand'])
                {
                    $title = ($year .
                              "." . $month .
                              "." . $day .
                              " " . $shorttitle);
                }
                else
                {
                    $title = $shorttitle;
                }
                if (array_key_exists($thread["fid"], $fid_suffix))
                {
                    $title .= $fid_suffix[$thread["fid"]];
                }
                $html .= ('<tr><td class="trow1"><small><a href="'
                          . get_thread_link($thread['tid'])
                          . "\"$style>"
                          . $title
                          . "</a></small></td></tr>");
            }
        }
    }
    $html .= "</table><br />";
    return $html;
}

function opnotify_expander () {
    global $mybb, $db;

    $now = time();
    $begin = $now - (1 * 60 * 60);
    $opexpand = $mybb->input['opexpand'];
    if ($opexpand)
    {
        $end = $now + (24 * 60 * 60 * 365);
        $location = get_current_location();
        $location = str_replace('?opexpand=true', '', $location);
        $location = str_replace('&amp;opexpand=true', '', $location);
        $expander = '<a href="'.$location.'">(fold)</a>';
    }
    else
    {
        $end = $now + (2 * 24 * 60 * 60);
        $location = get_current_location();
        $pos = strpos($location, "?");
        if($pos === false)
        {
            $location .= "?opexpand=true";
        }
        elseif ($pos == strlen($location) - 1)
        {
            $location .= "opexpand=true";
        }
        else
        {
            $location .= "&amp;opexpand=true";
        }
        $expander = '<a href="'.$location.'">(expand)</a>';
    }

    return array($expander, $begin, $end);
}
?>
