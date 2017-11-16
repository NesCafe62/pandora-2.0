<?php

$app->script('/ext/js/core.js', true);

$app->script('/ext/js/core.draggable.js');

$app->script('/ext/js/core.cookie.js');

$app->script('/core.plugins/debugger/js/view.profiler.js');

?><div class="debugger-height"></div>
<div class="debugger">
	<div class="debugger-resizing"></div>
	<div class="debugger-cont">
		<div class="debugger-resize-region"></div>
		<div class="debugger-caption">
			<div class="title">Консоль</div>
			<a href="javascript:void(0)" class="button-clear"><span class="icon"></span></a>
			<a href="javascript:void(0)" class="button-close"><span class="icon"></span></a>
			<a href="javascript:void(0)" class="button-minimize"><span class="icon"></span></a>
			<a href="javascript:void(0)" class="button-restore"><span class="icon"></span></a>
		</div>
		<div class="debugger-content">
			<div class="messages">
				<?php foreach ($messages as $message): ?>
					<?php $messageLabel = ($message->label) ? '<span class="label">'.$message->label.':</span>' : ''; ?>
					<div class="message-line message-channel-<?= $message->channel ?>">
						<div class="message-type"><?= $message->typeLabel ?></div>
						<div class="message-wrap">
							<div class="message-text"><?= $messageLabel.$message->message ?></div><div class="file"><?= $message->file ?></div><div class="line"><?= $message->line ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="debugger-command-line">
			<label>
				<div class="icon-console">›</div><input type="text" name="debugger_command_line">
			</label><?php /* $timePageGenerated */ ?>
		</div>
	</div>
</div>