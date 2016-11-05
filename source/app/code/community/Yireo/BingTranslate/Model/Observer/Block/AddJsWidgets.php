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
 * BingTranslate observer
 */
class Yireo_BingTranslate_Model_Observer_Block_AddJsWidgets
{
    /**
     * @var Yireo_BingTranslate_Helper_Data
     */
    protected $helper;

    /**
     * @var Mage_Core_Controller_Request_Http
     */
    protected $request;

    /**
     * @var Yireo_BingTranslate_Helper_Observer
     */
    protected $observerHelper;

    /**
     * @var Mage_Core_Helper_Url
     */
    protected $urlHelper;

    /**
     * @var Mage_Core_Model_Layout
     */
    protected $layout;

    /**
     * @var Mage_Catalog_Model_Category
     */
    protected $category;

    /**
     * Yireo_BingTranslate_Model_Observer_Block_AddJsWidgets constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('bingtranslate');
        $this->request = Mage::app()->getRequest();
        $this->observerHelper = Mage::helper('bingtranslate/observer');
        $this->urlHelper = Mage::helper('core/url');
        $this->layout = Mage::app()->getFrontController()->getAction()->getLayout();
        $this->category = Mage::getModel('catalog/category');
    }

    /**
     * Listen to the event core_block_abstract_to_html_before
     *
     * @parameter Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function execute(Varien_Event_Observer $observer)
    {
        // Check if this event can continue
        if ($this->allowEvent($observer) == false) {
            return $this;
        }

        // Determine the data-type
        $dataType = $this->getDataType();

        // If this data-type is unknown, do not display anything
        if ($dataType === 'unknown') {
            return $this;
        }

        // If this is a Root Catalog, do not display anything
        if ($dataType === 'category' && $this->isRootCatalogPage()) {
            return $this;
        }

        // Get the variables
        $block = $observer->getEvent()->getBlock();

        /** @var Varien_Data_Form_Element_Abstract $element */
        $element = $block->getElement();

        // Append all constructed HTML-code to the existing HTML-code
        if ($this->shouldAddButtonToElement($element)) {
            $html = $element->getData('after_element_html');
            $html .= $this->getButtonHtml($element);
            $element->setData('after_element_html', $html);
        }

        // Construct the JavaScript
        $this->addJsBlock($element);

