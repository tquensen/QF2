<?php
namespace DefaultModule\Controller;

use \QF\Controller;

class Error extends Controller
{
    public function error401($parameter, $c)
    {
        header('HTTP/1.1 401 Unauthorized', true, 401);
        
        $t = $c['i18n']->get('DefaultModule');
        
        $c['config']->page_title = $t->error401Title;
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'login required';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $c['controller']->parse('DefaultModule', 'error/error401'.$debugStr, $parameter);
    }
    
    public function error403($parameter, $c)
    {
        header('HTTP/1.1 403 Forbidden', true, 403);
        
        $t = $c['i18n']->get('DefaultModule');
        
        $c['config']->page_title = $t->error403Title;
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'permission denied';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $c['controller']->parse('DefaultModule', 'error/error403'.$debugStr, $parameter);
    }
    
    public function error404($parameter, $c)
    {
        header('HTTP/1.1 404 Not Found', true, 404);
        
        $t = $c['i18n']->get('DefaultModule');
        
        $c['config']->page_title = $t->error404Title;
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'page not found';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $c['controller']->parse('DefaultModule', 'error/error404'.$debugStr, $parameter);
    }
    
    public function error500($parameter, $c)
    {
        header('500 Internal Server Error', true, 500);
        
        $t = $c['i18n']->get('DefaultModule');
        
        $c['config']->page_title = $t->error500Title;
        
        if (empty($parameter['message'])) {
            $parameter['message'] = 'server error';
        }
        
        $debugStr = defined('\\QF_DEBUG') && \QF_DEBUG ? 'debug' : '';
        $parameter['t'] = $t;
        return $c['controller']->parse('DefaultModule', 'error/error500'.$debugStr, $parameter);
    }
}
