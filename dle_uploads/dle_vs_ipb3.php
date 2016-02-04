<?php

if (!function_exists('clean_url'))
{
	function clean_url($url)
	{
	
	  if ($url == '') return;
	
	  $url = str_replace("http://", "", $url);
	  if (strtolower(substr($url, 0, 4)) == 'www.')  $url = substr($url, 4);
	  $url = explode('/', $url);
	  $url = reset($url);
	  $url = explode(':', $url);
	  $url = reset($url);
	
	  return $url;
	}
}

class ipb_member
{
    public $member = array();
    
    protected $connect_method = 'connect';
    
    protected $config = array();

    public $lang = array();
    
    public $ipb_config = array();
    
    protected $db = null;
    
    protected $connected = false;
    
    public function __construct(db &$db)
    {   
        if (file_exists(ENGINE_DIR . "/data/dle_ipb_conf.php"))
        {
            $dle_ipb_conf = array();
            include(ENGINE_DIR . "/data/dle_ipb_conf.php");
            $this->config = $dle_ipb_conf;
        }
        else
        {
            die("Модуль интеграции не установлен");
        }
        
        if (file_exists(ROOT_DIR . "/conf_global.php"))
        {
            $INFO = array();
            include(ROOT_DIR . "/conf_global.php");
            
            $this->ipb_config = $INFO;
        }
        else
        {
            die("Не найден конфиг форума conf_global.php");
        }
        
        if (!defined('IPB_CHARSET'))
        {
            define('IPB_CHARSET', 'UTF-8');
        }
        
        if ($GLOBALS['config']['charset'])
        {
            define('DLE_CHARSET', $GLOBALS['config']['charset']);
        }
        else 
        {
            define('DLE_CHARSET', 'windows-1251');
        }
        
        if (!defined('COLLATE'))
        {
            define('COLLATE', 'cp1251');
        }
        
        $this->db =& $db;
        
        define('IPB_PREFIX', $this->IPBConfig('sql_tbl_prefix'));
        
        if ($this->ipb_config['sql_host'] === DBHOST && 
            $this->ipb_config['sql_user'] === DBUSER &&
            $this->ipb_config['sql_pass'] === DBPASS
            ) 
		{	
		    if ($this->ipb_config['sql_database'] === DBNAME)
		    {
		        $this->connect_method = 'none';
		    }
		    else
		    {
		        $this->connect_method = 'use';
		    }
		}
        
		$lang_dle_ipb = array();
		require_once(ROOT_DIR.'/language/'.$GLOBALS['config']['langs'].'/dle_ipb.lng');
		$this->lang = $lang_dle_ipb;
		
		if (isset($_REQUEST['do']) && 
		    $_REQUEST['do'] == "goforum" && 
		    $this->config['goforum'] && 
		    !empty($_REQUEST['postid']) && 
		    $this->config['allow_module']
		    )
		    {
		        $this->GoForum();
		    }
    }

    public function &_db_connect()
	{
	    if ($this->connected)
	    {
	        return $this->db;
	    }
	    
	    switch ($this->connect_method)
	    {
	        case "none":
	            break;
	            
	        case "use":
	            $this->db->query("USE `" . $this->ipb_config['sql_database'] . "`");
	            break;
	            
	        default:
	            $this->db->connect($this->ipb_config['sql_user'], 
	                               $this->ipb_config['sql_pass'], 
	                               $this->ipb_config['sql_database'], 
	                               $this->ipb_config['sql_host']
	                               );
	            break;
	    }
	    
	    if ($this->ipb_config['sql_charset'] && $this->ipb_config['sql_charset'] != COLLATE)
	    {
	        $this->db->query("SET NAMES '{$this->ipb_config['sql_charset']}'");
	    }
        
        if (isset($this->ipb_config['sql_character']))
        {
            if ($this->ipb_config['sql_character'])
            {
                $this->db->query("SET CHARACTER SET '{$this->ipb_config['sql_character']}'");
            }
        }
        else if ($this->ipb_config['sql_charset'])
        {
            $this->db->query("SET CHARACTER SET '{$this->ipb_config['sql_charset']}'");
        }
	    
	    $this->connected = true;
	    
	    return $this->db;
	}

    public function _db_disconnect()
	{
	    if ($this->connected)
	    {
    	    switch ($this->connect_method)
    	    {
    	        case "none":
    	            break;
    	            
    	        case "use":
    	            $this->db->query("USE `" . DBNAME . "`");
    	            break;
    	            
    	        default:
    	            $this->db->connect(DBUSER, DBPASS, DBNAME, DBHOST);
    	            break;
    	    }
    	    
    	    if ($this->ipb_config['sql_charset'] && $this->ipb_config['sql_charset'] != COLLATE)
    	    {
        	    $this->db->query("SET NAMES '" . COLLATE . "'");
        	    //$this->db->query("SET CHARACTER SET DEFAULT");
    	    }
    	    
    	    $this->connected = false;
	    }
	}

    public function _convert_charset(&$text, $back = false)
	{
	    if (IPB_CHARSET && IPB_CHARSET != DLE_CHARSET)
	    {
	        if (!$back)
	        {
	            $text = iconv(DLE_CHARSET, IPB_CHARSET, $text);
	        }
	        else 
	        {
	            $text = iconv(IPB_CHARSET, DLE_CHARSET, $text);
	        }
	    }
	    
	    return $text;
	}

    public function IPBConfig($varname)
	{
	    if (isset($this->ipb_config[$varname]))
	    {
	        return $this->ipb_config[$varname];
	    }
	    
	    if (!function_exists("dle_cache") || !($cache = dle_cache("config_ipb")))
    	{
    		$this->db->query("SELECT * FROM " . IPB_PREFIX . "core_sys_conf_settings");
    		while ($row = $this->db->get_row()) 
    		{
    			$this->ipb_config[$row['conf_key']] = ($row['conf_value'] != "")?$row['conf_value']:$row['conf_default'];
    		}
    		
    		if (function_exists('create_cache'))
    		{
    		    create_cache("config_ipb", serialize($this->ipb_config));
    		}
    	}
    	else 
    	{
    		$this->ipb_config += unserialize($cache);
    	}
    	
    	if (isset($this->ipb_config[$varname]))
    	{
    	    return $this->ipb_config[$varname];
    	}
    	else
    	{
    	    return '';
    	}
	}

    protected function UserAgents()
	{
	    if (!function_exists("dle_cache") || !($cache = dle_cache("useragents")))
    	{
    		$this->db->query("SELECT * FROM " . IPB_PREFIX . "core_uagents");
    		$useragents = array();
    		while ($row = $this->db->get_row()) 
    		{
    			$useragents[] = $row;
    		}
    		
    		if (function_exists('create_cache'))
    		{
    		    create_cache("useragents", serialize($useragents));
    		}
    	}
    	else
    	{
    	    $useragents = @unserialize($cache);
    	}
    	
    	return $useragents;
	}

    protected function ip()
	{
	    if (!empty($_SERVER['REMOTE_ADDR']))
	    {
	        return $_SERVER['REMOTE_ADDR'];
	    }
	    else
	    {
	        return 'not detected';
	    }
	}

    protected function findUserAgentID( $userAgent )
	{
		$uagentReturn = array( 'uagent_id'      => 0,
		 					   'uagent_key'     => NULL,
							   'uagent_name'    => NULL,
							   'uagent_type'    => NULL,
							   'uagent_version' => 0 );
		 						
		//-----------------------------------------
		// Test in the DB
		//-----------------------------------------
	
		$userAgentCache = $this->UserAgents();

		foreach( $userAgentCache as $key => $data )
		{
			$regex = str_replace( '#', '\\#', $data['uagent_regex'] );
			
			if ( ! preg_match( "#{$regex}#i", $userAgent, $matches ) )
			{
				continue;
			}
			else
			{
				//-----------------------------------------
				// Okay, we got a match - finalize
				//-----------------------------------------
				
				if ( $data['uagent_regex_capture'] )
				{
					 $version = $matches[ $data['uagent_regex_capture'] ];
				}
				else
				{
					$version = 0;
				}
				
				$uagentReturn = array( 'uagent_id'      => $data['uagent_id'],
									   'uagent_key'     => $data['uagent_key'],
									   'uagent_name'    => $data['uagent_name'],
									   'uagent_type'	=> $data['uagent_type'],
									   'uagent_version' => intval( $version ) );
									
				break;
			}
		}
		
		return $uagentReturn;
	}

    protected function generatePasswordSalt($len=5)
	{
		$salt = '';

		for ( $i = 0; $i < $len; $i++ )
		{
			$num   = mt_rand(33, 126);

			if ( $num == '92' )
			{
				$num = 93;
			}

			$salt .= chr( $num );
		}

		return $salt;
	}
    
    public function checkForumUser($username)
    {
        $this->_db_connect();
        
        $this->_findForumUser($username);
        
        $this->_db_disconnect();
        
        return $this->member?true:false;
    }
    
    protected function _findForumUser($username, $email = '')
    {
        $this->_convert_charset($username);
        $username_sql = $this->db->safesql($username);
            
        if ($email)
        {
            $email = $this->db->safesql($email);
            $this->_convert_charset($email);
        
            $res = $this->db->query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$username_sql' OR email='$email'");
            if ($this->db->num_rows($res) > 1)
            {
                while($user = $this->db->get_row($res))
                {
                    if ((!defined('CHECK_LOGIN_BY_EMAIL') || !CHECK_LOGIN_BY_EMAIL) && $user['name'] == $username)
                    {
                        $this->member = $user;
                        break;
                    }
                    else if ($user['email'] == $email)
                    {
                        $this->member = $user;
                        break;
                    }
                }
            }
            else
            {
                $this->member = $this->db->get_row($res);
            }
        }
        else
        {
            $this->member = $this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$username_sql' LIMIT 1");
        }
    }

    public function GetForumDomain()
	{
	    if ($this->IPBConfig('cookie_domain') != "")
    	{
    	    $domain = $this->IPBConfig('cookie_domain');
    	}
    	else
    	{
    	    $domain = ".".clean_url($this->IPBConfig('board_url'));
    	}
    	
    	return $domain;
	}
    
