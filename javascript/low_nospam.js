/**
* Low NoSpam JavaScript file
*
* @package			low-nospam-ee2_addon
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/

$(function(){
	
	// Low NoSpam Settings form
	$('input[name=check_comments]').click(function(){
		if ($(this).attr('value') == 'y') {
			$('#check_comments_more').slideDown(150);
		} else {
			$('#check_comments_more').slideUp(150);
		}
	});

});