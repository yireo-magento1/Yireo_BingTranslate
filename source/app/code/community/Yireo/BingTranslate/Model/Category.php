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
 * BingTranslate Category model
 */
class Yireo_BingTranslate_Model_Category extends Yireo_BingTranslate_Model_Entity
{
    /**
     * @var Mage_Catalog_Model_Category
     */
    protected $entity;

    /**
     * @param string $attribute
     * @param string $store
     *
     * @return string
     */
    protected function getStoreValue($attribute, $store)
    {
        $currentValue = Mage::getResourceModel('catalog/category')->getAttributeRawValue($this->entity->getId(), $attribute, $store);
        return trim($currentValue);
    }

    /**
     * @return string
     */
    protected function getEntityLabel()
    {
        return $this->entity->getId();
    }

    /**
     * @return string
     */
    protected function getEntityType()
    {
        return 'category';
    }
}
