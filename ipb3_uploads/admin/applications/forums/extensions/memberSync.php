<?php

/**
 * Invision Power Services
 * IP.Board v3.0.1
 * Forum permissions mappings
 *
 * @author 		$author$
 * @copyright		(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.iinvisionpower.com/community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.iinvisionpower.com
 * @version		$Rev: 4429 $ 
 **/
 

/**
 * Member Synchronization extensions
 *
 * @author 		$author$
 * @copyright		(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.iinvisionpower.com/community/board/license.html
 * @package		Invision Power Board
 * @subpackage  Forums
 * @link		http://www.iinvisionpower.com
 * @version		$Rev: 4429 $ 
 **/
class forumsMemberSync
{
	/**
	 * Registry reference
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	
	private $connect_method = 'connect';
	
	/**
	 * DataBase object
	 *
	 * @var db_driver_mysql
	 */
	public $db = null;
	
	private static $dle_user = array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	void
	 **/
	public function __construct()
	{
	    
	    if (defined('CONVERT'))
	    {
	        return true;
	    }
	    
	    
		$this->registry = ipsRegistry::instance();
		
		
/////////////////////////////////////////////////////////////////		
        /*print_r(ipsRegistry::$settings);

        $this->db =& ipsRegistry::DB();
		$this->db->obj['sql_tbl_prefix'] = 'dddd';
		
		print_r(ipsRegistry::$settings);exit;*/
		
///////////////////////////////////////////////////////////


        require_once(dirname(__FILE__) . "/dbconfig.php");
        require_once(dirname(__FILE__) . "/dle_config.php");
        
        if (!defined('COLLATE'))
        {
            define('COLLATE', 'cp1251');
        }
        
        if (ipsRegistry::$settings['sql_host'] === DBHOST && 
            ipsRegistry::$settings['sql_user'] === DBUSER &&
            ipsRegistry::$settings['sql_pass'] === DBPASS
            ) 
		{	
		    if (ipsRegistry::$settings['sql_database'] === DBNAME)
		    {
		        $this->connect_method = 'none';
		    }
		    else
		    {
		        $this->connect_method = 'use';
		    }
		    
		    $this->db = ipsRegistry::DB();
		}
		else
		{
		    if ( ! class_exists( 'dbMain' ) )
    		{
    			require_once( IPS_KERNEL_PATH.'classDb.php' );
    			require_once( IPS_KERNEL_PATH.'class_db_' . ucwords(ipsRegistry::$settings['sql_driver']) . ".php" );
    		}
    
    		$classname = "db_driver_" . ipsRegistry::$settings['sql_driver'];
    
    		$this->db = new $classname;
    
    		$this->db->obj['sql_database']			= DBNAME;
    		$this->db->obj['sql_user']				= DBUSER;
    		$this->db->obj['sql_pass']				= DBPASS;
    		$this->db->obj['sql_host']				= DBHOST;
    		$this->db->obj['sql_port']				= '';  
    		$this->db->obj['sql_tbl_prefix']	    = USERPREFIX;
    		$this->db->obj['use_shutdown']			= 0;
    		$this->db->obj['force_new_connection']	= 1;
		}
	}
	
