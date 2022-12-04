<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Controller;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @throws NotFoundException
     */
    #[Route(
        path: '/info/{slug}.html',
        name: 'pages/item',
        options: [
            'comment' => 'Просмотр страниц в public'
        ]
    )]
    public function view(
        ServerRequestInterface $request,
        EntityManager $entityManager,
        Environment $twig
    ): ResponseInterface {

        /** @var Page $page */
        $page = $entityManager->getRepository(Page::class)->findOneBy(
            ['slug' => $request->getAttribute('slug'), 'status' => true]
        );
        if ($page === null) {
            throw new NotFoundException();
        }

        $template_path = '@m/pages/view.twig';

        if (!$twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../template/view.twig.sample';
        }

        return $this->responseText(
            $twig->render(
                $template_path,
                [
                    '_title' => sprintf(
                        '%2$s - %1$s',
                        Setting::get('sitename'),
                        $page->getTitle()
                    ),
                    'page' => $page
                ]
            )
        );
    }
}
