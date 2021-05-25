<?php


namespace App\Module\Pages\Controller;


use App\Module\Admin\BaseController;
use App\Module\Pages\Admin\Add;
use App\Module\Pages\Admin\Edit;
use App\Module\Pages\Admin\Index;
use App\Module\Pages\Entities\Page;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Admin extends BaseController
{

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
        $this->twigLoader->addPath(__DIR__ . '/../template', 'pages');
    }

    public function edit(ContainerInterface $container)
    {
        return $this->twig->render(
            '@pages/admin/edit.twig',
            $container->get(Edit::class)->getContext()
        );
    }

    public function delete()
    {
        $item = $this->entityManager->getRepository(Page::class)->find($this->serverRequest->get('id'));
        if ($item === null) {
            throw new \InvalidArgumentException('Invalid Arguments');
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('pages/admin/list'));
    }


    public function list()
    {
        return $this->twig->render(
            '@pages/admin/list.twig',
            (new Index($this->entityManager))->getContext()
        );
    }


    public function add(ContainerInterface $container)
    {
        return $this->twig->render(
            '@pages/admin/add.twig',
            $container->get(Add::class)->getContext()
        );
    }
}
