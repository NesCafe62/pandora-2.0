<?php
	//$value = ($value || $value === 0) ? (int) $value : '';
?>
<select name="<?= $name ?>">
	<?php foreach ($params['options'] as $key => $option): ?>
		<option <?= ($key === $value) ? 'selected="selected"' : '' ?> value="<?= $key ?>"><?= $option ?></option>
	<?php endforeach; ?>
</select>