	/**
	 * This method is run when a new account is created
	 *
	 * @access	public
	 * @param	array 	$member	Array of member data
	 * @return	void
	 **/
	public function onCreateAccount( $member )
	{
	    if (!DLE_REGISTER || defined('CONVERT') || defined('CREATE_ACCOUNT'))
	    {
	        return true;
	    }
	    
        if (DLE_CHARSET && DLE_CHARSET != 'UTF-8')
        {
            $member['name'] = iconv('UTF-8', DLE_CHARSET, $member['name']);
            $_REQUEST['PassWord'] = iconv('UTF-8', DLE_CHARSET, $_REQUEST['PassWord']);
        }
        
        $this->db_connect();
	    $member['name'] = $this->db->addSlashes($member['name']);
	    self::$dle_user = $this->db->buildAndFetch(array("select" => '*', 
        		                                          "from" => "_users",
        		                                          'where' => "name=\"{$member['name']}\" OR email='{$member['email']}'"));
        		                                          
        if (!empty(self::$dle_user['user_id']))
        {
            $this->db_disconnect();
            return true;
        }
	    
        $member_psw = md5($_REQUEST['PassWord']);
	    $hashpasswd = md5($member_psw);
	    
    	self::$dle_user = array("name" => $member['name'],
    					  "password" => $hashpasswd,
    					  "email" => $member['email'],
    					  "reg_date" => $member['joined'],
    					  "lastdate" => $member['joined'],
    					  "user_group" => USER_GROUP,
    					  "logged_ip" => $this->db->addSlashes($_SERVER['REMOTE_ADDR']),
    					  "info" => '',
    					  "signature" => '',
    					  "foto" => '',
    					  "fullname" => '',
    					  "land" => '',
    					  "favorites" => '',
    					  "xfields" => '',
    					  "allowed_ip" => '',
    					  );
    					  
        
    
    	$this->db->insert('_users', self::$dle_user);
    	
    	if (DLE_VERSION < 7.3)
    	{
    		setcookie ("dle_name", $member['name'], time()+3600*24*365, "/", "." . DLE_DOMAIN);
    	}
    	else 
    	{
    	    $id = $this->db->getInsertId();
    	    self::$dle_user['user_id'] = $id;
    	    
    		setcookie ("dle_user_id", $id, time()+3600*24*365, "/", "." . DLE_DOMAIN);
    	}
    	
    	setcookie ("dle_password", $member_psw, time()+3600*24*365, "/", "." . DLE_DOMAIN);
    
    	$this->db_disconnect();
        
        define('CREATE_ACCOUNT', true);
	}
	
	/**
	 * This method is run when the register form is displayed to a user
	 *
	 * @access	public
	 * @return	void
	 **/
	public function onRegisterForm()
	{

	}
	
	/**
	 * This method is ren when a user successfully logs in
	 *
	 * @access	public
	 * @param	array 	$member	Array of member data
	 * @return	void
	 **/
	public function onLogin( $member )
	{
        $password = empty($_REQUEST['ips_password'])?$_REQUEST['password']:$_REQUEST['ips_password'];
    
	    if (!DLE_LOGIN || defined('CONVERT') || defined('CREATE_ACCOUNT') || empty($password))
	    {
	        return true;
	    }
	    
        if (DLE_CHARSET && DLE_CHARSET != 'UTF-8')
        {
            $member['name'] = iconv('UTF-8', DLE_CHARSET, $member['name']);
        }
       
        $this->db_connect();
        
	    if (defined("CONVERT_TO_DLE") && CONVERT_TO_DLE && empty(self::$dle_user['user_id']))
        {
            $member_name = $this->db->addSlashes($member['name']);
            
            self::$dle_user = $this->db->buildAndFetch(
                                                array("select" => '*', 
                                                      "from" => "_users",
                                                      'where' => "name=\"$member_name\" OR email='{$member['email']}'"));
            if (!self::$dle_user)
            {
                self::$dle_user = array("name" => $member['name'],
                          "password" => md5(md5($password)),
                          "email" => $member['email'],
                          "reg_date" => $member['joined'],
                          "lastdate" => $member['joined'],
                          "user_group" => USER_GROUP,
                          "logged_ip" => $this->db->addSlashes($_SERVER['REMOTE_ADDR']),
                          "info" => '',
                          "signature" => '',
                          "foto" => '',
                          "fullname" => '',
                          "land" => '',
                          "favorites" => '',
                          "xfields" => '',
                          "allowed_ip" => '',
                          );
    
                $this->db->insert('_users', self::$dle_user);
                
                self::$dle_user['user_id'] = $this->db->getInsertId();
            }
        }
	    
        if (DLE_VERSION < 7.3)
    	{
    		setcookie ("dle_name", $member['name'], time()+3600*24*365, "/", "." . DLE_DOMAIN);
    	}
    	else 
    	{
    	    if (empty(self::$dle_user['user_id']))
    	    {
    	        $member_name = $this->db->addSlashes($member['name']);
    	        $member_email = $this->db->addSlashes($member['email']);
    	        
        		self::$dle_user = $this->db->buildAndFetch(
        		                                    array("select" => '*', 
        		                                          "from" => "_users",
        		                                          'where' => "name=\"$member_name\" OR email='$member_email'"));
    	    }
    		
    	    if (!empty(self::$dle_user['user_id']))
    	    {
    	        setcookie ("dle_user_id", self::$dle_user['user_id'], time()+3600*24*365, "/", "." . DLE_DOMAIN);
    	    }
    	}
    	
    	setcookie ("dle_password", md5($password), time()+3600*24*365, "/", "." . DLE_DOMAIN);
    	
    	$this->db_disconnect();
	}
	