    protected function _create_dle_account()
    {
        $this->_convert_charset($value, true);
        
        $member_id['user_group'] = $GLOBALS['config']['reg_group'];
        $member_id['email'] = $this->_convert_charset($this->member['email'], true);
        $member_id['name'] = $_POST['login_name'];
        
        $add = array();
        $add['name'] = $this->db->safesql($_POST['login_name']);
        $add['password'] = md5($_POST['login_password']);
        $add['email'] = $this->db->safesql($member_id['email']);
        $add['icq'] = '';
        $add['info'] = '';
        $add['land'] = '';
        $add['fullname'] = '';
        $add['reg_date'] = time() + $GLOBALS['config']['date_adjust'] * 60;
        $add['lastdate'] = time() + $GLOBALS['config']['date_adjust'] * 60;
        $add['logged_ip'] = $this->db->safesql($this->member['ip_address']);
        $add['user_group'] = $GLOBALS['config']['reg_group'];
        $add['favorites'] = '';
        $add['signature'] = '';
        
        if ($this->db->super_query("SELECT * FROM " . USERPREFIX . "_users WHERE name='{$add['name']}' OR email='{$add['email']}'"))
            return array();
        
        $this->db->query( "INSERT INTO " . USERPREFIX . "_users (" . implode(", ", array_keys($add)) . ") VALUES ('" . implode("', '", $add) ."')" );
        
        $member_id['user_id'] = $this->db->insert_id();
        $member_id['logged_ip'] = $_SERVER['REMOTE_ADDR'];
        $member_id['reg_date'] = $member_id['lastdate'] = $add['reg_date'];
        
        set_cookie( "dle_user_id", $member_id['user_id'], 365 );
        set_cookie( "dle_password", $_POST['login_password'], 365 );
        
        $_SESSION['dle_user_id'] = $member_id['user_id'];
        $_SESSION['dle_password'] = $_POST['login_password'];
        $_SESSION['member_lasttime'] = $member_id['lastdate'];
        $_SESSION['dle_log'] = 0;
        $GLOBALS['dle_login_hash'] = md5( strtolower( $_SERVER['HTTP_HOST'] . $member_id['name'] . $_POST['login_password'] . $GLOBALS['config']['key'] . date( "Ymd" ) ) );
        
        if($GLOBALS['config']['log_hash'])
        {
            $hash = md5(uniqid(time()) . time());
            
            $this->db->query( "UPDATE " . USERPREFIX . "_users set hash='" . $hash . "' WHERE user_id='$member_id[user_id]'" );
            
            set_cookie( "dle_hash", $hash, 365 );
            
            $_COOKIE['dle_hash'] = $hash;
            $member_id['hash'] = $hash;
        }
        
        $GLOBALS['member_id'] = $member_id;
        $GLOBALS['is_logged'] = true;
        
        return $member_id;
    }
	
	public function login(array $member)
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_login'])
	    {
    		return true;
	    }
        /*
    	if (empty($member['user_id']) && !$this->config['allow_online_block']) 
    	{
    		return true;
    	}*/
      	if (isset($_REQUEST['action']) && $_REQUEST['action'] == "logout")
      	{
    		return true;
      	}
    		
      	$this->_db_connect();
      	
    	$TIME = time();
    	$location = "";
    	
    	if ($this->config['allow_online_block'])
    	{
    	    if (isset ($_REQUEST['do'])) $do = $_REQUEST['do']; else $do = "";
        	if (isset ($_REQUEST['category'])) $category = $_REQUEST['category']; else $category = "";
        	if (isset ($_REQUEST['subaction'])) $subaction = $_REQUEST['subaction']; else $subaction = "";
        	if ($do == "cat" AND $category != '' AND $subaction == '')
        	{
        		$location = "%incategory%" . stripslashes($GLOBALS['cat_info'][$GLOBALS['category_id']]['name']);
        	}
        	elseif ($subaction == 'userinfo') $location = "%view_pofile%" .$_REQUEST['user'];
        	elseif ($subaction == 'newposts') $location = "%newposts%";
        	elseif ($do == 'stats') $location = "%view_stats%"; 
        	elseif ($do == 'addnews') $location = "%posin%" . $GLOBALS['lang']['title_addnews'];
        	elseif ($do == 'register') $location = "%posin%" .$GLOBALS['lang']['title_register']; 
        	elseif ($do == 'favorites') $location = "%posin%" .$GLOBALS['lang']['title_fav']; 
        	elseif ($do == 'pm') $location = "%posin%" .$GLOBALS['lang']['title_pm']; 
        	elseif ($do == 'feedback') $location = "%posin%" .$GLOBALS['lang']['title_feed'];
        	elseif ($do == 'lastcomments') $location = "%posin%" .$GLOBALS['lang']['title_last'];
        	elseif ($do == 'lostpassword') $location = "%posin%" .$GLOBALS['lang']['title_lost'];
        	elseif ($do == 'search') $location = "%posin%" .$GLOBALS['lang']['title_search'];
        	else 
        		$location = "%mainpage%";
    	}
    	
	    $this->_convert_charset($location);
    		
    	$domain = $this->GetForumDomain();
    		
    	if (!empty($_COOKIE[$this->IPBConfig('cookie_id')."session_id"]))
    	{
    	    $session_id = $this->db->safesql($_COOKIE[$this->IPBConfig('cookie_id')."session_id"]);
    	}
    	else if (!empty($_COOKIE['forum_session_id']))
    	{
    	    $session_id = $this->db->safesql($_COOKIE['forum_session_id']);
    	}
    	else
    	{
    	    $session_id = '';
    	}
    	
        if (!empty($member['name']))
        {
            $name = $this->db->safesql($member['name']);
            $this->_convert_charset($name);
        }
        
    	if (
            !$session_id || 
    	    !($row = $this->db->super_query("SELECT m.*, s.id, s.member_id FROM " . IPB_PREFIX . "sessions AS s
                                             LEFT JOIN " . IPB_PREFIX . "members AS m
                                             ON m.member_id=s.member_id
                                             WHERE s.id='$session_id' AND s.running_time>".($TIME-$this->IPBConfig('session_expiration'))." LIMIT 1")) || 
    	    ($row['member_id'] == 0 && !empty($member['user_id'])) ||
            (empty($member['name']) && !empty($_POST['login_name'])) ||
            (!empty($name) && $row['name'] != $name)
            )
    	{
    	    if (empty($this->member) && !empty($member['name']))
    	    {
                $this->_findForumUser($member['name'], $member['email']);
    	        //$this->member = $this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$name' LIMIT 1");
    	        
                if (!empty($_POST['login_password']))
                {
                    $pass = $this->db->safesql($_POST['login_password']);
                }
                else if (!empty($_SESSION['dle_password']))
                {
                    $pass = $this->db->safesql($_SESSION['dle_password']);
                }
                else if (!empty($_COOKIE['dle_password']))
                {
                    $pass = $this->db->safesql($_COOKIE['dle_password']);
                }
                
    	        if (empty($this->member['member_id']))
    	        {
    	            $this->CreateMember($this->db->safesql($member['name']), $pass, $this->db->safesql($member['email']), $TIME);
    	        }
                else if (!defined('CHECK_LOGIN_PASS') || CHECK_LOGIN_PASS)
                {
                    $this->_convert_charset($pass);
                    
                    if ($this->member['members_pass_hash'] !== md5( md5( $this->member['members_pass_salt'] ) . $pass ))
                    {
                        $this->member = array();
                        $this->member['member_id'] = 0;
                    }
                }
    	    }
    	    else if (empty($member['name']) && !empty($_POST['login_name']))
    	    {
                $name = $this->db->safesql($_POST['login_name']);
                $this->_convert_charset($name);
                $password = $_POST['login_password'];
                $this->_convert_charset($password);
                
    	        $this->member = $this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$name' LIMIT 1");

                if ($this->member && $this->member['members_pass_hash'] == md5(md5($this->member['members_pass_salt']) . $password))
                {
                    $this->_db_disconnect();
                    $member = $this->_create_dle_account();
                    $this->_db_connect();
                }
                else
                {
                    $this->member = array();
                    $name = '';
                }
    	    }
    		
    		if (empty($this->member['member_id']) || empty($member['user_id']))
    		{
                $this->member = array();
                
    		    if (!$this->config['allow_online_block'])
    		    {
    		        $this->_db_disconnect();
    		        return false;
    		    }
    		    
    			$this->member['member_id'] = 0;
    			$this->member['name'] = "";
    			$this->member['members_display_name'] = "";
    			$this->member['members_seo_name'] = "";
    			$this->member['member_group_id'] = 2;
    		}
    		
    		if (!$session_id)
    		{
    			$session_id = md5(microtime().md5($member['password']));
    		}
    		
    		$uAgent = $this->findUserAgentID($_SERVER['HTTP_USER_AGENT']);
    			
    		$data = array(
							'id'					=> $session_id,
							'member_name'			=> $this->db->safesql($this->member['members_display_name']),
							'seo_name'				=> $this->db->safesql($this->member['members_seo_name']),
							'member_id'				=> intval($this->member['member_id']),
							'member_group'			=> $this->member['member_group_id'],
							'login_type'			=> 0,
							'running_time'			=> $TIME,
							'ip_address'		 	=> $this->db->safesql($this->ip()),
							'browser'				=> $this->db->safesql(substr($_SERVER['HTTP_USER_AGENT'], 0, 200)),
							//'location'				=> $location,
							'in_error'				=> 0,
							'current_appcomponent'	=> 'forums',
							'current_module'		=> '',
							'current_section'		=> '',
							'location_1_type'		=> $location,
							'location_1_id'			=> 0,
							'location_2_type'		=> '',
							'location_2_id'			=> 0,
							'location_3_type'		=> '',
							'location_3_id'			=> 0,
							'uagent_key'			=> $uAgent['uagent_key'],
							'uagent_version'		=> $uAgent['uagent_version'],
							'uagent_type'			=> $uAgent['uagent_type'],
							'uagent_bypass'			=> intval( $uAgent['uagent_bypass'] ) );
                            
            if (!(int)$this->config['ipb_version'])
                $data['location'] = $location;
    		
    		$this->db->query("REPLACE INTO " . IPB_PREFIX . "sessions (" . implode(",", array_keys($data)) . ") VALUES ('" . implode("', '", $data) . "')");
    		
    		if (!empty($this->member['member_id']))
    		{
    			setcookie($this->IPBConfig('cookie_id')."member_id",  $this->member['member_id'], time()+3600*24*365, $this->IPBConfig('cookie_path'), $domain);
    			setcookie($this->IPBConfig('cookie_id')."pass_hash",  $this->member['member_login_key'], time()+3600*7, $this->IPBConfig('cookie_path'), $domain);
    		}
    	}
    	elseif (!empty($row['id']))
    	{
    	    if (!empty($row['member_id']))
    	    {
    	        $this->member = $row;
    	    }
    	    
    		//$this->db->query("UPDATE " . IPB_PREFIX . "sessions SET running_time='$TIME', ip_address='" . $this->db->safesql($this->ip()) . "', browser='" .$this->db->safesql($_SERVER['HTTP_USER_AGENT'])."', location='$location' WHERE id='$session_id'");
    		$this->db->query("UPDATE " . IPB_PREFIX . "sessions SET running_time='$TIME', ip_address='" . $this->db->safesql($this->ip()) . "', browser='" .$this->db->safesql($_SERVER['HTTP_USER_AGENT'])."', location_1_type='$location' WHERE id='$session_id'");
    	}
    	
   		setcookie($this->IPBConfig('cookie_id')."session_id", $session_id, false, $this->IPBConfig('cookie_path'), $domain);
    	
    	if (empty($_COOKIE[$this->IPBConfig('cookie_id')."session_id"]))
    	{
    		setcookie("forum_session_id", $session_id, false, "/", ".".clean_url($_SERVER['HTTP_HOST']));
    	}
    	
      	$this->_db_disconnect();
	}
	
	public function logout()
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_logout'])
	    {
	        return true;  
	    }
    	
    	if (!empty($_SESSION['dle_name'])) 
    		$name = $_SESSION['dle_name'];
    	elseif (!empty($_COOKIE['dle_name']))
    		$name = $_COOKIE['dle_name'];
    	elseif (!empty($_SESSION['dle_user_id']) || !empty($_COOKIE['dle_user_id']))
    	{
    		$id = (empty($_SESSION['dle_user_id']))?intval($_COOKIE['dle_user_id']):intval($_SESSION['dle_user_id']);
    		
    		$name = $this->db->super_query("SELECT name FROM " . USERPREFIX . "_users WHERE user_id='$id'");
    		$name = $name['name'];
    	}
    	
    	if (!$name)
    	{
    		return false;
    	}
    	
    	$this->_db_connect();
    	
    	if (empty($this->member['member_id']))
	    {
            $this->_convert_charset($name);
	        
	        $this->member = $this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$name' LIMIT 1");
	    }
    	
	    if (!empty($this->member['member_id']))
	    {
	        $this->db->query("UPDATE " . IPB_PREFIX . "sessions SET member_name='', seo_name = '', member_id='0', running_time=" . time() . ", member_group=" . $this->IPBConfig('guest_group') . ", location_1_type='' WHERE member_id=" . $this->member['member_id']);
    	
           	$this->db->query("UPDATE " . IPB_PREFIX . "members SET last_visit='" . time() . "', last_activity='" . time() . "' WHERE member_id=" . $this->member['member_id']);
	    }
    
    	$domain = $this->GetForumDomain();
    	
    	setcookie($this->IPBConfig('cookie_id').'pass_hash' , "", time() - 3600, $this->IPBConfig('cookie_path'), $domain);
    	setcookie($this->IPBConfig('cookie_id').'member_id' ,  "", time() - 3600, $this->IPBConfig('cookie_path'), $domain);
    	setcookie($this->IPBConfig('cookie_id').'topicsread',  "", time() - 3600, $this->IPBConfig('cookie_path'), $domain);
    	setcookie($this->IPBConfig('cookie_id').'anonlogin' ,  "", time() - 3600, $this->IPBConfig('cookie_path'), $domain);
    	setcookie($this->IPBConfig('cookie_id').'forum_read',  "", time() - 3600, $this->IPBConfig('cookie_path'), $domain);
    	setcookie('forum_session_id',  "", time() - 3600, "/", ".".clean_url($_SERVER['HTTP_HOST']));
    	
    	$this->_db_disconnect();
    	
    	return true;
	}
	
