<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Controller;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\AssetsCollector\Assets;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollection;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Pages\Admin\Add;
use EnjoysCMS\Module\Pages\Admin\Edit;
use EnjoysCMS\Module\Pages\Admin\Index;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('pages/admin', '@pages_admin_',
    options: [
        'isAdmin' => true
    ]
)]
final class Admin extends AdminBaseController
{

  public function __construct(
      Container $container,
      Environment $twig,
      Assets $assets,
      Setting $setting,
      ResponseInterface $response,
      BreadcrumbCollection $breadcrumbs
  ) {
      parent::__construct($container, $twig, $assets, $setting, $response, $breadcrumbs);
      $this->twig->getLoader()->addPath(__DIR__ . '/../template', 'pages');
  }

    /**
     * @throws LoaderError
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ExceptionRule
     */
    #[Route('/edit@{id}',
        name: 'edit',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Редактирование страниц'
    )]
    public function edit(Edit $edit): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@pages/admin/edit.twig',
                $edit->getContext()
            )
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    #[Route(
        path: '/delete@{id}',
        name: 'delete',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Удаление страниц'
    )]
    public function delete(
        EntityManager $entityManager,
        ServerRequestInterface $request,
        RedirectInterface $redirect,
    ): ResponseInterface {
        $item = $entityManager->getRepository(Page::class)->find(
            $request->getAttribute(
                'id',
                $request->getQueryParams()['id'] ?? 0
            )
        );
        if ($item === null) {
            throw new \InvalidArgumentException('Invalid Arguments');
        }

        $entityManager->remove($item);
        $entityManager->flush();
        return $redirect->toRoute('pages/admin/list');
    }

    /**
     * @throws LoaderError
     * @throws NotSupported
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/list',
        name: 'list',
        comment: 'Список всех страниц (обзор)'
    )]
    public function list(Index $index): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@pages/admin/list.twig',
                $index->getContext()
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/add',
        name: 'add',
        comment: 'Добавить новую страницу'
    )]
    public function add(Add $add): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@pages/admin/add.twig',
                $add->getContext()
            )
        );
    }
}
