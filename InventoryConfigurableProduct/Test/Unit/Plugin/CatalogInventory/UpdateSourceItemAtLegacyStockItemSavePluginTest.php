<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Unit\Plugin\CatalogInventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CatalogInventory\Model\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ResourceModel\GetProductTypeById;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\Framework\Model\AbstractModel as StockItem;
use Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\UpdateSourceItemAtLegacyStockItemSavePlugin;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;

/**
 * Unit test for
 * Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\UpdateSourceItemAtLegacyStockItemSavePlugin class.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateSourceItemAtLegacyStockItemSavePluginTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $getProductTypeByIdMock;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatusMock;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $getSkusByProductIdsMock;

    /**
     * @var Configurable
     */
    private $configurableTypeMock;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalableMock;

    /**
     * @var UpdateSourceItemAtLegacyStockItemSavePlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->getProductTypeByIdMock = $this->getMockBuilder(GetProductTypeById::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setDataToLegacyStockStatusMock = $this->getMockBuilder(SetDataToLegacyStockStatus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getSkusByProductIdsMock = $this->getMockForAbstractClass(GetSkusByProductIdsInterface::class);
        $this->configurableTypeMock = $this->getMockBuilder(Configurable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->areProductsSalableMock = $this->getMockForAbstractClass(AreProductsSalableInterface::class);
        $this->plugin = new UpdateSourceItemAtLegacyStockItemSavePlugin(
            $this->getProductTypeByIdMock,
            $this->setDataToLegacyStockStatusMock,
            $this->getSkusByProductIdsMock,
            $this->configurableTypeMock,
            $this->areProductsSalableMock
        );
    }

    public function testConfigurableStockAfterLegacySockItemSave()
    {
        $product = [
            'id' => 1,
            'type' => Configurable::TYPE_CODE,
            'sku' => 'conf_1',
            'qty' => 0
        ];
        $childIds = [2,3];
        $childSkus = ['sku2', 'sku3'];

        $itemResourceModelMock = $this->getMockBuilder(ItemResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock = $this->getMockBuilder(StockItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getQty',
                'getIsInStock',
                'getProductId',
                'getStockStatusChangedAuto'
            ])
            ->getMock();
        $stockItemMock->expects(self::once())->method('getQty')->willReturn($product['qty']);
        $stockItemMock->expects(self::once())->method('getIsInStock')->willReturn(Stock::STOCK_IN_STOCK);
        $stockItemMock->expects($this->exactly(4))->method('getProductId')->willReturn($product['id']);
        $stockItemMock->expects(self::once())
            ->method('getStockStatusChangedAuto')
            ->willReturn(true);
        $this->getProductTypeByIdMock->expects(self::once())
            ->method('execute')
            ->with($product['id'])
            ->willReturn($product['type']);
        $this->getSkusByProductIdsMock->expects($this->at(0))
            ->method('execute')
            ->with($childIds)
            ->willReturn($childSkus);
        $this->getSkusByProductIdsMock->expects($this->at(1))
            ->method('execute')
            ->willReturn([$product['id'] => $product['sku']]);
        $this->configurableTypeMock->expects($this->once())->method('getChildrenIds')->willReturn([$childIds]);
        $isProductSalableMock = $this->getMockForAbstractClass(IsProductSalableResultInterface::class);
        $isProductSalableMock->expects($this->once())->method('isSalable')->willReturn(true);
        $this->areProductsSalableMock->expects($this->once())
            ->method('execute')
            ->with($childSkus, Stock::DEFAULT_STOCK_ID)
            ->willReturn([$isProductSalableMock]);
        $this->setDataToLegacyStockStatusMock->expects(self::once())
            ->method('execute')
            ->with($product['sku'], (float) $product['qty'], Stock::STOCK_IN_STOCK);
        $this->plugin->afterSave($itemResourceModelMock, $itemResourceModelMock, $stockItemMock);
    }

    public function testConfigurableStockAfterLegacySockItemSaveNegativeScenario()
    {
        $product = [
            'id' => 1,
            'type' => Configurable::TYPE_CODE,
            'sku' => 'conf_1',
            'qty' => 0
        ];
        $itemResourceModelMock = $this->getMockBuilder(ItemResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock = $this->getMockBuilder(StockItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getQty',
                'getIsInStock',
                'getProductId',
                'getStockStatusChangedAuto'
            ])
            ->getMock();
        $stockItemMock->expects(self::once())->method('getIsInStock')->willReturn(true);
        $stockItemMock->expects(self::once())->method('getProductId')->willReturn($product['id']);
        $this->getProductTypeByIdMock->expects(self::once())
            ->method('execute')
            ->with($product['id'])
            ->willReturn($product['type']);
        $stockItemMock->expects(self::once())
            ->method('getStockStatusChangedAuto')
            ->willReturn(false);
        $stockItemMock->expects($this->never())->method('getQty');
        $this->plugin->afterSave($itemResourceModelMock, $itemResourceModelMock, $stockItemMock);
    }
}
