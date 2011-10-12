<h2><?php echo $headline; ?></h2>
<?php if ($message): ?>
<p><?php echo $t->get($message); ?></p>
<?php endif; ?>
<pre>
<?php echo $exception; ?>
</pre>