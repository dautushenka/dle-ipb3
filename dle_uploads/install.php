<?PHP

@ob_start(); 
@ob_implicit_flush(0);
error_reporting(E_ALL ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL ^ E_NOTICE);

define('DATALIFEENGINE', true);
define('ROOT_DIR', dirname (__FILE__));
define('ENGINE_DIR', ROOT_DIR.'/engine');
$STOP = FALSE;
$licence = /*licadm*/'.'/*/licadm*/;

function check_login_install($username, $md5_password, $post = true){
    global $member_db, $db, $user_group, $lang, $member_id;

	if ($username == "" OR preg_match("/[\||\'|\<|\>|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\+]/", $username) OR $md5_password == "") return false;

	$result = false;

	if ($post) {

		$username 	  = $db->safesql($username);
		$md5_password = md5($md5_password);

		$row = $member_id = $db->super_query("SELECT * FROM " . USERPREFIX . "_users WHERE name='$username' and password='$md5_password'");

		if ($row['user_id'] AND $user_group[$row['user_group']]['allow_admin'] AND $row['banned'] != 'yes') $result = TRUE;

	} else {

		$username 	  = intval($username);
		$md5_password = md5($md5_password);

		$row = $member_id = $db->super_query("SELECT * FROM " . USERPREFIX . "_users WHERE user_id='$username'");

		if ($row['password'] == $md5_password  AND $user_group[$row['user_group']]['allow_admin'] AND $row['banned'] != 'yes') $result = TRUE; else $row = array();

	}

	if ($result)
	{

		$member_db[0] = $row['reg_date'];
		$member_db[1] = $row['user_group'];
		$member_db[2] = $row['name'];
		$member_db[5] = $row['email'];
		$member_db[6] = $row['news_num'];
		$member_db[7] = $row['allow_mail'];
		$member_db[10] = $row['user_id'];
		$member_db[11] = $row['fullname'];
		$member_db[12] = $row['land'];
		$member_db[13] = $row['icq'];
		$member_db[14] = $row['hash'];
		$member_db[15] = $row['logged_ip'];
		$member_db[16] = $row['user_id'];

	}

        return $result;
}

