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

        $i18n = array();
        if (file_exists($translationDir . '/' .$language . '.php')) {
            include($translationDir . '/' .$language . '.php');
        }
        $this->data = $i18n;
        $this->translations['default'] = new Translation(!empty($i18n['default']) ? $i18n['default'] : array());
    }

    /**
     *
     * @return qfTranslation
     */
    function get($ns = 'default')
    {
        if (empty($this->translations[$ns])) {
            $this->translations[$ns] = new Translation(!empty($i18n[$ns]) ? $i18n[$ns] : array());
        }
        return $this->translations[$ns];
    }
}