<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SongRequest;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SongRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SongRequest::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Имя')->setSortable(true);
        yield TextField::new('contact', 'Контакт')->setSortable(true);
        yield TextField::new('occasion', 'Повод')->hideOnIndex();
        yield TextField::new('tone', 'Настроение')->hideOnIndex();
        yield TextEditorField::new('story', 'История')->renderAsHtml(false);
        yield BooleanField::new('storyLater', 'Историю расскажет позже')->onlyOnIndex();
        yield DateTimeField::new('createdAt', 'Создано')->setSortable(true)->hideOnForm();
    }
}