function showRow_install($title="", $description="", $field="")
  {
  	global $text_full;
    $text_full .= "<tr>
        <td style=\"padding:4px\" class=\"option\">
        <b>$title</b><br /><span class=small>$description</span>
        <td width=394 align=middle >
        $field
        </tr><tr><td background=\"engine/skins/images/mline.gif\" height=1 colspan=2></td></tr>";
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

if (isset ($_REQUEST['step'])) $step = $_REQUEST['step']; else $step = "0";

include_once ENGINE_DIR.'/data/config.php';
require_once(ROOT_DIR.'/language/'.$config['langs'].'/dle_ipb.lng');
require_once(ROOT_DIR.'/language/Russian/adminpanel.lng');
require_once(ENGINE_DIR.'/modules/functions.php');

if (!preg_match("#" . $licence . "#i", $_SERVER['HTTP_HOST']) && 
    !preg_match('#localhost#', $_SERVER['HTTP_HOST']) &&
    strpos($_SERVER['HTTP_HOST'], $_SERVER['SERVER_ADDR']) === false
     )
{
	$STOP = TRUE;
	$error_licence = true;
}
$version = "1.0.0";
$next_step = $step + 1;

if ($config['version_id'] < 5.3)
{
	$STOP = TRUE;
	$error_version = true;
}
elseif (($config['version_id'] < 5.7) && !defined('USERPREFIX'))
{
	define("USERPREFIX", constant("PREFIX"));
	require_once ENGINE_DIR.'/inc/mysql.php';
}
elseif ($config['version_id'] < 6.3)
{
	require_once ENGINE_DIR.'/inc/mysql.php';
}
else
	require_once ENGINE_DIR.'/classes/mysql.php';
	
require_once ENGINE_DIR.'/data/dbconfig.php';
require_once(ENGINE_DIR.'/modules/sitelogin.php');

if (!$is_logged || $member_id['user_group'] != 1)
{
	$button = "Обновить";
	$title = array("Авторизация");
	$error_login = true;
	$STOP = TRUE;
}
else 
{
	$member_db[1]=$member_id['user_group'];
}
if (isset($_POST['name']) && $_POST['name'] != '')
{
	$user_group = get_vars ("usergroup");

	if (!$user_group) {
	  $user_group = array ();
	
	  $db->query("SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC");
	
	  while($row = $db->get_row()){
	
	   $user_group[$row['id']] = array ();
	
	     foreach ($row as $key => $value)
	     {
	       $user_group[$row['id']][$key] = $value;
	     }
	
	  }
	  set_vars ("usergroup", $user_group);
	  $db->free();
	}
	$pass_md5 = md5($_POST['password']);
	if (check_login_install($_POST['name'], $pass_md5))
	{
		setcookie ("dle_name", $_POST['name'],time()+3600*24*365, "/");
		setcookie ("dle_password", $pass_md5, time()+3600*24*365, "/");

        @session_register('dle_name');
        @session_register('dle_user_id');
        @session_register('dle_password');
        @session_register('member_lasttime');

        if ($config['version_id'] < 7.5)
		{
			setcookie ("dle_user_id", $member_db[10],time()+3600*24*365, "/");
			$_SESSION['dle_user_id']     = $member_db[10];
		}
		else 
		{
			setcookie ("dle_user_id", $member_id['user_id'],time()+3600*24*365, "/");
			$_SESSION['dle_user_id']     = $member_id['user_id'];
		}
		
        $_SESSION['dle_name']        = $_POST['name'];
        $_SESSION['dle_password']    = md5($_POST['password']);

		$_SESSION['dle_log'] = 0;
		$step = 0;
		$error_login = false;
		$STOP = FALSE;
	}
	else 
	{
		$status_report = "Вы не вошли, попробуйте еще раз, если забыли пароль, то можно его востановить пройдя по след ссылки <a href=\"" . $config['http_home_url'] . "index.php?do=lostpassword\" >Востановить пароль</a><br/><br/>";
		$error_login = true;
		$STOP = TRUE;
		$button = "Обновить";
		$title = array("Авторизация");
	}
}

	
if (!$STOP)
{
	$button = "Продолжить >>";
	$title = array(
					"Описание интеграции",
					"Лицензионное соглашение",
					"Проверка файлов и директорий на запись",
					"Создание файла настроек",
					"Завершение установки");
		
	switch ($step)
	{

		case "0":
			$button = "Начать установку";
$status_report .= <<<HTML
<font >Этот мастер поможет вам установить интеграцию на ваш сайт, <b>не забудте </b>его удалить после установки модуля </font><br>
<br /><br />
<b>Основные возможности:</b><br />
<br />
<ol style="text-align:left" >
<li>Форум может находиться на поддомене или на другом домене</li>
<li>Базы форума и сайта могут различаться, если используется одна база то переключение не происходит </li>
<li>Прификсы таблиц тоже могут быть как разными так и одинаковыми </li>
<li>Каждую возможность можно выключить в админки </li>
<li>Двухсторонняя регистрация </li>
<li>Общая авторизация </li>
<li>Общий профиль </li>
<li>Восстановление пароля в любом скрипте </li>
<li>При редактировании/удалении/добавлении пользователей в админке DLE изменения происходят и на форуме, вплоть до изменения логина </li>
<li>На сайт можно повесить ссылку "Обсудить на форуме" при переходе по которой автоматически создается(если нету) тема на форуме. </li>
<li>Возле ссылки можно выводить количство постов обсуждения </li>
<li>Для ссылки может использоваться ЧПУ </li>
<li>Вид ссылки настраивается в админке </li>
<li>Возможно для разных категорий на сайте назначать отдельные форумы. </li>
<li>На сайте также может быть установлен блоки: "Последние сообщения с форума", "Именинники", "Кто на сайте" </li>
<li>Все настройки производиться в админке сайта, включая вид отображения блоков, поста на форуме и ссылки на форум.</li>
<li>Данные для блока "Кто на сайте берутся из базы сессий форума и отображают всех пользователей, которые находятся на сайте и на форуме следовательно используется один запрос </li>
<li>Автоматический перенос пользвователей с сайта на форум</li>

		</ol>


    <input type="hidden" name="step" value="$next_step" />
HTML;
			break;
			
		case "1":
			$button = $button . '" disabled="disabled';
$status_report .= <<<HTML
<table width="100%">
    <tr>
        <td style="padding:2px;">Пожалуйста внимательно прочитайте и примите пользовательское соглашение по использованию интеграции DLE +  Invision Power Board 3 3.<br /><br /><div style="height: 300px; border: 1px solid #76774C; background-color: #FDFDD3; padding: 5px; overflow: auto;">Рекомендую вам внимательно ознакомиться с данным лицензионным соглашением. В нем содержится информация о том, какие правила будут регламентировать права и обязанности сторон, подписавших данное соглашение. Обратите внимание на выполнение обязательных условий при использовании моего продукта, как при самостоятельной установке, так и при обращении в службу технической поддержки.<br /><br /><b>Лицензионное соглашение конечного пользователя</b><br /><br /><b>Предмет лицензионного соглашения</b><br /><br />Предметом настоящего лицензионного соглашения является право использования одной лицензионной копии интеграции <b>DLE +  Invision Power Board 3</b>, в порядке и на условиях, установленных настоящим соглашением.<br /><br /><b>Содержание договора</b><br /><br />Я оставляю за собой право в любое время изменять условия данного договора, но данные действия не имеют обратной силы. Изменения данного договора будут разосланы пользователям по электронной почте на адреса, указанные при приобретении системы. Отсутствие у пользователей уведомления не может являться причиной невыполнения изменившихся условий использования модуля.<br /><br /><b>Покупатель имеет право:</b><ul><li>Изменять дизайн и структуру программного продукта в соответствии с нуждами своего сайта.</li><br /><li>Производить и распространять инструкции по созданным Вами модификациям шаблонов и языковых файлов, если в них будет иметься указание на оригинального разработчика программного продукта до Ваших модификаций.</li><br /><li>Переносить программный продукт на другой сайт после обязательного уведомления меня об этом, а также полного удаления скрипта с предыдущего сайта.</li><br /></ul><br /><b>Покупатель не имеет право:</b><br /><ul><li>Передавать права на использование интеграции третьим лицам, кроме случаев, перечисленных выше в нашем соглашении.</li><br /><li>Изменять структуру программных кодов, функции программы или создавать родственные продукты, базирующиеся на нашем программном коде</li><br /><li>Использовать более одной копии интеграции <b>DLE +  Invision Power Board 3</b> по одной лицензии</li><br /><li>Рекламировать, продавать или публиковать на своем сайте пиратские копии модуля</li><br /><li>Распространять или содействовать распространению нелицензионных копий интеграции <b>DLE +  Invision Power Board 3</b></li><br /></ul><br /><b>Ограничение гарантийных обязательств</b><br /><br /> Так же мои гарантии и техническая поддержка не распространяются на модификации, произведенные третьей стороной, включая изменения программного кода, стиля, языковых пакетов, а также на изменения перечисленных частей, внесенные владельцем лицензии самостоятельно. Если модуль изменен Вами или третьей стороной, то я вправе отказать Вам в технической поддержке. <br /><br /><b>Права на интеллектуальную собственность</b><br /><br />Входящие в данную интеграцию скрипты являются собственностью <b>kaliostro</b>, за исключением случаев, когда для компонента системы применяется другой тип лицензии. Программный продукт защищен законом об авторских правах. Любые публикуемые оригинальные материалы и связанные с этим права на них, так же как и материалы, создаваемые в результате использования моего скрипта, являются собственностью пользователя и защищены законом. Я не несу никакой ответственности за содержание Ваших сайтов.<br /><br /><b>Досрочное расторжение договорных обязательств</b><br /><br />Данное соглашение расторгается автоматически, если Вы отказываетесь выполнять условия этого договора. Данное лицензионное соглашение может быть расторгнуто мной в одностороннем порядке, в случае установления фактов нарушения данного лицензионного соглашения. В случае досрочного расторжения договора Вы обязуетесь удалить все Ваши копии моего модуля в течение 3 рабочих дней, с момента получения соответствующего уведомления.</div>
		<input onclick="agree();" type='checkbox' name='eula' value=1 id='eula'><b>Я принимаю данное соглашение</b>
		<br />
</td>
    </tr>
</table>
<script type="text/javascript" >
<!--
function agree()
{
if (document.form.eula.checked == true)
{
document.form.button.disabled=false;
}
else
{
document.form.button.disabled=true;
}
}
-->
</script>
    <input type="hidden" name="step" value="$step" />
HTML;
if (!$_REQUEST['eula'])
	break;
$step = $next_step;
$next_step++;
$text_full = "";
$status_report = "";
$button = "Продолжить >>";
			
		case "2":
$important_files = array(
'./engine/data/',
);

$text_full ="<tr>
<td height=\"25\">&nbsp;Папка/Файл
<td width=\"100\" height=\"25\">&nbsp;CHMOD
<td width=\"100\" height=\"25\">&nbsp;Статус</tr><tr><td colspan=3><div class=\"hr_line\"></div></td></tr>";
$chmod_errors = 0;
$not_found_errors = 0;
    foreach($important_files as $file)
    {

        if(!file_exists($file)){
            $file_status = "<font color=red>не найден!</font>";
            $not_found_errors ++;
        }
        elseif(is_writable($file)){
            $file_status = "<font color=green>разрешено</font>";
        }
        else{
            @chmod($file, 0777);
            if(is_writable($file)){
                $file_status = "<font color=green>разрешено</font>";
            }else{
                @chmod("$file", 0755);
                if(is_writable($file)){
                    $file_status = "<font color=green>разрешено</font>";
                }else{
                    $file_status = "<font color=red>запрещено</font>";
                    $chmod_errors ++;
                }
            }
        }
        $chmod_value = @decoct(@fileperms($file)) % 1000;

    $text_full .="<tr>
         <td height=\"22\" class=\"tableborder main\">&nbsp;$file</td>
         <td>&nbsp; $chmod_value</td>
         <td>&nbsp; $file_status</td>
         </tr><tr><td background=\"engine/skins/images/mline.gif\" height=1 colspan=3></td></tr>";
    }
$db->connect(DBUSER, DBPASS, DBNAME, DBHOST);
if ((float)($db->mysql_version) >= 4.1)
{
	$sql_status = true;
	$sql = "<font color=\"green\">" . $db->mysql_version . "</font><br/>";
}
else 
{
	$sql_status = false;
	$error = true;
	$sql = "<font color=\"red\">" .$db->mysql_version."</font>";
$status_report = "<font color=red>Внимание!!!</font><br /><br />Во время проверки обнаружена ошибка версии базы данных.<br />Необходимо исправить эту ошибку чтобы модуль работал корректно.<br /><input type=\"hidden\" name=\"step\" value=\"$step\" />";
}

if($chmod_errors == 0 and $not_found_errors == 0 && $sql_status){

$status_report = 'Проверка успешно завершена! Можете продолжить установку!';
$text_full .= "<input type=\"hidden\" name=\"step\" value=\"$next_step\" />";
}else{
if($chmod_errors > 0){
$status_report .= "<font color=red>Внимание!!!</font><br /><br />Во время проверки обнаружены ошибки: <b>$chmod_errors</b>. Запрещена запись в файл.<br />Вы должны выставить для папок CHMOD 777, для файлов CHMOD 666, используя ФТП-клиент.<br /><font color=red><br />";
}
if($not_found_errors > 0){
$status_report .= "<font color=red>Внимание!!!</font><br />Во время проверки обнаружены ошибки: <b>$not_found_errors</b>. Файлы не найдены!<br /><br /><font color=red><b>Не рекомендуется</b></font> продолжать установку, пока не будут произведены изменения.<br />";
}
$text_full .= "<input type=\"hidden\" name=\"step\" value=\"$step\" />";
$button = "Обновить";
$error = true;
}

$text_full .= "<tr><td colspan=3><div class=\"hr_line\"></div></td></tr><tr><td height=\"25\" colspan=3>&nbsp;&nbsp;MySQL $sql<div class=\"hr_line\"></div></td></tr>";
break;

		case 3:
		$save_con = $_REQUEST['save_con'];
		$button = "Продолжить >>";
		if ($_REQUEST['subaction'] == "save" && count($save_con))
		{
			$save_con['version_id'] = $version;

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
		    
		    if ($config['version_id'] >= 8.2)
        	{
        	    $db->query("INSERT IGNORE `" . PREFIX . "_admin_sections` (allow_groups, name, icon, title, descr) VALUES ('all', 'dle_ipb3', 'dle_ipb3.gif', 'DLE + IPB3', 'Settings integration')");
        	}
		}
		$text_full = "<tr><td class=\"option\" style=\"padding:20px;text-align:center;\"><font style=\"font-size:13px;\"><b>Укажите основные настройки модуля, если не понимаете о чём идёт реч, оставте без изменений или спросите у меня, для контакта ICQ: 415-74-19.</b></font></td></tr>";


$text_full .= <<<HTML
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
<script  language='JavaScript' type="text/javascript">
function showmenu(obj)
{ 
	document.getElementById('settings').style.display = "none";
	document.getElementById('block_new').style.display = "none";
	document.getElementById('block_birth').style.display = "none";
	document.getElementById('block_online').style.display = "none";
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

$text_full .= <<<HTML
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

$text_full .= <<<HTML
<tr id="block_new" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['block_new']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
HTML;
	
	showRow_install($lang_dle_ipb['allow_forum_block'], $lang_dle_ipb['allow_forum_block_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_forum_block]", "1"));
    showRow_install($lang_dle_ipb['forum_block_alt_url'], $lang_dle_ipb['forum_block_alt_url_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[forum_block_alt_url]", "0"));
	showRow_install($lang_dle_ipb['count_post'], $lang_dle_ipb['count_post_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[count_post]' value='10' size=10 onclick=\"freeze(0)\">");
	showRow_install($lang_dle_ipb['leght_name'], $lang_dle_ipb['leght_name_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[leght_name]' value='30' size=10 onclick=\"freeze(0)\">");
	showRow_install($lang_dle_ipb['cache_time'], $lang_dle_ipb['cache_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[block_new_cache_time]' value='60' size=10 onclick=\"freeze(0)\">");
	showRow_install($lang_dle_ipb['bad_forum_for_block'], $lang_dle_ipb['bad_forum_for_block_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[bad_forum_for_block]' value='' size=10 onclick=\"freeze(1)\" id=\"bad\">");
	showRow_install($lang_dle_ipb['good_forum_for_block'], $lang_dle_ipb['good_forum_for_block_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[good_forum_for_block]' value='' size=10 onclick=\"freeze(2)\" id=\"good\">");
	
$text_full .= "</table></td></tr>";


$text_full .= <<<HTML
<tr id="block_birth" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['block_birth']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
HTML;

	showRow_install($lang_dle_ipb['allow_birthday_block'], $lang_dle_ipb['allow_birthday_block_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_birthday_block]", "0"));
	showRow_install($lang_dle_ipb['cache_time'], $lang_dle_ipb['cache_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[birthday_cache_time]' value='60' size=10>");
	showRow_install($lang_dle_ipb['count_birthday'], $lang_dle_ipb['count_birthday_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[count_birthday]' value='50' size=10 onclick=\"freeze(0)\">");
	showRow_install($lang_dle_ipb['no_user_birthday'], $lang_dle_ipb['no_user_birthday_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[no_user_birthday]' value='".stripslashes($lang_dle_ipb['no_user_birthday'])."' size=30 onclick=\"freeze(0)\">");
	showRow_install($lang_dle_ipb['spacer'], $lang_dle_ipb['spacer_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[spacer]' value=', ' size=10 onclick=\"freeze(0)\">");
	showRow_install($lang_dle_ipb['birthday_block'], $lang_dle_ipb['birthday_block_desc'], "<textarea onclick=\"freeze(0)\" cols=\"50\" rows=\"6\" name='save_con[birthday_block]'><a href=\"{user_url}\">{name}</a> ({age})</textarea>");
	
$text_full .= "</table></td></tr>";
	
	
$text_full .= <<<HTML
<tr id="block_online" style='display:none'><td>
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$lang_dle_ipb['block_online']}</div></td>
    </tr>
</table>
<div class="unterline"></div><table width="100%">
HTML;

	showRow_install($lang_dle_ipb['allow_online_block'], $lang_dle_ipb['allow_online_block_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_online_block]", "{$dle_ipb_conf['allow_online_block']}"));
	showRow_install($lang_dle_ipb['cache_time'], $lang_dle_ipb['cache_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[block_online_cache_time]' value='60' size=10>");
	showRow_install($lang_dle_ipb['online_time'], $lang_dle_ipb['online_time_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[online_time]' value='900' size=10 >");
	showRow_install($lang_dle_ipb['separator'], $lang_dle_ipb['separator_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[separator]' value=', ' size=10 >");
	echo "</table></td></tr>";
$text_full .= "</table></td></tr>";
	
$text_full .= <<<HTML
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

		$link .= "<tr><td style=\"padding-right:3px;\">".$sublevelmarker."<a class=\"list\" href=\"{$config['http_home_url']}index.php?do=cat&category=".$row['alt_name']."\" target=\"_blank\">".stripslashes($row['name'])."</a></td><td><input class=edit type=text style=\"text-align: center;\" name='save_con[forumid][{$row['id']}]' value='' size=10></td></tr><tr><td background=\"engine/skins/images/mline.gif\" height=1 colspan=2></td></tr>";

       DisplayCategories($row['id'], $sublevelmarker, $link);
  }
  
}
DisplayCategories();

	showRow_install($lang_dle_ipb['goforum'], $lang_dle_ipb['goforum_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[goforum]", "1"));
	showRow_install($lang_dle_ipb['link_title'], $lang_dle_ipb['link_title_desc'], makeDropDown(array("old"=>$lang_dle_ipb['old_title'],"title"=>$lang_dle_ipb['title']), "save_con[link_title]", "title"));
	showRow_install($lang_dle_ipb['link_text'], $lang_dle_ipb['link_text_desc'], makeDropDown(array("full"=>$lang_dle_ipb['full_text'],"short"=>$lang_dle_ipb['short_text'],"old"=>$lang_dle_ipb['old_text']), "save_con[link_text]", "full"));
	showRow_install($lang_dle_ipb['link_on_news'], $lang_dle_ipb['link_on_news_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[link_on_news]", 0));
	showRow_install($lang_dle_ipb['link_user'], $lang_dle_ipb['link_user_desc'], makeDropDown(array("old"=>$lang_dle_ipb['old_user'],"author"=>$lang_dle_ipb['author'],"cur_user"=>$lang_dle_ipb['cur_user']), "save_con[link_user]", "author"));
	showRow_install($lang_dle_ipb['show_no_reginstred'], $lang_dle_ipb['show_no_reginstred_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[show_no_reginstred]", "1"));
	showRow_install($lang_dle_ipb['show_short'], $lang_dle_ipb['show_short_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[show_short]", "{$dle_ipb_conf['show_short']}"));
	showRow_install($lang_dle_ipb['allow_count_short'], $lang_dle_ipb['allow_count_short_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_count_short]", "0"));
	showRow_install($lang_dle_ipb['show_count'], $lang_dle_ipb['show_count_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[show_count]", "1"));
	showRow_install($lang_dle_ipb['name_post_on_forum'], $lang_dle_ipb['name_post_on_forum_desc'], "<textarea cols=\"50\" rows=\"3\" name='save_con[name_post_on_forum]'>Статья : {Post_name}</textarea>");
	showRow_install($lang_dle_ipb['text_post_on_forum'], $lang_dle_ipb['text_post_on_forum_desc'], "<textarea  cols=\"50\" rows=\"4\" name='save_con[text_post_on_forum]'>Здесь обсуждается статья: <a href='{post_link}'>{Post_name}</a></textarea>");
	showRow_install($lang_dle_ipb['link_on_forum'], $lang_dle_ipb['link_on_forum_desc'], "<textarea cols=\"50\" rows=\"4\" name='save_con[link_on_forum]'><a href='{link_on_forum}' title='Перейти на форум'>Обсудить на форуме[count] ({count})[/count]</a></textarea>");
	showRow_install($lang_dle_ipb['postusername'], $lang_dle_ipb['postusername_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[postusername]' value='SiteInformer' size=50>");
	showRow_install($lang_dle_ipb['postuserid'], $lang_dle_ipb['postuserid_desc'], "<input class=edit type=text style=\"text-align: center;\" name='save_con[postuserid]' value='0' size=8 onclick=\"freeze(0)\">");
	showRow_install($lang_dle_ipb['forumid'], $lang_dle_ipb['forumid_desc'], $link."</table>");

$text_full .= "</table></td></tr>";
	
$text_full .= <<<HTML
<tr id="settings" style=''><td>
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
							"cp1251_general_ci" => 'cp1251_general_ci',
							"cp1250_general_ci" => 'cp1250_general_ci',
							"latin1_swedish_ci" => 'latin1_swedish_ci',
							"latin2_general_ci" => 'latin2_general_ci',
							"koi8r_general_ci"	=> 'koi8r_general_ci',
							"ascii_general_ci" => 'ascii_general_ci',
							"koi8u_general_ci" => 'koi8u_general_ci',
							"utf8_general_ci" => 'utf8_general_ci',
							"cp866_general_ci" => 'cp866_general_ci');

	showRow_install($lang_dle_ipb['allow_module'], $lang_dle_ipb['allow_module_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_module]", "1"));
	showRow_install($lang_dle_ipb['allow_reg'], $lang_dle_ipb['allow_reg_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_reg]", "1"));
	showRow_install($lang_dle_ipb['allow_profile'], $lang_dle_ipb['allow_profile_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_profile]", "1"));
	showRow_install($lang_dle_ipb['allow_lostpass'], $lang_dle_ipb['allow_lostpass_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_lostpass]", "1"));
	showRow_install($lang_dle_ipb['allow_login'], $lang_dle_ipb['allow_login_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_login]", "1"));
	showRow_install($lang_dle_ipb['allow_logout'], $lang_dle_ipb['allow_logout_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_logout]", "1"));
	showRow_install($lang_dle_ipb['allow_admin'], $lang_dle_ipb['allow_admin_desc'], makeDropDown(array("1"=>$lang_dle_ipb['yes'],"0"=>$lang_dle_ipb['no']), "save_con[allow_admin]", "1"));
	showRow_install($lang_dle_ipb['ipb_version'], $lang_dle_ipb['ipb_version_desc'], makeDropDown(array(0 => $lang_dle_ipb['ipb_version_b3.2'], 1 => $lang_dle_ipb['ipb_version_a3.2']), "save_con[ipb_version]", 0));
	//showRow_install($lang_dle_ipb['other_names_ipb'], $lang_dle_ipb['other_names_ipb_desc'], makeDropDown($names_array, "save_con[other_names_ipb]", "0"));
//	showRow_install($lang_dle_ipb['other_charset_ipb'], $lang_dle_ipb['other_charset_ipb_desc'], makeDropDown($charset_array, "save_con[other_charset_ipb]", "0"));
//	showRow_install($lang_dle_ipb['other_names_dle'], $lang_dle_ipb['other_names_dle_desc'], makeDropDown($names_array, "save_con[other_names_dle]", "0"));
//	showRow_install($lang_dle_ipb['other_charset_dle'], $lang_dle_ipb['other_charset_dle_desc'], makeDropDown($charset_array, "save_con[other_charset_dle]", "0"));

$text_full .= "</table></td></tr>";

	$status_report = "<input type=\"hidden\" name=step value=$step />";
	$status_report .= "<input type=\"hidden\" name=subaction value=save />";
	$status_report .= "Создание файла настроек ....";
	if (!count($save_con) && $_REQUEST['subaction'] != "save")
		break;
	$step = $next_step;
	$next_step++;
	$text_full = "";
	$status_report = "";
			
		case 4:
			$button = "Finish";
			$PHP_SELF = $config['http_home_url']."index.php";
$status_report .= <<<HTML
Благодарим вас за покупку модуля. Надеемся что работа с ним доставит Вам только удовольствие!!! Все возникшие вопросы вы можете найти в документации или спросить у меня.<br/><br/>
<font color="red">Удалите этот файл во избежании повторной установки модуля, что повличёт за собой потерю всех данных модуля</font>
HTML;
			break;
			
			
		default:
			break;
	}
}
elseif ($error_login && $STOP)
{
	$step_count = 1;
	$title_step_0 = $title;
	$status_report .= "<font color=red >$info</font>Вы не вошли на форум, авторизуйтесь, тогда можно будет продолжать установку.";
	$text_full = "<form action='' method=POST>Логин: <input class=edit type=edit name='name' value=''><br/><br/>Пароль<input class=edit type=password name='password' value=''><br/><br/><input class=buttons type='submit' value='Войти'></form>";
}
elseif ($STOP && $error_version)
{
	$status_report = "Интеграция не работает на версиях DLE ниже <font color=red >5.3</font>. У вас ". $config['version_id'];
	$text_full = "";
}
elseif ($STOP && $error_licence)
{
	$status_report = "Вы используете интеграцию DLE + Invision Power Board 3 не на том хосте для которого она была куплена.<br/>За информацией обращайтесь на ICQ: 415-74-19";
	$text_full = "";
}

$step_count = count($title);
if ($step_count == 0)
	$step_count = 1;
if (preg_match("#IE#i", $_SERVER['HTTP_USER_AGENT']) || preg_match("#Opera#", $_SERVER['HTTP_USER_AGENT']))
	$size = @round(100/$step_count, 5);
else 
	$size = @ceil(100/$step_count);

$bar = "<table><tr><td align=center width=\"$size%\" >";
for ($i=0; $i<=$step_count; $i++)
{
	if ($i < $step && $i != $step_count) $bar .= "<img src=\"" . $config['http_home_url'] . "engine/skins/images/ok.jpg\" />";
	elseif ($i == $step && $error && $i != $step_count) $bar .= "<img src=\"" . $config['http_home_url'] . "engine/skins/images/stop.jpg\" />";
	elseif ($i == $step && !$error && $i != $step_count) $bar .= "<img src=\"" . $config['http_home_url'] . "engine/skins/images/now.jpg\" />";
	elseif ($i == $step_count)$bar .= "<img src=\"" . $config['http_home_url'] . "engine/skins/images/dle_ipb3.gif\" /";
	else $bar .= "<img src=\"" . $config['http_home_url'] . "engine/skins/images/next.jpg\" />";
	if ($i != $step_count) $bar .= "</td><td align=center width=\"$size%\" >";
}
$bar .= "</td></tr><tr style=\"padding-top:10px;\"><td align=center width=\"$size%\" >";
for ($i=0; $i<=$step_count; $i++)
{
	if ($i < $step && $i != $step_count) $bar .= $title[$i];
	elseif ($i == $step && $error && $i != $step_count) $bar .= "<b>" . $title[$i] . "</b>";
	elseif ($i == $step && !$error && $i != $step_count) $bar .= "<b>" . $title[$i] . "</b>";
	elseif ($i == $step_count)$bar .= "<font color=\"#cccccc\" >" . $title[$i] . "</font>";
	else $bar .= "<font color=\"#cccccc\" >" . $title[$i] . "</font>";
	if ($i != $step_count) $bar .= "</td><td align=center width=\"$size%\" >";
}
$bar .= "</td></tr></table>";

echo <<<HTML
<html>
<head>
<title>DataLife Engine +  Invision Power Board 3</title>
<meta content="text/html; charset={$config['charset']}" http-equiv="content-type" />
<script type="text/javascript" src="engine/skins/default.js"></script>

<style type="text/css">
html,body{
height:100%;
margin:0px;
padding: 0px;
background: #F4F3EE;
}

form {
margin:0px;
padding: 0px;
}

table{
border:0px;
border-collapse:collapse;
}

table td{
padding:0px;
font-size: 11px;
font-family: verdana;
}

a:active,
a:visited,
a:link {
	color: #4b719e;
	text-decoration:none;
	}

a:hover {
	color: #4b719e;
	text-decoration: underline;
	}

.navigation {
	color: #999898;
	font-size: 11px;
	font-family: tahoma;
}

.option {
	color: #717171;
	font-size: 11px;
	font-family: tahoma;
}

.upload input {
	border:1px solid #9E9E9E;
	color: #000000;
	font-size: 11px;
	font-family: Verdana; 
}

.small {
	color: #999898;
}

.navigation a:active,
.navigation a:visited,
.navigation a:link {
	color: #999898;
	text-decoration:none;
	}

.navigation a:hover {
	color: #999898;
	text-decoration: underline;
	}

.list {
	font-size: 11px;
}

.list a:active,
.list a:visited,
.list a:link {
	color: #0B5E92;
	text-decoration:none;
	}

.list a:hover {
	color: #999898;
	text-decoration: underline;
	}

.quick {
	color: #999898;
	font-size: 11px;
	font-family: tahoma;
	padding: 5px;
}

.quick h3 {
	font-size: 18px;
	font-family: verdana;
	margin: 0px;
	padding-top: 5px;
}
.system {
	color: #999898;
	font-size: 11px;
	font-family: tahoma;
	padding-bottom: 10px;
	text-decoration:none;
}

.system h3 {
	font-size: 18px;
	font-family: verdana;
	margin: 0px;
	padding-top: 4px;
}
.system a:active,
.system a:visited,
.system a:link,
.system a:hover {
	color: #999898;
	text-decoration:none;
	}

.quick a:active,
.quick a:visited,
.quick a:link,
.quick a:hover {
	color: #999898;
	text-decoration:none;
	}

.unterline {
	background: url(engine/skins/images/line_bg.gif);
	width: 100%;
	height: 9px;
	font-size: 3px;
	font-family: tahoma;
	margin-bottom: 4px;
} 

.hr_line {
	background: url(engine/skins/images/line.gif);
	width: 100%;
	height: 7px;
	font-size: 3px;
	font-family: tahoma;
	margin-top: 4px;
	margin-bottom: 4px;
}

.edit {
	border:1px solid #9E9E9E;
	color: #000000;
	font-size: 11px;
	font-family: Verdana;
	background: #FFF; 
}

.bbcodes {
	background: #FFF;
	border: 1px solid #9E9E9E;
	color: #666666;
	font-family: Verdana, Tahoma, helvetica, sans-serif;
	padding: 2px;
	vertical-align: middle;
	font-size: 10px; 
	margin:2px;
	height: 21px;
}

.buttons {
	background: #FFF;
	border: 1px solid #9E9E9E;
	color: #666666;
	font-family: Verdana, Tahoma, helvetica, sans-serif;
	padding: 0px;
	vertical-align: absmiddle;
	font-size: 11px; 
	height: 21px;
}

select {
	color: #000000;
	font-size: 11px;
	font-family: Verdana; 
	border:1px solid #9E9E9E;
}

.cat_select {
	color: #000000;
	font-size: 11px;
	font-family: Verdana; 
	border:1px solid #9E9E9E;
	width:316px;
	height:73px;
}

textarea {
	border: #9E9E9E 1px solid;
	color: #000000;
	font-size: 11px;
	font-family: Verdana;
	margin-bottom: 2px;
	margin-right: 0px;
	padding: 0px;
}

.xfields textarea {
width:98%; height:100px;border: #9E9E9E 1px solid; font-size: 11px;font-family: Verdana;
}
.xfields input {
width:350px; height:18px;border: #9E9E9E 1px solid; font-size: 11px;font-family: Verdana;
}
.xfields select {
height:18px; font-size: 11px;font-family: Verdana;
}

.xfields {
height:30px; font-size: 11px;font-family: Verdana;
}
.xprofile textarea {
width:100%; height:90px; font-family:verdana; font-size:11px; border:1px solid #E0E0E0;
}
.xprofile input {
width:250px; height:18px; font-family:verdana; font-size:11px; border:1px solid #E0E0E0;
}
#dropmenudiv{
border:1px solid white;
border-bottom-width: 0;
font:normal 10px Verdana;
background-color: #6497CA;
line-height:20px;
margin:2px;
filter: alpha(opacity=95, enabled=1) progid:DXImageTransform.Microsoft.Shadow(color=#CACACA,direction=135,strength=3);
}

#dropmenudiv a{
display: block;
text-indent: 3px;
border: 1px solid white;
padding: 1px 0;
MARGIN: 1px;
color: #FFF;
text-decoration: none;
font-weight: bold;
}

#dropmenudiv a:hover{ /*hover background color*/
background-color: #FDD08B;
color: #000;
}

#hintbox{ /*CSS for pop up hint box */
position:absolute;
top: 0;
background-color: lightyellow;
width: 150px; /*Default width of hint.*/ 
padding: 3px;
border:1px solid #787878;
font:normal 11px Verdana;
line-height:18px;
z-index:100;
border-right: 2px solid #787878;
border-bottom: 2px solid #787878;
visibility: hidden;
}

.hintanchor{ 
padding-left: 8px;
}

.editor_button {
	float:left;
	cursor:pointer;
	padding-left: 2px;
	padding-right: 2px;
}
.editor_buttoncl {
	float:left;
	cursor:pointer;
	padding-left: 1px;
	padding-right: 1px;
	border-left: 1px solid #BBB;
	border-right: 1px solid #BBB;
}
.editbclose {
	float:right;
	cursor:pointer;
}
	.dle_tabPane{
		height:26px;	/* Height of tabs */
	}
	.dle_aTab{
		border:1px solid #CDCDCD;
		padding:5px;		
		
	}
	.dle_tabPane DIV{
		float:left;
		padding-left:3px;
		vertical-align:middle;
		background-repeat:no-repeat;
		background-position:bottom left;
		cursor:pointer;
		position:relative;
		bottom:-1px;
		margin-left:0px;
		margin-right:0px;
	}
	.dle_tabPane .tabActive{
		background-image:url('engine/skins/images/tl_active.gif');
		margin-left:0px;
		margin-right:0px;	
	}
	.dle_tabPane .tabInactive{
		background-image:url('engine/skins/images/tl_inactive.gif');
		margin-left:0px;
		margin-right:0px;
	}

	.dle_tabPane .inactiveTabOver{
		margin-left:0px;
		margin-right:0px;
	}
	.dle_tabPane span{
		font-family:tahoma;
		vertical-align:top;
		font-size:11px;
		line-height:26px;
		float:left;
	}
	.dle_tabPane .tabActive span{
		padding-bottom:0px;
		line-height:26px;
	}
	
	.dle_tabPane img{
		float:left;
	}
</style>
</head>
<body>
<table align="center" width="94%">
    <tr>
        <td width="4" height="16"><img src="engine/skins/images/tb_left.gif" width="4" height="16" border="0" /></td>
		<td background="engine/skins/images/tb_top.gif"><img src="engine/skins/images/tb_top.gif" width="1" height="16" border="0" /></td>
		<td width="4"><img src="engine/skins/images/tb_right.gif" width="3" height="16" border="0" /></td>
    </tr>
	<tr>
        <td width="4" background="engine/skins/images/tb_lt.gif"><img src="engine/skins/images/tb_lt.gif" width="4" height="1" border="0" /></td>
		<td valign="top" style="padding-top:12px; padding-left:13px; padding-right:13px;" bgcolor="#FAFAFA">
		
<table width="100%">
    <tr>
        <td width="4"><img src="engine/skins/images/tl_lo.gif" width="4" height="4" border="0"></td>
        <td background="engine/skins/images/tl_oo.gif"><img src="engine/skins/images/tl_oo.gif" width="1" height="4" border="0"></td>
        <td width="6"><img src="engine/skins/images/tl_ro.gif" width="6" height="4" border="0"></td>
    </tr>
    <tr>
        <td background="engine/skins/images/tl_lb.gif"><img src="engine/skins/images/tl_lb.gif" width="4" height="1" border="0"></td>
        <td style="padding:5px;" bgcolor="#FFFFFF">
<center><font style="font-size:22px; font-weight:bold; font-family:Verdana, Arial, Helvetica, sans-serif; font-stretch:expanded; color:#333333;">DLE +  Invision Power Board 3</font> <font style="color:#666666">&nbsp;&nbsp;v$version</font></center>
</td>
        <td background="engine/skins/images/tl_rb.gif"><img src="engine/skins/images/tl_rb.gif" width="6" height="1" border="0"></td>
    </tr>
    <tr>
        <td><img src="engine/skins/images/tl_lu.gif" width="4" height="6" border="0"></td>
        <td background="engine/skins/images/tl_ub.gif"><img src="engine/skins/images/tl_ub.gif" width="1" height="6" border="0"></td>
        <td><img src="engine/skins/images/tl_ru.gif" width="6" height="6" border="0"></td>
    </tr>
</table>
<div>
<table width="100%">
    <tr>
        <td width="4"><img src="engine/skins/images/tl_lo.gif" width="4" height="4" border="0"></td>
        <td background="engine/skins/images/tl_oo.gif"><img src="engine/skins/images/tl_oo.gif" width="1" height="4" border="0"></td>
        <td width="6"><img src="engine/skins/images/tl_ro.gif" width="6" height="4" border="0"></td>
    </tr>
    <tr>
        <td background="engine/skins/images/tl_lb.gif"><img src="engine/skins/images/tl_lb.gif" width="4" height="1" border="0"></td>
        <td style="padding:5px;" bgcolor="#FFFFFF">
$bar
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
<!--MAIN area-->
HTML;

echo <<<HTML
<form method=POST action="$PHP_SELF" name="form">
<input type="hidden" name="type" value="$type" />
<div style="padding-top:5px;">
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
        <td bgcolor="#EFEFEF" height="29" style="padding-left:10px;"><div class="navigation">{$title[$step]}</div></td>
    </tr>
</table>
<div class="unterline"></div>
<table width="100%">
HTML;

echo $text_full;

echo <<<HTML
<tr>
<td>
<table width="100%">
	<tr>
		<td width=80px align=center>
			<img src="engine/skins/images/info.jpg" />
		</td>
		<td style="padding-left:10px">
			$status_report
		</td>
	</tr>
</table>
</td></tr>
HTML;

echo <<<HTML
     <tr>
     <td height="40" colspan=3 align="right">&nbsp;&nbsp;
     <input class=buttons id='but' name="button" type=submit style="padding:2px" value="$button">&nbsp;&nbsp;<input type=hidden name="action" value="chmod_check">
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

echo <<<HTML
	 <!--MAIN area-->
<div style="padding-top:5px; padding-bottom:10px;">
<table width="100%">
    <tr>
        <td bgcolor="#EFEFEF" height="40" align="center" style="padding-right:10px;"><div class="navigation">Copyright © 2009 created by <a href="mailto:kaliostro@kaliostro.net" style="text-decoration:underline;color:green">kaliostro</a><br/><a href="http://www.kaliostro.net" >http://www.kaliostro.net</a></div></td>
    </tr>
</table></div>		
		</td>
		<td width="4" background="engine/skins/images/tb_rt.gif"><img src="engine/skins/images/tb_rt.gif" width="4" height="1" border="0" /></td>
    </tr>
	<tr>
        <td height="16" background="engine/skins/images/tb_lb.gif"></td>
		<td background="engine/skins/images/tb_tb.gif"></td>
		<td background="engine/skins/images/tb_rb.gif"></td>
    </tr>
</table>
</body>

</html>
HTML;
?>
