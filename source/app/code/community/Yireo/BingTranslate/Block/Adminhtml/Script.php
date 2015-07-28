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
class Yireo_BingTranslate_Block_Adminhtml_Script extends Mage_Core_Block_Template
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
     * Return a specific URL
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = array())
    {
        return Mage::getModel('adminhtml/url')->getUrl($route, $params);
    }

    /**
     * Get the AJAX base URL for translating entities
     *
     * @return string
     */
    public function getAjaxEntityBaseUrl()
    {
        return $this->getUrl('bingtranslate/index/' . $this->getPageType());
    }

    /**
     * Get the AJAX base URL for translating strings
     *
     * @return string
     */
    public function getAjaxTextBaseUrl()
    {
        return $this->getUrl('bingtranslate/index/text');
    }
}