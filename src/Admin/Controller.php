<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Renderer\Renderer;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminController;
use EnjoysCMS\Module\Admin\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('pages/admin', '@pages_admin_',
    options: [
        'isAdmin' => true
    ]
)]
final class Controller extends AdminController
{

    public function __construct(Container $container)
    {
        parent::__construct($container);
        /** @psalm-suppress UndefinedInterfaceMethod */
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
     * @throws \EnjoysCMS\Core\Exception\NotFoundException
     */
    #[Route('/edit@{id}',
        name: 'edit',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Редактирование страниц'
    )]
    public function edit(
        AddEditPageForm $edit,
        \EnjoysCMS\Module\Pages\Config $config,
        Config $adminConfig,
        ContentEditor $contentEditor,
    ): ResponseInterface {
        $page = $this->container->get(EntityManager::class)->find(
            Page::class,
            $this->request->getAttribute(
                'id',
                $this->request->getQueryParams()['id'] ?? 0
            )
        ) ?? throw new \EnjoysCMS\Core\Exception\NotFoundException();

        $form = $edit->getForm($page);
        if ($form->isSubmitted()) {
            $edit->doAction($page);
            return $this->redirect->toRoute('@pages_admin_list');
        }

        /** @var Renderer $rendererForm */
        $rendererForm = $adminConfig->getRendererForm();

        $this->breadcrumbs
            ->add('@pages_admin_list', 'Страницы')
            ->setLastBreadcrumb(sprintf('Редактирование страницы: %s', $page->getTitle()));

        return $this->response(
            $this->twig->render(
                '@pages/admin/edit.twig',
                [
                    'form' => $rendererForm->setForm($form),
                    'contentEditorEmbedCode' => $contentEditor->withConfig(
                            $config->getCrudContentEditor()
                        )->setSelector('#body')->getEmbedCode()
                        . $contentEditor->withConfig(
                            $config->getScriptsContentEditor()
                        )->setSelector('#scripts')->getEmbedCode(),
                    '_title' => 'Редактирование страницы - Pages | Admin | ' . $this->setting->get(
                            'sitename'
                        )
                ]
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
        EntityManager $em,
        ServerRequestInterface $request,
        RedirectInterface $redirect,
    ): ResponseInterface {
        $entity = $em->getRepository(Page::class)->find(
            $request->getAttribute(
                'id',
                $request->getQueryParams()['id'] ?? 0
            )
        ) ?? throw new InvalidArgumentException('Invalid Arguments');

        $em->remove($entity);
        $em->flush();
        return $redirect->toRoute('@pages_admin_list');
    }

    /**
     * @throws LoaderError
     * @throws NotSupported
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '',
        name: 'list',
    )]
    public function list(EntityManager $em): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Страницы', '@pages_admin_list');

        return $this->response(
            $this->twig->render(
                '@pages/admin/list.twig',
                [
                    'items' => $em->getRepository(Page::class)->findAll(),
                    'title' => 'Pages | Admin | ' . $this->setting->get('sitename')
                ]
            )
        );
    }

    /**
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/add',
        name: 'add',
    )]
    public function add(
        AddEditPageForm $add,
        Config $adminConfig,
        \EnjoysCMS\Module\Pages\Config $config,
        ContentEditor $contentEditor,
    ): ResponseInterface {
        $form = $add->getForm();
        if ($form->isSubmitted()) {
            $add->doAction();
            return $this->redirect->toRoute('@pages_admin_list');
        }

        /** @var Renderer $rendererForm */
        $rendererForm = $adminConfig->getRendererForm();

        $this->breadcrumbs->add('@pages_admin_list', 'Страницы')
            ->setLastBreadcrumb('Добавление новой страницы');

        return $this->response(
            $this->twig->render(
                '@pages/admin/add.twig',
                [
                    'form' => $rendererForm->setForm($form),
                    'contentEditorEmbedCode' => $contentEditor->withConfig(
                            $config->getCrudContentEditor()
                        )->setSelector('#body')->getEmbedCode()
                        . $contentEditor->withConfig(
                            $config->getScriptsContentEditor()
                        )->setSelector('#scripts')->getEmbedCode(),
                    '_title' => 'Добавление страницы - Pages | Admin | ' . $this->setting->get(
                            'sitename'
                        ),
                ]
            )
        );
    }
}
