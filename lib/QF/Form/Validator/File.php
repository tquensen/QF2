<?php
namespace QF\Form\Validator;

class File extends Validator
{
	public function validate($value)
	{
        if (!is_array($value)) {
            return false;
        }

		if ($value['error'] && ($this->required || $value['error'] != \UPLOAD_ERR_NO_FILE)) {
            return false;
        }

        if ($value['error'] && $value['error'] != \UPLOAD_ERR_NO_FILE && !file_exists($value['tmp_name'])) {
            return false;
        }
        
        return true;
	}
}