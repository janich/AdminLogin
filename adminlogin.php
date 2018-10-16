<?php
/**
 * @package     Joomla
 * @subpackage  System.adminlogin
 
 * @copyright   Copyright (C) 2017 janich.dk, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;


require_once __DIR__ .'/helpers/helper.php';


class PlgSystemAdminlogin extends JPlugin
{
	protected   $autoloadLanguage   = true;
    protected   $helper	            = null;
    protected   $debug	            = null;
    protected   $key		        = null;
    

	public function __construct($subject, $config = array())
	{
		parent::__construct($subject, $config);
        
		$this->helper   = new AdminLoginHelper();
        $this->debug	= $this->params->get('debug', 0);
        $this->key	    = $this->params->get('key', md5(mt_rand(0, 99999)));
		$this->redirect	= $this->params->get('redirect', 0);
	}


	public function onGetAdminloginButton($userId = 0)
	{
        $link = $this->onGetAdminloginLink($userId);
		
		if (!$link) {
			return '';
		}
		
		return '<a href="'. $link .'" target="_blank" class="btn btn-success btn-mini btn-adminlogin btn-adminlogin-'. (int) $userId .'" title="'. JText::_('PLG_SYSTEM_ADMINLOGIN_BTN_TITLE') .'">'. JText::_('PLG_SYSTEM_ADMINLOGIN_BTN_TEXT') .'</a>';
	}
	
	
	public function onGetAdminloginLink($userId = 0)
	{
        return $this->helper->generateLoginLink($userId, $this->key);
	}
    
    
	public function onAfterRender()
	{
        $app = JFactory::getApplication();
        
		if (!$app->isAdmin())
		{
			return true;
		}

		if ($app->input->get('option', '', 'cmd') == 'com_users' && $app->input->get('view', '', 'cmd') == 'users')
        {
	        $this->helper->addJoomlaLoginButtons($this->key);
        }

		if ($app->input->get('option', '', 'cmd') == 'com_easysocial' && $app->input->get('view', '', 'cmd') == 'users')
		{
			$this->helper->addEasysocialLoginButtons($this->key);
		}

        return true;
	}
    
    
	public function onAfterInitialise()
	{
        $app = JFactory::getApplication();
        
		if (!$app->isSite())
		{
			return true;
		}

		$hash = $app->input->get('adminlogin', false);
		if (!$hash)
		{
			return true;
		}
        
        try 
        {
            // Test secret
            if ($hash != md5($this->key))
            {
                throw new Exception('PLG_SYSTEM_ADMINLOGIN_ERR_INVALIDKEY');
            }
            
            // Test user
            $username   = $app->input->get('username', '', 'raw');
            $password   = $app->input->get('password', '', 'raw');
            $user       = $this->helper->matchUser($username, $password);
            if (!$user)
            {
                throw new Exception('PLG_SYSTEM_ADMINLOGIN_ERR_USERNOTFOUND');
            }
            
            if ($user->block)
            {
                throw new Exception('PLG_SYSTEM_ADMINLOGIN_ERR_USERBLOCKED');
            }
            
            // Test backend session
            $session_id = $this->helper->getBackendSessionId();
            if (!$session_id)
            {
                throw new Exception('PLG_SYSTEM_ADMINLOGIN_ERR_NOTLOGGEDIN');
            }
    
            // Test backend session login
            $session = $this->helper->getUserSession($session_id);
            if (!$session)
            {
                throw new Exception('PLG_SYSTEM_ADMINLOGIN_ERR_NOSESSION');
            }
    
            // Do magic
            if (!$this->helper->loginUser($user->id))
            {
                throw new Exception('PLG_SYSTEM_ADMINLOGIN_ERR_UNKNOWN');
            }

			$url = 'index.php' . ($this->redirect ? '?Itemid='. (int) $this->redirect : '');
			
            $app->redirect($url, JText::sprintf('PLG_SYSTEM_ADMINLOGIN_SUCCESS', $user->username));
        }
        catch (Exception $e)
        {
			$error = $e->getMessage();
			if (substr($error, 0, 21) == 'PLG_SYSTEM_ADMINLOGIN') 
			{
				$error = JText::_($error);
			}
			
			if ($this->debug)
            {
                $app->enqueueMessage($error, 'error');
            }
        }

		return true;
	}
}
