<?php
namespace ExampleModule\Controller;

use \QF\Exception\HttpException,
    \ExampleModule\Entity\Foo,
    \ExampleModule\Form\ExampleForm;

class Example
{
    /**
     *
     * @var \QF\Core 
     */
    protected $qf;
    
    /**
     *
     * @var \QF\ViewManager
     */
    protected $view;
    
    /**
     *
     * @var \QF\I18n
     */
    protected $i18n;
    
    /**
     *
     * @var \QF\Utils\Meta
     */
    protected $meta;
    
    /**
     *
     * @var \QF\DB\DB
     */
    protected $db;
    
    /**
     *
     * @var \QF\Security
     */
    protected $security;
    
    public function getQf()
    {
        return $this->qf;
    }

    public function getView()
    {
        return $this->view;
    }

    public function getI18n()
    {
        return $this->i18n;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function getDb()
    {
        return $this->db;
    }
    
    public function getSecurity()
    {
        return $this->security;
    }

    public function setQf(\QF\Core $qf)
    {
        $this->qf = $qf;
    }

    public function setView(\QF\ViewManager $view)
    {
        $this->view = $view;
    }

    public function setI18n(\QF\I18n $i18n)
    {
        $this->i18n = $i18n;
    }

    public function setMeta(\QF\Utils\Meta $meta)
    {
        $this->meta = $meta;
    }

    public function setDb(\QF\DB\DB $db)
    {
        $this->db = $db;
    }

    public function setSecurity(\QF\Security $security)
    {
        $this->security = $security;
    }
        
    //default index, show, create, update, delete actions
    
    public function indexAction($parameter)
    {   
        /*
        $cacheKey = 'view_'.$this->qf->getRequestHash(true); //unique hash for current route (url+request method+current language)
        if ($cachedData = $this->cache->get($cacheKey)) {
            $this->meta->setTitle($cachedData['pageTitle']);
            $this->meta->setDescription($cachedData['metaDescription']);

            return $cachedData['response'];
        }
        */
        
        $t = $this->i18n->get('ExampleModule');

        $showPerPage = 20;
        $currentPage = !empty($_GET['p']) ? $_GET['p'] : 1;

        $pageTitle = $t->indexTitle(array('page' => $currentPage));
        $metaDescription = $t->indexDescription;

        $this->meta->setTitle($pageTitle);
        $this->meta->setDescription($metaDescription);
        
        $entities = Foo::getRepository($this->db->get())->load(null, null, 'id DESC', $showPerPage, ($currentPage - 1) * $showPerPage);

        $pager = new \QF\Utils\Pager(
            Foo::getRepository($this->db->get())->count(),
            $showPerPage,
            $this->qf->getUrl('example.index') . '(?p={page})',
            $currentPage,
            7,
            false
        );

        $response = $this->view->parse('ExampleModule', 'example/index', array('t' => $t, 'entities' => $entities, 'pager' => $pager));
        
        /*
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
        
        $this->cache->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_index', 'view_Example_index_page_'.$currentPage));
        */
        
        return $response;
    }
    
    public function showAction($parameter)
    {
        /*
        $cacheKey = 'view_'.$this->qf->getRequestHash(true); //unique hash for current route (url+request method)
        if ($cachedData = $this->cache->get($cacheKey)) {
            $this->meta->setTitle($cachedData['pageTitle']);
            $this->meta->setDescription($cachedData['metaDescription']);

            return $cachedData['response'];
        }
       */
        
        $t = $this->i18n->get('ExampleModule');

        $foo = Foo::getRepository($this->db->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }

        $pageTitle = $t->showTitle(array('title' => htmlspecialchars($foo->title)));
        $metaDescription = $t->showDescription(array('title' => htmlspecialchars($foo->title))); 

        $this->meta->setTitle($pageTitle);
        $this->meta->setDescription($metaDescription);
        
        $response = $this->view->parse('ExampleModule', 'example/show', array('t' => $t, 'entity' => $foo));
        
        /*
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
            
        $c['cache']->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_show', 'view_Example_show_'.$parameter['id']));   
        */
        
        return $response;
    }
    
    public function createAction($parameter)
    {
        $t = $this->i18n->get('ExampleModule');
        
        $this->meta->setTitle($t->createTitle);
        $this->meta->setDescription($t->createDescription);
        
        $form = new ExampleForm(array(
            'entity' => new Foo($this->db->get()),
            't' => $t
        ));
        
        $viewData = array();
        
        $success = false;
        if ($form->validate()) {
            $foo = $form->updateEntity();
            if ($foo->save()) {
                //$this->cache->outdateByTag(array('view_Example_index'));
                $success = true;
                $message = $t->createSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($this->view->getFormat() === null) {
                    //$this->messages->add($message, 'success');
                    return $this->qf->redirect($this->qf->getUrl('example.show', array('id' => $foo->id)));
                }
                
                $viewData['message'] = $message;
                $viewData['entity'] = $foo;
            } else {
                $form->setError($t->createErrorMessage);
            }
        }

        $viewData['success'] = $success;
        $viewData['form'] = $form;
        $viewData['t'] = $t;
        
        return $this->view->parse('ExampleModule', 'example/create', $viewData);
    }
    
    public function updateAction($parameter)
    {
        $t = $this->i18n->get('ExampleModule');
        
        $foo = Foo::getRepository($this->db->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }
        
        $this->meta->setTitle($t->updateTitle);
        $this->meta->setDescription($t->updateDescription);

        $form = new ExampleForm(array(
            'entity' => $foo,
            't' => $t
        ));
        
        $viewData = array();
        
        $success = false;
        if ($form->validate()) {
            $foo = $form->updateEntity();
            if ($foo->save()) {
                //$this->cache->outdateByTag(array('view_Example_index', 'view_Example_show_'.$parameter['id']));
                $success = true;
                $message = $t->updateSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($this->view->getFormat() === null) {
                    //$this->messages->add($message, 'success');
                    return $this->qf->redirect($this->qf->getUrl('example.show', array('id' => $foo->id)));
                }
                
                $viewData['message'] = $message;
                $viewData['entity'] = $foo;
            } else {
                $form->setError($t->updateErrorMessage);
            }
        }

        $viewData['success'] = $success;
        $viewData['form'] = $form;
        $viewData['t'] = $t;
        
        return $this->view->parse('ExampleModule', 'example/update', $viewData);
    }
    
    public function deleteAction($parameter)
    {
        $t = $this->i18n->get('ExampleModule');
        
        $foo = Foo::getRepository($this->db->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }
        
        if ($this->security->checkFormToken('deleteExampleToken')) {
            $success = $foo->delete();
        } else {
            $success = false;
        }

        if ($success) {
            //$this->cache->outdateByTag(array('view_Example_index'));
            //$this->cache->removeByTag('view_Example_show_'.$parameter['id']);
            $message = $t->deleteSuccessMessage(array('title' => htmlspecialchars($foo->title)));
            if ($this->view->getFormat() === null) {
                return $this->qf->redirect('example.index');
            }
        } else {
            $message = $t->deleteErrorMessage(array('title' => htmlspecialchars($foo->title)));
            if ($this->view->getFormat() === null) {
                return $this->qf->redirect('example.index');
            }
        }

        $viewData = array('t' => $t, 'entity' => $foo);
        $viewData['success'] = $success;
        $viewData['message'] = $message;

        return $this->view->parse('ExampleModule', 'example/delete', $viewData);
    }
}
