<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollection;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Add
{

    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly ContentEditor $contentEditor,
        private readonly RedirectInterface $redirect,
        private readonly Setting $setting,
        private readonly Config $config,
        private readonly BreadcrumbCollection $breadcrumbs,
    ) {
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ExceptionRule
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws OptimisticLockException
     * @throws NotFoundException
     */
    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('pages/admin/list', emit: true);
        }
        $this->renderer->setForm($form);

        $this->breadcrumbs->add('@pages_admin_list', 'Страницы')
            ->setLastBreadcrumb('Добавление новой страницы');

        return [
            'form' => $this->renderer,
            'contentEditorEmbedCode' => $this->contentEditor->withConfig(
                    $this->config->getCrudContentEditor()
                )->setSelector('#body')->getEmbedCode()
                . $this->contentEditor->withConfig(
                    $this->config->getScriptsContentEditor()
                )->setSelector('#scripts')->getEmbedCode(),
            '_title' => 'Добавление страницы - Pages | Admin | ' . $this->setting->get(
                    'sitename'
                ),
            'breadcrumbs' => $this->breadcrumbs
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->text('title', 'Название')
            ->addRule(Rules::REQUIRED);
        $form->text('slug', 'Уникальное имя для url')
            ->addRule(Rules::REQUIRED)
            ->setDescription('Используется в URL');
        $form->textarea('body', 'Контент')
            ->setRows(10);
        $form->textarea('scripts', 'Скрипты');


        $form->submit('addblock', 'Добавить страницу');
        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $page = new Page();
        $page->setTitle($this->request->getParsedBody()['title'] ?? null);
        $page->setBody($this->request->getParsedBody()['body'] ?? '');
        $page->setScripts($this->request->getParsedBody()['scripts'] ?? '');
        $page->setSlug($this->request->getParsedBody()['slug'] ?? null);
        $page->setStatus(true);
        $this->em->persist($page);
        $this->em->flush();
    }


}
