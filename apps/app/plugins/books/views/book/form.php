<form class="book-form" method="post" action="<?= $app->uri ?>">
<?php
    $form->input('title', ['label' => 'Title']);
    $form->textarea('description', ['label' => 'Description']);
    $form->input('icon', ['label' => 'Icon']);

    if ($form->isUpdate) {
        $form->hidden('id');
    }

    $form->buttonSubmit(($form->isUpdate) ? 'Save' : 'Add');
    $form->buttonLink('Cancel', $uriBack);
?>
</form>