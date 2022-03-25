<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Add
{
    private ModuleConfig $config;


    /**
     * @throws ExceptionRule
     */
    public function __construct(
        private ContainerInterface $container,
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->config = Config::getConfig($this->container);

        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
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

        $form->submit('addblock', 'Добавить страницу');
        return $form;
    }

    private function doAction()
    {
        $page = new Page();
        $page->setTitle($this->request->getParsedBody()['title'] ?? null);
        $page->setBody($this->request->getParsedBody()['body'] ?? null);
        $page->setScripts($this->request->getParsedBody()['scripts'] ?? null);
        $page->setSlug($this->request->getParsedBody()['slug'] ?? null);
        $page->setStatus(true);
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('pages/admin/list'));
    }


}
