<?php


namespace App\Module\Pages\Admin;


use App\Module\Pages\Entities\Items;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Core\Entities\Groups;
use EnjoysCMS\WYSIWYG\Summernote\Summernote;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Add
{

    /**
     * @var Form
     */
    private Form $form;
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


    public function __construct(RendererInterface $renderer, EntityManager $entityManager, ServerRequestInterface $serverRequest, UrlGeneratorInterface $urlGenerator, Environment $twig)
    {
        $this->renderer = $renderer;
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;

        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);

        $this->twig = $twig;
    }

    public function getContext(): array
    {
        $wysiwyg = new WYSIWYG(new Summernote());
        $wysiwyg->setTwig($this->twig);

        return [
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#body'),
            'title' => 'Добавление страницы - Pages | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename')
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->text('title', 'Название')->addRule(Rules::REQUIRED);
        $form->textarea('body', 'Контент')->addRule(Rules::REQUIRED)->setRows(10);
        $form->textarea('scripts', 'Скрипты');
        $form->text('slug', 'Уникальное имя')->addRule(Rules::REQUIRED)->setDescription('Используется в URL');

//        $form->checkbox('groups', 'Группа')->fill(
//            $this->entityManager->getRepository(Groups::class)->getGroupsArray()
//        )->addRule(Rules::REQUIRED);

        $form->submit('addblock', 'Добавить страницу');
        return $form;
    }

    private function doAction()
    {
        try {
            $page = new Items();
            $page->setTitle($this->serverRequest->post('title'));
            $page->setBody($this->serverRequest->post('body'));
            $page->setScripts($this->serverRequest->post('scripts'));
            $page->setSlug($this->serverRequest->post('slug'));
            $page->setStatus(true);
            $this->entityManager->persist($page);
            $this->entityManager->flush();

            Redirect::http($this->urlGenerator->generate('pages/item', ['slug' => $page->getSlug()]));
        } catch (OptimisticLockException | ORMException $e) {
            Error::code(500, $e->__toString());
        }
    }


}
