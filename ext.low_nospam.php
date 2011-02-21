<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_nospam/config.php');

/**
* Low NoSpam Extension class
*
* @package			low-nospam-ee2_addon
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Low_nospam_ext
{
	/**
	* Extension settings
	*
	* @var	array
	*/
	var $settings = array();

	/**
	* Extension name
	*
	* @var	string
	*/
	var $name = LOW_NOSPAM_NAME;

	/**
	* Extension version
	*
	* @var	string
	*/
	var $version = LOW_NOSPAM_VERSION;

	/**
	* Extension description
	*
	* @var	string
	*/
	var $description = 'Fight spam on your site by using the Akismet or TypePad AntiSpam service';

	/**
	* Do settings exist?
	*
	* @var	bool
	*/
	var $settings_exist = TRUE;

	/**
	* Documentation link
	*
	* @var	string
	*/
	var $docs_url = LOW_NOSPAM_DOCS;

	/**
	* Default settings
	*
	* @var	array
	*/
	var $default_settings = array(
		'service'                    => 'akismet',
		'api_key'                    => '',
		'check_members'              => array(2, 3, 4),
		'check_comments'             => 'y',
		'caught_comments'            => 'p',
		'check_forum_posts'          => 'n',
		'check_wiki_articles'        => 'n',
		'check_member_registrations' => 'n',
		'moderate_if_unreachable'    => 'y',
		'zero_tolerance'             => 'n'
	);

	/**
	* Error message line
	*
	* @var	string
	*/
	var $error = '';

	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Low_nospam_ext($settings = FALSE)
	{
		$this->__construct($settings);
	}

	// --------------------------------------------------------------------

	/**
	* PHP 5 Constructor
	*
	* @param	$settings	mixed	Array with settings or FALSE
	* @return	void
	*/
	function __construct($settings = FALSE)
	{
		//  Get global instance
		$this->EE =& get_instance();

		//  Load Low NoSpam Library
		$this->EE->load->add_package_path(PATH_THIRD.'low_nospam/');
		$this->EE->load->library('low_nospam');

		// Set extension class name
		$this->class_name = ucfirst(get_class($this));

		// set settings
		$this->settings = $settings;
	}
	
	// --------------------------------------------------------------------

	/**
	* Extension settings form
	*
	* @param	array
	* @return	string
	*/
	function settings_form($current)
	{
		$this->EE->load->helper('form');
		$this->EE->cp->load_package_js('low_nospam');

		// -------------------------------------
		//  Get Services from library file
		// -------------------------------------

		$services = array();

		foreach(array_keys($this->EE->low_nospam->services) AS $key)
		{
			$services[$key] = $key;
		}

		/** -------------------------------------
		/**  Get member groups
		/** -------------------------------------*/

		$query = $this->EE->db->query("SELECT group_id, group_title FROM exp_member_groups
							WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'
							ORDER BY group_title ASC");

		/** -------------------------------------
		/**  Initiate member groups array
		/** -------------------------------------*/

		$groups = array();

		/** -------------------------------------
		/**  Populate member groups array
		/** -------------------------------------*/

		foreach ($query->result_array() AS $row)
		{
			$groups[$row['group_id']] = $row['group_title'];
		}

		/** -------------------------------------
		/**  Get list of installed modules
		/** -------------------------------------*/

		$installed = $this->EE->cp->get_installed_modules();

		/** -------------------------------------
		/**  Define settings array for display
		/** -------------------------------------*/
		
		$data = array();
		$data['settings'] = array_merge($this->default_settings, $current);
		$data['member_groups'] = $groups;
		$data['version'] = LOW_NOSPAM_VERSION;
		$data['name'] = str_replace('_ext', '', strtolower(get_class($this)));
		$data['services'] = $services;
		$data['has_forum'] = in_array('forum', $installed);
		$data['has_wiki'] = in_array('wiki', $installed);

		/** -------------------------------------
		/**  Build output
		/** -------------------------------------*/

		$this->EE->cp->set_breadcrumb('#', LOW_NOSPAM_NAME);

		/** -------------------------------------
		/**  Load view
		/** -------------------------------------*/

		return $this->EE->load->view('settings', $data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	* Save extension settings
	*
	*/
	function save_settings()
	{
		// Initiate settings array
		$settings = array();
		
		// Loop through default settings
		foreach ($this->default_settings AS $setting => $default_value)
		{
			// Check posted values, fallback to default value
			if (($value = $this->EE->input->post($setting)) ===  FALSE)
			{
				$value = $default_value;
			}

			// Populate new settings
			$settings[$setting] = $value;
		}

		// Update new settings
		$this->EE->db->where('class', get_class($this));
		$this->EE->db->update('exp_extensions', array('settings' => serialize($settings)));
	}

	// --------------------------------------------------------------------

	/**
	*/
	function sessions_start(&$session)
	{
		// Mark as ham only if opening comments and checking the box
		if ((REQ == 'CP') &&
			($this->EE->input->get('method') == 'modify_comments') &&
			($this->EE->input->post('action') == 'open') &&
			($this->EE->input->post('mark_as_ham') == 'y') &&
			($this->EE->input->post('toggle') !== FALSE))
		{
			$this->_mark($this->EE->input->post('toggle'), 'ham');
		}
		
		// Zero Tolerance
		if ($this->settings['zero_tolerance'] == 'y' && (REQ == 'ACTION' || REQ == 'PAGE') && $_POST)
		{
			$post = array();
			$ignore = array('XID','ACT','RET','FROM','PRV','mbase','board_id','topic_id','forum_id','site_id','list',
							'URI','status','return','redirect_on_duplicate','form_name','id','tagdata','params_id');

			foreach ($_POST AS $key => $value)
			{
				// POST values to ignore
				if (in_array($key, $ignore)) continue;

				// Ignore empty fields
				if ( ! strlen(($value = trim($value)))) continue;

				// Fill author spot
				if ( ! $post['comment_author'] && in_array($key, array('name', 'username', 'screen_name', 'author')))
				{
					$post['comment_author'] = $value;
					continue;
				}
				
				// Fill email
				if ( ! $post['comment_author_email'] && in_array($key, array('mail', 'email', 'emailaddress', 'e-mail', 'from', 'to')))
				{
					$post['comment_author_email'] = $value;
					continue;
				}
				
				// Fill url
				if ( ! $post['comment_author_url'] && in_array($key, array('url', 'site', 'website')))
				{
					$post['comment_author_url'] = $value;
					continue;
				}

				// Convert to string
				if (is_array($value))
				{
					$value = (string) @implode(' ', $value);
				}
				
				// Fill the rest
				$post['comment_content'] .= $value ."\n";
			}

			// Send to service and get verdict
			if ($this->is_spam($post))
			{
				// Core lang file hasn't been loaded yet, so we'll do so now
				$this->EE->lang->loadfile('core');

				// Apply hand brake
				$this->abort('input_discarded');
			}
		}
	}
	
	// --------------------------------------------------------------------

	/**
	* Check incoming comment, exits if it's spam
	*
	* @param	array
	* @return	array
	*/
	function insert_comment_insert_array($data)
	{
		// -------------------------------------
		//  check settings to see if comment needs to be verified
		// -------------------------------------

		if ($this->settings['check_comments'] == 'y' AND $this->_check_user())
		{

			// -------------------------------------
			//  Array to check
			// -------------------------------------

			$comment = array(
				'comment_author'		=> $data['name'],
				'comment_author_email'	=> $data['email'],
				'comment_author_url'	=> $data['url'],
				'comment_content'		=> $data['comment'],
				'user_ip'				=> $data['ip_address']
			);

			// Check it!
			if ($this->is_spam($comment))
			{
				// -------------------------------------
				//  discard message (exit without saving)
				//  or save closed comment
				// -------------------------------------

				if ($this->settings['caught_comments'] == 'x')
				{
					$this->error = 'input_discarded';
				}
				else
				{
					// set comment status to 'c' or 'p'
					$data['status'] = $this->settings['caught_comments'];

					// insert closed comment to DB
					$this->EE->db->insert('exp_comments', $data);

					// Set error message if not already set
					if ($this->error == '')
					{
						$this->error = 'input_is_spam';
					}
				}

				// abort script
				$this->abort();
			}
		}

		// return data as if nothing happened...
		return $data;
	}

	// --------------------------------------------------------------------

	/**
	* Mark given comments as spam, if so desired
	*
	* @param	array
	* @return	void
	*/
	function delete_comment_additional($comment_ids)
	{
		// First, check if checkbox was checked
		if ($this->EE->input->post('mark_as_spam') != 'y') return;
		$this->_mark($comment_ids);
	}

	// --------------------------------------------------------------------

	/**
	* Check incoming forum post, exit if it's spam
	*
	* @param	object
	* @return	void
	*/
	function forum_submit_post_start($obj)
	{
		// check settings to see if trackback needs to be verified
		if (isset($this->settings['check_forum_posts']) AND $this->settings['check_forum_posts'] == 'y' AND $this->_check_user())
		{
			// input array
			$this->input = array(
				'user_ip'				=> $this->EE->input->ip_address(),
				'user_agent'			=> $this->EE->session->userdata['user_agent'],
				'comment_author'		=> (strlen($this->EE->session->userdata['screen_name']) ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username']),
				'comment_author_email'	=> $this->EE->session->userdata['email'],
				'comment_author_url'	=> $this->EE->session->userdata['url'],
				'comment_content'		=> ($this->EE->input->post('title') ? $this->EE->input->post('title')."\n\n" : '').$this->EE->input->post('body')
			);

			// Check it!
			if ($this->is_spam())
			{
				// Set error message if not already set
				if ( ! $this->error )
				{
					$this->error = 'input_discarded';
				}

				// No forum post moderation, so just exit
				$this->abort();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	* Check incoming wiki article, exit if it's spam
	*
	* @param	object
	* @param	string
	* @return	void
	*/
	function edit_wiki_article_end($obj, $query)
	{
		// check settings to see if comment needs to be verified
		if (isset($this->settings['check_wiki_articles']) AND $this->settings['check_wiki_articles'] == 'y' AND $this->_check_user())
		{
			$this->input = array(
				'user_ip'				=> $this->EE->input->ip_address(),
				'user_agent'			=> $this->EE->session->userdata['user_agent'],
				'comment_author'		=> (strlen($this->EE->session->userdata['screen_name']) ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username']),
				'comment_author_email'	=> $this->EE->session->userdata['email'],
				'comment_author_url'	=> $this->EE->session->userdata['url'],
				'comment_content'		=> $this->EE->input->post('title').' '.$this->EE->input->post('article_content')
			);

			// Check it!
			if ($this->is_spam())
			{
				// HANDLE WIKI ARTICLE SPAM
				$wiki_id = $obj->wiki_id;
				$page_id = $this->EE->db->escape_str($query->row['page_id']);

				// get real last revision id
				$query  = $this->EE->db->query("SELECT last_revision_id FROM exp_wiki_page WHERE wiki_id = {$wiki_id} AND page_id = {$page_id}");
				$row    = $query->row_array(); 
				$rev_id = $row['last_revision_id'];

				// close revision
				$this->EE->db->query("UPDATE exp_wiki_revisions SET revision_status = 'closed' WHERE wiki_id = {$wiki_id} AND page_id = {$page_id} AND revision_id = {$rev_id}");

				$this->abort();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	* Check incoming wiki article, exit if it's spam
	*
	* @return	void
	*/
	function member_member_register_start()
	{
		// check settings to see if comment needs to be verified
		if (isset($this->settings['check_member_registrations']) AND $this->settings['check_member_registrations'] == 'y' AND $this->_check_user())
		{
			// Don't send these values to the service
			$ignore = array('password', 'password_confirm', 'rules', 'email' , 'url', 'username',
							'XID', 'ACT', 'RET', 'FROM', 'site_id', 'accept_terms');

			// Init content var
			$content = '';

			// Loop through posted data, add to content var
			foreach ($_POST AS $key => $val)
			{
				if (in_array($key, $ignore)) continue;

				$content .= $val . "\n";
			}

			$this->input = array(
				'user_ip'				=> $this->EE->input->ip_address(),
				'user_agent'			=> $this->EE->session->userdata['user_agent'],
				'comment_author'		=> $this->EE->input->post('username'),
				'comment_author_email'	=> $this->EE->input->post('email'),
				'comment_author_url'	=> $this->EE->input->post('url'),
				'comment_content'		=> $content
			);

			// Check it!
			if ($this->is_spam())
			{
				// Set error message if not already set
				if ( ! $this->error )
				{
					$this->error = 'input_discarded';
				}

				// Exit if spam
				$this->abort();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	* Checks if given array is a spammy one
	*
	* @param	array
	* @return	bool
	*/
	function is_spam($array = array())
	{
		// Fallback
		if (empty($array) && isset($this->input))
		{
			$array = $this->input;
		}

		// initiate nospam lib
		if ( ! $this->EE->low_nospam->set_service($this->settings['service'], $this->settings['api_key']) )
		{
			$this->error = 'service_not_found';
			$this->abort();
		}

		// check connectivity
		if ( ! $this->EE->low_nospam->is_available() )
		{
			$this->error = 'service_unreachable';
			return ($this->settings['moderate_if_unreachable'] == 'y');
		}

		// check api key
		if ( ! $this->EE->low_nospam->key_is_valid() )
		{
			$this->error = 'invalid_api_key';
			$this->abort();
		}

		// set data to check
		$this->EE->low_nospam->prep_data($array);

		// get verdict
		return $this->EE->low_nospam->is_spam();
	}

	// --------------------------------------------------------------------

	/**
	* Abort script and show user message
	*
	* @param	string
	* @return	void
	*/
	function abort($msg = '')
	{
		$this->EE->extensions->end_script = TRUE;
		$this->EE->lang->loadfile('low_nospam');

		// get error msg
		$line = ($msg) ? $msg : $this->error;

		// show error message
		$this->EE->output->show_user_error('submission', $this->EE->lang->line($line));
		exit;
	}

	// --------------------------------------------------------------------

	/**
	* Check if current user needs to be checked
	*
	* @return	bool
	*/
	function _check_user()
	{
		// Don't check if we don't have to check logged-in members
		if ($this->settings['check_members'] == 'n' AND $this->EE->session->userdata['member_id'] != 0)
		{
			$do_check = FALSE;
		}
		// Don't check if user is not in selected member groups
		elseif (is_array($this->settings['check_members']) AND !in_array($this->EE->session->userdata['group_id'], $this->settings['check_members']))
		{
			$do_check = FALSE;
		}
		// Every other case, perform check
		else
		{
			$do_check = TRUE;
		}

		return $do_check;
	}

	// --------------------------------------------------------------------

	/**
	* Mark given comments as either spam or ham
	*
	* @param	array	the comment ids to mark
	* @param	string	'spam' or 'ham'
	* @return	void
	*/
	function _mark($comment_ids = array(), $as = 'spam')
	{
		// Set service and api key
		if ( ! $this->EE->low_nospam->set_service($this->settings['service'], $this->settings['api_key']))
		{
			// Show user error: service not found
			show_error('Service not found');
		}

		// compose where part of query
		$sql_where = "comment_id IN ('".implode("','", $comment_ids)."')";

		// Compose query, service-friendy
		$sql = "SELECT
				ip_address	AS user_ip,
				name		AS comment_author,
				email		AS comment_author_email,
				url			AS comment_author_url,
				comment		AS comment_content
			FROM
				`exp_comments`
			WHERE
				{$sql_where}
		";
		$query = $this->EE->db->query($sql);
		
		// Determine method
		$method = ($as == 'spam') ? 'mark_as_spam' : 'mark_as_ham';

		// send each one to service
		foreach ($query->result_array() AS $row)
		{
			$this->EE->low_nospam->$method($row);
		}
	}

	// --------------------------------------------------------------------

	/**
	* Activate extension
	*
	* @return	null
	*/
	function activate_extension()
	{
		// Hooks to insert
		$hooks = array(
			'sessions_start',
			'insert_comment_insert_array',
			'delete_comment_additional',
			'forum_submit_post_start',
			'edit_wiki_article_end',
			'member_member_register_start'
		);

		// insert hooks and methods
		foreach ($hooks AS $hook)
		{
			// data to insert
			$data = array(
				'class'		=> $this->class_name,
				'method'	=> $hook,
				'hook'		=> $hook,
				'priority'	=> 1,
				'version'	=> LOW_NOSPAM_VERSION,
				'enabled'	=> 'y',
				'settings'	=> serialize($this->default_settings)
			);

			// insert in database
			$this->EE->db->insert('exp_extensions', $data);
		}

	}

	// --------------------------------------------------------------------

	/**
	* Update extension
	*
	* @param	string	$current
	* @return	null
	*/
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		// Upate to version 2.1.1
		// - Add member registration check
		if ($current < '2.1.1')
		{
			// Get current settings
			$settings = $this->_get_current_settings();

			// Add new record to settings
			$settings['check_member_registrations'] = 'n';
			$new_settings = $this->EE->db->escape_str(serialize($settings));

			// save new settings to DB
			$this->EE->db->query("UPDATE exp_extensions SET settings = '{$new_settings}' WHERE class = '".$this->class_name."'");

			// Add new hook
			$this->EE->db->insert(
				'exp_extensions', array(
					'extension_id'	=> '',
					'class'			=> $this->class_name,
					'method'		=> 'member_member_register_start',
					'hook'			=> 'member_member_register_start',
					'settings'		=> $new_settings,
					'priority'		=> 1,
					'version'		=> $this->version,
					'enabled'		=> 'y'
				)
			); // end db->insert
		}
		
		// Upate to version 2.2.0
		// - Remove module
		// - Add delete_comment_additional hook
		// - Add sessions_start hook
		// - Add caught_comments setting
		if ($current < '2.2.0')
		{
			// Remove module
			$this->EE->db->where('module_name', LOW_NOSPAM_CLASS_NAME);
			$this->EE->db->delete('modules');

			// Add settings
			// Get current settings
			$settings = $this->_get_current_settings();

			// Add new record to settings and save to DB
			unset($settings['discard_comments']);
			$settings['caught_comments'] = 'p';
			$settings['zero_tolerance'] = 'n';
			$this->EE->db->where('class', $this->class_name);
			$this->EE->db->update('extensions', array('settings' => serialize($settings)));

			// Add new hooks
			foreach (array('delete_comment_additional', 'sessions_start') AS $new_hook)
			{
				$this->EE->db->insert(
					'exp_extensions', array(
						'extension_id'	=> '',
						'class'			=> $this->class_name,
						'method'		=> $new_hook,
						'hook'			=> $new_hook,
						'settings'		=> serialize($settings),
						'priority'		=> 1,
						'version'		=> $this->version,
						'enabled'		=> 'y'
					)
				); // end db->insert
			}
		}

		// init data array
		$data = array();

		// Add version to data array
		$data['version'] = $this->version;

		// Update records using data array
		$this->EE->db->where('class', $this->class_name);
		$this->EE->db->update('exp_extensions', $data);
	}

	// --------------------------------------------------------------------

	/**
	* Disable extension
	*
	* @return	null
	*/
	function disable_extension()
	{
		// Delete records
		$this->EE->db->where('class', $this->class_name);
		$this->EE->db->delete('exp_extensions');
	}

	// --------------------------------------------------------------------

	/**
	* Return current settings
	*
	* @return	array
	*/
	function _get_current_settings()
	{
		// Get current settings
		$query = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE settings != '' AND class = '".$this->class_name."' LIMIT 1");
		$row = $query->row();
		return unserialize($row->settings);
	}

}
// END CLASS

/* End of file ext.low_nospam.php */