<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Controller;

use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Module\Pages\Entities\Page;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Setting;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Item extends BaseController
{

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
    public function view(
        EntityManager $entityManager,
        ServerRequestInterface $request,
        Environment $twig
    ): ResponseInterface {
        /** @var Page $page */
        $page = $entityManager->getRepository(Page::class)->findOneBy(['slug' => $request->get('slug'), 'status' => true]);
        if ($page === null) {
            Error::code(404);
        }

        $template_path = '@m/pages/view.twig';

        if (!$twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../template/view.twig.sample';
        }

        return $this->responseText($twig->render(
            $template_path,
            [
                '_title' => sprintf(
                    '%2$s - %1$s',
                    Setting::get('sitename'),
                    $page->getTitle()
                ),
                'page' => $page
            ]
        ));
    }
}
