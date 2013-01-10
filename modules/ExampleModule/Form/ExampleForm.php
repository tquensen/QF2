<?php
namespace \ExampleModule\Form;

use \QF\Form\Form,
    \QF\Form\Element,
    \QF\Form\Validator;

class ExampleForm extends Form
{
    protected $name = 'example_form';
    
    public function __construct($options = array())
    {
        parent::__construct($options);
        
        $this->init();
    }
    
    protected function init()
    {
        $t = $this->getOption('t');
        $this->setElement(new Element\Text('title', array(
            'label' => $t->exampleFormTitleLabel
        ), array(
            new Validator\Required(array('errorMessage' => $t->exampleFormTitleRequired))
        )));
        $this->setElement(new Element\Textarea('description', array(
            'label' => $t->exampleFormDescriptionLabel
        ), array(
            new Validator\Required(array('errorMessage' => $t->exampleFormDescriptionRequired))
        )));
        
        $this->setElement(new Element\Button('description', array(
            'type' => 'submit',
            'label' => $t->exampleFormSubmitLabel
        ), array()));
    }
}