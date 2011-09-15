<?php
namespace QF;

class I18n
{
    /**
     * @var \QF\Core
     */
    protected $qf = null;
    
    /**
     * @var \QF\Translation
     */
    protected $translations = null;
    
    protected $currentLanguage = null;
    
    protected $data = array();

    /**
     * initializes the translation class
     *
     * @param \QF\Core $qf
     * @param string $translationDir the path to the translation files
     * @param string $language the target language (leave blank to use the default language)
     */
    function __construct(Core $qf, $translationDir, $language = null)
    {
        $this->qf = $qf;

        $defaultLanguage = $qf->getConfig('default_language', 'en');
        if (!$language || !in_array($language, $qf->getConfig('languages', array()))) {
            $language = $defaultLanguage;
        }

        $qf->setConfig('current_language', $language);
        $this->currentLanguage = $language;
        
        if (file_exists($translationDir . '/' .$language . '.php')) {
            $i18n = include($translationDir . '/' .$language . '.php');
        }
        $this->data = (array) $i18n;
        $this->translations['default'] = new Translation(!empty($i18n['default']) ? $i18n['default'] : array());
    }
    
    public function loadModule($module)
    {
        if (file_exists(\QF_BASEPATH . 'modules/'.$module.'/data/i18n/'.$this->currentLanguage)) {
            $newData = include \QF_BASEPATH . 'modules/'.$module.'/data/i18n/'.$this->currentLanguage;
            return (array) $newData;
        }
    }

    /**
     *
     * @return \QF\Translation
     */
    public function get($ns = 'default')
    {
        if (empty($this->translations[$ns])) {
            $this->translations[$ns] = new Translation(!empty($i18n[$ns]) ? $i18n[$ns] : array());
        }
        return $this->translations[$ns];
    }
}