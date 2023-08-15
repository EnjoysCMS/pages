<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/info/{slug}.html',
    name: 'pages_view',
    title: '[PAGES] Просмотр страницы',
    comment: 'Просмотр страниц в public'
)]
final class ViewController extends AbstractController
{

    /**
     * @throws SyntaxError
     * @throws NotSupported
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NotFoundException
     */
    public function __invoke(EntityManager $em): ResponseInterface
    {
        /** @var Page $page */
        $page = $em->getRepository(Page::class)->findOneBy(
            ['slug' => $this->request->getAttribute('slug'), 'status' => true]
        ) ?? throw new NotFoundException();


        $template_path = '@m/pages/view.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/template/view.twig.sample';
        }

        $this->breadcrumbs->setLastBreadcrumb($page->getTitle());

        return $this->response(
            $this->twig->render(
                $template_path,
                [
                    '_title' => sprintf(
                        '%2$s - %1$s',
                        $this->setting->get('sitename'),
                        $page->getTitle()
                    ),
                    'page' => $page,
                    'breadcrumbs' => $this->breadcrumbs
                ]
            )
        );
    }
}
