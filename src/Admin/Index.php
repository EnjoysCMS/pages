<?php


namespace App\Module\Pages\Admin;


use App\Module\Pages\Entities\Page;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Components\Helpers\Setting;
use JetBrains\PhpStorm\ArrayShape;

final class Index
{

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $pagesRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->pagesRepository = $entityManager->getRepository(Page::class);
    }

    #[ArrayShape([
            'items' => "array|mixed[]|object[]",
            'title' => "string"
        ]
    )]
    public function getContext(): array
    {
        return [
            'items' => $this->pagesRepository->findAll(),
            'title' => 'Pages | Admin | ' . Setting::get('sitename')
        ];
    }
}
