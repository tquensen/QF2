<?php
namespace QF\Form\Element;

class Custom extends Element
{
	protected $type = 'custom';

    public function setValue($value)
	{
        if ($this->getOption('setValueCallback') && is_callable($this->getOption('setValueCallback'))) {
            call_user_func($this->getOption('setValueCallback'), $value, $this);
        }
	}

	public function updateEntity($entity)
	{
        if ($this->getOption('useEntity') !== false && $entity) {
            if ($this->getOption('updateEntityCallback') && is_callable($this->getOption('updateEntityCallback'))) {
                call_user_func($this->getOption('updateEntityCallback'), $entity, $this);
            }
        }
	}
}