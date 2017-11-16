<?php

$labelAfter = false;

if ($fieldType == 'checkbox') {
	$labelAfter = true;
}

?>
<div class="field field-<?= $name ?> field-type-<?= $fieldType ?>">
<?php if ($label && !$noWrap): ?>
	<label>
		<?php if (!$labelAfter): ?>
			<div class="label"><?= $label ?></div>
		<?php endif; ?>
<?php endif; ?>
		<?= $fieldHtml ?>
		<?php if ($validateMessage): ?>
			<div class="validation-error"><?= $validateMessage ?></div>
		<?php endif ?>
<?php if ($label && !$noWrap): ?>
		<?php if ($labelAfter): ?>
			<div class="label"><?= $label ?></div>
		<?php endif; ?>
	</label>
<?php endif; ?>
</div>