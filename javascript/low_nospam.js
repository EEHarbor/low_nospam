/**
* Low NoSpam JavaScript file
*
* @package			low-nospam-ee2_addon
* @version			2.1.1
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/

$(function(){

	// Send form to mark comments as spam or ham
	// Delete/open them afterwards, using another Ajax call

	$('#low_form').submit(function(e){

		e.preventDefault();

		// define variables
		var action = $(this).attr('action'),
			next_action = $(this).attr('action').replace('addons_modules', 'content_edit'),
			as = $('#mark_as').val(),
			post_data = $(this).serialize(),
			next_post_data = $(this).serialize(),
			comment_ids = [];

		// get checked boxes values, strip the c, which we need to play nice with EE's native open/close functions
		$('#low_form tbody input[type=checkbox]:checked').each(function(){
			comment_ids.push($(this).val().replace('c',''));
		});

		// No comments checked? Bail with EE error notice
		if (!comment_ids.length) {
			$.ee_notice($.LOW.Lang.line('no_comments'),{type:'error'});
			return false;
		}

		// Either delete or open comments -- build appropriate URL and POST data to match
		if (as == 'spam') {
			next_action = next_action.replace(/show_module_cp.+/,'delete_comment');
			next_post_data += '&comment_ids=' + comment_ids.join('|');
		} else {
			next_action = next_action.replace(/show_module_cp.+/,'modify_comments');
			next_post_data += '&action=open';
		}

		// Show status using ee_notice, and show ajax indicator, disable form button to prevent double clicks
		$.ee_notice($.LOW.Lang.line('marking_as_'+as),{type:'custom',open:true});
		$('#filter_ajax_indicator').css('visibility','visible');
		$('#nospam_submit').attr('disabled','disabled');

		// First Ajax call: Mark comments as spam/ham
		$.ajax({
			url: action,
			data: post_data,
			type: 'POST',
			complete: function(xhr, status) {
				// Don't check if sending of comments actually was successful or not, just go ahead and go forth!
				$.ee_notice($.LOW.Lang.line('finishing_'+as),{type:'custom'});
				// Second Ajax call: delete/open comments
				$.ajax({
					url: next_action,
					data: next_post_data,
					dataType: 'json',
					type: 'POST',
					complete: function() {
						$('#filter_ajax_indicator').css('visibility','hidden');
						$('#nospam_submit').attr('disabled','');
					},
					success: function(data, status2, xhr2) {
						// Reload the page to play nice with pagination
						window.location.href = window.location.href.replace(/rownum=\d+/, 'rownum=0');
						/*
						// Show done message, either from JSON result or a general one as backup
						var msg = data.message_success || $.LOW.Lang.line('done');
						$.ee_notice(msg,{type:'custom'});
						// remove message after 2 seconds
						window.setTimeout(function(){$.ee_notice.destroy();}, 2000);
						// remove checked rows
						$('#low_form tbody input[type=checkbox]:checked').parents('tr').remove();
						// Hide form, show 'no comments' message if all comments were removed
						if ( ! $('#low_form tbody tr').length ) {
							$('#low_form').hide();
							$('#low_no_comments').show();
						}
						*/
					},
					error: function(xhr2, status2, msg) {
						$.ee_notice('An error occurred: '+status2+' '+msg, {type:'error'});
					}
				});
			}
		});
	});

});