<?php


namespace App\Module\Pages\Controller;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
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

    public function init()
    {
        return $this->twig->render('@pages/admin.twig', [
            'title' => 'Pages | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename')
        ]);
    }
}