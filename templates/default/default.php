<?php
	header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html class="no-js">
    <head>
        <script>(function(H){H.className=H.className.replace(/\bnojs\b/,'js')})(document.documentElement)</script>
        <meta charset="UTF-8">
        <?php echo $c['meta']->getTitleOutput(' | '); ?>
        <?php echo $c['meta']->getMetaOutput(); ?>
        <?php echo $c['meta']->getLinksOutput(); ?>
    </head>
    <body>
        <h1><?php echo htmlspecialchars($c['t']->website_title); ?></h1>
        <?php echo $content; ?>
        
        <?php echo $c['meta']->getJSOutput(); ?>
    </body>
</html>