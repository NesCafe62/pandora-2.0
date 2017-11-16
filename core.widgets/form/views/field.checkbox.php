<?php

$isChecked = ($value != '' || $value !== 0);

?>
<input type="checkbox" name="<?= $name ?>" value="on" <?= ($isChecked) ? 'checked="checked"' : '' ?>>



