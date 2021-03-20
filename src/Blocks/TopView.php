<?php

declare(strict_types=1);

namespace App\Module\Pages\Blocks;

use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use Symfony\Component\Yaml\Yaml;

class TopView extends AbstractBlock
{

    public function view()
    {
        return 'test';
    }

    public static function getMeta(): ?array
    {
        return Yaml::parseFile(__DIR__.'/../../blocks.yml')[__CLASS__];
    }
}
