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
 * BingTranslate Script-block
 */
class Yireo_BingTranslate_Block_Script extends Mage_Core_Block_Template
{
    /**
     * Return the customization ID
     *
     * @access public
     * @param null
     * @return string
     */
    public function getCustomizationId()
    {
        return Mage::helper('bingtranslate')->getCustomizationId();
    }

    /**
     * Allow translation
     *
     * @access public
     * @param null
     * @return bool
     */
    public function allowTranslation()
    {
        return true; // @todo: Disable on specific pages?
    }
}
