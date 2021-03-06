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
 * BingTranslate admin controller
 *
 * @category   BingTranslate
 * @package     Yireo_BingTranslate
 */
class Yireo_BingTranslate_BingtranslateController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var string
     */
    protected $translatorText = '';

    /**
     * @var Yireo_BingTranslate_Model_Translator
     */
    protected $translator;

    /**
     * @var Yireo_BingTranslate_Helper_Data
     */
    protected $helper;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->helper = Mage::helper('bingtranslate');
        $this->translator = Mage::getSingleton('bingtranslate/translator');

        parent::_construct();
    }

    /**
     * Common method
     *
     * @return Yireo_BingTranslate_BingtranslateController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/tools/bingtranslate')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('System'), Mage::helper('adminhtml')->__('System'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Tools'), Mage::helper('adminhtml')->__('Tools'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Bing Translate'), Mage::helper('adminhtml')->__('Bing Translate'));
        return $this;
    }

    /**
     * Batch page
     */
    public function batchAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('bingtranslate/adminhtml_batch'))
            ->renderLayout();
    }

    /**
     * Translate a specific product
     */
    public function translateProductAction()
    {
        $data = explode('|', $this->getRequest()->getParam('data'));
        $productId = $data[0];
        $storeId = $data[1];
        $attributeCode = $data[2];

        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId() > 0) {
            return $this->sendError($this->__('No product loaded for ID ' . $productId));
        }

        $store = Mage::getModel('core/store')->load($storeId);
        if (!$store->getId() > 0) {
            return $this->sendError($this->__('No store loaded for ID ' . $storeId));
        }

        $translator = Mage::getModel('bingtranslate/product');
        $translator->translate($product, array($attributeCode), array($store));
        $charCount = $translator->getCharCount();

        return $this->sendMessage($this->__('Translated attribute "%s" for SKU "%s" in Store View "%s" (%s characters)', $attributeCode, $product->getSku(), $store->getCode(), $charCount));
    }

    /**
     * AJAX callback for regular text
     *
     * @return mixed
     */
    public function textAction()
    {
        $string = $this->getRequest()->getParam('string');
        $fromLang = $this->getRequest()->getParam('from');
        $toLang = $this->getRequest()->getParam('to');

        // Sanity checks
        if (empty($string)) {
            return $this->sendError($this->__('No text value given'));
        }

        if (empty($fromLang)) {
            return $this->sendError($this->__('No value for parameter from'));
        }

        if (empty($toLang)) {
            return $this->sendError($this->__('No value for parameter to'));
        }

        // Set the source language to empty, if it is the same as the destination language
        if ($fromLang === $toLang) {
            $fromLang = null;
        }

        // Fetch the API-settings
        $clientKey = $this->helper->getClientKey();

        // Check for the API-key or client-ID plus client-secret
        if ($this->helper->hasApiSettings() === false) {
            return $this->sendError($this->__('No API-details configured yet'));
        }

        // Set these variables for use with the translator
        $this->translator->setData('text', $string);
        $this->translator->setData('fromLang', $fromLang);
        $this->translator->setData('toLang', $toLang);
        $this->translator->setData('clientKey', $clientKey);

        // Make the request to the API
        $this->translate();

        if ($this->translator->hasApiError()) {
            return $this->sendError($this->translator->getApiError());
        }

        return $this->sendTranslation($this->translatorText);
    }

    /**
     * AJAX callback for products
     *
     * @return mixed
     */
    public function productAction()
    {
        // Load the initial data, and don't continue if this fails
        if ($this->preload() === false) {
            return null;
        }

        // Load the correct data-model
        $id = $this->translator->getData('id');
        $store = $this->translator->getData('store');

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->setStoreId($store)->load($id);
        if (!$product->getId() > 0) {
            return $this->sendError($this->__('No product ID given'));
        }

        // Load the attribute-value
        $attribute = $this->translator->getData('attribute');
        $this->translatorText = $product->getData($attribute);

        if (empty($this->translatorText)) {
            return $this->sendError($this->__('No product-data found for attribute %s', $attribute));
        }

        if ($attribute === 'url_key') {
            $this->translatorText = str_replace('-', ' ', $this->translatorText);
        }

        $this->translator->setData('text', $this->translatorText);

        // Make the request to the API
        $this->translate();

        if ($attribute === 'url_key') {
            $this->translatorText = str_replace(' ', '-', strtolower($this->translatorText));
        }

        if ($this->translator->hasApiError()) {
            return $this->sendError($this->translator->getApiError());
        }

        return $this->sendTranslation($this->translatorText);
    }

    /**
     * AJAX callback for categories
     *
     * @return mixed
     */
    public function categoryAction()
    {
        // Load the initial data, and don't continue if this fails
        if ($this->preload() === false) {
            return null;
        }

        // Load the correct data-model
        $id = $this->translator->getData('id');
        $store = $this->translator->getData('store');

        /** @var Mage_Catalog_Model_Category $category */
        $category = Mage::getModel('catalog/category')->setStoreId($store)->load($id);
        if (!$category->getId() > 0) {
            return $this->sendError($this->__('No category ID given'));
        }

        // Load the attribute-value
        $attribute = $this->translator->getData('attribute');
        $text = $category->getData($attribute);
        if (empty($text)) {
            return $this->sendError($this->__('No category-data found for attribute %s', $attribute));
        }

        $this->translator->setData('text', $text);

        // Make the request to the API
        $this->translate();

        if ($this->translator->hasApiError()) {
            return $this->sendError($this->translator->getApiError());
        }

        return $this->sendTranslation($this->translatorText);
    }

    /**
     * AJAX callback for CMS-pages
     *
     * @return mixed
     */
    public function pageAction()
    {
        // Load the initial data, and don't continue if this fails
        if ($this->preload() === false) {
            return null;
        }

        // Load the correct data-model
        $id = $this->translator->getData('id');
        $store = $this->translator->getData('store');

        $page = Mage::getModel('cms/page')->setStoreId($store)->load($id);
        if (!$page->getId() > 0) {
            return $this->sendError($this->__('No CMS-page ID given'));
        }

        // Load the attribute-value
        $attribute = $this->translator->getData('attribute');
        $text = $page->getData($attribute);
        if (empty($text)) {
            return $this->sendError($this->__('No page-data found for attribute %s', $attribute));
        }

        $this->translator->setData('text', $text);

        // Make the request to the API
        $this->translate();

        if ($this->translator->hasApiError()) {
            return $this->sendError($this->translator->getApiError());
        }

        return $this->sendTranslation($this->translatorText);
    }

    /**
     * AJAX callback for CMS-blocks
     *
     * @return mixed
     */
    public function blockAction()
    {
        // Load the initial data, and don't continue if this fails
        if ($this->preload() === false) {
            return null;
        }

        // Load the correct data-model
        $id = $this->translator->getData('id');
        $store = $this->translator->getData('store');
        $block = Mage::getModel('cms/block')->setStoreId($store)->load($id);
        if (!$block->getId() > 0) {
            return $this->sendError($this->__('No CMS-block ID given'));
        }

        // Load the attribute-value
        $attribute = $this->translator->getData('attribute');
        $text = $block->getData($attribute);
        if (empty($text)) {
            return $this->sendError($this->__('No block-data found for attribute %s', $attribute));
        }

        $this->translator->setData('text', $text);

        // Make the request to the API
        $this->translate();

        if ($this->translator->hasApiError()) {
            return $this->sendError($this->translator->getApiError());
        }

        return $this->sendTranslation($this->translatorText);
    }

    /**
     * Perform some sanity checks
     *
     * @return string
     */
    protected function preload()
    {
        $id = $this->getRequest()->getParam('id');
        $attribute = $this->getRequest()->getParam('attribute');
        $fromLang = $this->getRequest()->getParam('from');
        $toLang = $this->getRequest()->getParam('to');
        $store = $this->getRequest()->getParam('store');

        // Sanity checks
        if (!$id > 0) {
            return $this->sendError($this->__('Wrong value for parameter id: %s', $id));
        }

        if (empty($attribute)) {
            return $this->sendError($this->__('No value for parameter attribute'));
        }

        if (empty($fromLang)) {
            return $this->sendError($this->__('No value for parameter from'));
        }

        if (empty($toLang)) {
            return $this->sendError($this->__('No value for parameter to'));
        }

        // Set the source language to empty, if it is the same as the destination language
        if ($fromLang === $toLang) {
            $fromLang = null;
        }

        // Fetch the API-settings
        $clientKey = $this->helper->getClientKey();

        // Check for the API-key or client-ID plus client-secret
        if ($this->helper->hasApiSettings() === false) {
            return $this->sendError($this->__('No API-details configured yet'));
        }

        // Set these variables for use with the translator
        $this->translator->setData('id', $id);
        $this->translator->setData('attribute', $attribute);
        $this->translator->setData('fromLang', $fromLang);
        $this->translator->setData('toLang', $toLang);
        $this->translator->setData('store', $store);
        $this->translator->setData('clientKey', $clientKey);

        return true;
    }

    /**
     * Method to call upon the API
     *
     * @return string
     */
    protected function translate()
    {
        try {
            $this->translatorText = $this->translator->translate();
        } catch(Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    /**
     * Helper method to send a success
     *
     * @param string $message
     *
     * @return boolean
     */
    protected function sendMessage($message = null)
    {
        $result = array('message' => $message);
        $this->sendJsonResponse($result);

        return true;
    }

    /**
     * Helper method to send a specific error
     *
     * @param string $message
     *
     * @return mixed
     * @throws Exception
     */
    protected function sendError($message = null)
    {
        $result = array('error' => $message);
        $this->sendJsonResponse($result);

        return false;
    }

    /**
     * Helper method to send the translation
     *
     * @param string $translation
     *
     * @return mixed
     */
    protected function sendTranslation($translation = null)
    {
        $result = array('translation' => $translation);
        $this->sendJsonResponse($result);

        return null;
    }


    /**
     * Helper method to send the JSON headers
     *
     * @params array $data
     */
    protected function sendJsonResponse($data)
    {
        $jsonData = Mage::helper('core')->jsonEncode($data);

        $this->getResponse()->setHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setBody($jsonData);
        $this->getResponse()->sendResponse();
        exit;
    }

    /**
     * Allow ACL access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/bingtranslate');
    }
}
