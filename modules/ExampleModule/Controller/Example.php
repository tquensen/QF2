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
    
    
    //default index, show, create, update, delete actions
    
    public function index($parameter)
    {     
        $t = $this->qf->i18n->get('ExampleModule');
        
        $showPerPage = 20;
        $currentPage = !empty($_GET['p']) ? $_GET['p'] : 1;

        $this->qf->config->page_title = $t->indexTitle(array('page' => $currentPage));
        $this->qf->config->meta_description = $t->indexDescription;

        $entities = Foo::getRepository($this->qf->db->get())->load(null, null, 'id DESC', $showPerPage, ($currentPage - 1) * $showPerPage);
        
        $pager = new \QF\Utils\Pager(
            Foo::getRepository($this->qf->db->get())->count(),
            $showPerPage,
            $this->qf->routing->getUrl('example.index') . '(?p={page})',
            $currentPage,
            7,
            false
        );
        
        return $this->qf->parse('ExampleModule', 'index', array('t' => $t, 'entities' => $entities, 'pager' => $pager));
    }
    
    public function show($parameter)
    {
        $t = $this->qf->i18n->get('ExampleModule');
        
        $foo = Foo::getRepository($this->qf->db->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            return $this->qf->routing->callError(404);
        }
        
        $this->qf->config->page_title = $t->showTitle(array('title' => htmlspecialchars($foo->title)));
        $this->qf->config->meta_description = $t->showDescription(array('title' => htmlspecialchars($foo->title))); 
        
        return $this->qf->parse('ExampleModule', 'show', array('t' => $t, 'entity' => $foo));
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
    
    public function delete($parameter)
    {
        $t = $this->qf->i18n->get('ExampleModule');
        
        $foo = Foo::getRepository($this->qf->db->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            return $this->qf->routing->callError(404);
        }
        
        if ($this->qf->user->checkFormToken('deleteExampleToken')) {
            $success = $foo->delete();
        } else {
            $success = false;
        }

        if ($success) {
            $message = $t->deleteSuccessMessage(array('title' => htmlspecialchars($foo->title)));

            if ($this->qf->getConfig('format') === null) {
                //$this->registry->helper->messages->add($message, 'success');
                return $this->redirect('example.index');
            }
        } else {
            $message = $t->deleteErrorMessage(array('title' => htmlspecialchars($foo->title)));
            if ($this->qf->getConfig('format') === null) {
                //$this->registry->helper->messages->add($message, 'error');
                return $this->redirect('example.index');
            }
        }

        $view = array('t' => $t, 'entity' => $foo);
        $view['success'] = $success;
        $view['message'] = $message;

        return $this->qf->parse('ExampleModule', 'delete', $view);
    }
}
