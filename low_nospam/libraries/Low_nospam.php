<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low NoSpam Library class
 *
 * @package        low_nospam
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-nospam
 * @license        http://creativecommons.org/licenses/by-sa/3.0/
 */
class Low_nospam {

	/**
	 * Available NoSpam services
	 */
	private $_services = array(

		// Akismet details
		'akismet' => array(
			'name'		=> 'Akismet',
			'version'	=> '1.1',
			'host'		=> 'rest.akismet.com',
			'port'		=> 80
		),

		// TypePad AntiSpam is no longer available
		// 'tpas' => array(
		// 	'name'		=> 'TypePad AntiSpam',
		// 	'version'	=> '1.1',
		// 	'host'		=> 'api.antispam.typepad.com',
		// 	'port'		=> 80
		// )

		// Maybe even more in the future?
		// They'd have to be compatible with the Akismet API, just like TypePad was...
	);

	/**
	 * API details
	 */
	private $_api;
	private $_api_key    = '';
	private $_api_name   = '';
	private $_user_agent = '';
	private $_site_url   = '';

	/**
	 * Connection pointer
	 */
	private $_connection;

	/**
	 * Data to check
	 *
	 * @var	array
	 */
	private $_data = array();

	/**
	 * Member groups to check
	 *
	 * @var	array
	 */
	private $_member_groups = array(2,3,4);

	/**
	 * Unnecessary $_SERVER variables
	 *
	 * @var	array
	 */
	private $_server_ignore = array(
		'HTTP_COOKIE',
		//'HTTP_X_FORWARDED_FOR',
		//'HTTP_X_FORWARDED_HOST',
		//'HTTP_X_FORWARDED_SERVER',
		'HTTP_MAX_FORWARDS',
		'REDIRECT_STATUS',
		'SERVER_PORT',
		'PATH',
		'DOCUMENT_ROOT',
		'SERVER_ADMIN',
		'QUERY_STRING',
		'PHP_SELF',
		'argv'
	);

	/**
	 * Unnecessary $_POST variables
	 *
	 * @var	array
	 */
	private $_post_ignore = array(
		'ACT',
		'XID',
		'csrf_token',
		'site_id'
	);

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		// set user agent
		$this->_user_agent = APP_NAME.'/'.APP_VER.' | '.LOW_NOSPAM_NAME.'/'.LOW_NOSPAM_VERSION;

		// set site url
		$this->_site_url = ee()->config->item('site_url');

