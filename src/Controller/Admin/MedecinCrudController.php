<?php

namespace App\Controller\Admin;

use App\Entity\Medecin;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
// removed ArrayField since Medecin doesn't store roles/login
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
// password/email not used for Medecin
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MedecinCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Medecin::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_EDIT, Action::INDEX)
                        ->add(Crud::PAGE_INDEX, Action::DETAIL)
                        ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('prenom'),
            BooleanField::new('isGeneraliste'),
            AssociationField::new('rendezVouses')->hideOnForm(),
            AssociationField::new('disponibilites')->hideOnForm(),
        ];
    }
}
