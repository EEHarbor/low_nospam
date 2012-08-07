<ul>
	<?php if ($pending_comments): ?>
		<li><a href="<?=BASE.AMP.'C=addons_modules&amp;M=show_module_cp&amp;module=comment&amp;status=p'?>"><?=lang('pending_comments')?>: <?=$pending_comments?></a></li>
	<?php endif; ?>
	<?php if ($closed_comments): ?>
		<li><a href="<?=BASE.AMP.'C=addons_modules&amp;M=show_module_cp&amp;module=comment&amp;status=c'?>"><?=lang('closed_comments')?>: <?=$closed_comments?></a></li>
	<?php endif; ?>
	<?php if ( ! $pending_comments AND ! $closed_comments): ?>
		<li><a href="<?=lang('donate_url')?>"><?=lang('donate_link')?></a></li>
	<?php endif;?>
</ul>