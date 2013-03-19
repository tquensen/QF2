<h2><?php echo $t->updateHeadline; ?></h2>
<?php if ($success): ?>
    <p>
        <a href="<?php echo $qf->getUrl('example.show', array('id' => $entity->id)); ?>">
            <?php echo htmlspecialchars($message); ?>
        </a>
    </p>
<?php else:
    echo $view->parse('DefaultModule', 'form/form', array('form' => $form));
endif;