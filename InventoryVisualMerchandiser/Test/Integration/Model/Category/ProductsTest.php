<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryVisualMerchandiser\Test\Integration\Model\Category;

use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\VisualMerchandiser\Model\Category\Products;
use PHPUnit\Framework\TestCase;

class ProductsTest extends TestCase
{
    /**
     * Test that products in category have correct quantity by source
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryVisualMerchandiser/Test/_files/product_with_category.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryVisualMerchandiser/Test/_files/source_items_products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     */
    public function testProductsInCategory(): void
    {
        $categoryId = 1234;

        /** @var StoreManagerInterface $storeManager */
        $storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        /** @var $product Product */
        $product = Bootstrap::getObjectManager()->create(Product::class);
        $product->setStoreId($storeManager->getStore('store_for_us_website')->getId());
        $product->load(10);
        /** @var Products $productsModel */
        $productsModel = Bootstrap::getObjectManager()->get(Products::class);
        $collection = $productsModel->getCollectionForGrid($categoryId, 'store_for_us_website');
        $productsStockData = [];
        foreach ($collection as $item) {
            $productsStockData[$item->getSku()] = $item->getData('stock');
        }

        self::assertEquals($product->getQty(), (int)$productsStockData['simple_10']);
    }
}
