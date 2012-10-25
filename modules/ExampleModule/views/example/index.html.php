<h2><?php echo $t->indexHeadline; ?></h2>

<?php if (count($entities)): ?>
<ol>
    <?php foreach ($entities as $entity): ?>
    <li>
        <h3>
            <a href="<?php $qf->getUrl('example.show', array('id' => $entity->id)); ?>">
                <?php htmlspecialchars($entity->title); ?>
            </a>
        </h3>
    </li>
    <?php endforeach; ?>
</ol>
<?php echo $qf->parse('DefaultModule', 'utils/pager', $pager->getForView()); ?>
<?php else: ?>
    <p><?php echo $t->indexNoEntities; ?></p>
<?php endif; ?>