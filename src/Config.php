<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages;

use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;

final class Config
{
    private ModuleConfig $config;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->config = $factory->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/pages']);
    }


    public function getConfig(): ModuleConfig
    {
        return $this->config;
    }

    public function getCrudContentEditor(): ?string
    {
        return $this->config->get('editor')['crud'] ?? null;
    }


}
