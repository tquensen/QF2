<?php
namespace QF;

class I18n
{
    
    /**
     * @var \QF\Translation[]
     */
    protected $translations = null;
    
    protected $languages = array();
    protected $currentLanguage = null;
    protected $defaultLanguage = null;
    
    protected $translationDir = '';
    protected $moduleDir = '';
    
    protected $data = array();

    /**
     * initializes the translation class
     *
     * @param string $translationDir the path to the translation files
     * @param string $language the target language
     */
    function __construct($translationDir, $moduleDir, $languages, $defaultLanguage)
    {
        $this->languages = $languages;
        $this->translationDir = $translationDir;
        $this->defaultLanguage = $defaultLanguage;
        $this->currentLanguage = $this->defaultLanguage;
        
        $this->moduleDir = rtrim($moduleDir, '/');
        
        $i18n = &$this->data;
        if (file_exists($this->translationDir . '/' .$this->currentLanguage . '.php')) {
            include($this->translationDir . '/' .$this->currentLanguage . '.php');
        }
        $this->translations['default'] = new Translation(!empty($this->data['default']) ? $this->data['default'] : array());
    }
    
    public function loadModule($module)
    {
        if (file_exists($this->moduleDir.'/'.$module.'/data/i18n/'.$this->currentLanguage . '.php')) {
            if (!isset($this->data[$module])) {
                $this->data[$module] = array();
            }
            $i18n = &$this->data[$module];
            include $this->moduleDir . '/'.$module.'/data/i18n/'.$this->currentLanguage . '.php';
        }
    }
    
    public function getLanguages()
    {
        return $this->languages;
    }
    
    public function getCurrentLanguage()
    {
        return $this->currentLanguage;
    }
    
    public function getDefaultLanguage()
    {
        return $this->defaultLanguage;
    }
    
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }
    
    public function setCurrentLanguage($currentLanguage)
    {
        if ($currentLanguage == $this->currentLanguage) {
            return;
        }
        $this->currentLanguage = $currentLanguage;
        $this->translations = array();
        $this->data = array();
        
        $i18n = &$this->data;
        if (file_exists($this->translationDir . '/' .$this->currentLanguage . '.php')) {
            include($this->translationDir . '/' .$this->currentLanguage . '.php');
        }
        $this->translations['default'] = new Translation(!empty($this->data['default']) ? $this->data['default'] : array());
    }
    
    public function setDefaultLanguage($defaultLanguage)
    {
        $this->defaultLanguage = $defaultLanguage;
    }

    /**
     *
     * @return \QF\Translation
     */
    public function get($ns = 'default')
    {
        if (empty($this->translations[$ns])) {
            $this->translations[$ns] = new Translation(!empty($this->data[$ns]) ? $this->data[$ns] : array());
        }
        return $this->translations[$ns];
    }
}