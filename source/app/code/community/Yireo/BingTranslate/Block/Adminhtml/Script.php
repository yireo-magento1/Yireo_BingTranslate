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
class Yireo_BingTranslate_Block_Adminhtml_Script extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Adminhtml_Model_Url
     */
    private $urlModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setData('area', 'adminhtml');
        $this->urlModel = Mage::getModel('adminhtml/url');
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
        return $this->urlModel->getUrl($route, $params);
    }

    /**
     * Get the AJAX base URL for translating entities
     *
     * @return string
     */
    public function getAjaxEntityBaseUrl()
    {
        return $this->getUrl('adminhtml/bingtranslate/' . $this->getPageType());
    }

    /**
     * Get the AJAX base URL for translating strings
     *
     * @return string
     */
    public function getAjaxTextBaseUrl()
    {
        return $this->getUrl('adminhtml/bingtranslate/text');
    }
}