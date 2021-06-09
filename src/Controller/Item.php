<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Controller;

use EnjoysCMS\Module\Pages\Entities\Page;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Setting;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Item
{

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $pagesRepository;

    public function __construct(
        EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private Environment $twig
    ) {
        $this->pagesRepository = $entityManager->getRepository(Page::class);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/info/{slug}.html',
        name: 'pages/item',
        options: [
            'aclComment' => 'Просмотр страниц в public'
        ]
    )]
    public function view(): string
    {
        /** @var Page $page */
        $page = $this->pagesRepository->findOneBy(['slug' => $this->serverRequest->get('slug'), 'status' => true]);
        if ($page === null) {
            Error::code(404);
        }

        $template_path = '@m/pages/view.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../template/view.twig.sample';
        }

        return $this->twig->render(
            $template_path,
            [
                '_title' => sprintf(
                    '%2$s - %1$s',
                    Setting::get('sitename'),
                    $page->getTitle()
                ),
                'page' => $page
            ]
        );
    }
}
