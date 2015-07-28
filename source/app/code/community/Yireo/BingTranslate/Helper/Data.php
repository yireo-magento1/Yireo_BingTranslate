<?php
/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
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
        if ($this->hasApiSettings() == false) return false;
        return true;
    }

    /**
     * Log a message
     *
     * @param type $message
     * @param type $variable
     *
     * @return type
     */
    public function log($message, $variable = null)
    {
        $logging = (bool)Mage::getStoreConfig('catalog/bingtranslate/logging');
        if ($logging == false) {
            return false;
        }

        if (!empty($variable)) {
            $message .= ': ' . var_export($variable, true);
        }

        Mage::log($message, null, 'bingtranslate.log');
    }
    
    /**
     * Check whether the API-details are configured
     *
     * @return bool
     */
    public function hasApiSettings()
    {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();
        if (empty($clientId) || empty($clientSecret)) {
            return false;
        }
        return true;
    }

    /**
     * Return the configured client-ID
     *
     * @return string
     */
    public function getClientId()
    {
        return Mage::getStoreConfig('catalog/bingtranslate/client_id');
    }

    /**
     * Return the configured client-secret
     *
     * @return mixed
     */
    public function getClientSecret()
    {
        return Mage::getStoreConfig('catalog/bingtranslate/client_secret');
    }

    /**
     * Return the text for the button label
     *
     * @return string
     */
    public function getButtonLabel()
    {
        $label = Mage::getStoreConfig('catalog/bingtranslate/buttonlabel');
        $label = str_replace('$FROM', self::getFromTitle(), $label);
        $label = str_replace('$TO', self::getToTitle(), $label);
        return $label;
    }

    /**
     * Return the source language
     *
     * @return string
     */
    public function getFromLanguage()
    {
        $parent_locale = Mage::getStoreConfig('general/locale/code');
        $from_language = preg_replace('/_(.*)/', '', $parent_locale);
        return $from_language;
    }

    /**
     * Return the title of the source language
     *
     * @return string
     */
    public function getFromTitle()
    {
        $from_language = self::getFromLanguage();
        $from_title = Zend_Locale::getTranslation($from_language, 'language');
        return $from_title;
    }

    /**
     * Return the destination language
     *
     * @return string
     */
    public function getToLanguage($store = null)
    {
        if (empty($store)) {
            $store = Mage::app()->getRequest()->getUserParam('store');
        }

        $to_language = Mage::getStoreConfig('catalog/bingtranslate/langcode', $store);
        if (empty($to_language)) {
            $to_language = $this->getLanguageFromStore($store);
        }

        $controllerName = Mage::app()->getRequest()->getControllerName();
        if ($controllerName == 'cms_block') {
            $blockId = Mage::app()->getRequest()->getParam('block_id');
            $storeId = $this->getStoreIdFromBlockId($blockId);
            $to_language = $this->getLanguageFromStore($storeId);

        } elseif ($controllerName == 'cms_page') {
            $pageId = Mage::app()->getRequest()->getParam('page_id');
            $storeId = $this->getStoreIdFromPageId($pageId);
            $to_language = $this->getLanguageFromStore($storeId);
        }

        return $to_language;
    }

    /**
     * Return the title of the destination language
     *
     * @return string
     */
    public function getToTitle()
    {
        $to_language = self::getToLanguage();
        $to_language = preg_replace('/\-(.*)$/', '', $to_language);
        $to_title = Zend_Locale::getTranslation($to_language, 'language');
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
        if ($pageId > 0) {
            $page = Mage::getModel('cms/page')->load($pageId);
            $storeIds = $page->getStoreId();
            if (is_array($storeIds) && count($storeIds) == 1) {
                $storeId = $storeIds[0];
                return $storeId;
            }
        }

        return false;
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
        if ($blockId > 0) {
            $block = Mage::getModel('cms/block')->load($blockId);
            $storeIds = $block->getStoreId();
            if (is_array($storeIds) && count($storeIds) == 1) {
                $storeId = $storeIds[0];
                return $storeId;
            }
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
     * @return array
     */
    public function isSupportedLanguage($code)
    {
        $supportedLanguages = Mage::helper('bingtranslate')->getSupportedLanguages();
        $onlySupportedLanguages = (bool)Mage::getStoreConfig('catalog/bingtranslate/only_supported_languages');
        if ($onlySupportedLanguages == false) {
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
