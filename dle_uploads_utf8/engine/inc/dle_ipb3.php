<?php

$licence = /*licadm*/'ozersk.ru'/*/licadm*/;
if(!defined('DATALIFEENGINE'))
{
  die("Hacking attempt!");
}
//if($member_db[1] == 1 and $action == "dologin"){ header("Location: $config_http_script_dir/index.php?mod=options&action=personal"); exit; }

if (file_exists(ENGINE_DIR.'/data/dle_ipb_conf.php')) require_once (ENGINE_DIR.'/data/dle_ipb_conf.php'); else $dle_ipb_conf = array() ;
//require_once ENGINE_DIR.'/inc/functions.inc.php';
require_once(ROOT_DIR.'/language/'.$config['langs'].'/dle_ipb.lng');

function showRow($title="", $description="", $field="")
  {
        echo"<tr>
        <td style=\"padding:4px\" class=\"option\">
        <b>$title</b><br /><span class=small>$description</span>
        <td width=394 align=middle >
        $field
        </tr><tr><td background=\"engine/skins/images/mline.gif\" height=1 colspan=2></td></tr>";
        $bg = ""; $i++;
  }
  
  
 
function makeDropDown($options, $name, $selected)
    {
        $output = "<select onclick=\"freeze(0)\" name=\"$name\">\r\n";
        foreach($options as $value=>$description)
        {
          $output .= "<option value=\"$value\"";
          if($selected == $value){ $output .= " selected "; }
          $output .= ">$description</option>\n";
        }
        $output .= "</select>";
        return $output;
    }

if (!preg_match("#" . $licence . "#i", $_SERVER['HTTP_HOST']) && 
    !preg_match('#localhost#', $_SERVER['HTTP_HOST']) &&
    strpos($_SERVER['HTTP_HOST'], $_SERVER['SERVER_ADDR']) === false
     )
{

	dle_ipb_msg("error", $lang_dle_ipb['error_lic'], $lang_dle_ipb['lic_text']);
	exit; 
}
    
function echomenu ($image, $header_text, $p = 0)
{
global $lang_dle_ipb;

echoheader ($image, $header_text, $p);
	
echo <<<HTML
<script  language='JavaScript' type="text/javascript">
function showmenu(obj)
{ 
	document.getElementById('settings').style.display = "none";
	document.getElementById('block_new').style.display = "none";
	document.getElementById('block_birth').style.display = "none";
	document.getElementById('block_online').style.display = "none";
	document.getElementById('stats').style.display = "none";
	document.getElementById('link').style.display = "none";
	document.getElementById(obj).style.display=''; 
} 
</script>
<div style="padding-top:5px;padding-bottom:2px;">
<table width="100%" style="text-align:center">
    <tr>
        <td width="4"><img src="engine/skins/images/tl_lo.gif" width="4" height="4" border="0"></td>
        <td background="engine/skins/images/tl_oo.gif"><img src="engine/skins/images/tl_oo.gif" width="1" height="4" border="0"></td>
        <td width="6"><img src="engine/skins/images/tl_ro.gif" width="6" height="4" border="0"></td>
    </tr>
    <tr>
        <td background="engine/skins/images/tl_lb.gif"><img src="engine/skins/images/tl_lb.gif" width="4" height="1" border="0"></td>
        <td style="padding:5px;" bgcolor="#FFFFFF">
<table width="100%">
	<tr>
		<td style="text-align:center"><a href="javascript:showmenu('block_new');" title='{$lang_dle_ipb['block_new']}'><img src="engine/skins/images/block_new.jpg" border="0" /></a></td>
		<td style="text-align:center"><a href="javascript:showmenu('block_birth');" title='{$lang_dle_ipb['block_birth']}'><img src="engine/skins/images/block_birth.jpg" border="0" /></a></td>
		<td style="text-align:center"><a href="javascript:showmenu('block_online');" title='{$lang_dle_ipb['block_online']}'><img src="engine/skins/images/block_online.jpg" border="0" /></a></td>
		<td style="text-align:center"><a href="javascript:showmenu('link');" title='{$lang_dle_ipb['link']}'><img src="engine/skins/images/link.jpg" border="0" /></a></td>
		<td style="text-align:center"><a class=main href="javascript:showmenu('settings');" title='{$lang_dle_ipb['settings']}' ><img src="engine/skins/images/settings.jpg" border="0" /></a></td>
	</tr>
</table>
</td>
        <td background="engine/skins/images/tl_rb.gif"><img src="engine/skins/images/tl_rb.gif" width="6" height="1" border="0"></td>
    </tr>

    <tr>
        <td><img src="engine/skins/images/tl_lu.gif" width="4" height="6" border="0"></td>
        <td background="engine/skins/images/tl_ub.gif"><img src="engine/skins/images/tl_ub.gif" width="1" height="6" border="0"></td>
        <td><img src="engine/skins/images/tl_ru.gif" width="6" height="6" border="0"></td>
    </tr>
    </table>
</div>
		
HTML;
}

