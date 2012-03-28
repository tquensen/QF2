<h2><?php echo $t->showHeadline(array('title' => htmlspecialchars($entity->title))); ?></h2>

<?php if ($qf->user->userHasRight('admin')): ?>
    <?php 
        echo $qf->routing->getLink(//$title, $url, $method = null, $attrs = array(), $tokenName = null, $confirm = null, $postData = array()
            'delete',
            $qf->routing->getUrl('example.delete', array('id' => $entity->id)),
            'DELETE',
            array('class' => 'button icon-delete'),
            'deleteExampleToken',
            $t->deleteConfirmMessage(array('title' => htmlspecialchars($entity->title)))
        );
    ?>
<?php endif; ?>
