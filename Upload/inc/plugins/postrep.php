<?php

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook("postbit", "postrep");

function postrep_info()
{
	return array(
		'name'			=>	'اعتبارات داده شده برای پست',
		'description'	=>	'نمایش افرادی که برای هر پستی اعتبار داده اند در پست بیت.<br /><a href="http://www.mybbiran.com">MyBBIran.com</a>',
		'website'		=>	'http://www.mybbiran.com',
		'author'		=>	'Ahmad Badkoubehei',
		'authorsite'	=>	'http://www.aucs.ir',
		'version'		=>	'1.0.1',
        'compatibility' =>	'16*'
	);
}

function postrep_activate()
{
	global $db;
	
	//Adding templates
	require MYBB_ROOT."inc/adminfunctions_templates.php";

	$templatearray = array(
		'title' => 'postrep_postbit_outline',
		'template' => "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" style=\"{\$display_style};margin-top:5px;\"><tr><td>
		<table border=\"0\" cellspacing=\"{\$theme[\'borderwidth\']}\" cellpadding=\"{\$theme[\'tablespace\']}\" class=\"tborder\"><tr class=\"trow1\"><td valign=\"top\" width=\"1%\" nowrap=\"nowrap\"><img src=\"{\$mybb->settings[\'bburl\']}/images/rep.gif\" align=\"absmiddle\" /> &nbsp;<span class=\"smalltext\">{\$lang->postrep_repby}</span></td><td class=\"trow2\">\$entries</td></tr></table>
		</td></tr></table>",
		'sid' => '-1',
		);
	$db->insert_query("templates", $templatearray);
	
	$templatearray = array(
		'title' => 'postrep_postbit_inline',
		'template' => "<tr class=\"trow1\"><td><img src=\"{\$mybb->settings[\'bburl\']}/images/rep.gif\" align=\"absmiddle\" /> &nbsp;<span class=\"smalltext\">{\$lang->postrep_repby}</span>&nbsp;<span>\$entries</span></td></tr>",
		'sid' => '-1',
		);	
	$db->insert_query("templates", $templatearray);
	
	$templatearray = array(
		'title' => 'postrep_postbit_inline_classic',
		'template' => "<tr class=\"trow1\"><td><img src=\"{\$mybb->settings[\'bburl\']}/images/rep.gif\" align=\"absmiddle\" /> &nbsp;<span class=\"smalltext\">{\$lang->postrep_repby}</span></td><td><span>\$entries</span></td></tr>",
		'sid' => '-1',
		);	
	$db->insert_query("templates", $templatearray);
	if(!find_replace_templatesets("postbit", '#'.preg_quote('{$seperator}').'#', '{$post[\'postrep_inline\']}{$seperator}{$post[\'postrep_outline\']}'))
	{
		find_replace_templatesets("postbit", '#button_delete_pm(.*)<\/tr>(.*)<\/table>#is', 'button_delete_pm$1</tr>{\$post[\'postrep_inline\']}$2</table>{$post[\'postrep_outline\']}');
	}
	find_replace_templatesets("postbit_classic", '#button_delete_pm(.*)<\/tr>(.*)<\/table>#is', 'button_delete_pm$1</tr>{\$post[\'postrep_inline\']}$2</table>{$post[\'postrep_outline\']}');
	
	$rep_group = array(
		"name"			=> "Postrep",
		"title"			=> "اعتبارات داده شده برای پست",
		"description"	=> "تنظيمات پلاگين نمايش اعتبارات داده شده براي هر پست .",
		"disporder"		=> "0",
		"isdefault"		=> "1"
	);
	$db->insert_query("settinggroups", $rep_group);
	$gid = $db->insert_id();
	
	$rep[] = array(
		"name"			=> "postrep_outline",
		"title"			=> "نمايش در جدول مجزا",
		"description"	=> "نمايش اعتبارات داده شده در يک جدول مجزا بين دو پست .",
		"optionscode"	=> "onoff",
		"value"			=> '1',
		"disporder"		=> '1',
		"gid"			=> intval($gid),
	);
	
	$rep[] = array(
		"name"			=> "postrep_negative",
		"title"			=> "نمايش اعتبارات منفي",
		"description"	=> "آيا مي خواهيد که اعتبارات منفي که براي پست داده شده را نمايش دهيد ؟",
		"optionscode"	=> "onoff",
		"value"			=> '0',
		"disporder"		=> '2',
		"gid"			=> intval($gid),
	);
	
	$rep[] = array(
		"name"			=> "postrep_neutral",
		"title"			=> "نمايش اعتبارات خنثي",
		"description"	=> "آيا مي  خواهيد که اعتبارات خنثي را نمايش دهيد ؟",
		"optionscode"	=> "onoff",
		"value"			=> '0',
		"disporder"		=> '3',
		"gid"			=> intval($gid),
	);

	$rep[] = array(
		"name"			=> "postrep_reputation",
		"title"			=> "نمايش میزان اعتبار داده شده توسط هر کاربر",
		"description"	=> "آيا مي  خواهيد که میزان اعتبار داده شده توسط هر کاربر برای پست نمایش داده شود ؟",
		"optionscode"	=> "onoff",
		"value"			=> '1',
		"disporder"		=> '4',
		"gid"			=> intval($gid),
	);	
	foreach($rep as $t)
	{
		$db->insert_query("settings", $t);
	}
	
	rebuild_settings();
}


