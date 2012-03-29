<?php
namespace QF\Form\Element;

use \QF\Form\Form;

class Element
{
	protected $name = false;
	protected $options = false;
	protected $validators = false;
	protected $form = false;
	protected $isValid = true;
	protected $type = false;

	public function __construct($name = false, $options = array(), $validators = array())
	{
		$this->name = $name;
		$this->options = (array) $options;
		$this->validators = (is_array($validators)) ? $validators : array($validators);
        foreach ($this->validators as $validator) {
            $validator->setElement($this);
        }
	}

	public function setForm(Form $form)
	{
		$this->form = $form;
	}

	public function getForm()
	{
		return $this->form;
	}

	public function getType()
	{
		return $this->type;
	}

	public function addValidator($validators)
	{
		if (!is_array($validators))
		{
			$validators = array($validators);
		}
        foreach ($validators as $validator) {
            $validator->setElement($this);
        }
		$this->validators = array_merge($this->validators, $validators);
	}

	public function getOption($option)
	{
        if ($option === 'value' && (!isset($this->options[$option]))) {
            if ($this->defaultValue !== null) {
                $this->value = $this->defaultValue;
            } else {
                if ($this->getOption('useModel') !== false && $entity = $this->getForm()->getEntity()) {
                    $property = $this->getOption('entityProperty') ? $this->getOption('entityProperty') : $this->name;
                    $this->value = isset($entity->$property) ? $entity->$property : null;
                }
            }
        }
		return (isset($this->options[$option])) ? $this->options[$option] : null;
	}

	public function setOption($option, $value)
	{
		$this->options[$option] = $value;
	}

	public function __get($option)
	{
		return $this->getOption($option);
	}

	public function __set($option, $value)
	{
		$this->setOption($option, $value);
	}

	public function getName()
	{
		return $this->name;
	}
    
    public function getValue()
    {
        return $this->value;
    }

	public function setValue($value)
	{
        if ($value !== null && !$this->alwaysUseDefault)
		{
            $this->value = $value;
        }
	}

	public function validate()
	{
		foreach ($this->validators as $validator)
		{
			if (!$validator->validate($this->value))
			{
				$errorMessage = $validator->errorMessage;
				if ($errorMessage)
				{
					$this->errorMessage = $errorMessage;
				}
				$this->isValid = false;

                if ($this->globalErrors) {
                    $this->getForm()->setError($this->errorMessage, $this->getName());
                }

				break;
			}
		}

		return $this->isValid;
	}

	public function setError($errorMessage)
	{
		$this->errorMessage = $errorMessage;
		$this->isValid = false;
        if ($this->globalErrors) {
            $this->getForm()->setError($this->errorMessage, $this->getName());
        } else {
            $this->getForm()->setError();
        }
        
	}

	public function isValid()
	{
		return (bool) $this->isValid;
	}

	public function wasSubmitted()
	{
		return ($this->form) ? $this->form->wasSubmitted() : false;
	}

    public function updateEntity($entity) {
        if ($this->getOption('useEntity') !== false && $entity) {
            $property = $this->getOption('entityProperty') ? $this->getOption('entityProperty') : $this->name;
            $entity->$property = $this->value;
        }
    }

    /**
     *
     * @param bool $public whether to export only "save" data (true, default) or any options of the element (false)
     * @return array the array representation of this form
     */
    public function toArray($public = true)
    {
        $element = array();
        $element['name'] = $this->name;
        $element['fullName'] = $this->forceName ? htmlspecialchars($this->forceName) : htmlspecialchars($this->getForm()->getName() . '[' . $this->getName() . ']');
        $element['type'] = $this->type;
        $element['isValid'] = $this->isValid;
        $element['errorMessage'] = $this->errorMessage;
        $element['value'] = $this->value;
        $element['required'] = $this->required;

        if ($public) {
            $element['options'] = array();
            $element['options']['defaultValue'] = $this->defaultValue;
            $element['options']['label'] = $this->label;
        } else {
            $element['options'] = $this->options;
        }

        return $element;
    }

}