<?php
	header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html class="no-js">
    <head>
        <script>(function(H){H.className=H.className.replace(/\bnojs\b/,'js')})(document.documentElement)</script>
        <meta charset="UTF-8">
        <?php echo $meta->getTitleOutput(' | '); ?>
        <?php echo $meta->getMetaOutput(); ?>
        <?php echo $meta->getLinksOutput(); ?>
    </head>
    <body>
        <h1><?php echo htmlspecialchars($meta->getWebsiteTitle()); ?></h1>
        <?php echo $content; ?>
        
        <?php echo $meta->getJSOutput(); ?>
    </body>
</html>