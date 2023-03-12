<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;


use DI\DependencyException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit
{
    private ?Page $page;

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws TransactionRequiredException
     * @throws NotFoundException
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private ContentEditor $contentEditor,
        private Config $config
    ) {
        $this->page = $this->entityManager->find(
            Page::class,
            $this->request->getAttribute(
                'id',
                $this->request->getQueryParams()['id'] ?? 0
            )
        );

        if ($this->page === null) {
            throw new NotFoundException();
        }

        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
    }

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
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
            'title' => 'Редактирование страницы - Pages | Admin | ' . Setting::get(
                    'sitename'
                ),
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
        $this->page->setSlug($this->request->getParsedBody()['slug'] ?? null);
        $this->page->setStatus((bool)($this->request->getParsedBody()['status'] ?? 0));

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('pages/admin/list'));
    }


}
