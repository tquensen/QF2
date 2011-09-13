<?php
namespace QF\Form\Validator;

class CombineOr extends Validator
{
	public function validate($value)
	{
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}
}