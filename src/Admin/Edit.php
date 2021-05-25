<?php


namespace App\Module\Pages\Admin;


use App\Module\Pages\Entities\Page;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use App\Module\Pages\Config;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Edit
{
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var Environment
     */
    private Environment $twig;

    private ?Page $page;
    private ContainerInterface $container;
    private ModuleConfig $config;


    public function __construct(ContainerInterface $container)
    {
        $this->renderer = $container->get(RendererInterface::class);
        $this->entityManager = $container->get(EntityManager::class);
        $this->serverRequest = $container->get(ServerRequestInterface::class);
        $this->urlGenerator = $container->get(UrlGeneratorInterface::class);
        $this->page = $this->entityManager->find(Page::class, $this->serverRequest->get('id'));

        if ($this->page === null) {
            Error::code(404);
        }

        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
        $this->twig = $container->get(Environment::class);
        $this->container = $container;
        $this->config = Config::getConfig($this->container);
    }

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
        $form->checkbox('status')->fill(['1 ' => 'Активный']);
        $form->text('title', 'Название')->addRule(Rules::REQUIRED);
        $form->textarea('body', 'Контент')->addRule(Rules::REQUIRED)->setRows(10);
        $form->textarea('scripts', 'Скрипты');
        $form->text('slug', 'Уникальное имя')->addRule(Rules::REQUIRED)->setDescription('Используется в URL');


        //        $form->checkbox('groups', 'Группа')->fill(
        //            $this->entityManager->getRepository(Groups::class)->getGroupsArray()
        //        )->addRule(Rules::REQUIRED);

        $form->submit('edit', 'Редактировать страницу');
        return $form;
    }

    private function doAction()
    {
        try {
            $this->page->setTitle($this->serverRequest->post('title'));
            $this->page->setBody($this->serverRequest->post('body'));
            $this->page->setScripts($this->serverRequest->post('scripts'));
            $this->page->setSlug($this->serverRequest->post('slug'));
            $this->page->setStatus((bool)$this->serverRequest->post('status', 0));

            $this->entityManager->flush();

            Redirect::http($this->urlGenerator->generate('pages/admin/list'));
        } catch (OptimisticLockException | ORMException $e) {
            Error::code(500, $e->__toString());
        }
    }


}
