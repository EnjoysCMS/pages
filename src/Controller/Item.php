<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Entities\Setting;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: '/info/{slug}.html',
    name: 'pages/item',
    options: [
        'comment' => 'Просмотр страниц в public'
    ]
)]
final class Item
{

    /**
     * @throws SyntaxError
     * @throws NotSupported
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NotFoundException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        EntityManager $em,
        Environment $twig,
        BreadcrumbsInterface $breadcrumbs,
    ): ResponseInterface {
        /** @var array $setting */
        $setting = $em->getRepository(Setting::class)->findAll();

        /** @var Page $page */
        $page = $em->getRepository(Page::class)->findOneBy(
            ['slug' => $request->getAttribute('slug'), 'status' => true]
        ) ?? throw new NotFoundException();


        $template_path = '@m/pages/view.twig';

        if (!$twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../template/view.twig.sample';
        }

        $breadcrumbs->add(null, $page->getTitle());
        $response->getBody()->write(
            $twig->render(
                $template_path,
                [
                    '_title' => sprintf(
                        '%2$s - %1$s',
                        $setting['sitename'] ?? null,
                        $page->getTitle()
                    ),
                    'page' => $page,
                    'breadcrumbs' => $breadcrumbs->get()
                ]
            )
        );
        return $response;
    }
}
