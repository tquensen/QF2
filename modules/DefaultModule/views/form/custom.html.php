<?php if ($element->module && $element->view): ?>
<?php echo $this->parse($element->module, $element->view, is_array($element->parameter) ? $element->parameter : array()); ?>
<?php elseif ($element->content): ?>
<?php echo $element->content; ?>
<?php endif; ?>
