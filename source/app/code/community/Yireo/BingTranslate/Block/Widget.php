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
class Yireo_BingTranslate_Block_Widget extends Mage_Core_Block_Template
{
    /**
     * Return the inline layout
     *
     * @return string (SIMPLE|HORIZONTAL|null
     */
    public function getInlineLayout()
    {
        return null; // @todo
    }

    /**
     * Return the current page language
     *
     * @return string (en)
     */
    public function getPageLanguage()
    {
        return null; // @todo
    }

    /**
     * Return the included languages
     *
     * @return array
     */
    public function getIncludedLanguages()
    {
        return array(); // @todo
    }

    /**
     * Return whether this is a multiple language page
     *
     * @return bool
     */
    public function isMultilanguagePage()
    {
        return false; // @todo
    }

    /**
     * Return the GA ID
     *
     * @return string
     */
    public function getGaId()
    {
        return null; // @todo: Fetch from Mage_BingAnalytics module
    }

    /**
     * Remove the 
     *
     * @return bool
     */
    public function removeAttribution()
    {
        return false;
    }

}