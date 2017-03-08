<?php
/**
 * @package     Joomla
 * @subpackage  System.adminlogin
 
 * @copyright   Copyright (C) 2017 janich.dk, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;


class AdminLoginHelper
{
	protected $app		= null;
	protected $dbo		= null;
	protected $input	= null;


	public function __construct()
	{
        $this->app		= JFactory::getApplication();
		$this->dbo		= JFactory::getDbo();
		$this->doc		= JFactory::getDocument();
	}


	public function generateLoginLink($userId = 0, $key = '')
	{
		$user = JFactory::getUser($userId);

		if (!$user || !$user->get('id', 0) || $user->get('block'))
		{
			return false;
		}

		$parts = array(
			'adminlogin=' . md5($key),
			'username=' .   $user->get('username'),
			'password=' .   md5($user->get('password'))
		);

		return JUri::root() . '?' . implode('&', $parts);
	}


    public function addLoginButtons($key = '')
    {
        $html = $this->app->getBody();

        if (preg_match_all('/task=user.edit&amp;id=[0-9]*/', $html, $matches)) {
            $matches = $matches[0];
        }

        $head   = array();
        $head[] = '<style type="text/css">.btn-adminlogin { float: right; background: #46a546; padding: 2px 5px 2px 6px; border-radius: 2px; font-weight: normal; color: white; font-size: 12px;} .btn-adminlogin:hover { background: #555; text-decoration: none; color: white; }</style>';
        $head[] = '<script type="text/javascript">';
        $head[] = 'function addLoginButton(id, link) {';
        $head[] = 'jQuery(\'#userList a[href$="task=user.edit&id=\'+ id +\'"]\').parent().parent().next().append(\'<a class="icon-shuffle btn-adminlogin" title="'. JText::_('PLG_SYSTEM_ADMINLOGIN_BTN_TITLE') .'" href="\'+ link +\'" target="_blank"> </a>\');';
        $head[] = '}';

        $head[] = 'jQuery(document).ready(function() {';

        foreach ($matches as $match)
        {
			if ($match)
			{
				$parts = (array) explode('=', $match);
				$id = (int) end($parts);

				if ($id) 
				{
					$head[] = 'addLoginButton(' . $id . ', \'' . $this->generateLoginLink($id, $key) . '\');';
				}
			}
        }

        $head[] = '});';
        $head[] = '</script>';

        $html = str_ireplace('</head>', implode("\n", $head) . '</head>', $html);

        $this->app->setBody($html);
    }


    public function matchUser($username = '', $password = '')
    {
        $query = $this->dbo->getQuery(true)
            ->select('*')
            ->from('#__users')
            ->where('`username` = '. $this->dbo->quote($username))
            ->where('MD5(`password`) = '. $this->dbo->quote($password));
        $this->dbo->setQuery($query);
        return $this->dbo->loadObject();
    }


    public function getBackendSessionId()
    {
        $name = md5(JApplicationHelper::getHash('administrator'));
        return $this->app->input->cookie->get($name, '');
    }


    public function getUserSession($session_id = '')
    {
        $q = $this->dbo->getQuery(true)
            ->select('*')
            ->from('#__session')
            ->where('`session_id` = '. $this->dbo->quote($session_id))
            ->where('`client_id` = 1')
            ->where('`guest` = 0');
        $this->dbo->setQuery($q);
        return $this->dbo->loadObject();
    }


    public function loginUser($userId = 0)
    {
        $user       = JFactory::getUser($userId);
        $session    = JFactory::getSession();

        $user->set('guest', 0);
        $user->set('aid', 1);

        $session->set('user', $user);

        $table = JTable::getInstance('session');
        $table->load($session->getId());

        $table->guest 		= 0;
        $table->username 	= $user->get('username');
        $table->userid 		= (int) $user->get('id', 0);

        $table->update();

        return true;
    }
}
