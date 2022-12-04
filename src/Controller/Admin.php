<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Pages\Admin\Add;
use EnjoysCMS\Module\Pages\Admin\Edit;
use EnjoysCMS\Module\Pages\Admin\Index;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Admin extends AdminBaseController
{

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->getTwig()->getLoader()->addPath(__DIR__ . '/../template', 'pages');
    }

    /**
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/pages/admin/edit@{id}',
        name: 'pages/admin/editpage',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => '[Pages][Admin] Редактирование страниц'
        ]
    )]
    public function edit(): ResponseInterface
    {
        return $this->responseText(
            $this->getTwig()->render(
                '@pages/admin/edit.twig',
                $this->getContainer()->get(Edit::class)->getContext()
            )
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route(
        path: '/pages/admin/delete@{id}',
        name: 'pages/admin/delpage',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => '[Pages][Admin] Удаление страниц'
        ]
    )]
    public function delete(
        EntityManager $entityManager,
        ServerRequestInterface $request,
        UrlGeneratorInterface $urlGenerator
    ) {
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
        Redirect::http($urlGenerator->generate('pages/admin/list'));
    }

    /**
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/pages/admin/list',
        name: 'pages/admin/list',
        options: [
            'aclComment' => '[Pages][Admin] Список всех страниц (обзор)'
        ]
    )]
    public function list(): ResponseInterface
    {
        return $this->responseText(
            $this->getTwig()->render(
                '@pages/admin/list.twig',
                $this->getContainer()->get(Index::class)->getContext()
            )
        );
    }

    /**
     * @return ResponseInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: '/pages/admin/addpage',
        name: 'pages/admin/addpage',
        options: [
            'aclComment' => '[Pages][Admin] Добавить новую страницу'
        ]
    )]
    public function add(): ResponseInterface
    {
        return $this->responseText(
            $this->getTwig()->render(
                '@pages/admin/add.twig',
                $this->getContainer()->get(Add::class)->getContext()
            )
        );
    }
}
