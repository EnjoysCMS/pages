<?php


namespace App\Module\Pages\Controller;


use App\Module\Pages\Entities\Items;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;

class Item
{

    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $pagesRepository;

    public function __construct(ServerRequestInterface $serverRequest, EntityManager $entityManager)
    {
        $this->serverRequest = $serverRequest;
        $this->pagesRepository = $entityManager->getRepository(Items::class);
    }

    public function view()
    {
        $page = $this->pagesRepository->findOneBy(['slug' => $this->serverRequest->get('slug')]);
        if ($page === null) {
            Error::code(404);
        }

    }
}
