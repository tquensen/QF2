<?php
namespace ExampleModule\Controller;

use \QF\Controller,
    \QF\Exception\HttpException,
    \ExampleModule\Entity\Foo,
    \ExampleModule\Form;


class Example extends Controller
{
    //default index, show, create, update, delete actions
    
    public function index($parameter, $qf, $view)
    {   
        $c = $qf->getContainer();
        $cacheKey = 'view_'.$qf->getRequestHash(true); //unique hash for current route (url+request method+current language)
        
        
        if ($cachedData = $c['cache']->get($cacheKey)) {
            $view->page_title = $cachedData['pageTitle'];
            $view->meta_description = $cachedData['metaDescription'];

            return $cachedData['response'];
        }
        
        $t = $qf->getI18n()->get('ExampleModule');

        $showPerPage = 20;
        $currentPage = !empty($_GET['p']) ? $_GET['p'] : 1;

        $pageTitle = $t->indexTitle(array('page' => $currentPage));
        $metaDescription = $t->indexDescription;

        $view->page_title = $pageTitle;
        $view->meta_description = $metaDescription;
        
        $entities = Foo::getRepository($c['db']->get())->load(null, null, 'id DESC', $showPerPage, ($currentPage - 1) * $showPerPage);

        $pager = new \QF\Utils\Pager(
            Foo::getRepository($c['db']->get())->count(),
            $showPerPage,
            $qf->getUrl('example.index') . '(?p={page})',
            $currentPage,
            7,
            false
        );

        $response = $view->parse('ExampleModule', 'example/index', array('t' => $t, 'entities' => $entities, 'pager' => $pager));
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
        
        $c['cache']->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_index', 'view_Example_index_page_'.$currentPage));;
            
        return $response;
    }
    
    public function show($parameter, $qf, $view)
    {
        $c = $qf->getContainer();
        $cacheKey = 'view_'.$qf->getRequestHash(true); //unique hash for current route (url+request method)
        
        if ($cachedData = $c['cache']->get($cacheKey)) {
            $view->page_title = $cachedData['pageTitle'];
            $view->meta_description = $cachedData['metaDescription'];

            return $cachedData['response'];
        }
        
        $t = $qf->getI18n()->get('ExampleModule');

        $foo = Foo::getRepository($c['db']->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }

        $pageTitle = $t->showTitle(array('title' => htmlspecialchars($foo->title)));
        $metaDescription = $t->showDescription(array('title' => htmlspecialchars($foo->title))); 

        $view->page_title = $pageTitle;
        $view->meta_description = $metaDescription;
        
        $response = $view->parse('ExampleModule', 'example/show', array('t' => $t, 'entity' => $foo));
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
            
        $c['cache']->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_show', 'view_Example_show_'.$parameter['id']));   
        
        return $response;
    }
    
    public function create($parameter, $qf, $view)
    {
        $c = $qf->getContainer();
        $t = $qf->getI18n()->get('ExampleModule');
        
        $view->page_title = $t->createTitle;
        $view->meta_description = $t->createDescription;
        
        $form = new ExampleForm(array(
            'entity' => new Foo($c['db']->get()),
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

                if ($view->getFormat() === null) {
                    //$this->registry->helper->messages->add($message, 'success');
                    return $qf->redirect($qf->getUrl('example.show', array('id' => $foo->id)));
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
        
        return $view->parse('ExampleModule', 'example/create', $viewData);
    }
    
    public function update($parameter, $qf, $view)
    {
        $c = $qf->getContainer();
        $t = $qf->getI18n()->get('ExampleModule');
        
        $foo = Foo::getRepository($c['db']->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }
        
        $view->page_title = $t->updateTitle;
        $view->meta_description = $t->updateDescription;

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

                if ($view->getFormat() === null) {
                    //$this->registry->helper->messages->add($message, 'success');
                    return $qf->redirect($qf->getUrl('example.show', array('id' => $foo->id)));
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
        
        return $view->parse('ExampleModule', 'example/update', $viewData);
    }
    
    public function delete($parameter, $qf, $view)
    {
        $c = $qf->getContainer();
        $t = $qf->getI18n()->get('ExampleModule');
        
        $foo = Foo::getRepository($c['db']->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }
        
        if ($qf->getSecurity()->checkFormToken('deleteExampleToken')) {
            $success = $foo->delete();
        } else {
            $success = false;
        }

        if ($success) {
            $c['cache']->outdateByTag(array('view_Example_index'));
            $c['cache']->removeByTag('view_Example_show_'.$parameter['id']);
            $message = $t->deleteSuccessMessage(array('title' => htmlspecialchars($foo->title)));
            if ($view->getFormat() === null) {
                return $qf->redirect('example.index');
            }
        } else {
            $message = $t->deleteErrorMessage(array('title' => htmlspecialchars($foo->title)));
            if ($view->getFormat() === null) {
                return $qf->redirect('example.index');
            }
        }

        $viewData = array('t' => $t, 'entity' => $foo);
        $viewData['success'] = $success;
        $viewData['message'] = $message;

        return $view->parse('ExampleModule', 'example/delete', $viewData);
    }
}