function postrep_deactivate()
{
	global $db;
	require '../inc/adminfunctions_templates.php';
	
	find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'postrep_outline\']}').'#', '', 0);
	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'postrep_outline\']}').'#', '', 0);
	find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'postrep_inline\']}').'#', '', 0);
	find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'postrep_inline\']}').'#', '', 0);
	
	$db->delete_query("templates", "title='postrep_postbit_inline'");
	$db->delete_query("templates", "title='postrep_postbit_inline_classic'");
	$db->delete_query("templates", "title='postrep_postbit_outline'");
	
	$db->delete_query("settings", "name IN ('postrep_outline' , 'postrep_negative' , 'postrep_neutral' , 'postrep_reputation')");
	$db->delete_query("settinggroups", "name='Postrep'");
	
	rebuild_settings();
}

function postrep(&$post) 
{
	global $db, $mybb, $lang ,$session, $theme, $altbg, $templates;
	
	if(!empty($session->is_spider))
	{
		return false;
	}
		
	$lang->load("postrep");
	
	$b=0;
	$entries = build_postrep($post['pid'], $b);

	$playout = $mybb->settings['postlayout'];
	if(!$entries)
		return 0;
	
	if(!$mybb->settings['postrep_outline'])
	{									
		if($playout == "classic")
		{
			eval("\$post['postrep_inline'] .= \"".$templates->get("postrep_postbit_inline_classic")."\";");
		}
		else
		{
			eval("\$post['postrep_inline'] .= \"".$templates->get("postrep_postbit_inline")."\";");
		}
	}
	else
	{	
		eval("\$post['postrep_outline'] .= \"".$templates->get("postrep_postbit_outline")."\";");
	}
}

function build_postrep($pid, &$is_rep)
{
	global $db, $mybb, $lang, $rep_cache;
	$is_rep = 0;
	
	$pid = intval($pid);
	
	if(file_exists($lang->path."/".$lang->language."/postrep.lang.php"))
	{
		$lang->load("postrep");
	}
	else
	{
		$l=$lang->language;
		$lang->set_language();
		$lang->load("postrep");
		$lang->set_language($l);
	}
	$dir = $lang->postrep_dir;
	
	$query=$db->query("SELECT rep.uid, rep.adduid, rep.pid, rep.dateline, rep.reputation, u.username, u.usergroup, u.displaygroup 
		FROM ".TABLE_PREFIX."reputation rep 
		JOIN ".TABLE_PREFIX."users u 
		ON rep.adduid=u.uid 
		WHERE rep.pid='$pid' 
		ORDER BY rep.dateline ASC" 
	);

	while($record = $db->fetch_array($query))
	{
		if(($record['reputation']<0 && !$mybb->settings['postrep_negative']) || ($record['reputation']==0 && !$mybb->settings['postrep_neutral']))
			continue;
		if($record['adduid'] == $mybb->user['uid'])
		{
			$is_rep++;
		}
		$date = my_date($mybb->settings['dateformat'].' '.$mybb->settings['timeformat'], $record['dateline']);
		if(!isset($rep_cache['showname'][$record['username']]))
		{
			$url = get_profile_link($record['adduid']);
			$name = format_name($record['username'], $record['usergroup'], $record['displaygroup']);
			$rep_cache['showname'][$record['username']] = "<a href=\"$url\" dir=\"$dir\">$name</a>";
		}
		$val="";
		
		if($mybb->settings['postrep_reputation']){
			if($record['reputation']>0)
				$val="<span class=\"reputation_positive\">(+".$record['reputation'].")</span>";
			else if($record['reputation']<0){
				if(!$mybb->settings['postrep_negative'])
					continue;
				else
					$val="<span class=\"reputation_negative\">(".$record['reputation'].")</span>";
			}
			else{
				if(!$mybb->settings['postrep_neutral'])
					continue;
				else
					$val="<span class=\"reputation_neutral\">(0)</span>";
			}
		}
		
		$entries .= $r1comma." <span title=\"".$date."\">".$rep_cache['showname'][$record['username']]."</span>".$val;
		
		$r1comma = $lang->postrep_comma;
	}
	
	return $entries;
}
?>