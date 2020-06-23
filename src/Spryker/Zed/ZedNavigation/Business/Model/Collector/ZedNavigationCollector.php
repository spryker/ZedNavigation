<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ZedNavigation\Business\Model\Collector;

use ErrorException;
use Exception;
use Spryker\Zed\ZedNavigation\Business\Model\SchemaFinder\ZedNavigationSchemaFinderInterface;
use Spryker\Zed\ZedNavigation\Business\Resolver\MergeNavigationStrategyResolverInterface;
use Spryker\Zed\ZedNavigation\ZedNavigationConfig;
use Zend\Config\Config;
use Zend\Config\Factory;

class ZedNavigationCollector implements ZedNavigationCollectorInterface
{
    /**
     * @var \Spryker\Zed\ZedNavigation\Business\Model\SchemaFinder\ZedNavigationSchemaFinderInterface
     */
    private $navigationSchemaFinder;

    /**
     * @var \Spryker\Zed\ZedNavigation\Business\Resolver\MergeNavigationStrategyResolverInterface
     */
    protected $mergeNavigationStrategyResolver;

    /**
     * @var \Spryker\Zed\ZedNavigation\ZedNavigationConfig
     */
    protected $zedNavigationConfig;

    /**
     * @param \Spryker\Zed\ZedNavigation\Business\Model\SchemaFinder\ZedNavigationSchemaFinderInterface $navigationSchemaFinder
     * @param \Spryker\Zed\ZedNavigation\Business\Resolver\MergeNavigationStrategyResolverInterface $mergeNavigationStrategyResolver
     * @param \Spryker\Zed\ZedNavigation\ZedNavigationConfig $zedNavigationConfig
     */
    public function __construct(
        ZedNavigationSchemaFinderInterface $navigationSchemaFinder,
        MergeNavigationStrategyResolverInterface $mergeNavigationStrategyResolver,
        ZedNavigationConfig $zedNavigationConfig
    ) {
        $this->navigationSchemaFinder = $navigationSchemaFinder;
        $this->mergeNavigationStrategyResolver = $mergeNavigationStrategyResolver;
        $this->zedNavigationConfig = $zedNavigationConfig;
    }

    /**
     * @throws \ErrorException
     *
     * @return array
     */
    public function getNavigation()
    {
        try {
            /** @var \Zend\Config\Config $navigationDefinition */
            $navigationDefinition = Factory::fromFile($this->zedNavigationConfig->getRootNavigationSchema(), true);
            $rootDefinition = clone $navigationDefinition;
        } catch (Exception $e) {
            $navigationDefinition = new Config([]);
            $rootDefinition = new Config([]);
        }

        $coreNavigationDefinition = new Config([]);
        foreach ($this->navigationSchemaFinder->getSchemaFiles() as $moduleNavigationFile) {
            if (!file_exists($moduleNavigationFile->getPathname())) {
                throw new ErrorException('Navigation-File does not exist: ' . $moduleNavigationFile);
            }
            /** @var \Zend\Config\Config $configFromFile */
            $configFromFile = Factory::fromFile($moduleNavigationFile->getPathname(), true);
            $navigationDefinition->merge($configFromFile);
            $coreNavigationDefinition->merge($configFromFile);
        }

        $navigationMergeStrategy = $this->mergeNavigationStrategyResolver->resolve($this->zedNavigationConfig->getMergeStrategy());

        return $navigationMergeStrategy->mergeNavigation(
            $navigationDefinition,
            $rootDefinition,
            $coreNavigationDefinition
        );
    }
}
