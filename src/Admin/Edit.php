<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Http\Message\ServerRequestInterface;

final class Edit
{
    private Page $page;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     * @throws NotFoundException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
        $this->page = $this->em->find(
            Page::class,
            $this->request->getAttribute(
                'id',
                $this->request->getQueryParams()['id'] ?? 0
            )
        ) ?? throw new NotFoundException();
    }

    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
    {
        $form = new Form();
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
            ->fill(['1 ' => 'Активный']);

        $form->text('title', 'Название')
            ->addRule(Rules::REQUIRED);

        $form->text('slug', 'Уникальное имя для url')
            ->addRule(Rules::REQUIRED)
            ->setDescription('Используется в URL');

        $form->textarea('body', 'Контент')
            ->setRows(10);
        $form->textarea('scripts', 'Скрипты');


        $form->submit('edit', 'Редактировать страницу');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $this->page->setTitle($this->request->getParsedBody()['title'] ?? null);
        $this->page->setBody($this->request->getParsedBody()['body'] ?? '');
        $this->page->setScripts($this->request->getParsedBody()['scripts'] ?? '');
        $this->page->setSlug($this->request->getParsedBody()['slug'] ?? null);
        $this->page->setStatus((bool)($this->request->getParsedBody()['status'] ?? 0));

        $this->em->flush();
    }

    public function getPage(): Page
    {
        return $this->page;
    }


}
