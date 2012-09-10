<?php foreach ($form->getElements() as $currentElement): ?>
<?php if ($currentElement->getType() == 'hidden') { continue; }; ?>
<?php echo $this->parse('DefaultModule', 'form/' . $currentElement->getType(), array('element' => $currentElement)); ?>
<?php if ($currentElement->info):?><span class="formInfo"><?php echo htmlspecialchars($currentElement->info)?></span><?php endif; ?>
<?php if (!$currentElement->isValid() && !$currentElement->globalErrors):?><span class="formError"><?php echo htmlspecialchars($currentElement->errorMessage)?></span><?php endif; ?>
<?php endforeach; ?>