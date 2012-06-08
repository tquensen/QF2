<?php
namespace QF;

use \QF\Exception\HttpException;

class Routing
{
    /**
     * @var \QF\Core
     */
    protected $qf = null;

    public function __construct(Core $qf)
    {
        $this->qf = $qf;
    }

    /**
     * gets the routename and parameters from the requested route
     *
     * @param string $route the raw route string
     * @return array an array containing the route name and parameters
     */
    public function parseRoute($route)
    {
        $method = isset($_REQUEST['REQUEST_METHOD']) ? strtoupper($_REQUEST['REQUEST_METHOD']) : (!empty($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET');
        
        $this->qf->setConfig('request_method', $method);
        
        $found = false;
        $routeParameters = '';

        if (empty($route) && ($homeRoute = $this->qf->getConfig('home_route')) && $routeData = $this->getRoute($homeRoute)) {
            return array('route' => $homeRoute, 'parameter' => array());
        } else {
            foreach ((array)$this->getRoute() as $routeName => $routeData) {
                if (!isset($routeData['url'])) {
                    continue;
                }
                if (isset($routeData['method']) && ((is_string($routeData['method']) && strtoupper($routeData['method']) != $method) || (is_array($routeData['method']) && !in_array($method, array_map('strtoupper', $routeData['method']))))) {
                    continue;
                }
                
                $routePattern = $this->generateRoutePattern($routeData);
                
                if (preg_match($this->generateRoutePattern($routeData), $route, $matches)) {
                    $routeParameters = array();
                    foreach ($matches as $paramKey => $paramValue) {
                        if (!is_numeric($paramKey)) {
                            if (trim($paramValue)) {
                                $routeParameters[urldecode($paramKey)] = urldecode($paramValue);
                            }
                        }
                    }

                    return array('route' => $routeName, 'parameter' => $routeParameters);
                }
            }
        }

        throw new Exception\HttpException('page not found', 404);
    }
    
    /**
     *
     * @param string $route the key of the route to get
     * @param array $parameter parameters for the page
     * @param bool $setAsMainRoute whether this route is the main call (set format, current_route and current_route_parameter config values) or not
     * @return @return string the parsed output of the page
     */
    public function callRoute($route, $parameter = array(), $setAsMainRoute = false)
    {
        $routeData = $this->getRoute($route);
        if (!$routeData || empty($routeData['controller']) || empty($routeData['action'])) {
            throw new HttpException('page not found', 404);
        }
        
        if (!empty($routeData['rights'])) {
            if (!$this->qf->user) {
                throw new Exception\HttpException('permission denied', 403);
            }
            if (!$this->qf->user->userHasRight($routeData['rights'])) {        
                if ($this->qf->user->getRole() === 'GUEST') {
                    throw new Exception\HttpException('login required', 401);
                } else {
                    throw new Exception\HttpException('permission denied', 403);
                }
            }
        }


        if (!empty($routeData['parameter'])) {
            $parameter = array_merge($routeData['parameter'], $parameter);
        }
        
        if ($setAsMainRoute) {
            $this->setConfig('current_route', $route);
            $this->setConfig('current_route_parameter', $parameter);
            if (!empty($parameter['_format'])) {
                $this->setConfig('format', $parameter['_format']);
            }
            if (!empty($parameter['_template'])) {
                $this->setConfig('template', $parameter['_template']);
            }
        }
        return $this->qf->callAction($routeData['controller'], $routeData['action'], $parameter);       
    }
    
    /**
     * calls the error page defined by $errorCode and shows $message
     *
     * @param string $errorCode the error page name (default error pages are 401, 403, 404, 500)
     * @param string $message a message to show on the error page, leave empty for default message depending on error code
     * @param Exception $exception an exception to display (only if QF_DEBUG = true)
     * @return string the parsed output of the error page
     */
    public function callError($errorCode = 404, $message = '', $exception = null)
    {
        return $this->callRoute('error'.$errorCode, array('message' => $message, 'exception' => $exception));
    }
    

    /**
     *
     * @param string $route the key of the route to get
     * @return mixed the routes array or a specifig route (if $route is set)
     */
    public function getRoute($route = null)
    {
        $routes = $this->qf->getConfig('routes');
        if (!$route) {
            return $routes;
        }
        return isset($routes[$route]) ? $routes[$route] : null;
    }  

    /**
     * redirects to the given url (by setting a location http header)
     *
     * @param string $url the (absolute) target url
     * @param int $code the code to send (302 (default) or 301 (permanent redirect))
     */
    public function redirect($url, $code = 302)
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    /**
     * redirects to the given route (by setting a location http header)
     *
     * @param string $route the name of the route to link to
     * @param array $params parameter to add to the url
     * @param mixed $language the target language, null for current, false for default/none, string for a specific language (must exist in$qf_config['languages'])
     * @param int $code the code to send (302 (default) or 301 (permanent redirect))
     */
    public function redirectRoute($route, $params = array(), $code = 302, $language = null)
    {
        $this->redirect($this->getUrl($route, $params, $language), $code);
    }

    /**
     * builds an internal url
     *
     * @param string $route the name of the route to link to
     * @param array $params parameter to add to the url
     * @param mixed $language the target language, null for current, false for default/none, string for a specific language (must exist in$qf_config['languages'])
     * @return string the url to the route including base_url (if available) and parameter
     */
    public function getUrl($route, $params = array(), $language = null)
    {
        $baseurl = $this->qf->getConfig('base_url', '/');
        $currentLanguage = $this->qf->getConfig('current_language');
        $defaultLanguage = $this->qf->getConfig('default_language');
        if ($language === null) {
            if ($currentLanguage && $currentLanguage != $defaultLanguage) {
                if ($baseurlI18n = $this->qf->getConfig('base_url_i18n')) {
                    $baseurl = str_replace(':lang:', $currentLanguage, $baseurlI18n);
                }
            }
        } elseif ($language && in_array($language, $this->qf->getConfig('languages', array())) && $language != $defaultLanguage) {
            if ($baseurlI18n = $this->qf->getConfig('base_url_i18n')) {
                    $baseurl = str_replace(':lang:', $language, $baseurlI18n);
                }
        }
        
        if ((!$route || $route == $this->qf->getConfig('home_route')) && empty($params)) {
            return $baseurl;
        }
        if (!($routeData = $this->getRoute($route)) || empty($routeData['url'])) {
            return $baseurl;
        }
        
        $search = array('(',')');
		$replace = array('','');
        $regexSearch =  array();

        $url = $routeData['url'];
        
        $allParameter = array_merge(isset($routeData['parameter']) ? $routeData['parameter'] : array(), $params);
		foreach ($allParameter as $param=>$value)
		{
            //remove optional parameters if -it is set to false, -it is the default value or -it doesn't match the parameter pattern
            if (!$value || empty($params[$param]) || (isset($routeData['parameter'][$param]) && $value == $routeData['parameter'][$param]) || (isset($routeData['patterns'][$param]) && !preg_match('#^'.$routeData['patterns'][$param].'$#', $value))) {
                $regexSearch[] = '#\([^:\)]*:'.$param.':[^\)]*\)#U';
            }
            $currentSearch = ':'.$param.':';
            $search[] = $currentSearch;
            $replace[] = urlencode($value);
		}
        if (count($regexSearch)) {
            $url = preg_replace($regexSearch, '', $url);
        }
		$url = str_replace($search, $replace, $url);
        
        return $baseurl.$url;
    }
    
    /**
     * builds a html link element or inline form
     *
     * @param string $title the link text
     * @return string the url to the route including base_url (if available) and parameter
     */
    public function getLink($title, $url, $method = null, $attrs = array(), $tokenName = null, $confirm = null, $postData = array())
    {
        if (!$url) {
            return $title;
        }

        if (!$method) {
            $method = 'GET';
        }

        if ($method == 'GET') {
            $attributes = '';
            foreach ((array) $attrs as $k => $v) {
                $attributes .= ' '.$k.'="'.$v.'"';
            }
            return '<a href="'.htmlspecialchars($url).'"'.($attributes).($confirm ? ' onclick="return confirm(\''.htmlspecialchars($confirm).'\')"' : '').'>'.$title.'</a>';
        } else {
            $form = new \QF\Form\Form(array(
                'name' => md5($url).'Form',
                'action' => $url,
                'method' => strtoupper($method),
                'class' => 'inlineForm',
                'wrapper' => 'plain',
                'formTokenName' => $tokenName ?: 'form_token'
            ));
            if ($confirm) {
                $form->setOption('attributes', array('onsubmit' => 'return confirm(\''.htmlspecialchars($confirm).'\')'));
            }
            $form->setElement(new \QF\Form\Element\Button('_submit', array('label' => $title, 'attributes' => $attrs ? $attrs : array())));
            foreach ((array) $postData as $postKey => $postValue) {
                $form->setElement(new \QF\Form\Element\Hidden($postKey, array('alwaysDisplayDefault' => true, 'defaultValue' => $postValue)));
            }
            return $this->qf->parse('DefaultModule', 'form/form', array('form' => $form));
        }
    }

    /**
     * builds an url to a public file (js, css, images, ...)
     *
     * @param string $file path to the file (relative from the baseurl or the given module)
     * @param string $module the module containing the file
     * @return string returns the url to the file including base_url (if available)
     */
    public function getAsset($file, $module = null)
    {
        $theme = $this->qf->getConfig('theme', null);
        $themeString = $theme ? 'themes/'.$theme . '/' : '';
        
        if (!$baseurl = $this->qf->getConfig('static_url')) {
            $baseurl = $this->qf->getConfig('base_url', '/');
        }
        if ($module) {
            if ($theme && file_exists(\QF_BASEPATH . '/templates/' . $themeString . 'modules/'.$module.'/public/'.$file)) {
                return $baseurl . 'templates/' . $themeString . 'modules/'.$module.'/public/'.$file;
            } elseif (file_exists(\QF_BASEPATH . '/templates/modules/'.$module.'/public/'.$file)) {
                return $baseurl . 'templates/modules/'.$module.'/public/'.$file;
            } else {
                return $baseurl . 'modules/'.$module.'/public/'.$file;
            }
        } else {
            if ($theme && file_exists(\QF_BASEPATH . '/templates/' . $themeString . 'public/'.$file)) {
                return $baseurl . 'templates/' . $themeString . 'public/'.$file;
            } elseif (file_exists(\QF_BASEPATH . '/templates/public/'.$file)) {
                return $baseurl . 'templates/public/'.$file;
            } else {
                return $baseurl . 'public/' . $file;
            }
        }
    }

    
    protected function generateRoutePattern($routeData) {
        $routePattern = str_replace(array('?','(',')','[',']','.'), array('\\?','(',')?','\\[','\\]','\\.'), $routeData['url']);
        if (isset($routeData['patterns'])) {
            $search = array();
            $replace = array();
            foreach ($routeData['patterns'] as $param => $regex) {
                $search[] = ':' . $param . ':';
                $replace[] = '(?P<' . $param . '>' . $regex . ')';
            }

            $routePattern = str_replace($search, $replace, $routePattern);
        }
        $routePattern = preg_replace('#:([^:]+):#i', '(?P<$1>[^\./]+)', $routePattern);

        $routePattern = '#^' . $routePattern . '$#';

        return $routePattern;
    }
}