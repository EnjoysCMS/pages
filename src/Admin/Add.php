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
use EnjoysCMS\Core\Entities\Setting;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add
{

    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private ContentEditor $contentEditor,
        private RedirectInterface $redirect,
        private Config $config
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
        $settingRepository = $this->em->getRepository(Setting::class);
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('pages/admin/list', emit: true);
        }
        $this->renderer->setForm($form);

        return [
            'form' => $this->renderer,
            'contentEditorEmbedCode' => $this->contentEditor->withConfig(
                    $this->config->getCrudContentEditor()
                )->setSelector('#body')->getEmbedCode()
                . $this->contentEditor->withConfig(
                    $this->config->getScriptsContentEditor()
                )->setSelector('#scripts')->getEmbedCode(),
            '_title' => 'Добавление страницы - Pages | Admin | ' . $settingRepository->find(
                    'sitename'
                )?->getValue(),
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

        $form->textarea('metaDescription', 'meta-description');

        $form->text('metaKeywords', 'meta-keywords');

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
        $page->setMetaDescription($this->request->getParsedBody()['metaDescription'] ?? null);
        $page->setMetaKeywords($this->request->getParsedBody()['metaKeywords'] ?? null);
        $page->setStatus(true);
        $this->em->persist($page);
        $this->em->flush();
    }


}
