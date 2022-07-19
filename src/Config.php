<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages;

use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;

final class Config
{
    private ModuleConfig $moduleConfig;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->moduleConfig = $factory->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/pages']);
    }


    public function getModuleConfig(): ModuleConfig
    {
        return $this->moduleConfig;
    }


}
