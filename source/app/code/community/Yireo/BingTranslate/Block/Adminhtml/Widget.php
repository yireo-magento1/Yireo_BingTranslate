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
 * BingTranslate Widget-block
 */
class Yireo_BingTranslate_Block_Adminhtml_Widget extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Yireo_BingTranslate_Helper_Data
     */
    protected $moduleHelper;

    /**
     * Internal constructor
     */
    protected function _construct()
    {
        $this->moduleHelper = Mage::helper('bingtranslate');

        parent::_construct();
    }

    /**
     * Return the current source-language
     *
     * @return string
     */
    public function getSourceLanguage()
    {
        return $this->moduleHelper->getFromLanguage();
    }

    /**
     * Return the current destination-language
     *
     * @param bool $stripped
     * @return string
     */
    public function getDestinationLanguage($stripped = true)
    {
        $code = $this->moduleHelper->getToLanguage();
        if ($stripped) {
            return preg_replace('/\-(.*)$/', '', $code);
        } else {
            return $code;
        }
    }

    /**
     * Return a list of languages
     *
     * @return array
     */
    public function getLanguages()
    {
        $options = array();

        $locale = Mage::getModel('core/locale')->getLocale();
        $locales = $locale->getLocaleList();
        $languages = $locale->getTranslationList('language', $locale);

        foreach ($locales as $code => $active) {

            if (strstr($code, '_')) continue;

            if (!isset($languages[$code])) {
                continue;
            }

            if ($this->moduleHelper->isSupportedLanguage($code) == false) {
                continue;
            }

            $label = $languages[$code];

            $options[] = array(
                'value' => $code,
                'label' => $label . ' [' . $code . ']',
            );
        }

        return $options;
    }

    /**
     * Get a listing of store languages
     *
     * @return array
     */
    public function getStoreLanguages()
    {
        $stores = Mage::getModel('core/store')->getCollection();
        $data = array();

        foreach ($stores as $store) {
            $locale = Mage::getStoreConfig('general/locale/code', $store);
            $language = preg_replace('/_(.*)/', '', $locale);
            $data['s' . $store->getId()] = $language;
        }

        return $data;
    }
}