function footer_dle_ipb () {

	global $dle_ipb_conf;
	
	$year = date("Y");
	
	echo <<<HTML
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px; text-align:center"><div class="navigation">Copyright © $year created by <a href="mailto:kaliostro.Denis@Gmail.com" style="text-decoration:underline;color:green">kaliostro</a>.</div></td>
    </tr>
</table>
HTML;
	
	echofooter();
}


function dle_ipb_msg($type, $title, $text, $back=FALSE){
global $lang, $lang_dle_ipb;

  if($back){
        $back = "<br /><br> <a class=main href=\"$back\">$lang[func_msg]</a>";
  }

  echoheader($type, $title);

echo <<<HTML
<div style="padding-top:5px;padding-bottom:2px;">
<table width="100%">
    <tr>
        <td width="4"><img src="engine/skins/images/tl_lo.gif" width="4" height="4" border="0"></td>
        <td background="engine/skins/images/tl_oo.gif"><img src="engine/skins/images/tl_oo.gif" width="1" height="4" border="0"></td>
        <td width="6"><img src="engine/skins/images/tl_ro.gif" width="6" height="4" border="0"></td>
    </tr>
    <tr>
        <td background="engine/skins/images/tl_lb.gif"><img src="engine/skins/images/tl_lb.gif" width="4" height="1" border="0"></td>
        <td style="padding:5px;" bgcolor="#FFFFFF">
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$title}</div></td>
    </tr>
</table>
<div class="unterline"></div>
<table width="100%">
    <tr>
        <td height="100" align="center">{$text} {$back}</td>
    </tr>
</table>
</td>
        <td background="engine/skins/images/tl_rb.gif"><img src="engine/skins/images/tl_rb.gif" width="6" height="1" border="0"></td>
    </tr>
    <tr>
        <td><img src="engine/skins/images/tl_lu.gif" width="4" height="6" border="0"></td>
        <td background="engine/skins/images/tl_ub.gif"><img src="engine/skins/images/tl_ub.gif" width="1" height="6" border="0"></td>
        <td><img src="engine/skins/images/tl_ru.gif" width="6" height="6" border="0"></td>
    </tr>
</table>
</div>
HTML;

  footer_dle_ipb();
