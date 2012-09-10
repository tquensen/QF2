<?php foreach ($form->getElements() as $currentElement): ?>
    <?php if ($currentElement->getType() == 'hidden') { continue; }; ?>

    <?php if (!in_array($currentElement->getType(), array('fieldset', 'fieldsetend'))): ?>
    <div id="<?php echo htmlspecialchars($form->getName())?>__<?php echo htmlspecialchars($currentElement->getName())?>__wrapper" class="form<?php echo ucfirst($currentElement->getType())?>Wrapper<?php if (!$currentElement->isValid()):?> invalid<?php elseif($form->wasSubmitted()): ?> valid<?php endif; ?><?php if ($currentElement->wrapperClass): ?> <?php echo $currentElement->wrapperClass; ?><?php endif; ?><?php if ($currentElement->required): ?> <?php echo 'required'; ?><?php endif; ?>" <?php if ($currentElement->wrapperAttributes): foreach ((array) $currentElement->wrapperAttributes as $attr => $attrValue): ?> <?php echo ' '.$attr.'="'.$attrValue.'"'; ?><?php endforeach; endif; ?>>
    <?php endif; ?>

<?php echo $this->parse('DefaultModule', 'form/' . $currentElement->getType(), array('element' => $currentElement)); ?>
<?php if ($currentElement->info):?><span class="formInfo"><?php echo htmlspecialchars($currentElement->info)?></span><?php endif; ?>
<?php if (!$currentElement->isValid() && !$currentElement->globalErrors):?><span class="formError"><?php echo htmlspecialchars($currentElement->errorMessage)?></span><?php endif; ?>

    <?php if (!in_array($currentElement->getType(), array('fieldset', 'fieldsetend'))): ?>
    </div>
    <?php endif; ?>

<?php endforeach; ?>