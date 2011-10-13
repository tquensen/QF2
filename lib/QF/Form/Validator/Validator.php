<?php
namespace QF\Form\Validator;

use \QF\Form\Form;
use \QF\Form\Element\Element;

class Validator
{
	protected $options = array();
    protected $element = null;
    protected $form = null;

	public function __construct($options = array())
	{
		$this->options = $options;
	}

	public function getOption($option)
	{
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

    public function setElement(Element $element)
    {
        $this->element = $element;
    }

    public function setForm(Form $form)
    {
        $this->form = $form;
    }

    public function getElement()
    {
        return $this->element;
    }

    public function getForm()
    {
        if ($this->form) {
            return $this->form;
        }
        return ($this->element) ? $this->element->getForm() : null;
    }

	public function validate($value)
	{
		return true;
	}
}