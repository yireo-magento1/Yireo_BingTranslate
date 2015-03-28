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
 * BingTranslate Widget-block
 */
class Yireo_BingTranslate_Block_Adminhtml_Widget extends Mage_Core_Block_Template
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setData('area', 'adminhtml');
    }

    /**
     * Return the current source-language
     *
     * @access public
     * @param null
     * @return string
     */
    public function getSourceLanguage()
    {
        return Mage::helper('bingtranslate')->getFromLanguage();
    }

    /**
     * Return the current destination-language
     *
     * @access public
     * @param bool $stripped
     * @return string
     */
    public function getDestinationLanguage($stripped = true)
    {
        $code = Mage::helper('bingtranslate')->getToLanguage();
        if ($stripped) {
            return preg_replace('/\-(.*)$/', '', $code);
        } else {
            return $code;
        }
    }

    /**
     * Return a list of languages
     *
     * @access public
     * @param null
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

            if (Mage::helper('bingtranslate')->isSupportedLanguage($code) == false) {
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
