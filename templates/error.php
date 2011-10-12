<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Server Error</title>
  </head>
  <body>
      <h1>Server Error</h1>
      <p>We are sorry, something went wrong :(</p>
      <?php if (defined('QF_DEBUG') && QF_DEBUG === true): ?>
      <pre><?php echo $e; ?></pre>
      <?php endif; ?>
  </body>
</html>
