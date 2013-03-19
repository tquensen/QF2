<?php
	header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html class="no-js">
    <head>
        <script>(function(H){H.className=H.className.replace(/\bnojs\b/,'js')})(document.documentElement)</script>
        <meta charset="UTF-8">
        <title><?php echo ($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?><?php echo htmlspecialchars($website_title); ?></title>
        <?php if ($meta_description): ?>
        <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>" />
        <?php endif; ?>
    </head>
    <body>
        <h1><?php echo htmlspecialchars($website_title); ?></h1>
        <?php echo $content; ?>
    </body>
</html>