	/**
	 * Create account in forum
	 *
	 * @param string $user_name
	 * @param string $password Password in md5()
	 * @param string $email
	 * @param int $add_time
	 * @return boolean
	 */
	public function CreateMember($user_name, $password, $email, $add_time = '')
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_reg'])
	    {
	        return true;
	    }
	    
	    $user_l_name = strtolower($user_name);
	    
	    $this->_convert_charset($user_l_name);
	    $this->_convert_charset($user_name);
	    $this->_convert_charset($password);
	    
	    $no_connect = true;
	    
	    if (!$this->connected)
	    {
	        $this->_db_connect();
	        $no_connect = false;
	    }
	    
	    if ($this->db->super_query("SELECT member_id FROM " . IPB_PREFIX . "members WHERE name='$user_name'"))
	    {
	        if (!$no_connect)
	        {
	            $this->_db_disconnect();
	        }
	        return true;
	    }
	
    	$salt = $this->generatePasswordSalt();
    	
    	$passhash = md5( md5( $salt ) . $password );
    	
    	if (function_exists('dle_cache'))
    	{
            $lang = dle_cache('default_language');
    	}
    	else
    	{
            $lang = false;
    	}
    	
    	if (!$add_time)
    	{
    	    $add_time = time() + ($GLOBALS['config']['date_adjust'] * 60);
    	}
    	
    	if (empty($lang))
    	{
    		$temp = $this->db->super_query("SELECT lang_id FROM " . IPB_PREFIX . "core_sys_lang WHERE lang_default=1 LIMIT 1");	
    		$lang = $temp['lang_id'];
    		if (function_exists('create_cache'))
    		{
                create_cache('default_language', $lang);
    		}
    	}
    	
    	$data['name']              		= $user_name;
    	$data['members_l_username']		= $user_l_name;
		$data['joined']					= $add_time;
		$data['email']					= $email;
		$data['member_group_id']		= $this->IPBConfig('member_group');
		$data['ip_address']				= $this->db->safesql($this->ip());
		//$data['members_created_remote']	= 0;
		$data['member_login_key']		= md5($this->generatePasswordSalt(60));
		$data['member_login_key_expire']= ( $this->IPBConfig('login_key_expire') ) ? ( time() + ( intval( $this->IPBConfig('login_key_expire') ) * 86400 ) ) : 0;
		//$data['view_sigs']				= 1;
		//$data['email_pm']				= 1;
		//$data['view_img']				= 1;
		//$data['view_avs']				= 1;
		//$data['restrict_post']			= 0;
		//$data['view_pop']				= 1;
		//$data['hide_email']				= 1;
		$data['allow_admin_mails']	    = 1;
		//$data['msg_count_total']		= 0;
		//$data['msg_count_new']			= 0;
		//$data['msg_show_notification']	= 1;
		//$data['coppa_user']				= 0;
		//$data['auto_track']				= 0;
		//$data['members_auto_dst']	    = 0;
		//$data['dst_in_use']       	    = 0;
		$data['last_visit']				= $add_time;
		$data['last_activity']			= $add_time;
		$data['language']				= $lang;
		//$data['members_editor_choice']	= $this->IPBConfig('ips_default_editor');
		$data['members_pass_salt']		= $this->db->safesql($salt);
		$data['members_pass_hash']		= $passhash;
		$data['members_display_name']	= $user_name;
		$data['members_l_display_name']	= $user_l_name;
		//$data['fb_uid']	 	            = 0;
		//$data['time_offset']	        = 0;
		//$data['fb_emailhash']	        = '';
		$data['members_seo_name']       = $user_name;
    	
    	$this->db->query("INSERT INTO " . IPB_PREFIX . "members (" . implode(",", array_keys($data)) . ") VALUES ('" . implode("', '", $data) . "')");
        $id = $this->db->insert_id();
    	$this->db->query("INSERT INTO " . IPB_PREFIX . "pfields_content (member_id) VALUES ('$id')");
    	$this->db->query("INSERT INTO " . IPB_PREFIX . "profile_portal (pp_member_id, pp_setting_count_friends, pp_setting_count_comments) VALUES ('$id', 1, 1)");
    	
    	$count_most = $this->db->super_query("SELECT cs_value FROM " . IPB_PREFIX . "cache_store WHERE cs_key='stats'");
    	$old_values = unserialize($count_most['cs_value']);
    	$values = array(
    					'total_replies' => $old_values['total_replies'],
        				'total_topics' =>  $old_values['total_topics'],
        				'mem_count' => $old_values['mem_count'] + 1,
        				'last_mem_name' => $user_name,
        				'last_mem_id' => $id,
        				'most_count' => $old_values['most_count'],
        				'most_date' => $old_values['most_date'], 
        				);
                        
        if (!(int)$this->config['ipb_version'])
        {
            $this->db->query("REPLACE INTO " . IPB_PREFIX . "cache_store (cs_key, cs_value, cs_extra, cs_array, cs_updated) VALUES ('stats', '" . $this->db->safesql(serialize($values)) . "', '', '1', " . time() . ")");
        }
        else
        {
            $this->db->query("REPLACE INTO " . IPB_PREFIX . "cache_store (cs_key, cs_value, cs_array, cs_updated) VALUES ('stats', '" . $this->db->safesql(serialize($values)) . "', 1, " . time() . ")");
        }
    
    	if (!$no_connect)
    	{
    	    $this->_db_disconnect();
    	}
    	
    	$this->member = $data;
    	$this->member['member_id'] = $id;
    	
