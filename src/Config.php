<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages;


use EnjoysCMS\Core\Modules\AbstractModuleConfig;

final class Config extends AbstractModuleConfig
{

    public function getModulePackageName(): string
    {
        return 'enjoyscms/pages';
    }

    public function getCrudContentEditor(): null|string|array
    {
        return $this->get('editor->crud');
    }

    public function getScriptsContentEditor(): null|string|array
    {
        return $this->get('editor->scripts');
    }


}
