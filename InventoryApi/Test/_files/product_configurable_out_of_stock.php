<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Setup\CategorySetup;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

require __DIR__ . '/../../../../../../dev/tests/integration/testsuite/Magento/ConfigurableProduct/'
    . '_files/configurable_attribute.php';

/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()
    ->create(ProductRepositoryInterface::class);

/** @var $installer CategorySetup */
$installer = Bootstrap::getObjectManager()->create(CategorySetup::class);

/** @var Website $website */
$website = Bootstrap::getObjectManager()->create(Website::class);
$website->load('us_website', 'code');
$websiteIds = [$website->getId()];

/* Create simple products per each option value*/
/** @var AttributeOptionInterface[] $options */
$options = $attribute->getOptions();

$attributeValues = [];
$attributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');
$associatedProductIds = [];
$productIds = [30, 40];
array_shift($options); //remove the first option which is empty

foreach ($options as $option) {
    /** @var $product Product */
    $product = Bootstrap::getObjectManager()->create(Product::class);
    $productId = array_shift($productIds);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds($websiteIds)
        ->setName('Configurable Option' . $option->getLabel())
        ->setSku('simple_' . $productId)
        ->setPrice($productId)
        ->setTestConfigurable($option->getValue())
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 0, 'is_qty_decimal' => 0, 'is_in_stock' => 0]);

    $product = $productRepository->save($product);

    /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
    $stockItem = Bootstrap::getObjectManager()->create(\Magento\CatalogInventory\Model\Stock\Item::class);
    $stockItem->load($productId, 'product_id');

    if (!$stockItem->getProductId()) {
        $stockItem->setProductId($productId);
    }
    $stockItem->setUseConfigManageStock(1);
    $stockItem->setQty(0);
    $stockItem->setIsQtyDecimal(0);
    $stockItem->setIsInStock(0);
    $stockItem->save();

    $attributeValues[] = [
        'label' => 'test',
        'attribute_id' => $attribute->getId(),
        'value_index' => $option->getValue(),
    ];
    $associatedProductIds[] = $product->getId();
}

/** @var $product Product */
$product = Bootstrap::getObjectManager()->create(Product::class);

/** @var Factory $optionsFactory */
$optionsFactory = Bootstrap::getObjectManager()->create(Factory::class);

$configurableAttributesData = [
    [
        'attribute_id' => $attribute->getId(),
        'code' => $attribute->getAttributeCode(),
        'label' => $attribute->getStoreLabel(),
        'position' => '0',
        'values' => $attributeValues,
    ],
];

$configurableOptions = $optionsFactory->create($configurableAttributesData);

$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);

$product->setExtensionAttributes($extensionConfigurableAttributes);

// Remove any previously created product with the same id.
/** @var \Magento\Framework\Registry $registry */
$registry = Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
try {
    $productToDelete = $productRepository->getById(2);
    $productRepository->delete($productToDelete);

    /** @var \Magento\Quote\Model\ResourceModel\Quote\Item $itemResource */
    $itemResource = Bootstrap::getObjectManager()->get(\Magento\Quote\Model\ResourceModel\Quote\Item::class);
    $itemResource->getConnection()->delete(
        $itemResource->getMainTable(),
        'product_id = ' . $productToDelete->getId()
    );
} catch (\Exception $e) {
    // Nothing to remove
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

$product->setTypeId(Configurable::TYPE_CODE)
    ->setId(2)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds($websiteIds)
    ->setName('Configurable Product Out of Stock')
    ->setSku('configurable_out_of_stock')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 0]);

$productRepository->save($product);

/** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);

$categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    [2]
);
