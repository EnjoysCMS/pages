<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollection;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Module\Pages\Entities\Page;

final class Index
{

    public function __construct(
        private readonly EntityManager $em,
        private readonly Setting $setting,
        private readonly BreadcrumbCollection $breadcrumbs
    ) {
    }

    /**
     * @throws NotSupported
     */
    public function getContext(): array
    {
        $this->breadcrumbs->setLastBreadcrumb('Страницы', '@pages_admin_list');

        return [
            'items' => $this->em->getRepository(Page::class)->findAll(),
            'title' => 'Pages | Admin | ' . $this->setting->get('sitename')?->getValue(),
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
