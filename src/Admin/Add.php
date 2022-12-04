<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use DI\Container;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Core\Components\WYSIWYG\WysiwygConfig;
use EnjoysCMS\Module\Pages\Config;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Add
{
    /**
     * @throws ExceptionRule
     */
    public function __construct(
        private Container $container,
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config
    ) {

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
    public function getContext(): array
    {
        $configWysiwyg = new WysiwygConfig($this->config->getModuleConfig()->get('WYSIWYG'));

        $wysiwyg = WYSIWYG::getInstance($configWysiwyg->getEditorName(), $this->container);
        $wysiwyg->getEditor()->setTwigTemplate($configWysiwyg->getTemplate('crud'));

        return [
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#body'),
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

    private function doAction()
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
