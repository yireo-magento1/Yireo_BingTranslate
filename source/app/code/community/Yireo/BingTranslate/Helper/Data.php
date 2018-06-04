<?php
/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * BingTranslate helper
 */
class Yireo_BingTranslate_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Switch to determine whether this extension is enabled or not
     *
     * @return bool
     */
    public function enabled()
    {
        if ((bool)Mage::getStoreConfig('catalog/bingtranslate/enabled') === false) {
            return false;
        }

        if ((bool)Mage::getStoreConfig('advanced/modules_disable_output/Yireo_BingTranslate')) {
            return false;
        }

        if ($this->hasApiSettings() === false) {
            return false;
        }

        return true;
    }

    /**
     * Log a message
     *
     * @param string $message
     * @param string $variable
     *
     * @return bool
     */
    public function log($message, $variable = null)
    {
        $logging = (bool)Mage::getStoreConfig('catalog/bingtranslate/logging');
        if ($logging === false) {
            return false;
        }

        if (!empty($variable)) {
            $message .= ': ' . var_export($variable, true);
        }

        Mage::log($message, null, 'bingtranslate.log');

        return true;
    }

    /**
     * Initialize the autoloader
     */
    public function loader()
    {
        require_once BP . '/lib/Yireo/Common/System/Autoloader.php';
        \Yireo\Common\System\Autoloader::addPath(BP . '/lib/Yireo');
        \Yireo\Common\System\Autoloader::init();
    }

    /**
     * Check whether the API-details are configured
     *
     * @return bool
     */
    public function hasApiSettings()
    {
        if (Mage::getStoreConfig('catalog/bingtranslate/bork')) {
            return true;
        }

        $clientKey = $this->getClientKey();
        if (empty($clientKey)) {
            return false;
        }

        return true;
    }

    /**
     * Return the configured client-ID
     *
     * @return string
     */
    public function getClientKey()
    {
        return Mage::getStoreConfig('catalog/bingtranslate/client_key');
    }

    /**
     * Return the text for the button label
     *
     * @return string
     */
    public function getButtonLabel()
    {
        $label = Mage::getStoreConfig('catalog/bingtranslate/buttonlabel');
        $label = str_replace('$FROM', $this->getFromTitle(), $label);
        $label = str_replace('$TO', $this->getToTitle(), $label);

        return $label;
    }

    /**
     * Return the source language
     *
     * @return string
     */
    public function getFromLanguage()
    {
        $fromLanguage = Mage::getStoreConfig('catalog/bingtranslate/langcode');
        if (!empty($fromLanguage)) {
            return $fromLanguage;
        }

        $parentLocale = Mage::getStoreConfig('general/locale/code');
        $fromLanguage = preg_replace('/_(.*)/', '', $parentLocale);

        return $fromLanguage;
    }

    /**
     * Return the title of the source language
     *
     * @return string
     */
    public function getFromTitle()
    {
        $fromLanguage = $this->getFromLanguage();
        $fromTitle = Zend_Locale::getTranslation($fromLanguage, 'language');

        return $fromTitle;
    }

    /**
     * Return the destination language
     *
     * @return string
     */
    public function getToLanguage($store = null)
    {
        if (empty($store)) {
            $store = $this->getStoreFromRequest();
        }

        $toLanguage = Mage::getStoreConfig('catalog/bingtranslate/langcode', $store);
        if (empty($toLanguage)) {
            $toLanguage = $this->getLanguageFromStore($store);
        }

        $controllerName = Mage::app()->getRequest()->getControllerName();
        if ($controllerName == 'cms_block') {
            $toLanguage = $this->getToLanguageFromCmsBlock();

        } elseif ($controllerName == 'cms_page') {
            $toLanguage = $this->getToLanguageFromCmsPage();
        }

        return $toLanguage;
    }

    /**
     * @return mixed
     */
    private function getStoreFromRequest()
    {
        return Mage::app()->getRequest()->getUserParam('store');
    }

    /**
     * @return string
     */
    protected function getToLanguageFromCmsBlock()
    {
        $blockId = Mage::app()->getRequest()->getParam('block_id');
        $storeId = $this->getStoreIdFromBlockId($blockId);

        return $this->getLanguageFromStore($storeId);
    }

    /**
     * @return string
     */
    protected function getToLanguageFromCmsPage()
    {
        $pageId = Mage::app()->getRequest()->getParam('page_id');
        $storeId = $this->getStoreIdFromPageId($pageId);

        return $this->getLanguageFromStore($storeId);
    }

    /**
     * Return the title of the destination language
     *
     * @return string
     */
    public function getToTitle()
    {
        $toLanguage = $this->getToLanguage();
        $toLanguage = preg_replace('/\-(.*)$/', '', $toLanguage);
        $to_title = Zend_Locale::getTranslation($toLanguage, 'language');

        return $to_title;
    }

    /**
     * Get store from page
     *
     * @param mixed $pageId Page indicator
     *
     * @return string
     */
    public function getStoreIdFromPageId($pageId)
    {
        if (!$pageId > 0) {
            return false;
        }

        $page = Mage::getModel('cms/page')->load($pageId);
        $storeIds = $page->getStoreId();

        if (!is_array($storeIds)) {
            return false;
        }

        if (count($storeIds) !== 1) {
            return false;
        }

        $storeId = $storeIds[0];

        return $storeId;
    }

    /**
     * Get store from block
     *
     * @param mixed $blockId Block indicator
     *
     * @return string
     */
    public function getStoreIdFromBlockId($blockId)
    {
        if (!$blockId > 0) {
            return false;
        }

        /** @var Mage_Cms_Model_Block $block */
        $block = Mage::getModel('cms/block')->load($blockId);
        $storeIds = $block->getStoreId();
        if (is_array($storeIds) && count($storeIds) == 1) {
            $storeId = $storeIds[0];
            return $storeId;
        }

        return false;
    }

    /**
     * Get language from a Store View
     *
     * @param mixed $store Store indicator (integer or Mage_Core_Model_Store)
     *
     * @return string
     */
    public function getLanguageFromStore($store)
    {
        $locale = Mage::getStoreConfig('general/locale/code', $store);
        $language = preg_replace('/_(.*)/', '', $locale);

        return $language;
    }

    /**
     * Return the title of the destination language
     *
     * @return string
     */
    public function getStoreByCode($code)
    {
        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            if ($store->getCode() == $code) {
                return $store;
            }
        }

        return Mage::getModel('core/store');
    }

    /**
     * Check whether the language-code is supported
     *
     * @return bool
     */
    public function isSupportedLanguage($code)
    {
        $supportedLanguages = $this->getSupportedLanguages();
        $onlySupportedLanguages = (bool)Mage::getStoreConfig('catalog/bingtranslate/only_supported_languages');
        if ($onlySupportedLanguages === false) {
            return true;
        }

        foreach ($supportedLanguages as $supportedLanguage) {
            if ($code == $supportedLanguage) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get supported languages
     *
     * @return array
     */
    public function getSupportedLanguages()
    {
        return array(
            'ar',
            'bg',
            'ca',
            'zh',
            'cs',
            'da',
            'nl',
            'en',
            'et',
            'fa',
            'fr',
            'de',
            'el',
            'ht',
            'he',
            'hi',
            'hu',
            'id',
            'it',
            'ja',
            'ko',
            'lv',
            'lt',
            'ms',
            'mww',
            'no',
            'pl',
            'pt',
            'ro',
            'ru',
            'sk',
            'sl',
            'es',
            'sv',
            'th',
            'tr',
            'uk',
            'ur',
            'vi',
        );
    }
}
