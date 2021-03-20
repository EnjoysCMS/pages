<?php

declare(strict_types=1);

namespace App\Module\Pages\Blocks;

use EnjoysCMS\Core\Components\Blocks\AbstractBlock;

class TopView extends AbstractBlock
{

    public function view()
    {
        return 'test';
    }

    public static function getMeta(): ?array
    {
        return [
            'name' => 'Топ просматриваемых страниц',
            'options' => [
                'limit' => [
                    'value' => '5',
                    'name' => 'Лимит записей',
                    'description' => null,
//                'form' => [
//                    'type' => '',
//                    'data' => [
//                        'true' => 'Да',
//                        'false' => 'Нет'
//                    ]
//                ]
                ]
            ]
        ];
    }
}
