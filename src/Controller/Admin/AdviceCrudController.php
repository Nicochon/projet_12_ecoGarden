<?php

namespace App\Controller\Admin;

use App\Entity\Advice;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdviceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Advice::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            ArrayField::new('month'),
            TextField::new('advice'),
        ];
    }
}
