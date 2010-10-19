<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Include config file
require PATH_THIRD.'low_nospam/config'.EXT;

/**
* Low NoSpam Accessory class
*
* @package			low-nospam-ee2_addon
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
* @since			2.1.0
*/
class Low_nospam_acc {

	var $name			= LOW_NOSPAM_NAME;
	var $id				= LOW_NOSPAM_CLASS_NAME;
	var $version		= LOW_NOSPAM_VERSION;
	var $description	= 'Accessory for the Low NoSpam add-on.';
	var $sections		= array();

	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Low_nospam_acc()
	{
		$this->__construct();
	}

	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	* Set Sections
	*
	* Set content for the accessory
	*
	* @access	public
	* @return	void
	*/
	function set_sections()
	{
		$this->EE->lang->loadfile('low_nospam');

		// Get closed comments
		$this->EE->db->select('COUNT(*) AS num');
		$this->EE->db->from('exp_comments');
		$this->EE->db->where_in('status', array('p','c'));
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get();
		$result = $query->row_array();

		// Show accessory tab accordingly
		if ( $num = $result['num'] )
		{
			$this->name .= " ($num)";
			$heading = ($num == 1) ? $this->EE->lang->line('closed_comments_one') : sprintf($this->EE->lang->line('closed_comments_many'), $num);
			$content = '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment">'.$this->EE->lang->line('go_moderate').'</a>';
		}
		else
		{
			$heading = $this->EE->lang->line('no_closed_comments');
			$content = '<a href="'.$this->EE->lang->line('donate_url').'">'.$this->EE->lang->line('donate_link').'</a>';
		}
		
		$content .= $this->_js();
		
		$this->sections[$heading] = $content;

	}

	// --------------------------------------------------------------------

	private function _js()
	{
		$mark_as_spam = $this->EE->input->post('mark_as_spam');
		
		return <<<EOJS
		<script type="text/javascript">
			(function($){
				$(function(){
					// Add mark as spam flag to delete comment confirmation page
					$('input[name=delete_comments]').after('<input type="hidden" name="mark_as_spam" value="{$mark_as_spam}" />');
					
					var dropdown = $('#comment_action').get(0);
					if (!dropdown) return;

					// switch submit button and dropdown around
					$(dropdown).after($(dropdown).prev().css('margin-left','10px'));

					// Add margin to dropdown
					$(dropdown).css('margin-right','10px');

					var mark_as_spam = $('<label><input type="checkbox" name="mark_as_spam" value="y" /> Mark as spam</label>');
					var mark_as_ham  = $('<label><input type="checkbox" name="mark_as_ham" value="y" /> Mark as ham</label>');

					// Add extra options to form
					$(dropdown).after(mark_as_spam);
					$(dropdown).after(mark_as_ham);

					var show_nospam_options = function() {
						$(mark_as_spam).hide();
						$(mark_as_ham).hide();
						switch ($(this).val()) {
							case 'open':
								$(mark_as_ham).show();
							break;
							case 'delete':
								$(mark_as_spam).show();
							break;
						}
					};

					$(dropdown).change(show_nospam_options);
					show_nospam_options();
					
				});
			})(jQuery);
		</script>
EOJS;
	}

}
// END CLASS

/* End of file acc.low_nospam.php */