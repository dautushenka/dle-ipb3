<?php

/**
 * Invision Power Services
 * IP.Board v3.0.1
 * Login handler abstraction : Internal Method
 * Last Updated: $Date: 2009-03-25 09:57:57 -0400 (Wed, 25 Mar 2009) $
 *
 * @author 		$Author: josh $
 * @copyright		(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.iinvisionpower.com/community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.iinvisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4300 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_dle extends login_core implements interface_login
{
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();
	
	/**
	 * 
	 * @var forumsMemberSync
	 */
	private $dle = null;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		Configuration info for this method
	 * @param	array 		Custom configuration info for this method
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $method, $conf=array() )
	{
		$this->method_config	= $method;
		$this->external_conf	= $conf;
		
//		require_once(IPS_ROOT_PATH . "applications/forums/extensions/dbconfig.php");
//		require_once(IPS_ROOT_PATH . "applications/forums/extensions/dle_config.php");
		
		parent::__construct( $registry );
		
		require_once(IPS_ROOT_PATH . "applications/forums/extensions/memberSync.php");
		
		$this->dle = new forumsMemberSync();
	}
	
	/**
	 * Authenticate the request
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authenticate( $username, $email_address, $password )
	{
		//-----------------------------------------
		// Check admin authentication request
		//-----------------------------------------
		
		if ( $this->is_admin_auth )
		{
			$this->adminAuthLocal( $username, $email_address, $password );
			
  			if ( $this->return_code == 'SUCCESS' )
  			{
  				return true;
  			}
		}

    	if (DLE_CHARSET && DLE_CHARSET != 'UTF-8')
    	{
    	    $username_dle = iconv("UTF-8", DLE_CHARSET, $username);
    	}
    	
		//--------------------------------
		// Get a DB connection
		//--------------------------------

		$this->dle->db_connect();
		
		//-----------------------------------------
		// Get member from remote DB
		//-----------------------------------------

		$remote_member = $this->dle->db->buildAndFetch( array( 'select' => '*',
															'from'   => "_users",
															'where'  => "name='".$this->dle->db->addSlashes($username_dle)."'" ) );

		$this->dle->db_disconnect();

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $remote_member[ 'name' ] )
		{
			$this->return_code = 'NO_USER';
			return false;
		}

		//-----------------------------------------
		// Check password
		//-----------------------------------------
		
		
		if (md5(md5($password)) != $remote_member['password'])
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		define('CONVERT', true);
		
		if (DLE_VERSION < 7.3)
    	{
    		setcookie ("dle_name", $username_dle, time()+3600*24*365, "/", "." . DLE_DOMAIN);
    	}
    	else 
    	{
   	        setcookie ("dle_user_id", $remote_member['user_id'], time()+3600*24*365, "/", "." . DLE_DOMAIN);
    	}
    	
    	setcookie ("dle_password", md5($password), time()+3600*24*365, "/", "." . DLE_DOMAIN);
		

		$password			= html_entity_decode($password, ENT_QUOTES);
		$html_entities		= array( "&#33;", "&#036;", "&#092;" );
		$replacement_char	= array( "!", "$", "\\" );
		$password 			= str_replace( $html_entities, $replacement_char, $password );

		//-----------------------------------------
		// Still here? Then we have a username
		// and matching password.. so get local member
		// and see if there's a match.. if not, create
		// one!
		//-----------------------------------------

		$this->_loadMember( $username );

		if ( $this->member_data['member_id'] )
		{
			$this->return_code = 'SUCCESS';
			return false;
		}
		else
		{
			//-----------------------------------------
			// Got no member - but auth passed - create?
			//-----------------------------------------

			$this->return_code = 'SUCCESS';
			
			$data = array();
			
			$data['name']              		= $username;
    		$data['email']					= $remote_member['email'];
    		$data['password']		        = $password;
    		
    		$pfields_content['field_4'] = $remote_member['icq'];
    		$pfields_content['field_6'] = $remote_member['land'];
    		$pfields_content['field_7'] = $remote_member['info'];

			$this->member_data = $this->createLocalMember( array( 'members' => $data, 'pfields_content' => $pfields_content) );
			
			return true;
		}
	}

	/**
	 * Load a member
	 *
	 * @access	private
	 * @param	string		Username
	 * @return	void
	 */
	private function _loadMember( $username )
	{
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_username='" . mb_strtolower($username) . "'" ) );
		
		if( $member['member_id'] )
		{
			$this->member_data = IPSMember::load( $member['member_id'], 'extendedProfile,groups' );
		}
	}
	
	public function changePass($email, $new_pass_md5)
	{
	    $this->dle->db_connect()->update("_users", array("password" => md5($new_pass_md5)), "email='$email'");
	    $this->dle->db_disconnect();
	    
	    $this->return_code = 'SUCCESS';
	}

	public function logoutCallback()
	{
        setcookie ("dle_name", '', time() - 3600*24*365, "/", "." . DLE_DOMAIN);
        setcookie ("dle_newpm", '', time() - 3600*24*365, "/", "." . DLE_DOMAIN);
        setcookie ("dle_user_id", '', time() - 3600*24*365, "/", "." . DLE_DOMAIN);
        setcookie ("dle_password", '', time() - 3600*24*365, "/", "." . DLE_DOMAIN);
        setcookie ("PHPSESSID", '', time() - 3600*24*365, "/", "." . DLE_DOMAIN);
        setcookie ("PHPSESSID", '', time() - 3600*24*365);
        
        if (session_id())
        {
            setcookie (session_name(), '', time() - 3600*24*365);
            session_destroy();
        }
        
        return $this->return_code = 'SUCCESS';
	}
}