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
class Yireo_BingTranslate_Model_Observer_Grid_AddMassAction
{
    /**
     * @var Mage_Adminhtml_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected $adminHelper;

    /**
     * Yireo_BingTranslate_Model_Observer_Grid_AddMassAction constructor.
     */
    public function __construct()
    {
        $this->adminHelper = Mage::helper('adminhtml');
    }

    /**
     * Method fired on the event <core_block_abstract_prepare_layout_before>
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Yireo_BingTranslate_Model_Observer_Grid_AddMassaction
     */
    public function execute(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($this->isAllowedBlock($block) && $this->isAllowedController($block->getRequest()->getControllerName())) {
            $this->addMassactionItem($block);
        }

        return $this;
    }

    /**
     * @param $block Mage_Adminhtml_Block_Widget_Grid_MassAction
     */
    protected function addMassactionItem($block)
    {
        $block->addItem('bingtranslate', array(
            'label' => 'Translate via BingTranslate',
            'url' => $this->adminHelper->getUrl('adminhtml/bingtranslate/batch', array('type' => 'product')),
        ));
    }

    /**
     * @param $block
     *
     * @return bool
     */
    protected function isAllowedBlock($block)
    {
        $blockClass = 'Mage_Adminhtml_Block_Widget_Grid_Massaction';

        if ($block instanceof $blockClass) {
            return true;
        }

        return false;
    }

    /**
     * @param $controllerName
     *
     * @return bool
     */
    protected function isAllowedController($controllerName)
    {
        $allowedControllers = array(
            'catalog_product'
        );

        if (!in_array($controllerName, $allowedControllers)) {
            return false;
        }

        return true;
    }
}