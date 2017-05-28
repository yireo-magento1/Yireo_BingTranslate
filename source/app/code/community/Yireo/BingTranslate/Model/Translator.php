<?php
/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * BingTranslate observer
 */
class Yireo_BingTranslate_Model_Translator extends Varien_Object
{
    /**
     * @var Yireo_BingTranslate_Helper_Data
     */
    protected $helper;

    /**
     * Yireo_BingTranslate_Model_Translator constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('bingtranslate');
        parent::__construct();
    }

    /**
     * Maximum length to translate
     */
    const TEXT_MAX_LENGTH = 10000;

    /**
     * String containing the translated content received from the API
     *
     * @var null
     */
    protected $apiTranslation = null;

    /**
     * @var array
     */
    protected $reverseStrings = array();

    /**
     * Method to call upon the Bing API
     *
     * @param string $text
     * @param string $fromLang
     * @param string $toLang
     *
     * @throws Exception
     * @return string
     */
    public function translate($text = null, $fromLang = null, $toLang = null)
    {
        // Load text from data-object
        $text = trim($text);
        if (empty($text)) {
            $text = $this->getData('text');
        }

        // Return empty text
        $text = trim($text);
        if (empty($text)) {
            throw new Exception($this->helper->__('Empty text in translation request'));
        }

        // Set maximum length
        if (strlen($text) > self::TEXT_MAX_LENGTH) {
            $difference = strlen($text) - self::TEXT_MAX_LENGTH;
            throw new Exception($this->helper->__('Text exceeds maximum length of %d characters with %d', self::TEXT_MAX_LENGTH, $difference));
        }

        // Disable translating
        if (Mage::getStoreConfig('catalog/bingtranslate/skip_translation')) {
            throw new Exception($this->helper->__('API-translation is disabled through setting'));
        }

        $clientKey = $this->helper->getClientKey();

        // Demo
        if (strtolower($clientKey) === 'demo') {
            throw new Exception($this->helper->__('API-translation is disabled for this demo'));
        }

        // Load some variables
        if (empty($text)) {
            $text = $this->getData('text');
        }

        if (empty($fromLang)) {
            $fromLang = $this->getData('fromLang');
        }

        if (empty($toLang)) {
            $toLang = $this->getData('toLang');
        }

        if ($toLang === 'auto') {
            $toLang = null;
        }

        // Skip empty to-language
        if (empty($toLang)) {
            throw new Exception($this->helper->__('Empty or unsupported destination-language'));
        }

        // If the languages are the same, return the original
        if ($fromLang === $toLang) {
            return $text;
        }

        // Check for reversable strings
        if (preg_match_all('/{{([^}]+)}}/', $text, $matches)) {
            foreach ($matches[0] as $match) {
                $this->reverseStrings[] = $match;
            }
        }

        // Dispatch an event
        Mage::dispatchEvent('content_translate_before', array('text' => &$text, 'from' => $fromLang, 'to' => $toLang));

        // Autoloading
        $this->helper->loader();

        // Setup the parameters
        $params = [];
        $params['handler'] = '\\Yireo\\Translate\\Handler\\MicrosoftTranslate';
        $params['key'] = $clientKey;
        $params['session'] = true;

        // Bork debugging
        if (Mage::getStoreConfig('catalog/bingtranslate/bork') || strtolower($clientKey) === 'bork') {
            $params['handler'] = '\\Yireo\\Translate\\Handler\\Bork';
        }

        $translator = new \Yireo\Translate\Translator();
        $translator->setParams($params);
        $translator->setFromLanguage($fromLang);
        $translator->setToLanguage($toLang);
        $translator->setText($text);
        $translation = $translator->translate();

        /*if (empty($translation)) {
            $apiError = $this->helper->__('Unknown data');
            $apiError .= '[' . $this->helper->__('from %s to %s', $fromLang, $toLang) . ']';
            throw new Exception($apiError);
        }

        if ($translation === $text) {
            $apiError = $this->helper->__('Translation is same as origin');
            $apiError .= '[' . $this->helper->__('from %s to %s', $fromLang, $toLang) . ']';
            throw new Exception($apiError);
        }*/

        return $this->setTranslationOutput($translation, $fromLang, $toLang);
    }

    /**
     * @param $text
     * @param $fromLang
     * @param $toLang
     *
     * @return mixed|null
     */
    public function setTranslationOutput($text, $fromLang, $toLang)
    {
        if (preg_match_all('/{{([^}]+)}}/', $text, $matches)) {
            $i = 0;
            foreach ($matches[0] as $match) {
                if (isset($this->reverseStrings[$i])) {
                    $text = str_replace($match, $this->reverseStrings[$i], $text);
                }
                $i++;
            }
        }

        // Dispatch an event
        Mage::dispatchEvent('content_translate_after', array('text' => &$text, 'from' => $fromLang, 'to' => $toLang));

        $this->apiTranslation = $text;

        return $this->apiTranslation;
    }

    /**
     * Method to return the API translation
     *
     * @return string
     */
    public function getApiTranslation()
    {
        return $this->apiTranslation;
    }
}
