<?php

namespace App\Controller\Admin;

use App\Entity\Disponibilite;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class DisponibiliteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Disponibilite::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('medecin')->setLabel('Doctor'),
            DateTimeField::new('debut')->setLabel('Start'),
            DateTimeField::new('fin')->setLabel('End'),
            BooleanField::new('estLibre')->setLabel('Is available'),
        ];
    }
}
