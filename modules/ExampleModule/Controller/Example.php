<?php
namespace ExampleModule\Controller;

use \QF\Controller,
    \QF\Exception\HttpException,
    \ExampleModule\Entity\Foo,
    \ExampleModule\Form;


class Example extends Controller
{
    //default index, show, create, update, delete actions
    
    public function index($parameter, $c)
    {   
        $cacheKey = 'view_'.$c['routing']->getRequestHash(true); //unique hash for current route (url+request method+current language)
        
        //
        if ($cachedData = $c['cache']->get($cacheKey)) {
            $c['controller']->page_title = $cachedData['pageTitle'];
            $c['controller']->meta_description = $cachedData['metaDescription'];

            return $cachedData['response'];
        }
        
        $t = $c['i18n']->get('ExampleModule');

        $showPerPage = 20;
        $currentPage = !empty($_GET['p']) ? $_GET['p'] : 1;

        $pageTitle = $t->indexTitle(array('page' => $currentPage));
        $metaDescription = $t->indexDescription;

        $c['controller']->page_title = $pageTitle;
        $c['controller']->meta_description = $metaDescription;
        
        $entities = Foo::getRepository($c['db']->get())->load(null, null, 'id DESC', $showPerPage, ($currentPage - 1) * $showPerPage);

        $pager = new \QF\Utils\Pager(
            Foo::getRepository($c['db']->get())->count(),
            $showPerPage,
            $c['routing']->getUrl('example.index') . '(?p={page})',
            $currentPage,
            7,
            false
        );

        $response = $c['controller']->parse('ExampleModule', 'example/index', array('t' => $t, 'entities' => $entities, 'pager' => $pager));
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
        
        $c['cache']->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_index', 'view_Example_index_page_'.$currentPage));;
            
        return $response;
    }
    
    public function show($parameter, $c)
    {
        $cacheKey = 'view_'.$c['routing']->getRequestHash(true); //unique hash for current route (url+request method)
        
        if ($cachedData = $c['cache']->get($cacheKey)) {
            $c['controller']->page_title = $cachedData['pageTitle'];
            $c['controller']->meta_description = $cachedData['metaDescription'];

            return $cachedData['response'];
        }
        
        $t = $c['i18n']->get('ExampleModule');

        $foo = Foo::getRepository($c['db']->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }

        $pageTitle = $t->showTitle(array('title' => htmlspecialchars($foo->title)));
        $metaDescription = $t->showDescription(array('title' => htmlspecialchars($foo->title))); 

        $c['controller']->page_title = $pageTitle;
        $c['controller']->meta_description = $metaDescription;
        
        $response = $c['controller']->parse('ExampleModule', 'example/show', array('t' => $t, 'entity' => $foo));
        $cache = array(
            'response' => $response,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription
        );
            
        $c['cache']->set($cacheKey, $cache, 60*60, false, array('view', 'view_Example', 'view_Example_show', 'view_Example_show_'.$parameter['id']));   
        
        return $response;
    }
    
    public function create($parameter, $c)
    {
        $t = $c['i18n']->get('ExampleModule');
        
        $c['controller']->page_title = $t->createTitle;
        $c['controller']->meta_description = $t->createDescription;
        
        $form = new ExampleForm(array(
            'entity' => new Foo($c['db']->get()),
            't' => $t
        ));
        
        $view = array();
        
        $success = false;
        if ($form->validate()) {
            $foo = $form->updateEntity();
            if ($foo->save()) {
                $success = true;
                $message = $t->createSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($c['controller']->format === null) {
                    //$this->registry->helper->messages->add($message, 'success');
                    return $c['routing']->redirect($c['routing']->getUrl('example.show', array('id' => $foo->id)));
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
        
        return $c['controller']->parse('ExampleModule', 'example/create', $view);
    }
    
    public function update($parameter, $c)
    {
        $t = $c['i18n']->get('ExampleModule');
        
        $foo = Foo::getRepository($c['db']->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }
        
        $c['controller']->page_title = $t->updateTitle;
        $c['controller']->meta_description = $t->updateDescription;

        $form = new ExampleForm(array(
            'entity' => $foo,
            't' => $t
        ));
        
        $view = array();
        
        $success = false;
        if ($form->validate()) {
            $foo = $form->updateEntity();
            if ($foo->save()) {
                $c['cache']->outdateByTag('view_Example_show_'.$parameter['id']);
                $success = true;
                $message = $t->updateSuccessMessage(array('title' => htmlspecialchars($foo->title)));

                if ($c['controller']->format === null) {
                    //$this->registry->helper->messages->add($message, 'success');
                    return $c['routing']->redirect($c['routing']->getUrl('example.show', array('id' => $foo->id)));
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
        
        return $c['controller']->parse('ExampleModule', 'example/update', $view);
    }
    
    public function delete($parameter, $c)
    {
        $t = $c['i18n']->get('ExampleModule');
        
        $foo = Foo::getRepository($c['db']->get())->loadOne('id', $parameter['id']);
        if (!$foo) {
            throw new HttpException('Foo with id '.$parameter['id'].' not found.', 404);
        }
        
        if ($c['user']->checkFormToken('deleteExampleToken')) {
            $success = $foo->delete();
        } else {
            $success = false;
        }

        if ($success) {
            $c['cache']->removeByTag('view_Example_show_'.$parameter['id']);
            $message = $t->deleteSuccessMessage(array('title' => htmlspecialchars($foo->title)));
            if ($c['controller']->format === null) {
                //$this->registry->helper->messages->add($message, 'success');
                return $c['routing']->redirect('example.index');
            }
        } else {
            $message = $t->deleteErrorMessage(array('title' => htmlspecialchars($foo->title)));
            if ($c['controller']->format === null) {
                //$this->registry->helper->messages->add($message, 'error');
                return $c['routing']->redirect('example.index');
            }
        }

        $view = array('t' => $t, 'entity' => $foo);
        $view['success'] = $success;
        $view['message'] = $message;

        return $c['controller']->parse('ExampleModule', 'example/delete', $view);
    }
}
