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
 * BingTranslate Script-block
 */
class Yireo_BingTranslate_Block_Script extends Mage_Core_Block_Template
{
    /**
     * Return the customization ID
     *
     * @return string
     */
    public function getCustomizationId()
    {
        return Mage::helper('bingtranslate')->getCustomizationId();
    }

    /**
     * Allow translation
     *
     * @return bool
     */
    public function allowTranslation()
    {
        return true; // @todo: Disable on specific pages?
    }
}
