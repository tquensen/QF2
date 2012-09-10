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
    
    protected $data = array();

    /**
     * initializes the translation class
     *
     * @param string $translationDir the path to the translation files
     * @param string $language the target language
     */
    function __construct($translationDir, $languages, $currentLanguage, $defaultLanguage)
    {
        $this->languages = $languages;
        $this->currentLanguage = $currentLanguage;
        $this->defaultLanguage = $defaultLanguage;
        
        $i18n = &$this->data;
        if (file_exists($translationDir . '/' .$language . '.php')) {
            include($translationDir . '/' .$language . '.php');
        }
        $this->translations['default'] = new Translation(!empty($this->data['default']) ? $this->data['default'] : array());
    }
    
    public function loadModule($module)
    {
        if (file_exists(\QF_BASEPATH . '/modules/'.$module.'/data/i18n/'.$this->currentLanguage . '.php')) {
            if (!isset($this->data[$module])) {
                $this->data[$module] = array();
            }
            $i18n = &$this->data[$module];
            include \QF_BASEPATH . '/modules/'.$module.'/data/i18n/'.$this->currentLanguage . '.php';
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
        $this->currentLanguage = $currentLanguage;
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