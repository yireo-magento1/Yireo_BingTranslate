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
 * BingTranslate Batch-block
 */
class Yireo_BingTranslate_Block_Adminhtml_Batch extends Mage_Core_Block_Template
{
    /**
     * Listing of items
     *
     * @var array
     */
    protected $_items;

    /**
     * Listing of numerical IDs for items
     *
     * @var array
     */
    protected $_itemIds;

    /**
     * Listing of Store Views
     *
     * @var array
     */
    protected $_storeViews;

    /**
     * Listing of attributes
     *
     * @var array
     */
    protected $_attributes;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setData('area', 'adminhtml');
        $this->setTemplate('bingtranslate/batch.phtml');
    }

    /**
     * Return the batch type currently set in the URL
     *
     * @return mixed
     * @throws Exception
     */
    public function getBatchType()
    {
        return $this->getRequest()->getParam('type');
    }

    /**
     * Return the currently selected items
     *
     * @return array
     */
    public function getItemIds()
    {
        if (empty($this->_itemIds)) {
            $type = $this->getRequest()->getParam('type');
            $key = $this->getRequest()->getParam('massaction_prepare_key');
            $this->_itemIds = $this->getRequest()->getParam($key);
        }

        return $this->_itemIds;
    }

    /**
     * Return the number of items
     *
     * @return mixed
     */
    public function getItemCount()
    {
        static $itemCount = null;

        if (!is_numeric($itemCount)) {
            $itemCount = $this->getItems()->getSize();
        }

        return $itemCount;
    }

    /**
     * Return a listing of the selected items
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getItems()
    {
        if (empty($this->_items)) {

            $itemIds = $this->getItemIds();

            $type = $this->getRequest()->getParam('type');
            if ($type == 'product') {
                $this->_items = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect(array('name', 'sku'));

                if (!empty($itemIds)) {
                    $this->_items->addAttributeToFilter('entity_id', array('IN' => $itemIds));
                }
            }
        }

        return $this->_items;
    }

    /**
     * Return a listing of the selected Store Views
     *
     * @return Mage_Core_Model_Resource_Store_Collection
     */
    public function getStoreViews()
    {
        if (empty($this->_storeViews)) {

            $this->_storeViews = Mage::getModel('core/store')->getCollection();

            $batchFilter = Mage::getStoreConfig('catalog/bingtranslate/batch_stores');
            $batchFilter = explode(',', $batchFilter);

            if (!empty($batchFilter)) {
                $this->_storeViews->addFieldToFilter('store_id', array('IN' => $batchFilter));
            }

            foreach ($this->_storeViews as $store) {
                $locale = Mage::getStoreConfig('general/locale/code', $store);
                $locale = preg_replace('/_(.*)/', '', $locale);
                $store->setLocale($locale);
            }
        }

        return $this->_storeViews;
    }

    /**
     * Return a listing of the selected attributes
     *
     * @return mixed
     */
    public function getAttributes()
    {
        if (empty($this->_attributes)) {

            $this->_attributes = Mage::getModel('bingtranslate/system_config_source_attribute')->getCollection();

            $batchFilter = Mage::getStoreConfig('catalog/bingtranslate/batch_attributes');
            $batchFilter = explode(',', $batchFilter);

            if (!empty($batchFilter)) {
                $this->_attributes->addFieldToFilter('attribute_code', array('IN' => $batchFilter));
            }
        }

        return $this->_attributes;
    }

    /**
     * Return a merged array on all the selected entities (items, store views and attributes)
     *
     * @return array
     */
    public function getItemData()
    {
        $items = $this->getItems();
        $storeViews = $this->getStoreViews();
        $attributes = $this->getAttributes();

        $data = array();
        foreach ($items as $item) {
            foreach ($storeViews as $storeView) {
                foreach ($attributes as $attribute) {
                    $data[] = $item->getId() . '|' . $storeView->getId() . '|' . $attribute->getAttributeCode();
                }
            }
        }

        return $data;
    }
}