		// if site url is something like '/' or '/weblog', create full path
		if (substr($this->_site_url, 0, 7) != 'http://')
		{
			$this->_site_url = 'http://'.$_SERVER['SERVER_NAME'].$this->_site_url;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Return supported services
	 */
	public function get_services()
	{
		return $this->_services;
	}

	/**
	 * Return current set service
	 */
	public function get_service()
	{
		return $this->_api;
	}

	/**
	 * Return current set service name
	 */
	public function get_service_name()
	{
		return $this->_api_name;
	}

	/**
	 * Set service and api key
	 */
	public function set_service($name, $key)
	{
		if (isset($this->_services[$name]))
		{
			$this->_api = $this->_services[$name];
			$this->_api_key  = $key;
			$this->_api_name = $name;

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Add keys to SERVER ignore array
	 */
	public function set_server_ignore($key, $force = FALSE)
	{
		// Add single string to array
		if (is_string($key))
		{
			$this->_server_ignore[] = $key;
		}
		// Add array or replace whole values
		elseif (is_array($key))
		{
			$this->_server_ignore = ($force === TRUE)
				? $key
				: array_merge($this->_server_ignore, $key);
		}

		// Clean up
		$this->_server_ignore = array_unique(array_filter($this->_server_ignore));
	}

	// --------------------------------------------------------------------

	/**
	 * Get member groups
	 */
	public function get_member_groups()
	{
		return $this->_member_groups;
	}

	/**
	 * Get member groups
	 */
	public function set_member_groups($ids)
	{
		$this->_member_groups = $ids;
	}

	// --------------------------------------------------------------------

	/**
	 * Connect to service
	 */
	private function _connect()
	{
		return ($this->_connection = @fsockopen($this->_api['host'], $this->_api['port'])) ? TRUE : FALSE;
	}

	/**
	 * Close connection to the service
	 */
	private function _disconnect()
	{
		return @fclose($this->_connection);
	}

	// --------------------------------------------------------------------

	/**
	 * Check if service is available
	 */
	public function is_available()
	{
		if ($this->_connect())
		{
			$this->_disconnect();
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Communicate with service
	 */
	private function _get_response($request, $path, $type = 'post', $response_length = 1160)
	{
		if ($this->_connect())
		{
			// Determine host
			$host = $this->_api['host'];

			// Add api key if not verifying it
			if ($path != 'verify-key') $host = $this->_api_key.'.'.$host;

			// build request
			$request
				= strtoupper($type)." /{$this->_api['version']}/{$path} HTTP/1.0\r\n"
				. "Host: {$host}\r\n"
				. "Content-Type: application/x-www-form-urlencoded; charset=".ee()->config->item('charset')."\r\n"
				. "Content-Length: ".strlen($request)."\r\n"
				. "User-Agent: {$this->_user_agent}\r\n"
				. "\r\n"
				. $request;

			// Initiate response
			$response = '';

			@fwrite($this->_connection, $request);

			while (!feof($this->_connection))
			{
				$response .= @fgets($this->_connection, $response_length);
			}

			$response = explode("\r\n\r\n", $response, 2);
			$res = $response[1];
			$this->_disconnect();
		}
		else
		{
			$res = FALSE;
		}

		// return the response or FALSE
		return $res;
	}

	// --------------------------------------------------------------------

	/**
	 * Set given key as the data array
	 */
	public function set_data($key, $value = FALSE)
	{
		if (is_array($key))
		{
			$this->_data = array_merge($this->_data, $key);
		}
		elseif (is_string($key))
		{
			$this->_data[$key] = $value;
		}
	}

	/**
	 * Set the $this->data['content'] by $_POST
	 */
	public function set_content_by_post($ignore = array())
	{
		if ( ! empty($ignore))
		{
			$this->_post_ignore = array_merge($this->_post_ignore, $ignore);
		}

		// Get the flattened POST data
		$post = (empty($_POST)) ? array() : $this->_flatten($_POST);

		// Implode to single string
		$post = implode("\n", $post);

		// Add the data to the content key
		$this->_data['content'] = $post;

		return $post;
	}

	/**
	 * Flatten given array to a 1-dimensional array
	 */
	private function _flatten($array = array())
	{
		$flat = array();

		foreach ($array AS $key => $val)
		{
			// Skip ignored keys
			if (in_array($key, $this->_post_ignore)) continue;

			if (is_array($val))
			{
				// Recursively flatten arrays in POST
				$flat = array_merge($flat, $this->_flatten($val));
			}
			else
			{
				$flat[] = $val;
			}
		}

		return $flat;
	}

	/**
	 * Set required data
	 */
	private function _set_required_data($referrer = TRUE)
	{
		// Default required
		$req = array(
			'blog'       => $this->_site_url,
			'user_ip'    => ee()->input->ip_address(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT']
		);

		// Add referrer?
		if ($referrer) $req['referrer'] = @$_SERVER['HTTP_REFERER'];

		// Set them, only if not already set
		foreach ($req AS $key => $val)
		{
			if ( ! array_key_exists($key, $this->_data))
			{
				$this->set_data($key, $val);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Verify API key
	 */
	public function key_is_valid()
	{
		$qs = http_build_query(array(
			'key'  => $this->_api_key,
			'blog' => $this->_site_url
		));

		return ($this->_get_response($qs, 'verify-key') == 'valid');
	}

	// --------------------------------------------------------------------

	/**
	 * Ask service if given data is spam
	 */
	public function is_spam($data = array())
	{
		return ($this->_send('comment-check', $data) == 'true');
	}

	/**
	 * Tell service this data is spam
	 */
	public function mark_as_spam($data = array())
	{
		return $this->_send('submit-spam', $data);
	}

	/**
	 * Tell service this data is ham
	 */
	public function mark_as_ham($data = array())
	{
		return $this->_send('submit-ham', $data);
	}

	/**
	 * Send (current) data as given type and get response
	 */
	private function _send($type, $data = array())
	{
		// Set any given data
		if ( ! empty($data)) $this->set_data($data);

		// Incoming stuff or not
		$incoming = ($type == 'comment-check');

		// Set required
		$this->_set_required_data($incoming);

		// Get Query String
		$request = $this->_query_string($incoming);

		// Return the response
		return $this->_get_response($request, $type);
	}

	// --------------------------------------------------------------------

	/**
	 * Compose query string from $_SERVER vars and $this->_data
	 */
	private function _query_string($add_server_vars = TRUE)
	{
		// Loop through SERVER vars, ignore some, add to $this->_data
		if ($add_server_vars === TRUE)
		{
			foreach ($_SERVER AS $key => $value)
			{
				if ( ! in_array($key, $this->_server_ignore))
				{
					$this->set_data($key, $value);
				}
			}
		}

		// Create QS from data
		$query_string = http_build_query($this->_data);

		// And return it
		return $query_string;
	}
}

// END CLASS

/* End of file Low_nospam.php */