	/**
	 * This method is called after a member account has been removed
	 *
	 * @access	public
	 * @param	string	$ids	SQL IN() clause
	 * @return	void
	 **/
	public function onDelete( $mids )
	{

	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @access	public
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	void
	 **/
	public function onMerge( $member, $member2 )
	{

	}
	
	/**
	 * This method is run after a users email address is successfully changed
	 *
	 * @param  integer  $id         Member ID
	 * @param  string   $new_email  New email address
	 * @return void
	 **/
	public function onEmailChange( $id, $new_email )
	{
	    if (!DLE_PROFILE)
	    {
	        return true;
	    }
	    
        $name = ips_MemberRegistry::getProperty('name');
	    
	    if (ips_MemberRegistry::getProperty('member_id') != $id)
	    {
	        $members = IPSMember::load($id, 'members', 'id');
	        
	        $name = $members['name'];
	    }
	    
	    if ($name)
	    {
	        if (DLE_CHARSET && DLE_CHARSET != 'UTF-8')
            {
                $name = iconv('UTF-8', DLE_CHARSET, $name);
            }
        
	        $this->db_connect()->update("_users", array("email" => $this->db->addSlashes($new_email)), "name='$name'");
	        
	        $this->db_disconnect();
	    }
	}
	
	/**
	 * This method is run after a users password is successfully changed
	 *
	 * @access	public
	 * @param	integer	$id						Member ID
	 * @param	string	$new_plain_text_pass	The new password
	 * @return	void
	 **/
	public function onPassChange( $id, $new_plain_text_pass )
	{
	    if (!DLE_PROFILE || defined('CREATE_ACCOUNT'))
	    {
	        return true;
	    }

	    $name = ips_MemberRegistry::getProperty('name');
	    
	    if (ips_MemberRegistry::getProperty('member_id') != $id)
	    {
	        $members = IPSMember::load($id, 'members', 'id');
	        
	        $name = $members['name'];
	    }
	    
	    if ($name)
	    {
	        if (DLE_CHARSET && DLE_CHARSET != 'UTF-8')
            {
                $name = iconv('UTF-8', DLE_CHARSET, $name);
                $new_plain_text_pass = iconv('UTF-8', DLE_CHARSET, $new_plain_text_pass);
            }
            
	        $this->db_connect()->update("_users", array("password" => md5(md5($new_plain_text_pass))), "name='$name'");
	        
	        $this->db_disconnect();
	        
	        setcookie ("dle_password", md5($new_plain_text_pass), time()+3600*24*365, "/", "." . DLE_DOMAIN);
	    }
	}
	
	/**
	 * This method is run after a users profile is successfully updated
	 *
	 * @access	public
	 * @param	array 	$member		Array of values that were changed
	 * @return	void
	 **/
	public function onProfileUpdate( $member )
	{
	    if (!DLE_PROFILE)
	    {
	        return true;
	    }
	    
	    $update_array = array();
	    
	    if (isset($member['customFields']['field_6']))
	    {
	        $update_array['land'] = $member['customFields']['field_6'];
	    }
	    
	    if (isset($member['customFields']['field_7']))
        {
            $update_array['info'] = $member['customFields']['field_7'];
        }
        
        $user = ips_MemberRegistry::getProperty('name');
	    
        if ($update_array && $user)
        {
            if (DLE_CHARSET && DLE_CHARSET != 'UTF-8')
            {
                $user = iconv('UTF-8', DLE_CHARSET, $user);
                
                foreach ($update_array as &$value)
                {
                    $value = iconv('UTF-8', DLE_CHARSET, $value);
                }
            }
            
    	    $user = $this->db_connect()->addSlashes($user);
    	    
    	  /*  $set = '';
    	    foreach ($update_array as $field=>&$value)
    	    {
    	        if ($set)
    	        {
    	            $set .= ", ";
    	        }
    	        
    	        $value = $this->db->addSlashes($value);
    	        $set .= $field . "='" . $value . "'";
    	    }*/
    	    
    	    $this->db->update("_users", $update_array, "name='$user'");
        
       		$this->db_disconnect();
        }
	}
	
	/**
	 * This method is run after a users group is successfully changed
	 *
	 * @access	public
	 * @param	integer	$id			Member ID
	 * @param	integer	$new_group	New Group ID
	 * @return	void
	 **/
	public function onGroupChange( $id, $new_group )
	{

	}
	
	/**
	 * This method is run after a users display name is successfully changed
	 *
	 * @access	public
	 * @param	integer	$id			Member ID
	 * @param	string	$new_name	New display name
	 * @return	void
	 **/
	public function onNameChange( $id, $new_name )
	{
        
	}
	
	/**
	 * Connect to DLE DataBase
	 *
	 * @return db_driver_mysql
	 */
	public function &db_connect()
	{
	    switch ($this->connect_method)
	    {
	        case "none":
	            $this->db->prefix_changed = 0;
	            $this->db->obj['sql_tbl_prefix'] = USERPREFIX;
	            break;
	            
	        case "use":
	            $this->db->query("USE `" . DBNAME . "`");
	            $this->db->prefix_changed = 0;
	            $this->db->obj['sql_tbl_prefix'] = USERPREFIX;
	            break;
	            
	        default:
	            $this->db->connect();
	            break;
	    }
        
	    if (COLLATE != '')
	    {
	        $this->db->query("SET NAMES '" . COLLATE . "'");
	        $this->db->query("SET CHARACTER SET '" . COLLATE . "'");
	    }
	    
	    return $this->db;
	}
	
	public function db_disconnect()
	{
	    switch ($this->connect_method)
	    {
	        case "none":
	            $this->db->prefix_changed = 1;
	            $this->db->obj['sql_tbl_prefix'] = ipsRegistry::$settings['sql_tbl_prefix'];
	            if (COLLATE != '' && !empty(ipsRegistry::$settings['sql_charset']))
                {
            	    $this->db->query("SET NAMES '" . ipsRegistry::$settings['sql_charset'] . "'");
                    $this->db->query("SET CHARACTER SET '" . ipsRegistry::$settings['sql_charset'] . "'");
        	    }
	            break;
	            
	        case "use":
	            $this->db->query("USE `" . ipsRegistry::$settings['sql_database'] . "`");
	            $this->db->prefix_changed = 1;
	            $this->db->obj['sql_tbl_prefix'] = ipsRegistry::$settings['sql_tbl_prefix'];
	            if (COLLATE != '' && !empty(ipsRegistry::$settings['sql_charset']))
                {
            	    $this->db->query("SET NAMES '" . ipsRegistry::$settings['sql_charset'] . "'");
                    $this->db->query("SET CHARACTER SET '" . ipsRegistry::$settings['sql_charset'] . "'");
        	    }
	            break;
	            
	        default:
	            $this->db->disconnect();
	            break;
	    }
	}
}