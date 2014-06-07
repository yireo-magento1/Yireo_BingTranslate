<?php
/**
 * Yireo BingTranslate for Magento
 *
 * @package     Yireo_BingTranslate
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * BingTranslate Product-extension
 */
class Yireo_BingTranslate_Model_Product extends Mage_Core_Model_Abstract
{
    protected $charCount = 0;

    public function translate($product, $productAttributes, $stores, $delay = 0, $translate = true)
    {
        // Reset some values
        $this->charCount = 0;

        // Load the entire product
        $product = Mage::getModel('catalog/product')->load($product->getId());

        // Initialize the translator
        $translator = Mage::getSingleton('bingtranslate/translator');

        // Get the parent-locale
        $parentLocale = Mage::getStoreConfig('general/locale/code');
        $parentLanguage = preg_replace('/_(.*)/', '', $parentLocale);

        // Loop through the stores
        foreach($stores as $store) {

            if(!is_object($store)) {
                $store = Mage::getModel('core/store')->load($store);
            }

            // Load the product into this store-scope
            $product->setStoreId($store->getId());

            $currentLanguage = Mage::helper('bingtranslate')->getToLanguage($store);

            // Loop through the attributes
            foreach($productAttributes as $productAttribute) {

                // Reset some values
                $translatedValue = null;

                // Load both the global-value as the store-value
                $productValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), $productAttribute, Mage_Core_Model_App::ADMIN_STORE_ID);
                $currentValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), $productAttribute, $store);

                // Sanity checks
                $productValue = trim($productValue);
                $currentValue = trim($currentValue);
                if(empty($productValue)) continue;
                
                // Overwrite existing values
                if($productValue != $currentValue) {
                    if((bool)Mage::getStoreConfig('catalog/bingtranslate/overwrite_existing') == false) {
                        continue;
                    }
                }

                // Translate the value
                if($translate == true) {
                    $translatedValue = $translator->translate($productValue, $parentLanguage, $currentLanguage);
                    $apiError = $translator->getApiError();
                    if(!empty($apiError)) {
                        echo Mage::helper('bingtranslate')->__('API-error for %s: %s', $product->getSku(), $apiError)."\n";
                    }

                    if(!empty($translatedValue)) {
                        $product->setData($productAttribute, $translatedValue);
                        $product->getResource()->saveAttribute($product, $productAttribute);
                    }
                }

                // Increment the total-chars
                $this->charCount = $this->charCount + strlen($productValue);
            }

            // Resave entire product
            if($translate == true) {
                $product->save();
            }
            if($delay > 0) sleep((int)$delay);
        }
    }

    public function getCharCount()
    {
        return $this->charCount;
    }
}
