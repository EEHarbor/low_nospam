<?php if ($total_results): ?>
	
	<?=form_open($mod_url.AMP.'method=mark',array('id'=>'low_form'))?>
	<?php

		$this->table->set_template($cp_table_template);
		
		$this->table->set_heading(
			ucfirst(lang('comment')),
			ucfirst(lang('title')),
			ucfirst(lang('author')),
			ucfirst(lang('email')),
			lang('url'),
			lang('ip_address'),
			form_checkbox('select_all', 'true', FALSE, 'id="togglebox"')
		);

		foreach($comments as $row)
		{
			$this->table->add_row(
				htmlspecialchars(substr($row['comment'], 0, 100)),
				htmlspecialchars($row['title']),
				htmlspecialchars($row['name']),
				htmlspecialchars($row['email']),
				htmlspecialchars($row['url']),
				$row['ip_address'],
				form_checkbox('toggle[]', "c{$row['comment_id']}")
			);
		}
	?>
	
	<?=$this->table->generate()?>
	
	<p style="text-align:right">
		<select name="mark_as" id="mark_as">
			<option value="spam"><?=lang('spam_and_delete')?></option>
			<option value="ham"><?=lang('ham_and_open')?></option>
		</select>
		<?=form_submit(array('id' => 'nospam_submit', 'value' => lang('submit'), 'class' => 'submit'));?>
	</p>
	
	<?=form_close()?>
	
<?php endif; ?>

<p<?php if ($total_results): ?> style="display:none"<?php endif; ?> id="low_no_comments">
	<?=lang('no_closed_comments')?><br /><br />
	<a class="submit" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=low%40loweblog%2ecom&amp;item_name=Low%20NoSpam&amp;no_shipping=1&amp;cn=Optional%20remark&amp;tax=0&amp;currency_code=EUR&amp;lc=US&amp;bn=PP%2dDonationsBF&amp;charset=UTF%2d8">Support Low NoSpam by donating!</a>
</p>


