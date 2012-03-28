<h2><?php echo $t->deleteHeadline(array('title' => htmlspecialchars($entity->title))); ?></h2>
<p>
    <a href="<?php echo $qf->routing->getUrl('example.index'); ?>">
        <?php echo htmlspecialchars($message); ?>
    </a>
</p>