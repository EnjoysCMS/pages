<?php

declare(strict_types=1);


namespace App\Module\Pages;


use DI\FactoryInterface;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use Psr\Container\ContainerInterface;

final class Config
{

    public static function getConfig(ContainerInterface $container): ModuleConfig
    {
        return $container
            ->get(FactoryInterface::class)
            ->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/pages'])
            ;
    }
}