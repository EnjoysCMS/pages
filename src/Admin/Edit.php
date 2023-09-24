<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;


use DI\DependencyException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Entities\Setting;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit
{
    private Page $page;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     * @throws NotFoundException
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private ContentEditor $contentEditor,
        private RedirectInterface $redirect,
        private Config $config
    ) {
        $this->page = $this->em->find(
            Page::class,
            $this->request->getAttribute(
                'id',
                $this->request->getQueryParams()['id'] ?? 0
            )
        ) ?? throw new NotFoundException();
    }


    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws \DI\NotFoundException
     * @throws ORMException
     * @throws NotSupported
     * @throws DependencyException
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
            '_title' => 'Редактирование страницы - Pages | Admin | ' . $settingRepository->find(
                    'sitename'
                )?->getValue(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('pages/admin/list') => 'Страницы',
                sprintf('Редактирование страницы: %s', $this->page->getTitle())
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults(
            [
                'title' => $this->page->getTitle(),
                'body' => $this->page->getBody(),
                'metaDescription' => $this->page->getMetaDescription(),
                'metaKeywords' => $this->page->getMetaKeywords(),
                'scripts' => $this->page->getScripts(),
                'slug' => $this->page->getSlug(),
                'status' => [(string)$this->page->isStatus()]
            ]
        );
        $form->checkbox('status')
            ->fill(['1 ' => 'Активный']);

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

        $form->submit('edit', 'Редактировать страницу');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $this->page->setTitle($this->request->getParsedBody()['title'] ?? null);
        $this->page->setBody($this->request->getParsedBody()['body'] ?? '');
        $this->page->setScripts($this->request->getParsedBody()['scripts'] ?? '');
        $this->page->setMetaDescription($this->request->getParsedBody()['metaDescription'] ?? null);
        $this->page->setMetaKeywords($this->request->getParsedBody()['metaKeywords'] ?? null);
        $this->page->setSlug($this->request->getParsedBody()['slug'] ?? null);
        $this->page->setStatus((bool)($this->request->getParsedBody()['status'] ?? 0));

        $this->em->flush();
    }


}
