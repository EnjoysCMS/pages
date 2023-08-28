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

final class AddEditPageForm
{
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request
    ) {
    }

    /**
     * @throws ExceptionRule
     */
    public function getForm(Page $page = null): Form
    {
        $form = new Form();
        $form->setDefaults(
            [
                'title' => $page?->getTitle(),
                'body' => $page?->getBody(),
                'scripts' => $page?->getScripts(),
                'slug' => $page?->getSlug(),
                'status' => [(string)($page?->isStatus() ?? true)]
            ]
        );
        $form->checkbox('status')
            ->fill(['1 ' => 'Активный']);

        $form->text('title', 'Название')
            ->addRule(Rules::REQUIRED);

        $form->text('slug', 'Уникальное имя для url')
            ->addRule(Rules::REQUIRED)
            ->addRule(Rules::CALLBACK, 'Такой url уже существует', function () use ($page) {
                $item = $this->em->getRepository(Page::class)->findOneBy([
                    'slug' => $this->request->getParsedBody()['slug'] ?? ''
                ]);

                if ($item?->getId() === $page?->getId()) {
                    return true;
                }
                return $item === null;
            })
            ->setDescription('Используется в URL');

        $form->textarea('body', 'Контент')
            ->setRows(10);
        $form->textarea('scripts', 'Скрипты');


        $form->submit('edit', 'Сохранить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(Page $page = null): void
    {
        $page = $page ?? new Page();
        $page->setTitle($this->request->getParsedBody()['title'] ?? '');
        $page->setBody($this->request->getParsedBody()['body'] ?? '');
        $page->setScripts($this->request->getParsedBody()['scripts'] ?? '');
        $page->setSlug($this->request->getParsedBody()['slug'] ?? '');
        $page->setStatus((bool)($this->request->getParsedBody()['status'] ?? 0));
        $this->em->persist($page);
        $this->em->flush();
    }
}