exit();
}


  
if ($action == "save") {
	
    $save_con = $_POST['save_con'];
	$save_con['version_id'] = "1.0.0";
    
    if ($config['version_id'] < 7.5)
	{
    	if($member_db[1] != 1){ msg("error", $lang['opt_denied'], $lang['opt_denied']); }
	}
    else 
    {
    	if($member_id['user_group'] != 1){ msg("error", $lang['opt_denied'], $lang['opt_denied']); }
    }
    $handler = fopen(ENGINE_DIR.'/data/dle_ipb_conf.php', "w");
    fwrite($handler, "<?PHP \n\n//DLE + Invision Pover Board 3 Configurations\n\n\$dle_ipb_conf = array (\n\n");
    
    function save_conf($save_con, $array=false) 
    {
    	global $handler, $find, $replace;
    	
        foreach($save_con as $name => $value)
        {
            if (is_array($value)) { fwrite($handler, "'{$name}' => array (\n\n"); save_conf($value, true);} else {
        $value = strtr($value, '"', "'");

        fwrite($handler, "'{$name}' => \"".stripslashes($value)."\",\n\n"); }
        }
        if ($array) fwrite($handler, "),\n\n");
    }
    
    save_conf($save_con);
    fwrite($handler, ");\n\n?>");
    fclose($handler);

    
    dle_ipb_msg("info", $lang['opt_sysok'], $lang['opt_sysok_1'], $PHP_SELF."?mod=dle_ipb3");
}

