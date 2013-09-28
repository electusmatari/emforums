<?php
// Convo Mycode Plugin
// By Arkady Sadik http://www.arkady-sadik.de/
// Version 1.0

$plugins->add_hook("parse_message", "convo_run");

function convo_info()
{
    return array(
        "name"        => "Convo BBCode",
        "description" => "EVE Convo BBCode",
        "website"     => "http://www.arkady-sadik.de/",
        "author"      => "Arkady Sadik",
        "authorsite"  => "http://www.arkady-sadik.de/",
        "version"     => "1.0",
	);
}

function convo_activate()
{
}

function convo_deactivate()
{
}

function convo_run($message)
{
    // preg_replace_callback errors out when stuff is too big. Wuzzy.
    $str = "";
    $pos = 0;
    while ($pos < strlen($message)) {
        $convo_start = strpos($message, "[convo]", $pos);
        if ($convo_start === false) {
            $str .= substr($message, $pos);
            return $str;
        }
        $convo_end = strpos($message, "[/convo]", $convo_start+7);
        if ($convo_end === false) {
            $str .= substr($message, $pos);
            return $str;
        }
        $str .= substr($message, $pos, ($convo_start-$pos));
        $str .= translate(substr($message, $convo_start+7, ($convo_end-($convo_start+7))));
        $pos = $convo_end + 8;
    }
    return $str;
}

function translate($str)
{
    $str = trim($str);
    // Timestamp
    $str = preg_replace("#^(?:...)?(\\[.*?\\]) #m",
                        "<font color=\"#A0A0A0\">$1</font> ",
                        $str);
   // Emotes
   $str = preg_replace("#(^|<font.*?</font> )([^< [].*?) &gt; /emote#m",
                       "$1<b>* $2</b> ",
                       $str);
   // Normal chat
   $str = preg_replace("#(^|<font.*?</font> )([^< [].*?) &gt; #m",
                       "$1<b>$2</b> &gt; ",
                       $str);
    // Links
    $str = preg_replace("#&lt;url=.*?&gt;(.*?)&lt;/url&gt;#",
                        "$1",
                        $str);
    // &lt;
    $str = preg_replace("#&amp;lt;#", "&lt;", $str);
    // i, b, u
    $str = preg_replace("#&lt;(/?[ibu])&gt;#", "<$1>", $str);
    // Zebra
    $str = preg_replace("#^(.*?)\n(.*)$#m",
                        "<div>$1</div>\n<div style=\"background: #E7E7E7;\">$2</div>",
                        $str);
    return "<div class=\"convo\">".$str."</div>";
}

?>
