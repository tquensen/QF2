<?php
namespace ExampleModule\Controller;

use \QF\Controller,
    \ExampleModule\Entity\Foo,
    \ExampleModule\Form;


class Example extends Controller
{
    public function home($parameter)
    {
        $t = $this->qf->i18n->get('ExampleModule');
        return $this->qf->parse('ExampleModule', 'home', array('t' => $t));
    }
    
    public function staticPage($parameter)
    {
        if (empty($parameter['page']) || !preg_match('/[\w\-\+]/i', $parameter['page'])) {
            return $this->qf->callError();
        }
        
        //set title/description
        $t = $this->qf->i18n->get('ExampleModule');
        $titleKey = 'page_'.$parameter['page'].'_title';
        $descriptionKey = 'page_'.$parameter['page'].'_description';
        $title = $t->get($titleKey);
        $description = $t->get($descriptionKey);
        if ($title && $title != $titleKey) {
            $this->qf->config->page_title = $title;
        }
        if ($description && $description != $descriptionKey) {
            $this->qf->config->meta_description = $description;
        }

        return $this->qf->parse('ExampleModule', 'pages/'.$parameter['page'], array('t' => $t));
    }
    
    public function create($parameter)
    {
        $t = $this->qf->i18n->get('ExampleModule');
        
        $this->qf->config->page_title = $t->createTitle;
        $this->qf->config->meta_description = $t->createDescription;
        
        $form = new ExampleForm(array(
            'model' => new Foo($this->qf->db->get()),
            't' => $t
        ));
        
        $view = array();
        
        $success = false;
        if ($form->validate()) {
            $foo = $form->updateModel();
            if ($foo->save()) {
                $success = true;
                $message = $t->createSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($this->qf->getConfig('format') === null) {
                    //$this->registry->helper->messages->add($message, 'success');
                    return $this->qf->routing->redirect($this->qf->routing->getUrl('example.show', array('id' => $foo->id)));
                }
                
                $view['message'] = $message;
                $view['entity'] = $foo;
            } else {
                $form->setError($t->createErrorMessage);
            }
        }

        $view['success'] = $success;
        $view['form'] = $form;
        
        $view['t'] = $t;
        
        return $this->qf->parse('ExampleModule', 'create', $view);
    }
    
    public function update($parameter)
    {
        $t = $this->qf->i18n->get('ExampleModule');
        
        $foo = Foo::getRepository($this->qf->db->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            return $this->qf->routing->callError(404);
        }
        
        $this->qf->config->page_title = $t->updateTitle;
        $this->qf->config->meta_description = $t->updateDescription;

        $form = new ExampleForm(array(
            'model' => $foo,
            't' => $t
        ));
        
        $view = array();
        
        $success = false;
        if ($form->validate()) {
            $foo = $form->updateModel();
            if ($foo->save()) {
                $success = true;
                $message = $t->updateSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($this->qf->getConfig('format') === null) {
                    //$this->registry->helper->messages->add($message, 'success');
                    return $this->qf->routing->redirect($this->qf->routing->getUrl('example.show', array('id' => $foo->id)));
                }
                
                $view['message'] = $message;
                $view['entity'] = $foo;
            } else {
                $form->setError($t->updateErrorMessage);
            }
        }

        $view['success'] = $success;
        $view['form'] = $form;
        
        $view['t'] = $t;
        
        return $this->qf->parse('ExampleModule', 'update', $view);
    }
}
