<?php
namespace ExampleModule\Controller;

use \QF\Controller,
    \QF\Exception\HttpException,
    \ExampleModule\Entity\Foo,
    \ExampleModule\Form;


class Example extends Controller
{
    //if your controller extends \QF\Controller, you can create a static array $services to autoload services from the DI container array(parameter => servicekey), if no string-arraykey is given, the parameter = servicekey)
    protected static $services = array('qf' => 'core', 'view', 'i18n', 'meta', 'db');
     
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
    
    //default index, show, create, update, delete actions
    
    public function index($parameter)
    {   
        $c = $this->getContainer();
        $cacheKey = 'view_'.$this->qf->getRequestHash(true); //unique hash for current route (url+request method+current language)
        
        
        if ($cachedData = $c['cache']->get($cacheKey)) {
            $this->meta->setTitle($cachedData['pageTitle']);
            $this->meta->setDescription($cachedData['metaDescription']);

            return $cachedData['response'];
        }
        
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
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
        
        $c['cache']->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_index', 'view_Example_index_page_'.$currentPage));
            
        return $response;
    }
    
    public function show($parameter)
    {
        $c = $this->getContainer();
        $cacheKey = 'view_'.$this->qf->getRequestHash(true); //unique hash for current route (url+request method)
        
        if ($cachedData = $c['cache']->get($cacheKey)) {
            $this->meta->setTitle($cachedData['pageTitle']);
            $this->meta->setDescription($cachedData['metaDescription']);

            return $cachedData['response'];
        }
        
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
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
            
        $c['cache']->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_show', 'view_Example_show_'.$parameter['id']));   
        
        return $response;
    }
    
    public function create($parameter)
    {
        $c = $this->getContainer();
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
                $c['cache']->outdateByTag(array('view_Example_index'));
                $success = true;
                $message = $t->createSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($this->view->getFormat() === null) {
                    //$this->registry->helper->messages->add($message, 'success');
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
    
    public function update($parameter)
    {
        $c = $this->getContainer();
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
                $c['cache']->outdateByTag(array('view_Example_index', 'view_Example_show_'.$parameter['id']));
                $success = true;
                $message = $t->updateSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($this->view->getFormat() === null) {
                    //$this->registry->helper->messages->add($message, 'success');
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
    
    public function delete($parameter)
    {
        $c = $this->getContainer();
        $t = $this->i18n->get('ExampleModule');
        
        $foo = Foo::getRepository($this->db->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }
        
        if ($c['security']->checkFormToken('deleteExampleToken')) {
            $success = $foo->delete();
        } else {
            $success = false;
        }

        if ($success) {
            $c['cache']->outdateByTag(array('view_Example_index'));
            $c['cache']->removeByTag('view_Example_show_'.$parameter['id']);
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
