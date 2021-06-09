<?php


namespace App\Module\Pages\Admin;


use App\Module\Pages\Config;
use App\Module\Pages\Entities\Page;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Add
{
    private RendererInterface $renderer;
    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private UrlGeneratorInterface $urlGenerator;
    private ModuleConfig $config;


    /**
     * @throws ExceptionRule
     */
    public function __construct(private ContainerInterface $container)
    {
        $this->renderer = $container->get(RendererInterface::class);
        $this->entityManager = $container->get(EntityManager::class);
        $this->serverRequest = $container->get(ServerRequestInterface::class);
        $this->urlGenerator = $container->get(UrlGeneratorInterface::class);

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
        try {
            $page = new Page();
            $page->setTitle($this->serverRequest->post('title'));
            $page->setBody($this->serverRequest->post('body'));
            $page->setScripts($this->serverRequest->post('scripts'));
            $page->setSlug($this->serverRequest->post('slug'));
            $page->setStatus(true);
            $this->entityManager->persist($page);
            $this->entityManager->flush();

            Redirect::http($this->urlGenerator->generate('pages/admin/list'));
        } catch (OptimisticLockException | ORMException $e) {
            Error::code(500, $e->__toString());
        }
    }


}
