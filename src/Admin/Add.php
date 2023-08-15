<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Pages\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\Pages\Entities\Page;
use Psr\Http\Message\ServerRequestInterface;

final class Add
{

    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
    }

    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
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


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $page = new Page();
        $page->setTitle($this->request->getParsedBody()['title'] ?? '');
        $page->setBody($this->request->getParsedBody()['body'] ?? '');
        $page->setScripts($this->request->getParsedBody()['scripts'] ?? '');
        $page->setSlug($this->request->getParsedBody()['slug'] ?? '');
        $page->setStatus(true);
        $this->em->persist($page);
        $this->em->flush();
    }


}
