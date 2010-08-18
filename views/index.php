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
	
	<div style="overflow:hidden">

		<p style="float:right;">
			<select name="mark_as" id="mark_as">
				<option value="spam"><?=lang('spam_and_delete')?></option>
				<option value="ham"><?=lang('ham_and_open')?></option>
			</select>
			<?=form_submit(array('id' => 'nospam_submit', 'value' => lang('submit'), 'class' => 'submit'));?>
		</p>

		<?=$pagination?>

	</div>

	<?=form_close()?>

<?php endif; ?>

<p<?php if ($total_results): ?> style="display:none"<?php endif; ?> id="low_no_comments">
	<?=lang('no_closed_comments')?><br /><br />
	<a class="submit" href="<?=lang('donate_url')?>"><?=lang('donate_link')?></a>
</p>
