<?php
namespace QF\Form\Validator;

class CombineOr extends Validator
{
    protected $validators = array();

    public function __construct($validators = array(), $options = array())
	{
        $this->validators = $validators;
		$this->options = $options;

        foreach ($this->validators as $validator) {
            $validator->setElement($this->getElement());
        }
	}

	public function validate($value)
	{

        foreach ($this->validators as $validator)
        {
            if ($validator->validate($value)) {
                return true;
            } else {
                $errorMessage = $validator->errorMessage;
				if ($errorMessage)
				{
					$this->errorMessage = $errorMessage;
				}
            }
        }
		return false;
	}
}