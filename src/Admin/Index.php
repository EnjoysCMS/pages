<?php


namespace App\Module\Pages\Admin;


use App\Module\Pages\Entities\Items;
use Doctrine\ORM\EntityManager;

class Index
{

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $pagesRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->pagesRepository = $entityManager->getRepository(Items::class);
    }

    public function getContext(): array
    {
        return   [
            'items' => $this->pagesRepository->findAll(),
            'title' => 'Pages | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename')
        ];
    }
}
