<?php if (!$form->isValid() && $form->getOption('showGlobalErrors') && $form->hasErrors()): ?>
<ul id="<?php echo htmlspecialchars($form->getName())?>__errors" class="formErrors">
    <?php foreach ($form->getErrors() as $error): ?>
        <li>
            <?php if ($error['element']): ?>
                <label for="<?php echo htmlspecialchars($form->getName() . '__' . $form->getElement($error['element'])->getName()) ?>">
                    <span><?php echo htmlspecialchars($form->getElement($error['element'])->label) ?>:</span>
                    <?php echo htmlspecialchars($error['message']) ?>
                </label>
            <?php else: ?>
                <?php echo htmlspecialchars($error['message']) ?>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>