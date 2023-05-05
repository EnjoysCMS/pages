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
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add
{
    /**
     * @throws ExceptionRule
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private ContentEditor $contentEditor,
        private Config $config
    ) {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getContext(): array
    {
        return [
            'form' => $this->renderer,
            'contentEditorEmbedCode' => $this->contentEditor->withConfig(
                    $this->config->getCrudContentEditor()
                )->setSelector('#body')->getEmbedCode()
                . $this->contentEditor->withConfig(
                    $this->config->getScriptsContentEditor()
                )->setSelector('#scripts')->getEmbedCode(),
            'title' => 'Добавление страницы - Pages | Admin | ' . Setting::get(
                    'sitename'
                ),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('pages/admin/list') => 'Страницы',
                'Добавление новой страницы'
            ],
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
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('pages/admin/list'));
    }


}
