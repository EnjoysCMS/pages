<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Entities\Setting;
use EnjoysCMS\Module\Pages\Entities\Page;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Index
{

    public function __construct(private EntityManager $em, private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @throws NotSupported
     */
    public function getContext(): array
    {
        $settingRepository = $this->em->getRepository(Setting::class);

        return [
            'items' => $this->em->getRepository(Page::class)->findAll(),
            'title' => 'Pages | Admin | ' . $settingRepository->find('sitename')?->getValue(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('pages/admin/list') => 'Страницы',
            ],
        ];
    }
}