    	return true;
	}
	
	public function UpdateRegister($user_name, $land, $icq, $info)
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_reg'])
	    {
	        return true;
	    }
	    
	    $this->_db_connect();
	    
        $this->_convert_charset($user_name);
        $this->_convert_charset($land);
        $this->_convert_charset($icq);
        $this->_convert_charset($info);
	    
	    if (empty($this->member['member_id']))
	    {
	        $this->member = $this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$user_name' LIMIT 1");
	    }
	    
	    if (empty($this->member['member_id']))
	    {
	        return false;
	    }
	    
	    $this->db->query("UPDATE " . IPB_PREFIX . "pfields_content SET field_4='$icq', field_6='$land', field_7='$info' WHERE member_id='{$this->member['member_id']}'");
	    
	    $this->_db_disconnect();
	    
	    return true;
	}
	
	public function UpdateProfile($user_name, $email, $password, $icq, $land, $info, $admin = false)
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_profile'])
	    {
	        return true;
	    }
	    
	    if ($admin)
	    {
	        $user_dle = $this->db->super_query("SELECT * FROM " . USERPREFIX . "_users WHERE user_id=" . $user_name);
	        
	        if (empty($user_dle['name']))
	        {
	            return false;
	        }
	        else
	        {
	            $user_name = $this->db->safesql($user_dle['name']);
	        }
	    }
	    
	    $this->_convert_charset($user_name);
        $this->_convert_charset($land);
        $this->_convert_charset($icq);
        $this->_convert_charset($info);
	    
	    $this->_db_connect();
	    
	    if (empty($this->member['member_id']))
	    {
	        $this->member = $this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$user_name' LIMIT 1");
	    }
	    
	    if (empty($this->member['member_id']))
	    {
	        $this->_db_disconnect();
	        return false;
	    }
	
    	if (strlen($password) > 0)
    	{
    	    $salt = $this->generatePasswordSalt();
    	    
	        $this->_convert_charset($password);
	        $this->_convert_charset($salt);
	    
    	    if ($admin || $GLOBALS['config']['version_id'] > 8.2)
    	    {
            	$password			= html_entity_decode($password, ENT_QUOTES);
        		$html_entities		= array( "&#33;", "&#036;", "&#092;" );
        		$replacement_char	= array( "!", "$", "\\" );
        		$password 			= str_replace( $html_entities, $replacement_char, $password );
    	    }
    	    
    		$passhash = md5(md5($salt) . md5($password));
    		
    		$change_pass = ", members_pass_salt='" . $this->db->safesql($salt) . "', members_pass_hash='$passhash'";
    	} 
    	else
    	{
    	    $change_pass = '';
    	}
       
    	$this->db->query("UPDATE " . IPB_PREFIX . "members SET email='$email'$change_pass WHERE member_id=" . $this->member['member_id']); 
    	
    	$this->db->query("UPDATE " . IPB_PREFIX . "pfields_content SET field_4='$icq', field_6='$land', field_7='$info' WHERE member_id='{$this->member['member_id']}'");
    	 
    	$this->_db_disconnect();
    	
    	return true;
	}
	
	public function LostPassword($user_name, $new_pass)
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_lostpass'])
	    {
	        return true;
	    }
    	
    	$this->_db_connect();
    	
        $this->_convert_charset($user_name);
        $this->_convert_charset($new_pass);
    	
        $salt = $this->generatePasswordSalt();
       	$passhash = md5(md5($salt) . md5($new_pass));
       	
       	$this->db->query("UPDATE " . IPB_PREFIX . "members SET members_pass_hash='$passhash', members_pass_salt='".$this->db->safesql($salt)."' WHERE name='$user_name'");
       	
    	$this->_db_disconnect();
	}
	
	public function ChangeUserName($member_id, $new_name)
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_admin'])
	    {
	        return true;
	    }
	    
	    if (empty($this->member['name']))
	    {
	        $member = $this->db->super_query("SELECT name FROM " . USERPREFIX . "_users WHERE user_id=" . $member_id);
    	    
    	    if (empty($member['name']))
    	    {
    	        return false;
    	    }
    	    else
    	    {
    	        $name = $this->db->safesql($member['name']);
    	    }
	    }
	    else
	    {
	        $name = $this->db->safesql($this->member['name']);
	    }
	    
	    $this->_db_connect();
	    
        $new_name_l = strtolower($new_name);
        $this->_convert_charset($name);
        $this->_convert_charset($new_name_l);
        $this->_convert_charset($new_name);
	    
	    if ($this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$new_name' OR members_display_name='$new_name'"))
	    {
	        $this->_db_disconnect();
	        return false;
	    }
	    
	    $this->db->query("UPDATE " . IPB_PREFIX . "members SET name='$new_name', members_l_username='$new_name_l', members_l_display_name='$new_name_l', members_display_name='$new_name', members_seo_name='$new_name' WHERE name='$name'");
	    $this->db->query("UPDATE " . IPB_PREFIX . "moderators SET member_name='$new_name' WHERE member_name='$name'");
	    $this->db->query("UPDATE " . IPB_PREFIX . "forums SET last_poster_name='$new_name', seo_last_name='$new_name' WHERE last_poster_name='$name'");
	    $this->db->query("UPDATE " . IPB_PREFIX . "sessions SET member_name='$new_name', seo_name='$new_name' WHERE member_name='$name'");
	    $this->db->query("UPDATE " . IPB_PREFIX . "topics SET starter_name='$new_name', seo_first_name='$new_name' WHERE starter_name='$name'");
	    $this->db->query("UPDATE " . IPB_PREFIX . "topics SET last_poster_name='$new_name', seo_last_name='$new_name' WHERE last_poster_name='$name'");
	    
	    $this->_db_disconnect();
	}
	
	public function DeleteUser($user_name)
	{
	    if (!$this->config['allow_module'] || !$this->config['allow_admin'])
	    {
	        return true;
	    }
	    
        $this->_convert_charset($user_name);
	    
	    $user_name = $this->_db_connect()->safesql($user_name);
	    
        $member = $this->db->super_query("SELECT * FROM " . IPB_PREFIX . "members WHERE name='$user_name' LIMIT 1");
	    
	    if (empty($member['member_id']))
	    {
	        $this->_db_disconnect();
	        return false;
	    }
	    
	    $delete_files = array();
	    
		$this->db->query("SELECT * FROM " . IPB_PREFIX . "profile_portal WHERE pp_member_id=" . $member['member_id']);

		while( $r = $this->db->get_row())
		{
			if ( $r['pp_main_photo']  )
			{
				$delete_files[] = $r['pp_main_photo'];
			}

			if ( $r['pp_thumb_photo']  )
			{
				$delete_files[] = $r['pp_thumb_photo'];
			}
			
			if ( $r['avatar_type'] == 'upload' and $r['avatar_location'] )
			{
				$delete_files[] = $r['avatar_location'];
			}
		}

		$this->db->query("UPDATE " . IPB_PREFIX . "posts SET author_id=0 WHERE author_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "topics SET starter_id=0 WHERE starter_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "announcements SET announce_member_id=0 WHERE announce_member_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "attachments SET attach_member_id=0 WHERE attach_member_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "polls SET starter_id=0 WHERE starter_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "voters SET member_id=0 WHERE member_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "forums SET last_poster_name='' WHERE last_poster_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "forums SET seo_last_name='' WHERE last_poster_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "forums SET seo_last_name='' WHERE last_poster_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "profile_ratings SET rating_by_member_id=0 WHERE rating_by_member_id=" . $member['member_id']);

        if (!(int)$this->config['ipb_version'])
        {
            $this->db->query("UPDATE " . IPB_PREFIX . "profile_comments SET comment_by_member_id=0 WHERE comment_by_member_id=" . $member['member_id']);
            $this->db->query("DELETE FROM " . IPB_PREFIX . "profile_comments WHERE comment_for_member_id=" . $member['member_id']);
        }
        $this->db->query("DELETE FROM " . IPB_PREFIX . "profile_ratings WHERE rating_for_member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "profile_portal WHERE pp_member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "profile_portal_views WHERE views_member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "profile_friends WHERE friends_member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "profile_friends WHERE friends_friend_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "dnames_change WHERE dname_member_id=" . $member['member_id']);
        
        $this->db->query("DELETE FROM " . IPB_PREFIX . "pfields_content WHERE member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "members_partial WHERE partial_member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "moderators WHERE member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "sessions WHERE member_id=" . $member['member_id']);
        $this->db->query("DELETE FROM " . IPB_PREFIX . "warn_logs WHERE wlog_mid=" . $member['member_id']);
        $this->db->query("UPDATE " . IPB_PREFIX . "warn_logs SET wlog_addedby=0 WHERE wlog_addedby=" . $member['member_id']);

		$this->db->query("DELETE FROM " . IPB_PREFIX . "admin_permission_rows WHERE row_id_type='member' AND row_id=" . $member['member_id']);
		$this->db->query("DELETE FROM " . IPB_PREFIX . "core_sys_cp_sessions WHERE session_member_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "upgrade_history SET upgrade_mid=0 WHERE upgrade_mid=" . $member['member_id']);
		
		$this->db->query("DELETE FROM " . IPB_PREFIX . "message_topic_user_map WHERE map_user_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "message_posts SET msg_author_id=0 WHERE msg_author_id=" . $member['member_id']);
		$this->db->query("UPDATE " . IPB_PREFIX . "message_topics SET mt_starter_id=0 WHERE mt_starter_id=" . $member['member_id']);
		$this->db->query("DELETE FROM " . IPB_PREFIX . "ignored_users WHERE ignore_owner_id=" . $member['member_id'] . " or ignore_ignore_id=" . $member['member_id']);
		
        if (!(int)$this->config['ipb_version'])
        {
            $this->db->query("DELETE FROM " . IPB_PREFIX . "tracker WHERE member_id=" . $member['member_id']);
            $this->db->query("DELETE FROM " . IPB_PREFIX . "forum_tracker WHERE member_id=" . $member['member_id']);
        }
		$this->db->query("DELETE FROM " . IPB_PREFIX . "core_item_markers WHERE item_member_id=" . $member['member_id']);
		$this->db->query("DELETE FROM " . IPB_PREFIX . "validating WHERE member_id=" . $member['member_id']);
		$this->db->query("DELETE FROM " . IPB_PREFIX . "members WHERE member_id=" . $member['member_id']);

		$this->_db_disconnect();

		if ( count($delete_files) )
		{
			foreach( $delete_files as $file )
			{
				@unlink( $this->IPBConfig('upload_dir') . "/" . $file );
			}
		}
	}
	
	/**
	 * Generate block with last post in forum
	 *
	 * @param dle_template $tpl
	 * @return string
	 */
	public function last_forum_posts(dle_template &$tpl)
    {
    	if (!$this->config['allow_module'] || !$this->config['allow_forum_block'])
    	{
    	    return '';
    	}
    	
    	if ((int)$this->config['block_new_cache_time'] && 
    		file_exists(ENGINE_DIR . "/cache/last_forum_posts.tmp") &&
    		(time() - filemtime(ENGINE_DIR . "/cache/last_forum_posts.tmp")) > (int)$this->config['block_new_cache_time'] &&
    		$cache = dle_cache("last_forum_posts")
    		)
    		{
    		    $tpl->result['last_forum_posts'] = $cache;
    		}
    		
   	    $this->_db_connect();
    		
    	if ($this->config['bad_forum_for_block'] != "")
    	{
    		$forum_bad = explode(",", $this->config['bad_forum_for_block']);
    		$forum_id = " WHERE forum_id NOT IN('". implode("','", $forum_bad) ."')";
    	}
    	elseif ($this->config['good_forum_for_block'] != "")
    	{	
    		$forum_good = explode(",", $this->config['good_forum_for_block']);
    		$forum_id = " WHERE forum_id IN('". implode("','", $forum_good) ."')";
    	}
    	else
    	{
    		$forum_id = "";
    	}
    		
    	if ($forum_id !="")
    	{
    		$forum_id .= " AND state='open' AND approved=1";
    	}
    	else 
    	{
    		$forum_id .= " WHERE state='open' AND approved=1";
    	}
    	
    	if (!(int)$this->config['count_post'])
    	{
    	    die("Не указано количество постов для блока сообщений с форума");
    	}

    	$result = $this->db->query("SELECT t.posts, 
                                           t.views, 
                                           t.forum_id, 
                                           t.tid, 
                                           t.title, 
                                           t.title_seo, 
                                           t.last_post, 
                                           t.last_poster_name, 
                                           t.last_poster_id, 
                                           t.starter_name, 
                                           t.starter_id, 
                                           f.name AS forum_name, 
                                           t.seo_last_name, 
                                           t.seo_first_name, 
                                           f.name_seo AS fname_seo 
                                   FROM " . IPB_PREFIX . "topics AS t
    						  LEFT JOIN " . IPB_PREFIX . "forums AS f
    						  ON f.id=t.forum_id
    						  ". $forum_id ." ORDER BY t.last_post DESC LIMIT 0 ," . (int)$this->config['count_post']);
    
    	$tpl->load_template('block_forum_posts.tpl');
    	preg_match("'\[row\](.*?)\[/row\]'si", $tpl->copy_template, $matches);
    	
    	while ($row = $this->db->get_row($result))
    	{
	        foreach ($row as &$value)
	        {
	            $this->_convert_charset($value, true);
	        }
    	    
    		$short_name=$name=$row["title"];
    		quoted_printable_decode($name);
    		
    		if ($this->config['leght_name'])
    		{
    		    if (strlen($name) > $this->config['leght_name'])
        		{
        		    if (function_exists('mb_substr'))
        		    {
        		        $short_name = mb_substr ($name, 0, $this->config['leght_name'], DLE_CHARSET)." ..."; 	
        		    }
        		    else 
        		    {
        		        $short_name = substr ($name, 0, $this->config['leght_name'])." ...";
        		    }
        		}
    		}
            
            //$server_offset = timezone_offset_get();
            
            if (isset($this->member['time_offset']) && (int)$this->member['time_offset'] && !((int)$this->config['block_new_cache_time']))
            {
                $row["last_post"] += ($this->member['time_offset'] - $this->IPBConfig('time_offset'))*3600;
            }
            /*
            else if ((int)$this->IPBConfig('time_offset'))
            {
                //$row["last_post"] += $this->IPBConfig('time_offset')*3600;
            }*/
    		
    		switch (date("d.m.Y", $row["last_post"]))
    		{
    			case date("d.m.Y"):
    	        	$date=date($this->lang['today_in'] . "H:i", $row["last_post"]);	
    	    		break;
    	    		
    			case date("d.m.Y", time()-86400):
    	            $date=date($this->lang['yestoday_in'] . "H:i", $row["last_post"]);	
    	    		break;
    	    		
    	    	default:
    	    		$date=date("d.m.Y H:i", $row["last_post"]);
    		}
    		
    		$replace = array('{user}'=> $row['last_poster_name'],
    						'{user_url}' => ($this->config['forum_block_alt_url'])?
                                            $this->ipb_config['board_url'] . "/user/{$row['last_poster_id']}-{$row['seo_last_name']}/":
                                            $this->ipb_config['board_url'] . "/index.php?showuser=".$row['last_poster_id']
                                            ,
    						'{author_url}' => ($this->config['forum_block_alt_url'])?
                                            $this->ipb_config['board_url']."/user/{$row['starter_id']}-{$row['seo_first_name']}/":
                                            $this->ipb_config['board_url']."/index.php?showuser=".$row['starter_id'],
    						'{reply_count}'=> $row["posts"],
    						'{view_count}'=> $row["views"],
    						'{full_name}'=> $name,
    						'{author}'=> $row['starter_name'],
    						'{forum}'=> $row['forum_name'],
    						'{forum_url}'=> ($this->config['forum_block_alt_url'])?
                                            $this->ipb_config['board_url']."/forum/{$row['forum_id']}-{$row['fname_seo']}/":
                                            $this->ipb_config['board_url']."/index.php?showforum=".$row['forum_id'],
    						'{post_url}'=>  ($this->config['forum_block_alt_url'])?
                                            $this->ipb_config['board_url']."/topic/{$row['tid']}-{$row['title_seo']}/page__view__getnewpost":
                                            $this->ipb_config['board_url']."/index.php?showtopic=".$row["tid"]."&amp;view=getnewpost",
    						'{shot_name_post}'=> $short_name,
    						'{date}'=> $date,);
    	
    		$tpl->copy_template = strtr($tpl->copy_template, $replace);
    		$tpl->copy_template = preg_replace("'\[row\](.*?)\[/row\]'si", "\\1\n".$matches[0], $tpl->copy_template);
    	}
     	$tpl->set_block("'\[row\](.*?)\[/row\]'si", "");
    	$tpl->compile('block_forum_posts');
    	$tpl->clear();
     	$this->db->free();
     	
    	$this->_db_disconnect();
    	
    	if ((int)$this->config['block_new_cache_time'])
    	{
    		create_cache("last_forum_posts", $tpl->result['block_forum_posts']);
    	}
    }
    
    public function birthday_user_forum(dle_template &$tpl)
    {
    	if (!$this->config['allow_module'] || !$this->config['allow_birthday_block'])
    	{
    	    return true;
    	}
    	
    	if ((int)$this->config['birthday_cache_time'] && 
    		file_exists(ENGINE_DIR . "/cache/birthday_user_forum.tmp") &&
    		(time() - filemtime(ENGINE_DIR . "/cache/birthday_user_forum.tmp")) > (int)$this->config['birthday_cache_time'] &&
    		$cache = dle_cache("birthday_user_forum")
    		)
    		{
                $tpl->result['birthday_user_forum'] = $cache;
                
                return true;
    		}
    	
    	$this->_db_connect();
    	
    	if (!(int)$this->config['count_birthday'])
    	{
    	    die("Не задано количество вывода для блока имениников");
    	}
    	
    	$result = $this->db->query("SELECT member_id, name, bday_year FROM " . IPB_PREFIX . "members WHERE bday_month=MONTH(CURDATE()) AND bday_day=DAY(CURDATE()) ORDER BY name ASC LIMIT 0 ,".$this->config['count_birthday']);
    	
    	$block = ""; $i = 0;
    	
    	while ($row = $this->db->get_row($result))
    	{
	        foreach ($row as &$value)
	        {
	            $this->_convert_charset($value, true);
	        }
    	    
    		if ($i != 0 && $block != "") 
    		{
    		    $block .= $this->config['spacer'];
    		}
    		
    		if ($row['bday_year'] == "0000" || $row['bday_year'] == "")
    		{
    		    $age = "n/a"; 
    		}
    		else 
    		{
    			$age = date("Y", time()) - $row['bday_year'];
    		}
    
    		$user = preg_replace('/{name}/',$row['name'], $this->config['birthday_block']);
    		$user = preg_replace('/{age}/',$age, $user);
    		$user = preg_replace('/{user_url}/',$this->ipb_config['board_url']."/index.php?showuser=".$row["member_id"], $user);
    
    		$block.=$user;
    		$i++;
    	}
    	if ($block == "")
    	{
    		$block = $this->config['no_user_birthday'];
    	}
    	
     	$this->db->free();
    	
     	$this->_db_disconnect();
     	
     	if ((int)$this->config['birthday_cache_time'])
     	{
    		create_cache("birthday_user_forum", $block);
     	}
     			
    	$tpl->result['birthday_user_forum'] = $block;
    }

    protected function browser($useragent)
    {
    	$browser_type = "Unknown";
    	$browser_version = "";
    	if (@preg_match('#MSIE ([0-9].[0-9]{1,2})#', $useragent, $version)) {
    		$browser_type = "Internet Explorer";
    		$browser_version = $version[1];
    	} elseif (@preg_match('#Opera ([0-9].[0-9]{1,2})#', $useragent, $version)) {
    		$browser_type = "Opera";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/Opera/i', $useragent)) {
    		$browser_type = "Opera";
    		$val = stristr($useragent, "opera");
    		if (preg_match("#/#", $val)){
    			$val = explode("/",$val);
    			$browser_type = $val[0];
    			$val = explode(" ",$val[1]);
    			$browser_version  = $val[0];
    		} else {
    			$val = explode(" ",stristr($val,"opera"));
    			$browser_type = $val[0];
    			$browser_version  = $val[1];
    		}
    	} elseif (@preg_match('/Firefox\/(.*)/i', $useragent, $version)) {
    		$browser_type = "Firefox";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/SeaMonkey\/(.*)/i', $useragent, $version)) {
    		$browser_type = "SeaMonkey";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/Minimo\/(.*)/i', $useragent, $version)) {
    		$browser_type = "Minimo";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/K-Meleon\/(.*)/i', $useragent, $version)) {
    		$browser_type = "K-Meleon";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/Epiphany\/(.*)/i', $useragent, $version)) {
    		$browser_type = "Epiphany";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/Flock\/(.*)/i', $useragent, $version)) {
    		$browser_type = "Flock";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/Camino\/(.*)/i', $useragent, $version)) {
    		$browser_type = "Camino";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/Firebird\/(.*)/i', $useragent, $version)) {
    		$browser_type = "Firebird";
    		$browser_version = $version[1];
    	} elseif (@preg_match('/Safari/i', $useragent)) {
    		$browser_type = "Safari";
    		$browser_version = "";
    	} elseif (@preg_match('/avantbrowser/i', $useragent)) {
    		$browser_type = "Avant Browser";
    		$browser_version = "";
    	} elseif (@preg_match('/America Online Browser [^0-9,.,a-z,A-Z]/i', $useragent)) {
    		$browser_type = "Avant Browser";
    		$browser_version = "";
    	} elseif (@preg_match('/libwww/i', $useragent)) {
    		if (@preg_match('/amaya/i', $useragent)) {
    			$browser_type = "Amaya";
    			$val = explode("/",stristr($useragent,"amaya"));
    			$val = explode(" ", $val[1]);
    			$browser_version = $val[0];
    		} else {
    			$browser_type = "Lynx";
    			$val = explode("/",$useragent);
    			$browser_version = $val[1];
    		}
    	} elseif (@preg_match('#Mozilla/([0-9].[0-9]{1,2})#i'. $useragent, $version)) {
    		$browser_type = "Netscape";
    		$browser_version = $version[1];
    	}
    
    	return $browser_type." ".$browser_version;
    }

    protected function robots($useragent)
    {
    	$r_or=false;
    	$remap_agents = array (
    		'antabot'			=>	'antabot (private)',
    		'aport'				=>	'Aport',
    		'Ask Jeeves'		=>	'Ask Jeeves',
    		'Asterias'			=>	'Singingfish Spider',
    		'Baiduspider'		=>	'Baidu Spider',
    		'Feedfetcher-Google'=>	'Feedfetcher-Google',
    		'GameSpyHTTP'		=>	'GameSpy HTTP',
    		'GigaBlast'			=>	'GigaBlast',
    		'Gigabot'			=>	'Gigabot',
    		'Accoona'			=>	'Google.com',
    		'Googlebot-Image'	=>	'Googlebot-Image',
    		'Googlebot'			=>	'Googlebot',
    		'grub-client'		=>	'Grub',
    		'gsa-crawler'		=>	'Google Search Appliance',
    		'Slurp'				=>	'Inktomi Spider',
    		'slurp@inktomi'		=>	'Hot Bot',
    
    		'lycos'				=>	'Lycos.com',
    		'whatuseek'			=>	'What You Seek',
    		'ia_archiver'		=>	'Alexa',
    		'is_archiver'		=>	'Archive.org',
    		'archive_org'		=>	'Archive.org',
    
    		'YandexBlog'		=>	'YandexBlog',
    		'YandexSomething'	=>	'YandexSomething',
    		'Yandex'			=>	'Yandex',
    		'StackRambler'		=>	'Rambler',
    
    		'WebAlta Crawler'	=>	'WebAlta Crawler',
    
    		'Yahoo'				=>	'Yahoo',
    		'zyborg@looksmart'	=>	'WiseNut',
    		'WebCrawler'		=>	'Fast',
    		'Openbot'			=>	'Openfind',
    		'TurtleScanner'		=>	'Turtle',
    		'libwww'			=>	'Punto',
    
    		'msnbot'			=>  'MSN',
    		'MnoGoSearch'		=>  'mnoGoSearch',
    		'booch'				=>  'booch_Bot',
    		'WebZIP'			=>	'WebZIP',
    		'GetSmart'			=>	'GetSmart',
    		'NaverBot'			=>	'NaverBot',
    		'Vampire'			=>	'Net_Vampire',
    		'ZipppBot'			=>	'ZipppBot',
    
    		'W3C_Validator'		=>	'W3C Validator',
    		'W3C_CSS_Validator'	=>	'W3C CSS Validator',
    	);
    
    	$remap_agents=array_change_key_case($remap_agents, CASE_LOWER);
    
    	$pmatch_agents="";
    	foreach ($remap_agents as $k => $v) {
    	$pmatch_agents.=$k."|";
    	}
    	$pmatch_agents=substr_replace($pmatch_agents, '', strlen($pmatch_agents)-1, 1);
    
    	if (preg_match( '/('.$pmatch_agents.')/i', $useragent, $match ))
    
    	if (count($match)) {
    		$r_or = @$remap_agents[strtolower($match[1])];
    	}
    	
    	return $r_or;
    }

    protected function os($useragent)
    {
    	$os = 'Unknown';
    	if(strpos($useragent, "Win") !== false) 
    	{
    		if(strpos($useragent, "NT 7") !== false) $os = 'Windows Seven';
    		if(strpos($useragent, "NT 6.1") !== false) $os = 'Windows Seven';
    		if(strpos($useragent, "NT 6.0") !== false) $os = 'Windows Vista';
    		if(strpos($useragent, "NT 5.2") !== false) $os = 'Windows Server 2003 ??? XPx64';
    		if(strpos($useragent, "NT 5.1") !== false || strpos($useragent, "Win32") !== false || strpos($useragent, "XP")) $os = 'Windows XP';
   			if(strpos($useragent, "NT 5.0") !== false) $os = 'Windows 2000';
   			if(strpos($useragent, "NT 4.0") !== false || strpos($useragent, "3.5") !== false) $os = 'Windows NT';
   			if(strpos($useragent, "Me") !== false) $os = 'Windows Me';
   			if(strpos($useragent, "98") !== false) $os = 'Windows 98';
   			if(strpos($useragent, "95") !== false) $os = 'Windows 95';
   		}
    	
   		if(strpos($useragent, "Linux")    !== false
   		|| strpos($useragent, "Lynx")     !== false
   		|| strpos($useragent, "Unix")     !== false) $os = 'Linux';
   		if(strpos($useragent, "Macintosh")!== false
   		|| strpos($useragent, "PowerPC")) $os = 'Macintosh';
   		if(strpos($useragent, "OS/2")!== false) $os = 'OS/2';
   		if(strpos($useragent, "BeOS")!== false) $os = 'BeOS';
   	
   		return $os;
   	}

    protected function changeend($value,$v1,$v2,$v3)
   	{
    	$endingret="";
    	if (substr($value,-1)==1) $endingret = $v1;
    	if (substr($value,-1)==2) $endingret = $v2;
    	if (substr($value,-1)==3) $endingret = $v2;
    	if (substr($value,-1)==4) $endingret = $v2;
    	if (substr($value,-2)==11) $endingret = $v3;
    	if (substr($value,-2)==12) $endingret = $v3;
    	if (substr($value,-2)==13) $endingret = $v3;
    	if (substr($value,-2)==14) $endingret = $v3;
    	if (empty($endingret)) $endingret = $v3;
    	
    	return $endingret;
    }

    protected function timeagos($timestamp)
    {
    	$current_time = time();
    	$difference = $current_time - $timestamp;
    
    	$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);
    
    	for ($val = sizeof($lengths) - 1; ($val >= 0) && (($number = $difference / $lengths[$val]) <= 1); $val--);
    
    	if ($val < 0) $val = 0;
    	$new_time = $current_time - ($difference % $lengths[$val]);
    	$number = floor($number);
    
    	switch ($val) {
    		case 0: $stamp = $this->changeend($number,$this->lang['stamp01'],$this->lang['stamp02'],$this->lang['stamp03']); break;
    		case 1: $stamp = $this->changeend($number,$this->lang['stamp11'],$this->lang['stamp12'],$this->lang['stamp13']); break;
    		case 2: $stamp = $this->changeend($number,$this->lang['stamp21'],$this->lang['stamp22'],$this->lang['stamp23']); break;
    		case 3: $stamp = $this->changeend($number,$this->lang['stamp31'],$this->lang['stamp32'],$this->lang['stamp33']); break;
    		case 4: $stamp = $this->changeend($number,$this->lang['stamp41'],$this->lang['stamp42'],$this->lang['stamp43']); break;
    		case 5: $stamp = $this->changeend($number,$this->lang['stamp51'],$this->lang['stamp52'],$this->lang['stamp53']); break;
    		case 6: $stamp = $this->changeend($number,$this->lang['stamp61'],$this->lang['stamp62'],$this->lang['stamp63']); break;
    		case 5: $stamp = $this->changeend($number,$this->lang['stamp71'],$this->lang['stamp72'],$this->lang['stamp73']); break;
    	}
    	$text = sprintf("%d %s ", $number, $stamp);
    	if (($val >= 1) && (($current_time - $new_time) > 0)){
    		$text .= $this->timeagos($new_time);
    	}
    	
    	return $text;
    }
    
    public function block_online(dle_template &$tpl)
    {
    	if (!$this->config['allow_module'] || !$this->config['allow_online_block'])
    	{
    	    return false;
    	}
    	
    	if ((int)$this->config['block_online_cache_time'] && 
    		file_exists(ENGINE_DIR . "/cache/block_online.tmp") &&
    		(time() - filemtime(ENGINE_DIR . "/cache/block_online.tmp")) > (int)$this->config['block_online_cache_time'] &&
    		$cache = dle_cache("block_online")
    		)
    		{
    		    $tpl->result['block_online'] = $cache;
    		    return true;
    		}
    		
    	$this->_db_connect();
    	
    	$this->db->query("SELECT member_id, ip_address, running_time, location_1_type, browser, member_name FROM " . IPB_PREFIX . "sessions WHERE running_time>".(time()-$this->config['online_time']));
    	
    	$users = $robots = $onl_onlinebots = array(); $guests = $count_user = $count_robots = 0;
    	while ($user = $this->db->get_row())
    	{
	        foreach ($user as &$value)
	        {
	            $this->_convert_charset($value, true);
	        }
    	    
    		if($user['member_id']==0) 
    		{
    			$current_robot = $this->robots($user['browser']);
    			if ($current_robot!="")
    			{
    				if ($onl_onlinebots[$current_robot]['lastactivity']<$user['running_time'])
    				{
    					$robots[$current_robot]['name']=$current_robot;
    					$robots[$current_robot]['lastactivity']=$user['running_time'];
    					$robots[$current_robot]['host']=$user['ip_address'];
    					$robots[$current_robot]['location']=$user['location_1_type'];
    				}
    			}
    			else
    				$guests++;
    		}
    		else
    		{
    			if ($users[$user['member_id']]['lastactivity']<$user['running_time'])
    			{
    				$users[$user['member_id']]['username']=$user['member_name'];
    				$users[$user['member_id']]['lastactivity']=$user['running_time'];
    				$users[$user['member_id']]['useragent']=$user['browser'];
    				$users[$user['member_id']]['host']=$user['ip_address'];
    				$users[$user['member_id']]['location']=$user['location_1_type'];
    			}
    		}
    	}
    
    	$location_array = array("%addcomments%" => $this->lang['paddcomments'],
    							"%readnews%"	=> $this->lang['preadnews'],
    							"%incategory%"	=> $this->lang['pincategory'],
    							"%posin%"		=> $this->lang['pposin'],
    							"%mainpage%"	=> $this->lang['pmainpage'],
    							"%view_pofile%"	=> $this->lang['view_profile'],
    							"%newposts%"	=> $this->lang['newposts'],
    							"%view_stats%"	=> $this->lang['view_stats']);
    	if (count($users))
    	{
    		foreach ($users AS $id=>$value)
    		{
    		    $user_array[$value['username']]['desc'] = '';
    			if(@$GLOBALS['member_id']['user_group'] == 1)
    			{
    				$user_array[$value['username']]['desc'] .= $this->lang['os'].$this->os($value['useragent']).'<br />' . $this->lang['browser'].$this->browser($users[$id]['useragent']).'<br />' . '<b>IP:</b>&nbsp;'.$users[$id]['host'].'<br />';
    			}
    
    			$user_array[$value['username']]['desc'] .= $this->lang['was'].$this->timeagos($users[$id]['lastactivity']).$this->lang['back'].'<br />' . $this->lang['location'];
    			if (preg_match("'%(.*?)%'si", $users[$id]['location']))
    			{
    				foreach ($location_array as $find => $replace)
    				{
    					$users[$id]['location'] = str_replace($find, $replace, $users[$id]['location']);
    				}
    			}
    			else 
    				$users[$id]['location'] = $this->lang['pforum'];
    			$user_array[$value['username']]['desc'] .= $users[$id]['location']."<br/>";
    			$user_array[$value['username']]['id'] = $id;
    			$count_user++;
    		}
    	}
   
    	if (count($robots))
    	{
    		foreach ($robots AS $name=>$value)
    		{
    			if(!empty($GLOBALS['member_id']['user_group']) && $GLOBALS['member_id']['user_group'] == 1)
    				$robot_array[$name]= $this->lang['os'].$this->os($robots[$name]['useragent']).'<br />' . $this->lang['browser'].$this->browser($robots[$name]['useragent']).'<br />' . '<b>IP:</b>&nbsp;'.$robots[$name]['host'].'<br />';
    
    			$robot_array[$name] .= $this->lang['was'].$this->timeagos($robots[$name]['lastactivity']).$this->lang['back'].'<br />' . $this->lang['location'];
    			if (preg_match("'%(.*?)%'si", $robots[$name]['location']))
    			{
    				foreach ($location_array as $find => $replace)
    				{
    					$robots[$name]['location'] = str_replace($find, $replace, $robots[$name]['location']);
    				}
    			}
    			else 
    				$robots[$name]['location'] = $this->lang['pforum'];
    			$robot_array[$name] .= $robots[$name]['location']."<br/>";
    			$count_robots++;
    		}
    	}
    	
    	$users = ""; $i=0;
    	if (count($user_array))
    	{
    		foreach ($user_array as $name=>$desc)
    		{
    			if ($i) $users .= $this->config['separator'];
    			$desc['desc'] = htmlspecialchars($desc['desc'], ENT_QUOTES);
    			$users .= "<a onmouseover=\"showhint('{$desc['desc']}', this, event, '180px');\" href=\"{$this->ipb_config['board_url']}/index.php?showuser={$desc['id']}\" >".$name."</a>"; 
    			$i++;
    		}
    	}
    	else 
    		$users = $this->lang['notusers'];	
    		
    	$robots = ""; $i = 0;
    	if (count($robot_array))
    	{
    		foreach ($robot_array as $name=>$desc)
    		{
    			if ($i) $robots .= $this->config['separator'];
    			$desc = htmlspecialchars($desc, ENT_QUOTES);
    			$robots .= "<span onmouseover=\"showhint('{$desc}', this, event, '180px');\"  style=\"cursor:hand;\" >".$name."</span>"; 
    			$i++;
    		}
    	}
    	else 
    		$robots = $this->lang['notbots'];
    	
    	$tpl->load_template('block_online.tpl');
    	$tpl->set('{users}',$count_user);
    	$tpl->set('{guest}',$guests);
    	$tpl->set('{robots}',$count_robots);
    	$tpl->set('{all}',($count_user+$guests+$count_robots));
    	$tpl->set('{userlist}',$users);
    	$tpl->set('{botlist}',$robots);
        /*
    	$tpl->copy_template = "\n<script type=\"text/javascript\" src=\"".$GLOBALS['config']['http_home_url']."engine/skins/default.js\"></script>\n
    <style type=\"text/css\" media=\"all\">
    @import url(/templates/".$GLOBALS['config']['skin']."/css/block_online.css);
    </style>"
    	.$tpl->copy_template;*/
    	$tpl->compile('block_online');
    	$tpl->clear();
    	
    	$this->_db_disconnect();
    	
    	if ((int)$this->config['block_online_cache_time'])
    	{
    		create_cache("block_online", $tpl->result['block_online']);
    	}

        return true;
    }

    public function GetNewsLink($row, $category_id)
    {
        if( $GLOBALS['config']['allow_alt_url'] == "yes" ) {
			
			if( $GLOBALS['config']['seo_type'] == 1 OR $GLOBALS['config']['seo_type'] == 2  ) {
				
				if( $category_id and $GLOBALS['config']['seo_type'] == 2 ) {
					
					$full_link = $GLOBALS['config']['http_home_url'] . get_url( $row['category'] ) . "/" . $row['id'] . "-" . $row['alt_name'] . ".html";
				
				} else {
					
					$full_link = $GLOBALS['config']['http_home_url'] . $row['id'] . "-" . $row['alt_name'] . ".html";
				
				}
			
			} else {
				
				$full_link = $GLOBALS['config']['http_home_url'] . date( 'Y/m/d/', $row['date'] ) . $row['alt_name'] . ".html";
			}
		
		} else {
			
			$full_link = $GLOBALS['config']['http_home_url'] . "index.php?newsid=" . $row['id'];
		
		}
    		
    	return $full_link;
    }

    public function link_forum(array &$row, dle_template &$tpl)
    {
    	$categories = explode(",", $row['category']);
    	foreach ($categories as $category)
       	{
       		if (intval($this->config['forumid'][$category]))
       		{
       			$cat_id = $category;
       			break;
       		}
       	}
       	//var_dump($cat_id, $categories, $this->config['forumid']);exit();
    	if (!$this->config['goforum'] || 
    	    !$this->config['allow_module'] || 
    	    !$cat_id || 
    	    (!$this->config['show_no_reginstred'] && !$GLOBALS['is_logged'])
    	    )
    	{
    		return $tpl->set('{link_on_forum}', "");
    	}
    		
    	if (!intval($GLOBALS['newsid']))
    	{
    		if (!$this->config['show_short'])
    		{
    			return $tpl->set('{link_on_forum}', "");
    		}
    		elseif ($this->config['allow_count_short'])
    		{
    			$this->config['show_count'] = 1;
    		}
    		else 
    		{
    			$this->config['show_count'] = 0;
    		}
    	}               
    
    	$link_on_forum = $this->config['link_on_forum'];
    	
    	if ($this->config['show_count'])
    	{
    		$this->_db_connect();
       		
       		switch ($this->config['link_title'])
    	   	{
    	   		case "old":
    	   			$title_forum = preg_replace('/{Post_name}/', $row['title'], $this->config['name_post_on_forum']);
    	   			$title_forum = $this->db->safesql($title_forum);
    	   			if ($title_forum == "") return;
    	  			break;
    	   			
    	   		case "title":
    	   			$title_forum = $this->db->safesql(stripslashes($row['title']));
    	   			break;
    	
    	   		default:
    	   			$this->_db_disconnect();
    	   			return false;
    	   			break;
    	   	}
            
    	   	$this->_convert_charset($title_forum);
            
       		$topic = $this->db->super_query("SELECT tid, posts FROM ". IPB_PREFIX ."topics WHERE title='$title_forum' AND state='open'");
       		
    		if (empty($topic['tid']))
    		{
    			$link_on_forum = preg_replace("'\[count\](.*?)\[/count\]'si", "", $link_on_forum);
    			$count = 0;
    		}
    		else 
    		{
    			$count = $topic['posts'];
    			$link_on_forum = preg_replace("'\[count\](.*?)\[/count\]'si", "\\1", $link_on_forum);
    		}
    		$this->_db_disconnect();
    	}
    	else 
    		$link_on_forum = preg_replace("'\[count\](.*?)\[/count\]'si", "", $link_on_forum);
    		
    	$link_on_forum = str_replace("{count}", $count, $link_on_forum);
    	$link_on_forum = str_replace('{link_on_forum}',(($GLOBALS['config']['allow_alt_url'] == "yes")?$GLOBALS['config']['http_home_url']."goforum/post-".$row['id']."/":$GLOBALS['PHP_SELF']."?do=goforum&postid=".$row['id']), $link_on_forum);
    	
    	$tpl->set('{link_on_forum}', $link_on_forum);

        return true;
    }
    
    public function _parse_post($text_forum, $id)
    {
        require_once ENGINE_DIR . '/classes/parse.class.php';
        $parse = new ParseFilter( Array (), Array (), 1, 1 );
        
        function build_thumb(ParseFilter &$parse, $gurl = "", $url = "", $align = "")
        {
            $url = trim( $url );
            $gurl = trim( $gurl );
            $option = explode( "|", trim( $align ) );
            
            $align = $option[0];
            
            if( $align != "left" and $align != "right" ) $align = '';
            
            $url = $parse->clear_url( urldecode( $url ) );
            $gurl = $parse->clear_url( urldecode( $gurl ) );
            
            if( $gurl == "" or $url == "" ) return;
            
            if( $align == '' )
            {
                return "[$align][url=\"$gurl\"][img]{$url}[/img][/url][/$align]";
            }
            else
            {
                return "[url=\"$gurl\"][img]{$url}[/img][/url]";
            }
        
        }
        
        function decode_img($img, $txt) 
        {
            $txt = stripslashes( $txt );
            $align = false;
            
            if( strpos( $txt, "align=\"" ) !== false ) {
                
                $align = preg_replace( "#(.+?)align=\"(.+?)\"(.*)#is", "\\2", $txt );
            }
            
            if( $align != "left" and $align != "right" ) $align = false;
            
            if($align)
            {
                return "[$align][img]" . $img . "[/img][/$align]";
            }
            else
            {
                return "[img]" . $img . "[/img]";
            }
        }
        
        //$text_forum = stripslashes($text_forum);
        
        if ( strpos( $text_forum, "[attachment=" ) !== false)
        {
            $this->_db_disconnect();
            $text_forum = show_attach($text_forum, $id);
            $this->_db_connect();
        }
        
        $text_forum = preg_replace('#\[.+?\]#', '', $text_forum);
        $text_forum = preg_replace( "#<img src=[\"'](\S+?)['\"](.+?)>#ie", "decode_img('\\1', '\\2')", $text_forum);
        
        $text_forum = $parse->decodeBBCodes( $text_forum, false );
        $text_forum = nl2br(preg_replace('#<.+?>#s', '', $text_forum));
        
        $text_forum = str_replace('leech', 'url', $text_forum);
        $text_forum = preg_replace( "#\[video\s*=\s*(\S.+?)\s*\]#ie", "\$parse->build_video('\\1')", $text_forum );
        $text_forum = preg_replace( "#\[audio\s*=\s*(\S.+?)\s*\]#ie", "\$parse->build_audio('\\1')", $text_forum );
        $text_forum = preg_replace( "#\[flash=([^\]]+)\](.+?)\[/flash\]#ies", "\$parse->build_flash('\\1', '\\2')", $text_forum );
        $text_forum = preg_replace( "#\[youtube=([^\]]+)\]#ies", "\$parse->build_youtube('\\1')", $text_forum );
        $text_forum = preg_replace( "'\[thumb\]([^\[]*)([/\\\\])(.*?)\[/thumb\]'ie", "build_thumb(\$parse, '\$1\$2\$3', '\$1\$2thumbs\$2\$3')", $text_forum );
        $text_forum = preg_replace( "'\[thumb=(.*?)\]([^\[]*)([/\\\\])(.*?)\[/thumb\]'ie", "build_thumb(\$parse, '\$2\$3\$4', '\$2\$3thumbs\$3\$4', '\$1')", $text_forum );
        
        $text_forum = preg_replace('#<!--.+?-->#s', '', $text_forum);
        
        return $text_forum;
    }
    
    public function GoForum()
    {
       	$news_id = intval($_REQUEST['postid']);
       	
       	if (!$news_id) 
       	{
       	    die("Hacking attempt!");
       	}
       	
       	
       	if (version_compare($GLOBALS['config']['version'], 9.6, ">="))
       	{
            $title = $this->db->super_query("SELECT * FROM " . PREFIX . "_post p
                                         INNER JOIN " . PREFIX . "_post_extras e
                                         ON e.news_id=p.id
                                         WHERE id='$news_id'");
       	}
       	else
       	{
            $title = $this->db->super_query("SELECT * FROM " . PREFIX . "_post WHERE id='$news_id'");
       	}
       	
       	
       	
       	$categories = explode(",", $title['category']); $forum_id = 0;
       	
       	foreach ($categories as $category)
       	{
       		if (intval($this->config['forumid'][$category]))
       		{
       			$category_id = $category;
       			$forum_id = $this->config['forumid'][$category];
       			break;
       		}
       	}
       	
       	if (!$forum_id)
       	{
       		return false;
       	}
       	
       	$this->_db_connect();
       	
       	switch ($this->config['link_title'])
       	{
       		case "old":
       			$title_forum = preg_replace('/{Post_name}/',$title['title'], $this->config['name_post_on_forum']);
       			$title_forum = $this->db->safesql(stripslashes($title_forum));
       			break;
       			
       		case "title":
       			$title_forum = $this->db->safesql(stripslashes($title['title']));
       			break;
    
       		default:
       			$this->_db_disconnect();
       			return false;
       			break;
       	}
       	
   	    $this->_convert_charset($title_forum);
       	
    	$isset_post = $this->db->super_query("SELECT tid FROM ". IPB_PREFIX ."topics WHERE title='$title_forum' AND state='open'");
    	
    	if ($isset_post['tid'] != "")   
    	{      
    		header("Location:{$this->ipb_config['board_url']}/index.php?showtopic={$isset_post['tid']}&view=getnewpost");
       		exit;
    	}
    	
       	switch ($this->config['link_text'])
       	{
       		case "full":
       			if (strlen($title['full_story']) > 10)
       				$text_forum = $title['full_story'];
       			else 
       				$text_forum = $title['short_story'];
       		
                $text_forum = stripslashes($text_forum);
       			$news_seiten = explode("{PAGEBREAK}", $text_forum);
    			$text_forum = $news_seiten[0];
    			$text_forum = preg_replace('#(\A[\s]*<br[^>]*>[\s]*|'                                     
                                             .'<br[^>]*>[\s]*\Z)#is', '', $text_forum);
                if (count($news_seiten) > 1)
                {
                	$text_forum .= "<a href=\"".(($GLOBALS['config']['allow_alt_url'] == "yes")?$GLOBALS['config']['http_home_url'].date('Y/m/d/', $title['date']).$title['alt_name'].".html":"/index.php?newsid=".$title['id'])."\" >".$this->lang['view_full']."</a>";
                }
                elseif ($this->config['link_on_news'])
    			{
    				$this->config['text_post_on_forum'] = preg_replace('/{post_name}/i',$title['title'], $this->config['text_post_on_forum']);
       				$this->config['text_post_on_forum'] = preg_replace('/{post_link}/i', $this->GetNewsLink($title, $category_id), $this->config['text_post_on_forum']);
       				$text_forum .= "\n" . $this->config['text_post_on_forum'];
    			}
 
                if ($title['allow_br'])
                {
                    $text_forum = $this->_parse_post($text_forum, $title['id']);
                }
    			//$text_forum = "<noindex>" . $text_forum . "</noindex>";
                $text_forum = $this->db->safesql($text_forum);
                break;
                
    		case "short":
    			$text_forum = $title['short_story'];
                $text_forum = stripslashes($text_forum);
    			if ($this->config['link_on_news'])
    			{
    				$this->config['text_post_on_forum'] = preg_replace('/{post_name}/i',$title['title'], $this->config['text_post_on_forum']);
       				$this->config['text_post_on_forum'] = preg_replace('/{post_link}/i', $this->GetNewsLink($title, $category_id), $this->config['text_post_on_forum']);
       				$text_forum .= "\n" . $this->config['text_post_on_forum'];
    			}
                
                if ($title['allow_br'])
                {
                    $text_forum = $this->_parse_post($text_forum, $title['id']);
                }

                //$text_forum = "<noindex>" . $text_forum . "</noindex>";
                $text_forum = $this->db->safesql($text_forum);
       			break;
       			
    		case "old":
       			$text_forum = preg_replace('/{Post_name}/',$title['title'], $this->config['text_post_on_forum']);
       			$text_forum = preg_replace('/{post_link}/', $this->GetNewsLink($title, $category_id), $text_forum);
       			$text_forum = $this->db->safesql(stripslashes($text_forum));
       			break;
       			
       		default:
       			$this->_db_disconnect();
       			return false;
       			break;
       	}
       	
       	switch ($this->config['link_user'])
       	{
       		case "old":
       			$user = $this->db->safesql($this->config['postusername']);
       			if ($user == "")
       			{
       			    $this->_db_disconnect();
       				return false;
       			}
       			$user_id = intval($this->config['postuserid']);
       			
    			if (!$user_id)
    			{
    				$user_id = 0;
    			}
       			break;
       			
       		case "author":
                $autor = $this->_convert_charset($title['autor']);
       			$user_info = $this->db->super_query("SELECT member_id FROM ". IPB_PREFIX ."members WHERE name='".$this->db->safesql($autor)."' LIMIT 1");
       			if (empty($user_info['member_id']))
       			{
       				$user = $this->db->safesql($this->config['postusername']);
       				$user_id = 0;
       				if ($user == "")
       				{
       				    $this->_db_disconnect();
       					return false;
       				}
       			}
       			else 
       			{
       				$user_id = $user_info['member_id'];
       				$user = $this->db->safesql(stripslashes($title['autor']));
       			}
       			break;
       			
       		case "cur_user":
       		    if (empty($this->member['member_id']))
       		    {
       		        if ($GLOBALS['member_id']['name'])
        			{
        				$this->member = $this->db->super_query("SELECT * FROM ". IPB_PREFIX ."members WHERE name='".$this->db->safesql($GLOBALS['member_id']['name'])."' LIMIT 1");
        			}
        			elseif (!empty($_COOKIE['dle_name']))
        			{
        				$this->member = $this->db->super_query("SELECT * FROM ". IPB_PREFIX ."members WHERE name='".$this->db->safesql($_COOKIE['dle_name'])."' LIMIT 1");
        			}
        			elseif (!empty($_COOKIE['dle_user_id']))
        			{
        				$this->_db_disconnect();
        				$user_name = $this->db->super_query("SELECT name FROM " . USERPREFIX . "_users WHERE user_id='" . intval($_COOKIE['dle_user_id']) . "'");
        				$this->_db_connect();
        				$this->member = $this->db->super_query("SELECT * FROM ". IPB_PREFIX ."members WHERE name='".$this->db->safesql(($user_name['name']))."' LIMIT 1");
        			}
       		    }
       				
    			if (empty($this->member['member_id']))
    			{
    				if (!$this->config['postusername'])
    				{
    					$this->_db_disconnect();
    					return ;
    				}
    				$user_id = intval($this->config['postuserid']);
    				
    				if (!$user_id)
    				{
    					$user_id = 0;
    				}
    					
    				$user = $this->config['postusername'];
    			}
    			else 
    			{
      				$user_id = $this->member['member_id'];
      				$user = $this->member['name'];
    			}
      				
      			$user = $this->db->safesql($user);
       			break;
       			
       		default:
       			$this->_db_disconnect();
       			return false;
       			break;
       	}
       	
       	$forum = $this->db->super_query("SELECT id FROM " . IPB_PREFIX . "forums WHERE id='$forum_id'");
    	if (empty($forum['id']))
    	{
        	$this->_db_disconnect();
    		return false;
    	}
    	
        
        $this->_convert_charset($user);
        $this->_convert_charset($text_forum);
        $post_htmlstate = 0;
        
        if ($title['allow_br'])
        {
            $post_htmlstate = 2;
    	}
        
       	$this->db->query("INSERT INTO ". IPB_PREFIX ."topics (title, start_date, forum_id, state, posts, last_post, starter_name, last_poster_name, poll_state, last_vote, views, approved, author_mode, pinned, starter_id, last_poster_id) VALUES ('$title_forum', '".time()."', '$forum_id', 'open', '0', '".time()."', '$user', '$user', '0', '0', '1', '1', '0', '0', '$user_id', '$user_id')");
       	$tp_id = $this->db->insert_id();
       	$this->db->query("INSERT INTO ". IPB_PREFIX ."posts (author_name, author_id, use_emo, ip_address, post_date, post, topic_id, new_topic, post_htmlstate) VALUES ('$user', '$user_id', '1', '".$this->db->safesql($this->ip())."', '".time()."', '$text_forum', '$tp_id', '1', $post_htmlstate)");
       	$post_id = $this->db->insert_id();
       	$this->db->query("UPDATE " . IPB_PREFIX . "topics SET topic_firstpost='$post_id' WHERE tid='$tp_id'");
       	$this->db->query("UPDATE " . IPB_PREFIX . "forums SET last_poster_name='$user', last_poster_id='$user_id', last_title='$title_forum', last_id='$tp_id', newest_title='$title_forum', newest_id='$tp_id', topics=topics+1 WHERE id='".$this->db->safesql($forum_id)."'");
       	
       	$row = $this->db->super_query("SELECT cs_value FROM " . IPB_PREFIX . "cache_store WHERE cs_key='forum_cache'");
       	$forum_cache = unserialize($row['cs_value']);
       	$forum_cache[$forum_id]['topics']++;
       	$forum_cache[$forum_id]['last_poster_name'] = $user;
       	$forum_cache[$forum_id]['last_title'] = $title_forum;
       	$forum_cache[$forum_id]['last_id'] = $tp_id;
       	$forum_cache[$forum_id]['newest_title'] = $title_forum;
       	$forum_cache[$forum_id]['newest_id'] = $tp_id;
       	
        if (!(int)$this->config['ipb_version'])
        {
            $this->db->query("REPLACE INTO " . IPB_PREFIX . "cache_store (cs_key, cs_value, cs_extra, cs_array, cs_updated) VALUES ('forum_cache', '" . $this->db->safesql(serialize($forum_cache)) . "', '', '1', " . time() . ")");
        }
        else
        {
            $this->db->query("REPLACE INTO " . IPB_PREFIX . "cache_store (cs_key, cs_value, cs_array, cs_updated) VALUES ('forum_cache', '" . $this->db->safesql(serialize($forum_cache)) . "', '1', " . time() . ")");
        }
       	
       	header("Location:{$this->ipb_config['board_url']}/index.php?showtopic=$tp_id&view=getnewpost");
       	exit();
    }
    
}

if (isset($_REQUEST['ipbdebug']))
{
    class IPBDebug extends ipb_member
    {
        public function __construct(db &$db)
        {
            parent::__construct($db);
            
            if (!empty($_REQUEST['connect']))
            {
                $this->connect_method = $_REQUEST['connect'];
            }
        }

        public function _db_connect($collate = '', $charset = '')
        {
            if (!$this->lock_connect && !$this->connected)
            {
                switch ($this->connect_method) 
                {
                    case 'connect':
                        $this->db->connect($this->ipb_config['sql_user'], 
	                               $this->ipb_config['sql_pass'], 
	                               $this->ipb_config['sql_database'], 
	                               $this->ipb_config['sql_host']);
                        break;
                        
                    case 'use':
                        $this->db->query("USE `" . $this->ipb_config['sql_database'] . "`");
                        break;
                	
                    default:
                        break;
                }
                
                if ($collate)
                {
                    $this->db->query("SET NAMES '" . $collate ."'");
                }
                if ($charset)
                {
                    $this->db->query("SET CHARACTER SET '" . $charset . "'");
                }
                
                $this->connected = true;
            }
        }

        public function _db_disconnect()
        {
            if (!$this->lock_connect && $this->connected)
            {
                switch ($this->connect_method) 
                {
                    case 'connect':
                        $this->db->close();
//                        $this->db->connect(DBUSER, DBPASS, DBNAME, DBHOST);
                        //$this->db->query("SET CHARACTER SET '" . COLLATE . "'");
                        break;
                        
                    case 'use':
                        $this->db->query("USE `" . DBNAME . "`");
                        
                    default:
                        $this->db->query("SET NAMES '" . COLLATE ."'");
                        //$this->db->query("SET CHARACTER SET '" . COLLATE . "'");
                        break;
                }
                
                $this->connected = false;
            }
        }
        
        protected function query()
        {
            $thread = $this->db->super_query("SELECT title FROM " . IPB_PREFIX . "topics ORDER BY last_post DESC LIMIT 1");
            
            if (!$thread)
            {
                die('Topic not found');
            }
            
            return $thread['title'];
        }

        public function _convert_charset($data, $to, $from = '')
        {
            global $config;
            
            if (!$from)
            {
                $from = $config['charset'];
            }
            
            if ($to != $from)
            {
                return iconv($from, $to, $data);
            }
            
            return $data;
        }
        
        public function Debug()
        {
            $COLLATES = array(
                                'utf8',
                                'cp1251',
                                'latin1'
                                );
                                
            $CHARACTERS = array(
                                '',
                                'utf8',
                                'cp1251'
                                );
                                
            $CHARSETS = array(
                                '',
                                'UTF-8',
                                'windows-1251',
                               );
                               
            foreach ($COLLATES AS $collate)
            {
                echo  "<br />============================= COLLATE: $collate ========================================<br />";
                foreach ($CHARACTERS AS $character)
                {
                    echo  "  _____________________________ CHARACTER: $character _____________________________<br />";
                    foreach ($CHARSETS as $charset)
                    {
                        $this->_db_connect($collate, $character);
                        
                        if ($charset)
                        {
                            $text = $this->_convert_charset($this->query(), $charset);
                        }
                        else
                        {
                            $text = $this->query();
                            $charset = 'NONE';
                        }
                        
                        echo  "COLLATE: $collate; CHARACTER: " . ($character?$character:"NONE") . "; CHARSET: $charset : " . $text . "<br />";
                        $this->_db_disconnect();
                    }
                }
            }
        }
    }
    
    header('Content-type: text/html; charset=' . $config['charset']);
    $debug = new IPBDebug($db);
    $debug->Debug();
    exit();
}

$ipb = new ipb_member($db);


?>