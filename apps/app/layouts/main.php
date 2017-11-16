<!DOCTYPE html>
<html>
	<head>
		<base href="<?= $app->uriBase ?>"/>
		<meta charset="utf-8"/>
		<link rel="stylesheet" href="css/styles.css"/>
		<?= $this->head() ?>
	</head>
	<body>
		<?= $this->bodyBegin() ?>

		<div class="content">
			<?= $this->content() ?>
		</div>

		<?= $this->bodyEnd() ?>
		<script src="js/script.js"></script>
	<body>
</html>