<?php


namespace App\Module\Pages\Controller;


use App\Module\Pages\Entities\Page;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use Twig\Environment;

class Item
{

    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $pagesRepository;
    /**
     * @var Environment
     */
    private Environment $twig;

    public function __construct(ServerRequestInterface $serverRequest, EntityManager $entityManager, Environment $twig)
    {
        $this->serverRequest = $serverRequest;
        $this->pagesRepository = $entityManager->getRepository(Page::class);
        $this->twig = $twig;
    }

    public function view()
    {
        $page = $this->pagesRepository->findOneBy(['slug' => $this->serverRequest->get('slug'), 'status' => true]);
        if ($page === null) {
            Error::code(404);
        }

        $template_path = '@m/pages/view.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path =  __DIR__ . '/../template/view.twig.sample';
        }

        return $this->twig->render(
            $template_path,
            [
                'page' => $page
            ]
        );
    }
}
