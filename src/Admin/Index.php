<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Module\Pages\Entities\Page;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Index
{

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $pagesRepository;

    public function __construct(EntityManager $entityManager, private UrlGeneratorInterface $urlGenerator)
    {
        $this->pagesRepository = $entityManager->getRepository(Page::class);
    }

    public function getContext(): array
    {
        return [
            'items' => $this->pagesRepository->findAll(),
            'title' => 'Pages | Admin | ' . Setting::get('sitename'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('pages/admin/list') => 'Страницы',
            ],
        ];
    }
}
