<h2><?php echo $t->updateHeadline; ?></h2>
<?php if ($success): ?>
    <p>
        <a href="<?php echo $qf->routing->getUrl('example.show', array('id' => $entity->id)); ?>">
            <?php echo htmlspecialchars($message); ?>
        </a>
    </p>
<?php else:
    echo $qf->parse('DefaultModule', 'form/form', array('form' => $form));
endif;