<?php
namespace QF\Form\Validator;

class Callback extends Validator
{
	public function validate($value)
	{
        $callback = $this->getOption('callback');
        if (is_callable($callback))
        {
            return call_user_func($callback, $this, $value);
        }

		return false;
	}
}