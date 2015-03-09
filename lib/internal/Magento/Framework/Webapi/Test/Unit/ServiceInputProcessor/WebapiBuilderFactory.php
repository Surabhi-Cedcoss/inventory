<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Webapi\Test\Unit\ServiceInputProcessor;

class WebapiBuilderFactory extends \Magento\Framework\Serialization\DataBuilderFactory
{
    /**
     * @param \Magento\Framework\Test\Unit\TestFramework\Helper\ObjectManager  $objectManager
     */
    public function __construct(\Magento\Framework\Test\Unit\TestFramework\Helper\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Creates builder object
     *
     * @param $builderClassName
     * @return \Magento\Framework\Api\BuilderInterface Builder Instance
     */
    protected function createObject($builderClassName)
    {
        return $this->objectManager->getObject($builderClassName);
    }
}
