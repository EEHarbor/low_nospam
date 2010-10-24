<form method="post" action="<?=BASE?>&amp;D=cp&amp;C=addons_extensions&amp;M=save_extension_settings">
	<div>
		<input type="hidden" name="file" value="<?=strtolower($name)?>" />
		<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>" />
	</div>
	<table cellpadding="0" cellspacing="0" style="width:100%" class="mainTable">
		<colgroup>
			<col style="width:50%" />
			<col style="width:50%" />
		</colgroup>
		<thead>
			<tr>
				<th scope="col"><?=lang('preference')?></th>
				<th scope="col"><?=lang('setting')?></th>
			</tr>
		</thead>
		<?php $i = 0; ?>
		<tbody>
			<tr class="<?=((++$i%2)?'odd':'even')?>">
				<td>
					<label for="service"><?=lang('service')?></label>
				</td>
				<td>
					<select name="service" id="service">
						<?php foreach ($services AS $service): ?>
							<option value="<?=$service?>"<?php if ($service == $settings['service']): ?> selected="selected"<?php endif; ?>><?=lang($service)?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr class="<?=((++$i%2)?'odd':'even')?>">
				<td>
					<label for="api_key"><?=lang('api_key')?></label>
				</td>
				<td>
					<input type="text" name="api_key" id="api_key" style="width:220px" value="<?=htmlspecialchars($settings['api_key'])?>" />
				</td>
			</tr>
			<tr class="<?=((++$i%2)?'odd':'even')?>">
				<td style="vertical-align:top">
					<label for="check_members"><?=lang('check_members')?></label>
				</td>
				<td>
					<?php foreach ($member_groups AS $group_id => $group_name): ?>
						<label style="display:block;cursor:pointer">
							<input type="checkbox" name="check_members[]" value="<?=$group_id?>" <?php if (in_array($group_id, $settings['check_members'])): ?>checked="checked" <?php endif; ?>/>
							<?=htmlspecialchars($group_name)?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr class="<?=((++$i%2)?'odd':'even')?>">
				<td style="vertical-align:top">
					<strong class="label"><?=lang('check_comments')?></strong>
				</td>
				<td>
					<label style="cursor:pointer"><input type="radio" name="check_comments" value="y"<?php if ($settings['check_comments'] == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
					<label style="cursor:pointer;margin-left:10px"><input type="radio" name="check_comments" value="n"<?php if ($settings['check_comments'] == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
					<div class="more" id="check_comments_more" style="overflow:hidden;border-top:1px dashed #ccc;padding:7px 10px;margin:7px -10px -7px -10px;<?php if ($settings['check_comments'] == 'n'): ?>display:none<?php endif; ?>">
						<?php foreach (array('p', 'c', 'x') AS $option): ?>
							<label style="cursor:pointer;display:block">
								<input type="radio" name="caught_comments" value="<?=$option?>"
								<?php if ($settings['caught_comments'] == $option): ?> checked="checked"<?php endif; ?> /> <?=lang('caught_comments_'.$option)?>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
			<?php if ($has_forum): ?>
				<tr class="<?=((++$i%2)?'odd':'even')?>">
					<td>
						<strong class="label"><?=lang('check_forum_posts')?></strong>
					</td>
					<td>
						<label style="cursor:pointer"><input type="radio" name="check_forum_posts" value="y"<?php if ($settings['check_forum_posts'] == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
						<label style="cursor:pointer;margin-left:10px"><input type="radio" name="check_forum_posts" value="n"<?php if ($settings['check_forum_posts'] == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
					</td>
				</tr>
			<?php endif; ?>
			<?php if ($has_wiki): ?>
				<tr class="<?=((++$i%2)?'odd':'even')?>">
					<td>
						<strong class="label"><?=lang('check_wiki_articles')?></strong>
					</td>
					<td>
						<label style="cursor:pointer"><input type="radio" name="check_wiki_articles" value="y"<?php if ($settings['check_wiki_articles'] == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
						<label style="cursor:pointer;margin-left:10px"><input type="radio" name="check_wiki_articles" value="n"<?php if ($settings['check_wiki_articles'] == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
					</td>
				</tr>
			<?php endif; ?>
			<tr class="<?=((++$i%2)?'odd':'even')?>">
				<td>
					<strong class="label"><?=lang('check_member_registrations')?></strong>
				</td>
				<td>
					<label style="cursor:pointer"><input type="radio" name="check_member_registrations" value="y"<?php if ($settings['check_member_registrations'] == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
					<label style="cursor:pointer;margin-left:10px"><input type="radio" name="check_member_registrations" value="n"<?php if ($settings['check_member_registrations'] == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
				</td>
			</tr>
			<tr class="<?=((++$i%2)?'odd':'even')?>">
				<td>
					<strong class="label"><?=lang('moderate_if_unreachable')?></strong>
				</td>
				<td>
					<label style="cursor:pointer"><input type="radio" name="moderate_if_unreachable" value="y"<?php if ($settings['moderate_if_unreachable'] == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
					<label style="cursor:pointer;margin-left:10px"><input type="radio" name="moderate_if_unreachable" value="n"<?php if ($settings['moderate_if_unreachable'] == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
				</td>
			</tr>
			<tr class="<?=((++$i%2)?'odd':'even')?>">
				<td>
					<strong class="label"><?=lang('zero_tolerance')?></strong>
					<p><?=lang('zero_tolerance_help')?></p>
				</td>
				<td style="vertical-align:top">
					<label style="cursor:pointer"><input type="radio" name="zero_tolerance" value="y"<?php if ($settings['zero_tolerance'] == 'y'): ?> checked="checked"<?php endif; ?> /> <?=lang('yes')?></label>
					<label style="cursor:pointer;margin-left:10px"><input type="radio" name="zero_tolerance" value="n"<?php if ($settings['zero_tolerance'] == 'n'): ?> checked="checked"<?php endif; ?> /> <?=lang('no')?></label>
				</td>
			</tr>
		</tbody>
	</table>
	<input type="submit" class="submit" value="<?=lang('submit')?>" />
</form>