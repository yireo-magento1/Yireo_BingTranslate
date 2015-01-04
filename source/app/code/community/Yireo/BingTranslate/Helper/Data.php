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
     * @access public
     * @param null
     * @return bool
     */
    public function enabled()
    {
        if($this->hasApiSettings() == false) return false;
        return true;
    }

    /**
     * Check whether the API-details are configured
     * 
     * @access public
     * @param null
     * @return bool
     */
    public function hasApiSettings()
    {
        $clientId = Mage::helper('bingtranslate')->getClientId();
        $clientSecret = Mage::helper('bingtranslate')->getClientSecret();
        if(empty($clientId) || empty($clientSecret)) {
            return false;
        }
        return true;
    }

    /**
     * Return the configured client-ID
     * 
     * @access public
     * @param null
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
     * @access public
     * @param null
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
     * @access public
     * @param null
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
     * @access public
     * @param null
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
     * @access public
     * @param null
     * @return string
     */
    public function getToLanguage($store = null)
    {
        if(empty($store)) $store = Mage::app()->getRequest()->getUserParam('store');
        $toLanguage = Mage::getStoreConfig('catalog/bingtranslate/langcode', $store);
        if(empty($toLanguage)) {
            $locale = Mage::getStoreConfig('general/locale/code', $store);
            $toLanguage = preg_replace('/_(.*)/', '', $locale);
        }
        return $toLanguage;
    }

    /**
     * Return the title of the destination language
     * 
     * @access public
     * @param null
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
     * Check whether the language-code is supported
     * 
     * @access public
     * @param null
     * @return array
     */
    public function isSupportedLanguage($code)
    {
        $supportedLanguages = Mage::helper('bingtranslate')->getSupportedLanguages();
        $onlySupportedLanguages = (bool)Mage::getStoreConfig('catalog/bingtranslate/only_supported_languages');
        if($onlySupportedLanguages == false) {
            return true;
        }
        
        foreach($supportedLanguages as $supportedLanguage) {
            if($code == $supportedLanguage) {
                return true;
            }
        }
        
        return false;
    } 
    
    /**
     * Get supported languages
     * 
     * @access public
     * @param null
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

    /**
     * Return the title of the destination language
     * 
     * @access public
     * @param null
     * @return string
     */
    public function getStoreByCode($code)
    {
        $stores = Mage::app()->getStores();
        foreach($stores as $store){
            if($store->getCode() == $code) {
                return $store;
            }
         }
        return Mage::getModel('core/store');
    }
}