        return $this;
    }

    /**
     * @return bool
     */
    protected function isRootCatalogPage()
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = $this->category->load($this->getDataId());

        if ($category->getParentId() === 1) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function getDataId()
    {
        // Fetch the data ID (either category ID or product ID) from the URL
        $dataId = $this->request->getParam('id');
        if (empty($dataId)) {
            $dataId = $this->request->getParam('page_id');
        }

        if (empty($dataId)) {
            $dataId = $this->request->getParam('block_id');
        }

        return $dataId;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function getButtonHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Fetch the data ID (either category ID or product ID) from the URL
        $dataId = $this->getDataId();

        // Fetch the languages from the configuration
        $fromLanguage = $this->helper->getFromLanguage();
        $toLanguage = $this->helper->getToLanguage();

        // Construct the button-label
        $buttonLabel = $this->helper->getButtonLabel();

        // Determine whether this field is disabled or not
        $disabled = false;
        if ($fromLanguage == $toLanguage) {
            $disabled = true;
        }

        $storeId = $this->request->getParam('store');
        $htmlId = $element->getHtmlId();
        $attributeCode = $element->getData('name');

        $buttonArgs = array($dataId, $attributeCode, $htmlId, $storeId, $fromLanguage, $toLanguage);
        $buttonHtml = $this->observerHelper->button($attributeCode, $buttonLabel, $disabled, $buttonArgs);

        return $buttonHtml;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     */
    protected function addJsBlock(Varien_Data_Form_Element_Abstract $element)
    {
        $htmlId = $element->getHtmlId();
        $attributeCode = $element->getData('name');

        // Construct the JavaScript
        $jsHtml = $this->observerHelper->script($attributeCode, $htmlId);

        // Insert the JavaScript in the bottom of the page
        $jsBlock = $this->layout->createBlock('core/text');
        $jsBlock->setText($jsHtml);
        $this->layout->getBlock('before_body_end')->insert($jsBlock);
    }

    /**
     * Method to check whether a certain event is allowed
     *
     * @param $observer Varien_Event_Observer
     *
     * @return bool
     */
    protected function allowEvent(Varien_Event_Observer $observer)
    {
        // If the configuration is told to disable this module, quit now
        if ($this->helper->enabled() == false) {
            return false;
        }

        // Get the parameters from the event
        $block = $observer->getEvent()->getBlock();
        if (empty($block) || !is_object($block)) {
            return false;
        }

        if ($this->isAllowedBlockClass($block) === false && $this->isAllowedBlockType($block) === false) {
            return false;
        }

        $element = $block->getElement();
        if (!$element instanceof Varien_Data_Form_Element_Abstract) {
            return false;
        }

        $isAllowedElement = $this->isAllowedElement($element);

        // Check if the form-element is text-input based
        if ($isAllowedElement === false && $this->isWysiwygElement($element) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return bool
     */
    protected function shouldAddButtonToElement(Varien_Data_Form_Element_Abstract $element)
    {
        $forbiddenElements = array('attribute_code');

        if (in_array($element->getId(), $forbiddenElements)) {
            return false;
        }

        if (strstr($element->getClass(), 'validate-digits')) {
            return false;
        }

        return true;
    }

    /**
     * @param $element Varien_Data_Form_Element_Abstract
     *
     * @return bool
     */
    protected function isWysiwygElement(Varien_Data_Form_Element_Abstract $element)
    {
        if (stristr(get_class($element), 'wysiwyg') === false) {
            return false;
        }

        return true;
    }

    /**
     * @param $element Varien_Data_Form_Element_Abstract
     *
     * @return bool
     */
    protected function isAllowedElement(Varien_Data_Form_Element_Abstract $element)
    {
        $allowedElements = array(
            'Varien_Data_Form_Element_Text',
            'Varien_Data_Form_Element_Editor',
        );

        foreach ($allowedElements as $allowedElement) {
            if ($element instanceof $allowedElement) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $block Mage_Core_Block_Abstract
     *
     * @return bool
     */
    protected function isAllowedBlockType(Mage_Core_Block_Abstract $block)
    {
        // Check whether this block-object is of the right instance
        $allowedTypes = array(
            'adminhtml/catalog_form_renderer_fieldset_element',
            'adminhtml/widget_form_renderer_fieldset_element',
        );

        if (in_array($block->getType(), $allowedTypes) == false) {
            return true;
        }

        return false;
    }

    /**
     * @param $block Mage_Core_Block_Abstract
     *
     * @return bool
     */
    protected function isAllowedBlockClass(Mage_Core_Block_Abstract $block)
    {
        $allowedClasses = array(
            'Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element',
            'Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset_Element',
        );

        foreach ($allowedClasses as $allowedClass) {
            if ($block instanceof $allowedClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method to return the data types for specific URLs
     *
     * @return string
     * @throws RuntimeException
     */
    protected function getDataType()
    {
        static $dataType = null;

        if (!empty($dataType)) {
            return $dataType;
        }

        $currentUrl = $this->urlHelper->getCurrentUrl();

        if (stristr($currentUrl, 'cms_block/edit')) {
            $dataType = 'block';
        } elseif (stristr($currentUrl, 'cms_page/edit')) {
            $dataType = 'page';
        } elseif (stristr($currentUrl, 'catalog_category/edit')) {
            $dataType = 'category';
        } elseif (stristr($currentUrl, 'catalog_product/edit')) {
            $dataType = 'product';
        } elseif (stristr($currentUrl, 'catalog_product_attribute/edit')) {
            $dataType = 'attribute';
        } else {

            /*if (Mage::getIsDeveloperMode()) {
                throw new RuntimeException('Unknown URL ' . $currentUrl);
            }*/

            $dataType = 'unknown';
        }

        return $dataType;
    }
}
