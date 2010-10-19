<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Low NoSpam Library class
*
* @package			low-nospam-ee2_addon
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Low_nospam
{
	/**
	* Selected API details
	*
	* @var	array
	*/
	var $api = array();

	/**
	* Selected API key
	*
	* @var	string
	*/
	var $api_key = '';

	/**
	* Data to check
	*
	* @var	array
	*/
	var $data = array();

	/**
	* Connection pointer
	*
	* @var	array
	*/
	var $connection;

	/**
	* NoSpam services
	*
	* @var	array
	*/
	var $services = array(

		// Akismet details
		'akismet' => array(
			'name'		=> 'Akismet',
			'version'	=> '1.1',
			'host'		=> 'rest.akismet.com',
			'port'		=> 80
		),

		// TypePad AntiSpam details
		'tpas' => array(
			'name'		=> 'TypePad AntiSpam',
			'version'	=> '1.1',
			'host'		=> 'api.antispam.typepad.com',
			'port'		=> 80
		)

		// Maybe even more in the future?
		// They'd have to be compatible with the Akismet API, just like TypePad is...
	);

	/**
	* Unnecessary $_SERVER variables
	*
	* @var	array
	*/
	var $ignore = array(
		'HTTP_COOKIE',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED_HOST',
		'HTTP_MAX_FORWARDS',
		'HTTP_X_FORWARDED_SERVER',
		'REDIRECT_STATUS',
		'SERVER_PORT',
		'PATH',
		'DOCUMENT_ROOT',
		'SERVER_ADMIN',
		'QUERY_STRING',
		'PHP_SELF',
		'argv'
	);

	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Low_nospam()
	{
		$this->__construct();
	}

	// --------------------------------------------------------------------

	/**
	* PHP 5 Constructor
	*
	* @return	void
	*/
	function __construct()
	{
		/** -------------------------------------
		/**  Get global instance
		/** -------------------------------------*/

		$this->EE =& get_instance();

		// set user agent
		$this->user_agent = APP_NAME.'/'.APP_VER;

		// set site url
		$this->site_url = $this->EE->config->item('site_url');

		// if site url is something like '/' or '/weblog', create full path
		if (substr($this->site_url, 0, 7) != 'http://')
		{
			$this->site_url = 'http://'.$_SERVER['SERVER_NAME'].$this->site_url;
		}
	}

	// --------------------------------------------------------------------

	/**
	* Set service and api key
	*
	* @return	bool
	*/
	function set_service($name, $key)
	{
		if (isset($this->services[$name]))
		{
			$this->api = $this->services[$name];
			$this->api_key = $key;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	* Connect to service
	*
	* @return	bool
	*/
	function connect()
	{
		return ($this->connection = @fsockopen($this->api['host'], $this->api['port'])) ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	* Close connection to the service
	*
	* @return	bool
	*/
	function disconnect()
	{
		return @fclose($this->connection);
	}

	// --------------------------------------------------------------------

	/**
	* Check if service is available
	*
	* @return	bool
	*/
	function is_available()
	{
		if ($this->connect())
		{
			$this->disconnect();
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
	*
	* @param	string
	* @param	string
	* @param	string
	* @param	int
	* @return	mixed	string if successful, FALSE if not
	*/
	function get_response($request, $path, $type = 'post', $response_length = 1160)
	{				
		if ($this->connect())
		{
			// build request
			$request
				= strtoupper($type)." /{$this->api['version']}/{$path} HTTP/1.0\r\n"
				. "Host: ".((!empty($this->api_key)) ? $this->api_key."." : null)."{$this->api['host']}\r\n"
				. "Content-Type: application/x-www-form-urlencoded; charset=".$this->EE->config->item('charset')."\r\n"
				. "Content-Length: ".strlen($request)."\r\n"
				. "User-Agent: {$this->user_agent}\r\n"
				. "\r\n"
				. $request;

			// Initiate response
			$response = '';

			@fwrite($this->connection, $request);

			while (!feof($this->connection))
			{
				$response .= @fgets($this->connection, $response_length);
			}

			$response = explode("\r\n\r\n", $response, 2);
			$res = $response[1];
			$this->disconnect();
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
	* Compose query string from $_SERVER vars and $this->data
	*
	* @return	string
	*/
	function get_query_string($add_server_vars = TRUE)
	{
		// Loop through SERVER vars, ignore some, add to $this->data
		if ($add_server_vars === TRUE)
		{
			foreach ($_SERVER AS $key => $value)
			{
				if ( ! in_array($key, $this->ignore) )
				{
					$this->data[$key] = $value;
				}
			}

		}

		// initiate query string
		$query_string = '';

		// loop through data, create QS
		foreach ($this->data AS $key => $data)
		{
			$query_string .= $key . '=' . urlencode(stripslashes($data)) . '&';
		}

		return $query_string;
	}

	// --------------------------------------------------------------------

	/**
	* Prepare $this->data, make sure required data is present:
	* http://akismet.com/development/api/#comment-check
	*
	* @param	array
	* @return	string
	*/
	function prep_data($data)
	{
		$this->data = $data;

		// blog (required)
		if (!isset($this->data['blog']) || empty($this->data['blog']))
		{
			$this->data['blog'] = $this->site_url;
		}

		// user_ip (required)
		if (!isset($this->data['user_ip']) || empty($this->data['user_ip']))
		{
			$this->data['user_ip'] = $this->EE->input->ip_address();
		}

		// user_agent (required)
		if (!isset($this->data['user_agent']) || empty($this->data['user_agent']))
		{
			$this->data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		// referrer (not required but useful to add anyway)
		if (!isset($this->data['referrer']) || empty($this->data['referrer']))
		{
			$this->data['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		}
	}

	// --------------------------------------------------------------------

	/**
	* Verify API key
	*
	* @return	bool
	*/
	function key_is_valid()
	{
		$key_check = $this->get_response("key={$this->api_key}&blog={$this->site_url}", 'verify-key');

		return ($key_check == 'valid');
	}

	// --------------------------------------------------------------------

	/**
	* Check if $this->data is spam
	*
	* @return	bool
	*/
	function is_spam()
	{
		$response = $this->get_response($this->get_query_string(), 'comment-check');

		return ($response == 'true');
	}

	// --------------------------------------------------------------------

	/**
	* Tell service this comment is spam
	*
	* @return	void
	*/
	function mark_as_spam($comment = array())
	{
		if ( ! empty($comment) )
		{
			$this->data = $comment;
		}

		return $this->get_response($this->get_query_string(FALSE), 'submit-spam');
	}

	// --------------------------------------------------------------------

	/**
	* Tell service this comment is ham
	*
	* @return	void
	*/
	function mark_as_ham($comment = array())
	{
		if ( ! empty($comment) )
		{
			$this->data = $comment;
		}

		return $this->get_response($this->get_query_string(FALSE), 'submit-ham');
	}

	// --------------------------------------------------------------------
}

// END CLASS

/* End of file Low_nospam.php */