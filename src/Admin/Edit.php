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
use EnjoysCMS\WYSIWYG\Summernote\Summernote;
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

    private ?Items $item;


    public function __construct(
        RendererInterface $renderer,
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig
    ) {
        $this->renderer = $renderer;
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->item = $entityManager->find(Items::class, $serverRequest->get('id'));

        if ($this->item === null) {
            Error::code(404);
        }


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
            'title' => 'Добавление страницы - Pages | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get(
                    'sitename'
                )
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->setDefaults(
            [
                'title' => $this->item->getTitle(),
                'body' => $this->item->getBody(),
                'scripts' => $this->item->getScripts(),
                'slug' => $this->item->getSlug(),
                'status' => [(string) $this->item->isStatus()]
            ]
        );
        $form->checkbox('status')->fill(['1 ' =>'Активный']);
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

            $this->item->setTitle($this->serverRequest->post('title'));
            $this->item->setBody($this->serverRequest->post('body'));
            $this->item->setScripts($this->serverRequest->post('scripts'));
            $this->item->setSlug($this->serverRequest->post('slug'));
            $this->item->setStatus((bool)$this->serverRequest->post('status', 0));

            $this->entityManager->flush();

            Redirect::http($this->urlGenerator->generate('pages/admin/list'));
        } catch (OptimisticLockException | ORMException $e) {
            Error::code(500, $e->__toString());
        }
    }


}