if (!$action) {
	
	echomenu("options", $lang_dle_ipb['settings'], '');
	
	
echo <<<HTML
<form action="" method="post" name="form">
<div style="padding-top:5px;padding-bottom:2px;">
<table width="100%">
    <tr>
        <td width="4"><img src="engine/skins/images/tl_lo.gif" width="4" height="4" border="0"></td>
        <td background="engine/skins/images/tl_oo.gif"><img src="engine/skins/images/tl_oo.gif" width="1" height="4" border="0"></td>
        <td width="6"><img src="engine/skins/images/tl_ro.gif" width="6" height="4" border="0"></td>
    </tr>
    <tr>
        <td background="engine/skins/images/tl_lb.gif"><img src="engine/skins/images/tl_lb.gif" width="4" height="1" border="0"></td>
        <td style="padding:5px;" bgcolor="#FFFFFF">
<table width="100%">
HTML;
	
if ($work_date)
{
	$exp_module = <<<HTML
				    <tr>
				        <td style="padding:2px;">{$lang_dle_ipb['work_date']}</td>
				        <td><font style="color:red" >$work_date</font></td>
				    </tr>
HTML;
}

$lic = str_replace("\\", "", $licence);
echo <<<HTML
<tr id="stats" style=''><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['stat_all']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
    <tr>
        <td width="265" style="padding:2px;">{$lang_dle_ipb['version']}</td>
        <td>{$dle_ipb_conf['version_id']}</td>
    </tr>
    <tr>
        <td style="padding:2px;">{$lang_dle_ipb['module_reg']}</td>
        <td><b>{$lic}</b></td>
    </tr>
    $exp_module
</table></td></tr>
HTML;

echo <<<HTML
<tr id="block_new" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['block_new']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
HTML;
	
	showRow($lang_dle_ipb['allow_forum_block'], $lang_dle_ipb['allow_forum_block_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_forum_block]", "{$dle_ipb_conf['allow_forum_block']}"));
	showRow($lang_dle_ipb['forum_block_alt_url'], $lang_dle_ipb['forum_block_alt_url_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[forum_block_alt_url]", "{$dle_ipb_conf['forum_block_alt_url']}"));
	showRow($lang_dle_ipb['count_post'], $lang_dle_ipb['count_post_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[count_post]' value='{$dle_ipb_conf['count_post']}' size=10 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['leght_name'], $lang_dle_ipb['leght_name_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[leght_name]' value='{$dle_ipb_conf['leght_name']}' size=10 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['cache_time'], $lang_dle_ipb['cache_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[block_new_cache_time]' value='{$dle_ipb_conf['block_new_cache_time']}' size=10 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['bad_forum_for_block'], $lang_dle_ipb['bad_forum_for_block_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[bad_forum_for_block]' value='{$dle_ipb_conf['bad_forum_for_block']}' size=10 onclick=\"freeze(1)\" id=\"bad\">");
	showRow($lang_dle_ipb['good_forum_for_block'], $lang_dle_ipb['good_forum_for_block_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[good_forum_for_block]' value='{$dle_ipb_conf['good_forum_for_block']}' size=10 onclick=\"freeze(2)\" id=\"good\">");
	
	echo "</table></td></tr>";
	
echo <<<HTML
<tr id="block_birth" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['block_birth']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
HTML;

	showRow($lang_dle_ipb['allow_birthday_block'], $lang_dle_ipb['allow_birthday_block_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_birthday_block]", "{$dle_ipb_conf['allow_birthday_block']}"));
	showRow($lang_dle_ipb['cache_time'], $lang_dle_ipb['cache_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[birthday_cache_time]' value='{$dle_ipb_conf['birthday_cache_time']}' size=10>");
	showRow($lang_dle_ipb['count_birthday'], $lang_dle_ipb['count_birthday_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[count_birthday]' value='{$dle_ipb_conf['count_birthday']}' size=10 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['no_user_birthday'], $lang_dle_ipb['no_user_birthday_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[no_user_birthday]' value='".stripslashes($dle_ipb_conf['no_user_birthday'])."' size=30 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['spacer'], $lang_dle_ipb['spacer_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[spacer]' value='{$dle_ipb_conf['spacer']}' size=10 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['birthday_block'], $lang_dle_ipb['birthday_block_desc'], "<textarea onclick=\"freeze(0)\" cols=\"50\" rows=\"6\" name='save_con[birthday_block]'>".stripslashes($dle_ipb_conf['birthday_block'])."</textarea>");
	
	echo "</table></td></tr>";
	
echo <<<HTML
<tr id="block_online" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['block_online']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
HTML;

	showRow($lang_dle_ipb['allow_online_block'], $lang_dle_ipb['allow_online_block_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_online_block]", "{$dle_ipb_conf['allow_online_block']}"));
	showRow($lang_dle_ipb['cache_time'], $lang_dle_ipb['cache_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[block_online_cache_time]' value='{$dle_ipb_conf['block_online_cache_time']}' size=10>");
	showRow($lang_dle_ipb['online_time'], $lang_dle_ipb['online_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[online_time]' value='{$dle_ipb_conf['online_time']}' size=10 >");
	showRow($lang_dle_ipb['separator'], $lang_dle_ipb['separator_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[separator]' value='{$dle_ipb_conf['separator']}' size=10 >");
	echo "</table></td></tr>";
	
	
echo <<<HTML
<tr id="link" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['link']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
HTML;
$link ="<table><tr><td>{$lang_dle_ipb['category']}</td><td>{$lang_dle_ipb['forums']}</td></tr>";
function DisplayCategories($parentid = 0, $sublevelmarker = '', $link='')
{ global $db, $config, $link, $dle_ipb_conf;

  if($parentid != 0)
  {
    $sublevelmarker .= '--';
  }

  $getcategories = $db->query("SELECT * FROM " . PREFIX . "_category WHERE parentid = '$parentid' ORDER BY posi ASC");

 
  while($row = $db->get_row($getcategories))
  {

		$link .= "<tr><td style=\"padding-right:3px;\">".$sublevelmarker."<a class=\"list\" href=\"{$config['http_home_url']}index.php?do=cat&category=".$row['alt_name']."\" target=\"_blank\">".stripslashes($row['name'])."</a></td><td><input class=edit type=text style=\"text-align: center;\" name='save_con[forumid][{$row['id']}]' value='{$dle_ipb_conf['forumid'][$row['id']]}' size=10></td></tr><tr><td background=\"engine/skins/images/mline.gif\" height=1 colspan=2></td></tr>";

       DisplayCategories($row['id'], $sublevelmarker, $link);
  }
  
}
DisplayCategories();

	showRow($lang_dle_ipb['goforum'], $lang_dle_ipb['goforum_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[goforum]", "{$dle_ipb_conf['goforum']}"));
	showRow($lang_dle_ipb['link_title'], $lang_dle_ipb['link_title_desc'], makeDropDown(array("old"=>$lang_dle_ipb['old_title'],"title"=>$lang_dle_ipb['title']), "save_con[link_title]", "{$dle_ipb_conf['link_title']}"));
	showRow($lang_dle_ipb['link_text'], $lang_dle_ipb['link_text_desc'], makeDropDown(array("full"=>$lang_dle_ipb['full_text'],"short"=>$lang_dle_ipb['short_text'],"old"=>$lang_dle_ipb['old_text']), "save_con[link_text]", "{$dle_ipb_conf['link_text']}"));
	showRow($lang_dle_ipb['link_on_news'], $lang_dle_ipb['link_on_news_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[link_on_news]", "{$dle_ipb_conf['link_on_news']}"));
	showRow($lang_dle_ipb['link_user'], $lang_dle_ipb['link_user_desc'], makeDropDown(array("old"=>$lang_dle_ipb['old_user'],"author"=>$lang_dle_ipb['author'],"cur_user"=>$lang_dle_ipb['cur_user']), "save_con[link_user]", "{$dle_ipb_conf['link_user']}"));
	showRow($lang_dle_ipb['show_no_reginstred'], $lang_dle_ipb['show_no_reginstred_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[show_no_reginstred]", "{$dle_ipb_conf['show_no_reginstred']}"));
	showRow($lang_dle_ipb['show_short'], $lang_dle_ipb['show_short_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[show_short]", "{$dle_ipb_conf['show_short']}"));
	showRow($lang_dle_ipb['allow_count_short'], $lang_dle_ipb['allow_count_short_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_count_short]", "{$dle_ipb_conf['allow_count_short']}"));
	showRow($lang_dle_ipb['show_count'], $lang_dle_ipb['show_count_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[show_count]", "{$dle_ipb_conf['show_count']}"));
	showRow($lang_dle_ipb['name_post_on_forum'], $lang_dle_ipb['name_post_on_forum_desc'], "<textarea onclick=\"freeze(0)\" cols=\"50\" rows=\"3\" name='save_con[name_post_on_forum]'>".stripslashes($dle_ipb_conf['name_post_on_forum'])."</textarea>");
	showRow($lang_dle_ipb['text_post_on_forum'], $lang_dle_ipb['text_post_on_forum_desc'], "<textarea onclick=\"freeze(0)\" cols=\"50\" rows=\"4\" name='save_con[text_post_on_forum]'>".stripslashes($dle_ipb_conf['text_post_on_forum'])."</textarea>");
	showRow($lang_dle_ipb['link_on_forum'], $lang_dle_ipb['link_on_forum_desc'], "<textarea onclick=\"freeze(0)\" cols=\"50\" rows=\"4\" name='save_con[link_on_forum]'>".stripslashes($dle_ipb_conf['link_on_forum'])."</textarea>");
	showRow($lang_dle_ipb['postuserid'], $lang_dle_ipb['postuserid_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[postuserid]' value='".intval($dle_ipb_conf['postuserid'])."' size=8 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['postusername'], $lang_dle_ipb['postusername_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[postusername]' value='".stripslashes($dle_ipb_conf['postusername'])."' size=50 onclick=\"freeze(0)\">");
	showRow($lang_dle_ipb['forumid'], $lang_dle_ipb['forumid_desc'], $link."</table>");

echo "</table></td></tr>";

echo <<<HTML
<tr id="settings" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['settings']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
	<script language="JavaScript">
	<!--
	
	function freeze(value)
	{
	
	if (value == 1)
		{ 
		document.getElementById('bad').disabled = false
		document.getElementById('good').value = ""
		document.getElementById('good').disabled = true
		}
	
	if (value == 2)
		{ 
		document.getElementById('good').disabled = false
		document.getElementById('bad').value = ""
		document.getElementById('bad').disabled = true
		}
		
	if (value == 0)
		{ 
		document.getElementById('good').disabled = false
		document.getElementById('bad').disabled = false
		}
	}
	//-->
	</script>
HTML;

	$names_array = array("0"=>$lang_dle_ipb['no_other_names'],
							"cp1251" => 'cp1251',
							"cp1250" => 'cp1250',
							"latin1" => 'latin1',
							"latin2" => 'latin2',
							"koi8r"	=> 'koi8r',
							"ascii" => 'ascii',
							"koi8u" => 'koi8u',
							"utf8" => 'utf8',
							"cp866" => 'cp866');
							
	$charset_array = array("0"=>$lang_dle_ipb['no_other_charset'],
							"cp1251" => 'cp1251',
							"cp1250" => 'cp1250',
							"latin1" => 'latin1',
							"latin2" => 'latin2',
							"koi8r"	=> 'koi8r',
							"ascii" => 'ascii',
							"koi8u" => 'koi8u',
							"utf8" => 'utf8',
							"cp866" => 'cp866');

	showRow($lang_dle_ipb['allow_module'], $lang_dle_ipb['allow_module_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_module]", "{$dle_ipb_conf['allow_module']}"));
	showRow($lang_dle_ipb['allow_reg'], $lang_dle_ipb['allow_reg_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_reg]", "{$dle_ipb_conf['allow_reg']}"));
	showRow($lang_dle_ipb['allow_profile'], $lang_dle_ipb['allow_profile_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_profile]", "{$dle_ipb_conf['allow_profile']}"));
	showRow($lang_dle_ipb['allow_lostpass'], $lang_dle_ipb['allow_lostpass_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_lostpass]", "{$dle_ipb_conf['allow_lostpass']}"));
	showRow($lang_dle_ipb['allow_login'], $lang_dle_ipb['allow_login_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_login]", "{$dle_ipb_conf['allow_login']}"));
	showRow($lang_dle_ipb['allow_logout'], $lang_dle_ipb['allow_logout_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_logout]", "{$dle_ipb_conf['allow_logout']}"));
	showRow($lang_dle_ipb['allow_admin'], $lang_dle_ipb['allow_admin_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_admin]", "{$dle_ipb_conf['allow_admin']}"));
	showRow($lang_dle_ipb['ipb_version'], $lang_dle_ipb['ipb_version_desc'], makeDropDown(array("0" => $lang_dle_ipb['ipb_version_b3.2'], "1" => $lang_dle_ipb['ipb_version_a3.2']), "save_con[ipb_version]", "{$dle_ipb_conf['ipb_version']}"));
	//showRow($lang_dle_ipb['other_charset_ipb'], $lang_dle_ipb['other_charset_ipb_desc'], makeDropDown($charset_array, "save_con[other_charset_ipb]", "{$dle_ipb_conf['other_charset_ipb']}"));
	//showRow($lang_dle_ipb['other_names_dle'], $lang_dle_ipb['other_names_dle_desc'], makeDropDown($names_array, "save_con[other_names_dle]", "{$dle_ipb_conf['other_names_dle']}"));
	//showRow($lang_dle_ipb['other_charset_dle'], $lang_dle_ipb['other_charset_dle_desc'], makeDropDown($charset_array, "save_con[other_charset_dle]", "{$dle_ipb_conf['other_charset_dle']}"));
	
echo "</table></td></tr>";
	
	echo <<<HTML
    <tr>
        <td style="padding-top:10px; padding-bottom:10px;padding-right:10px;">
    <input type=hidden name=action value=save><input type="submit" class="buttons" value="{$lang['user_save']}"></td>
    </tr>
</table>
</td>
        <td background="engine/skins/images/tl_rb.gif"><img src="engine/skins/images/tl_rb.gif" width="6" height="1" border="0"></td>
    </tr>
    <tr>
        <td><img src="engine/skins/images/tl_lu.gif" width="4" height="6" border="0"></td>
        <td background="engine/skins/images/tl_ub.gif"><img src="engine/skins/images/tl_ub.gif" width="1" height="6" border="0"></td>
        <td><img src="engine/skins/images/tl_ru.gif" width="6" height="6" border="0"></td>
    </tr>
</table>
</div></form>
HTML;

	footer_dle_ipb ();
}
?>
