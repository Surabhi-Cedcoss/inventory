<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tax\Test\Unit\Model\Calculation;

use \Magento\Tax\Model\Calculation\UnitBaseCalculator;

class UnitBaseCalculatorTest extends \PHPUnit_Framework_TestCase
{
    const STORE_ID = 2300;
    const QUANTITY = 1;
    const UNIT_PRICE = 500;
    const RATE = 10;
    const STORE_RATE = 11;

    const CODE = 'CODE';
    const TYPE = 'TYPE';
    const ROW_TAX = 44.954135954136;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $taxDetailsItemDataObjectFactoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockCalculationTool;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $appliedTaxRateDataObjectFactoryMock;

    /** @var UnitBaseCalculator */
    protected $model;

    protected $addressRateRequest;

    /**
     * @var \Magento\Tax\Api\Data\TaxDetailsItemInterface
     */
    protected $taxDetailsItem;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxRateInterface
     */
    protected $appliedTaxRate;

    public function setUp()
    {
        /** @var \Magento\Framework\Test\Unit\TestFramework\Helper\ObjectManager  $objectManager */
        $objectManager = new \Magento\Framework\Test\Unit\TestFramework\Helper\ObjectManager($this);
        $this->taxDetailsItem = $objectManager->getObject('Magento\Tax\Model\TaxDetails\ItemDetails');
        $this->taxDetailsItemDataObjectFactoryMock =
            $this->getMockBuilder('Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxDetailsItemDataObjectFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->taxDetailsItem);

        $this->mockCalculationTool = $this->getMockBuilder('\Magento\Tax\Model\Calculation')
            ->disableOriginalConstructor()
            ->setMethods(['__wakeup', 'round', 'getRate', 'getStoreRate', 'getRateRequest', 'getAppliedRates'])
            ->getMock();
        $this->mockCalculationTool->expects($this->any())
            ->method('round')
            ->withAnyParameters()
            ->will($this->returnArgument(0));
        $this->mockConfig = $this->getMockBuilder('\Magento\Tax\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressRateRequest = new \Magento\Framework\Object();

        $this->appliedTaxRate = $objectManager->getObject('Magento\Tax\Model\TaxDetails\AppliedTaxRate');
        $this->appliedTaxRateDataObjectFactoryMock = $this->getMock(
            'Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->appliedTaxRateDataObjectFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->appliedTaxRate);

        $appliedTaxDataObject = $objectManager->getObject('Magento\Tax\Model\TaxDetails\AppliedTax');
        $appliedTaxDataObjectFactoryMock = $this->getMock(
            'Magento\Tax\Api\Data\AppliedTaxInterfaceFactory',
            ['create'],
            [],
            '',
            false
        );
        $appliedTaxDataObjectFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($appliedTaxDataObject);

        $arguments = [
            'taxDetailsItemDataObjectFactory' => $this->taxDetailsItemDataObjectFactoryMock,
            'calculationTool'       => $this->mockCalculationTool,
            'config'                => $this->mockConfig,
            'storeId'               => self::STORE_ID,
            'addressRateRequest'    => $this->addressRateRequest,
            'appliedRateDataObjectFactory'    => $this->appliedTaxRateDataObjectFactoryMock,
            'appliedTaxDataObjectFactory'    => $appliedTaxDataObjectFactoryMock,
        ];
        $this->model = $objectManager->getObject('Magento\Tax\Model\Calculation\UnitBaseCalculator', $arguments);
    }

    public function testCalculateWithTaxInPrice()
    {
        $mockItem = $this->getMockItem();
        $mockItem->expects($this->once())
            ->method('getTaxIncluded')
            ->will($this->returnValue(true));

        $this->mockConfig->expects($this->once())
            ->method('crossBorderTradeEnabled')
            ->will($this->returnValue(false));
        $this->mockConfig->expects($this->once())
            ->method('applyTaxAfterDiscount')
            ->will($this->returnValue(true));

        $this->mockCalculationTool->expects($this->once())
            ->method('getRate')
            ->with($this->addressRateRequest)
            ->will($this->returnValue(self::RATE));
        $this->mockCalculationTool->expects($this->once())
            ->method('getStoreRate')
            ->with($this->addressRateRequest, self::STORE_ID)
            ->will($this->returnValue(self::STORE_RATE));
        $this->mockCalculationTool->expects($this->once())
            ->method('getAppliedRates')
            ->withAnyParameters()
            ->will($this->returnValue([]));

        $this->assertSame($this->taxDetailsItem, $this->model->calculate($mockItem, self::QUANTITY));
        $this->assertSame(self::CODE, $this->taxDetailsItem->getCode());
        $this->assertSame(self::TYPE, $this->taxDetailsItem->getType());
        $this->assertSame(self::ROW_TAX, $this->taxDetailsItem->getRowTax());
    }

    public function testCalculateWithTaxNotInPrice()
    {
        $mockItem = $this->getMockItem();
        $mockItem->expects($this->once())
            ->method('getTaxIncluded')
            ->will($this->returnValue(false));

        $this->mockConfig->expects($this->once())
            ->method('applyTaxAfterDiscount')
            ->will($this->returnValue(true));

        $this->mockCalculationTool->expects($this->once())
            ->method('getRate')
            ->with($this->addressRateRequest)
            ->will($this->returnValue(self::RATE));
        $this->mockCalculationTool->expects($this->once())
            ->method('getAppliedRates')
            ->withAnyParameters()
            ->will($this->returnValue([['id' => 0, 'percent' => 0, 'rates' => []]]));

        $this->assertSame($this->taxDetailsItem, $this->model->calculate($mockItem, self::QUANTITY));
        $this->assertEquals(self::CODE, $this->taxDetailsItem->getCode());
        $this->assertEquals(self::TYPE, $this->taxDetailsItem->getType());
        $this->assertEquals(0.0, $this->taxDetailsItem->getRowTax());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockItem()
    {
        /** @var $mockItem \PHPUnit_Framework_MockObject_MockObject */
        $mockItem = $this->getMockBuilder('Magento\Tax\Api\Data\QuoteDetailsItemInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $mockItem->expects($this->once())
            ->method('getDiscountAmount')
            ->will($this->returnValue(1));
        $mockItem->expects($this->once())
            ->method('getCode')
            ->will($this->returnValue(self::CODE));
        $mockItem->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(self::TYPE));
        $mockItem->expects($this->once())
            ->method('getUnitPrice')
            ->will($this->returnValue(self::UNIT_PRICE));

        return $mockItem;
    }
}
