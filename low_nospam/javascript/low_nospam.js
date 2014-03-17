;
/**
 * Low NoSpam JavaScript file
 *
 * @package        low_nospam
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-nospam
 */

// Extension Settings
$(function(){

	var $commentOptions = $('#check_comments_more'),
		speed = 150;

	if ( ! $commentOptions) return;

	$('input[name=check_comments]').click(function(){
		if ($(this).attr('value') == 'y') {
			$commentOptions.slideDown(speed);
		} else {
			$commentOptions.slideUp(speed);
		}
	});

});

$(function(){

	// Bail out if add-on JS object isn't set
	if ( typeof LOW == 'undefined' || ! LOW.NoSpam) return;

	(function(){
		var $dropdown = $('#comment_action');
		if ( ! $dropdown.length) return;

		var $ham = $('<label><input type="checkbox" name="mark_as_ham" value="y" /> '+LOW.NoSpam.lang.mark_as_ham+'</label>');
		$ham.css('margin-left', '10px');

		// switch submit button and dropdown around
		$dropdown.after($dropdown.prev().css('margin-left','10px')).after($ham);

		var toggle = function() {
			$ham.hide();
			if ($dropdown.val() == 'open') $ham.show();
		};

		$dropdown.change(toggle);
		toggle();
	})();

	// Add mark as spam flag to delete comment confirmation page
	(function(){
		var $spam = $('input[name=delete_comments]').clone();
		if ( ! $spam.length) return;
		$spam.attr('value', LOW.NoSpam.lang.mark_as_spam + ' & ' + $spam.attr('value'));
		$spam.attr('name', 'mark_as_spam');
		$spam.css('margin-left', '10px');
		$('input[name=delete_comments]').after($spam);
	})();

});