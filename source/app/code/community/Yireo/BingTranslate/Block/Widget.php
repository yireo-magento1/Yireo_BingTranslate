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
class Yireo_BingTranslate_Block_Widget extends Mage_Core_Block_Template
{
    /*
     * Return the inline layout
     * 
     * @access public
     * @param null
     * @return string (SIMPLE|HORIZONTAL|null
     */
    public function getInlineLayout()
    {
        return null; // @todo
    }

    /*
     * Return the current page language
     * 
     * @access public
     * @param null
     * @return string (en)
     */
    public function getPageLanguage()
    {
        return null; // @todo
    }

    /*
     * Return the included languages
     * 
     * @access public
     * @param null
     * @return array
     */
    public function getIncludedLanguages()
    {
        return array(); // @todo
    }

    /*
     * Return whether this is a multiple language page
     * 
     * @access public
     * @param null
     * @return bool
     */
    public function isMultilanguagePage()
    {
        return false; // @todo
    }

    /*
     * Return the GA ID
     * 
     * @access public
     * @param null
     * @return string
     */
    public function getGaId()
    {
        return null; // @todo: Fetch from Mage_BingAnalytics module
    }

    /*
     * Remove the 
     * 
     * @access public
     * @param null
     * @return bool
     */
    public function removeAttribution()
    {
        return false;
    }

}