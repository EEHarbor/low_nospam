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

	// Bail out if add-on JS object isn't set
	if ( typeof LOW == 'undefined' || ! LOW.NoSpam) return;

	// Add mark as spam flag to delete comment confirmation page
	if (LOW.NoSpam.add_marker) {
		$('input[name=delete_comments]').after('<input type="hidden" name="mark_as_spam" value="y" />');
	}
	
	var dropdown = $('#comment_action').get(0);
	if (!dropdown) return;

	// switch submit button and dropdown around
	$(dropdown).after($(dropdown).prev().css('margin-left','10px'));

	// Add margin to dropdown
	$(dropdown).css('margin-right','10px');

	var mark_as_spam = $('<label><input type="checkbox" name="mark_as_spam" value="y" /> '+LOW.NoSpam.lang.mark_as_spam+'</label>');
	var mark_as_ham  = $('<label><input type="checkbox" name="mark_as_ham" value="y" /> '+LOW.NoSpam.lang.mark_as_ham+'</label>');

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