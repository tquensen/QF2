<?php
namespace QF;

use \QF\Exception\HttpException;

class Core
{
    protected $c;
    
    protected $parameter = array();
    protected $routes = array();
    
    protected $theme = null;
    protected $format = null;
    protected $defaultFormat = null;
    protected $template = null;
    
    protected $homeRoute = null;
    protected $baseUrl = null;
    protected $baseUrlI18n = null;
    protected $staticUrl = null;
    protected $currentRoute = null;
    protected $currentRouteParameter = null;
    protected $requestMethod = null;
    
    protected $templatePath = null;
    protected $modulePath = null;
    
    public function __construct($c, $parameter, $routes)
    {
        $this->c = $c;
        
        $this->parameter = $parameter;
        $this->routes = $routes;
        
        $this->templatePath = __DIR__.'../../templates';
        $this->modulePath = __DIR__.'../../modules';
        
        if (isset($_REQUEST['REQUEST_METHOD'])) {
            $this->requestMethod = strtoupper($_REQUEST['REQUEST_METHOD']);
        } else {
            $this->requestMethod = !empty($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        }
    }
    
    public function __get($key)
    {
        return !empty($this->parameter[$key]) ? $this->parameter[$key] : null;
    }
    
    public function __set($key, $value)
    {
        $this->parameter[$key] = $value;
    }
    
    /**
     * gets the routename and parameters from the requested route
     *
     * @param string $route the raw route string
     * @return array an array containing the route name and parameters
     */
    public function parseRoute($route)
    {
        $method = $this->requestMethod;
        
        $language = !empty($this->c['i18n']) ? $this->c['i18n']->getCurrentLanguage() : false;
        
        if (empty($route) && ($homeRoute = $this->homeRoute) && $routeData = $this->getRoute($homeRoute)) {
            return array('route' => $homeRoute, 'parameter' => array());
        } else {
            foreach ((array)$this->getRoute() as $routeName => $routeData) {
                if (!isset($routeData['url'])) {
                    continue;
                }
                
                if (isset($routeData['method']) && ((is_string($routeData['method']) && strtoupper($routeData['method']) != $method) || (is_array($routeData['method']) && !in_array($method, array_map('strtoupper', $routeData['method']))))) {
                    continue;
                }
                
                if (!$routePattern = $this->generateRoutePattern($routeData, $language)) {
                    continue;
                }
                
                if (preg_match($routePattern, $route, $matches)) {
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

        throw new HttpException('page not found', 404);
    }
    
    /**
     *
     * @param string $route the key of the route to get
     * @return mixed the routes array or a specifig route (if $route is set)
     */
    public function getRoute($route = null)
    {
        $routes = $this->routes;
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
            if (empty($this->c['user'])) {
                throw new HttpException('permission denied', 403);
            }
            if (!$this->c['user']->userHasRight($routeData['rights'])) {        
                if ($this->c['user']->getRole() === 'GUEST') {
                    throw new HttpException('login required', 401);
                } else {
                    throw new HttpException('permission denied', 403);
                }
            }
        }


        if (!empty($routeData['parameter'])) {
            $parameter = array_merge($routeData['parameter'], $parameter);
        }
        
        if ($setAsMainRoute) {
            $this->currentRoute = $route;
            $this->currentRouteParameter = $parameter;
            if (!empty($parameter['_format'])) {
                $this->format = $parameter['_format'];
            }
            if (!empty($parameter['_template'])) {
                $this->template = $parameter['_template'];
            }
        }
        return $this->callAction($routeData['controller'], $routeData['action'], $parameter);       
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
     * calls the action defined by $controller and $action and returns the output
     *
     * @param string $controller the controller
     * @param string $action the action
     * @param array $parameter parameters for the page
     * @return string the parsed output of the page
     */
    public function callAction($controller, $action, $parameter = array())
    {
        if (!class_exists($controller) || !method_exists($controller, $action)) {
            throw new HttpException('action not found', 404);
        }
        
        $controller = new $controller();
        return $controller->$action($parameter, $this->c);
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
        $baseurl = $this->baseUrl ?: '/';
        
        if ($language === null && !empty($this->c['i18n'])) {
            $language = $this->c['i18n']->getCurrentLanguage();
        }
        if ($language && !empty($this->c['i18n']) && in_array($language, $this->c['i18n']->getLanguages()) && $language != $this->c['i18n']->getDefaultLanguage()) {
            if ($baseurlI18n = $this->baseUrlI18n) {
                $baseurl = str_replace(':lang:', $language, $baseurlI18n);
            }
        }
        
        if ((!$route || $route == $this->homeRoute) && empty($params)) {
            return $baseurl;
        }
        if (!($routeData = $this->getRoute($route)) || empty($routeData['url'])) {
            return false;
        }

        $search = array('(',')');
		$replace = array('','');
        $regexSearch =  array();

        if (is_array($routeData['url'])) {
            if ($language && isset($routeData['url'][$language])) {
                $url = $routeData['url'][$language];
            } elseif (isset($routeData['url']['default'])) {
                $url = $routeData['url']['default'];
            } else {
                return false;
            } 
        } else {
            $url = $routeData['url'];
        }
        
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
            return $this->parse('DefaultModule', 'form/form', array('form' => $form));
        }
    }

    /**
     * builds an url to a public file (js, css, images, ...)
     *
     * @param string $file path to the file (relative from {baseurl}/public/ or modules/{module}/public/)
     * @param string $module the module containing the file
     * @param bool $cacheBuster add the last modified time as parameter to the url to prevent caching if ressource has changed
     * @return string returns the url to the file including base_url (if available)
     */
    public function getAsset($file, $module = null, $cacheBuster = false)
    {
        $theme = $this->theme;
        $themeString = $theme ? 'themes/'.$theme . '/' : '';
        
        if (!$baseurl = $this->staticUrl) {
            $baseurl = $this->baseUrl ?: '/';
        }
        if ($module) {
            if ($theme && file_exists(\QF_BASEPATH . '/templates/' . $themeString . 'modules/'.$module.'/public/'.$file)) {
                return $baseurl . 'templates/' . $themeString . 'modules/'.$module.'/public/'.$file . ($cacheBuster ? '?'. filemtime(\QF_BASEPATH . '/templates/' . $themeString . 'modules/'.$module.'/public/'.$file) : '');
            } elseif (file_exists(\QF_BASEPATH . '/templates/modules/'.$module.'/public/'.$file)) {
                return $baseurl . 'templates/modules/'.$module.'/public/'.$file . ($cacheBuster ? '?'. filemtime(\QF_BASEPATH . '/templates/modules/'.$module.'/public/'.$file) : '');
            } elseif (file_exists(\QF_BASEPATH . '/modules/'.$module.'/public/'.$file)) {
                return $baseurl . 'modules/'.$module.'/public/'.$file . ($cacheBuster ? '?'. filemtime(\QF_BASEPATH . '/modules/'.$module.'/public/'.$file) : '');
            } else {
                return $baseurl . 'modules/'.$module.'/public/'.$file;
            }
        } else {
            if ($theme && file_exists(\QF_BASEPATH . '/templates/' . $themeString . 'public/'.$file)) {
                return $baseurl . 'templates/' . $themeString . 'public/'.$file . ($cacheBuster ? '?'. filemtime(\QF_BASEPATH . '/templates/' . $themeString . 'public/'.$file) : '');
            } elseif (file_exists(\QF_BASEPATH . '/templates/public/'.$file)) {
                return $baseurl . 'templates/public/'.$file . ($cacheBuster ? '?'. filemtime(\QF_BASEPATH . '/templates/public/'.$file) : '');
            } else {
                return $baseurl . 'public/' . $file;
            }
        }
    }
    
    /**
     * parses the given page and returns the output
     *
     * inside the page, you have direct access to any given parameter
     *
     * @param string $module the module containing the page
     * @param string $view the name of the view file
     * @param array $parameter parameters for the page
     * @return string the parsed output of the page
     */
    public function parse($module, $view, $parameter = array())
    {
        $_theme = $this->theme;
        $_themeString = $_theme ? 'themes/'.$_theme . '/' : '';
        $_format = isset($parameter['_format']) ? $parameter['_format'] : $this->format;
        $_formatString = $_format ? '.' . $_format : '';
        $_lang = !empty($this->c['i18n']) ? $this->c['i18n']->getCurrentLanguage() : false;

        if ($_lang && $_theme && file_exists(\QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $_lang . '/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $_lang . '/' . $view . $_formatString . '.php';
        } elseif ($_lang && $_theme && !$_format && file_exists(\QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $_lang . '/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $_lang . '/' . $view . '.' . $this->defaultFormat . '.php';
        } elseif ($_lang && file_exists(\QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $_lang . '/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $_lang . '/' . $view . $_formatString . '.php';
        } elseif ($_lang && !$_format && file_exists(\QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $_lang . '/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $_lang . '/' . $view . '.' . $this->defaultFormat . '.php';
        } elseif ($_lang && file_exists(\QF_BASEPATH . '/modules/' . $module . '/views/' . $_lang . '/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/modules/' . $module . '/views/' . $_lang . '/' . $view . $_formatString . '.php';
        } elseif ($_lang && !$_format && file_exists(\QF_BASEPATH . '/modules/' . $module . '/views/' . $_lang . '/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php';
        } elseif ($_theme && file_exists(\QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . $_formatString . '.php';
        } elseif ($_theme && !$_format && file_exists(\QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/templates/' .$_themeString. 'modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php';
        } elseif (file_exists(\QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . $_formatString . '.php';
        } elseif (!$_format && file_exists(\QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/templates/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php';
        } elseif (file_exists(\QF_BASEPATH . '/modules/' . $module . '/views/' . $view . $_formatString . '.php')) {
            $_file = \QF_BASEPATH . '/modules/' . $module . '/views/' . $view . $_formatString . '.php';
        } elseif (!$_format && file_exists(\QF_BASEPATH . '/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php')) {
            $_file = \QF_BASEPATH . '/modules/' . $module . '/views/' . $view . '.' . $this->defaultFormat . '.php';
        } else {
            throw new HttpException('view not found', 404);
        }

        extract($parameter, \EXTR_OVERWRITE);
        ob_start();
        require($_file);
        return ob_get_clean();
    }

    /**
     * parses the template with the given content
     *
     * inside the template, you have direct access to the page content $content
     *
     * @param string $content the parsed output of the current page
     * @param array $parameter parameters for the page
     * @return string the output of the template
     */
    public function parseTemplate($content, $parameter = array())
    {
        $_templateName = $this->template;
        $_theme = $this->theme;
        $_themeString = $_theme ? 'themes/'.$_theme . '/' : '';
        $_format = $this->format;
        $_defaultFormat = $this->defaultFormat;
        $_lang = !empty($this->c['i18n']) ? $this->c['i18n']->getCurrentLanguage() : false;
        $_file = false;

        if (is_array($_templateName)) {
            if ($_format) {
                $_templateName = isset($_templateName[$_format]) ? $_templateName[$_format] : (isset($_templateName['all']) ? $_templateName['all'] : null);
            } else {
                if (isset($_templateName['default'])) {
                    $_templateName = $_templateName['default'];
                } else {
                    $_templateName = isset($_templateName[$_defaultFormat]) ? $_templateName[$_defaultFormat] : (isset($_templateName['all']) ? $_templateName['all'] : null);
                }
            }
        }

        if ($_templateName === false) {
            return $content;
        }

        if ($_format) {
            if ($_lang && $_theme && $_templateName && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_lang . '/' . $_templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_lang . '/' . $_templateName . '.' . $_format . '.php';
            } elseif ($_lang && $_templateName && file_exists(\QF_BASEPATH . '/templates/' . $_lang . '/' . $_templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_lang . '/' . $_templateName . '.' . $_format . '.php';
            } elseif ($_lang && $_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_lang . '/' . 'default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/'. $_themeString . $_lang . '/' . 'default.' . $_format . '.php';
            } elseif ($_lang && file_exists(\QF_BASEPATH . '/templates/' . $_lang . '/default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_lang . '/default.' . $_format . '.php';
            } else if ($_theme && $_templateName && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.' . $_format . '.php';
            } elseif ($_templateName && file_exists(\QF_BASEPATH . '/templates/' . $_templateName . '.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_templateName . '.' . $_format . '.php';
            } elseif ($_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . 'default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/'. $_themeString . 'default.' . $_format . '.php';
            } elseif (file_exists(\QF_BASEPATH . '/templates/default.' . $_format . '.php')) {
                $_file = \QF_BASEPATH . '/templates/default.' . $_format . '.php';
            }
        } elseif ($_templateName) { 
            if ($_lang && $_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_lang . '/' . $_templateName . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_lang . '/' . $_templateName . '.php';
            } elseif ($_lang && file_exists(\QF_BASEPATH . '/templates/' . $_lang . '/' . $_templateName . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_lang . '/' . $_templateName . '.php';
            } elseif ($_lang && $_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_lang . '/' . $_templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_lang . '/' . $_templateName . '.' . $_defaultFormat . '.php';
            } elseif ($_lang && file_exists(\QF_BASEPATH . '/templates/' . $_lang . '/' . $_templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_lang . '/' . $_templateName . '.' . $_defaultFormat . '.php';
            } elseif ($_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.php';
            } elseif (file_exists(\QF_BASEPATH . '/templates/' . $_templateName . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_templateName . '.php';
            } elseif ($_theme && file_exists(\QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_themeString . $_templateName . '.' . $_defaultFormat . '.php';
            } elseif (file_exists(\QF_BASEPATH . '/templates/' . $_templateName . '.' . $_defaultFormat . '.php')) {
                $_file = \QF_BASEPATH . '/templates/' . $_templateName . '.' . $_defaultFormat . '.php';
            }
        }

        if (!$_file) {
            throw new HttpException('template not found', 404);
        }

        extract($parameter, \EXTR_OVERWRITE);
        ob_start();
        require($_file);
        return ob_get_clean();
    }
    
    public function getParameter()
    {
        return $this->parameter;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function getTheme()
    {
        return $this->theme;
    }
    
    public function getFormat()
    {
        return $this->format;
    }
    
    public function getDefaultFormat()
    {
        return $this->defaultFormat;
    }
    
    public function getTemplate()
    {
        return $this->template;
    }
    
    public function getHomeRoute()
    {
        return $this->homeRoute;
    }
    
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
    
    public function getBaseUrlI18n()
    {
        return $this->baseUrlI18n;
    }
    
    public function getStaticUrl()
    {
        return $this->staticUrl;
    }
    
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }
    
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }
    
    public function getCurrentRouteParameter()
    {
        return $this->currentRouteParameter;
    }
    
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    public function getModulePath()
    {
        return $this->modulePath;
    }

    public function getRequestHash($includeI18n = false)
    {
        return md5(serialize(array($this->currentRoute, $this->currentRouteParameter, $this->requestMethod, $includeI18n && !empty($this->c['i18n']) ? $this->c['i18n']->getCurrentLanguage() : '')));
    }

    public function setParameter($parameter)
    {
        $this->parameter = $parameter;
    }

    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }
    
    public function setDefaultFormat($defaultFormat)
    {
        $this->defaultFormat = $defaultFormat;
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function setHomeRoute($homeRoute)
    {
        $this->homeRoute = $homeRoute;
    }
    
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
    
    public function setBaseUrlI18n($baseUrlI18n)
    {
        $this->baseUrlI18n = $baseUrlI18n;
    }
    
    public function setStaticUrl($staticUrl)
    {
        $this->staticUrl = $staticUrl;
    }
    
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = strtoupper($requestMethod);
    }
    
    public function setCurrentRoute($currentRoute)
    {
        $this->currentRoute = $currentRoute;
    }
    
    public function setCurrentRouteParameter($currentRouteParameter)
    {
        $this->currentRouteParameter = $currentRouteParameter;
    }
    
    public function setTemplatePath($templatePath)
    {
        $this->templatePath = $templatePath;
    }

    public function setModulePath($modulePath)
    {
        $this->modulePath = $modulePath;
    }
    
    protected function generateRoutePattern($routeData, $language) {
        if (is_array($routeData['url'])) {
            if ($language && isset($routeData['url'][$language])) {
                $url = $routeData['url'][$language];
            } elseif (isset($routeData['url']['default'])) {
                $url = $routeData['url']['default'];
            } else {
                return false;
            }        
        } else {
            $url = $routeData['url'];
        }
        $routePattern = str_replace(array('?','(',')','[',']','.'), array('\\?','(',')?','\\[','\\]','\\.'), $url);
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