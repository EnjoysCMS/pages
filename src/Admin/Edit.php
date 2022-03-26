<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Edit
{
    private ?Page $page;
    private ModuleConfig $config;


    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws TransactionRequiredException
     */
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private ContainerInterface $container
    ) {
        $this->page = $this->entityManager->find(
            Page::class,
            $this->requestWrapper->getAttributesData(
                'id',
                $this->requestWrapper->getQueryData('id', 0)
            )
        );

        if ($this->page === null) {
            Error::code(404);
        }

        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
        $this->config = Config::getConfig($this->container);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[ArrayShape([
        'form' => "\Enjoys\Forms\Renderer\RendererInterface",
        'wysiwyg' => "string",
        'title' => "string"
    ])]
    public function getContext(): array
    {
        $wysiwyg = WYSIWYG::getInstance($this->config->get('WYSIWYG'), $this->container);

        return [
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#body'),
            'title' => 'Добавление страницы - Pages | Admin | ' . Setting::get(
                    'sitename'
                )
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
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
            ->fill(['1 ' => 'Активный'])
        ;
        $form->text('title', 'Название')
            ->addRule(Rules::REQUIRED)
        ;
        $form->textarea('body', 'Контент')
            ->addRule(Rules::REQUIRED)
            ->setRows(10)
        ;
        $form->textarea('scripts', 'Скрипты');
        $form->text('slug', 'Уникальное имя')
            ->addRule(Rules::REQUIRED)
            ->setDescription('Используется в URL')
        ;

        $form->submit('edit', 'Редактировать страницу');
        return $form;
    }

    private function doAction()
    {
        $this->page->setTitle($this->requestWrapper->getPostData('title'));
        $this->page->setBody($this->requestWrapper->getPostData('body'));
        $this->page->setScripts($this->requestWrapper->getPostData('scripts'));
        $this->page->setSlug($this->requestWrapper->getPostData('slug'));
        $this->page->setStatus((bool)$this->requestWrapper->getPostData('status', 0));

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('pages/admin/list'));
    }


}
