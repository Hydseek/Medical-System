<?php

namespace App\Controller\Admin;

use App\Entity\Patient;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\QueryBuilder;

class AdminCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getEntityFqcn(): string
    {
        return Patient::class;
    }

    /**
     * Filter to show only admins (users with ROLE_ADMIN)
     */
    public function createIndexQueryBuilder($searchDto, $entityDto, $context, $repository): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $context, $repository);
        
        // Filter to show only users with ROLE_ADMIN
        $qb->andWhere('entity.roles LIKE :admin_role')
           ->setParameter('admin_role', '%ROLE_ADMIN%');
        
        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Admins can only view administrators, not create/edit/delete them
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE)
                       ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Administrator')
            ->setEntityLabelInPlural('Administrators')
            ->setPageTitle(Crud::PAGE_INDEX, 'Manage Administrators')
            ->setPageTitle(Crud::PAGE_NEW, 'Create New Administrator')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit Administrator')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('prenom'),
            EmailField::new('email'),
            TextField::new('motDePasse')->setFormType(RepeatedType::class)->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Password', 'row_attr'=>['class'=>"col-md-6 col-xxl-5"]],
                'second_options' => ['label' => 'Confirm Password', 'row_attr'=>['class'=>"col-md-6 col-xxl-5"]],
                'mapped' => false,
            ])
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms(),
            DateTimeField::new('dateNaissance'),
            TextField::new('adresse'),
            TextField::new('telephone'),
            ChoiceField::new('roles')
                ->setChoices([
                    'Administrator' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderAsBadges(),
        ];
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        return $this->addPasswordEventListener($formBuilder);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        return $this->addPasswordEventListener($formBuilder);
    }

    public function addPasswordEventListener(FormBuilderInterface $formBuilder)
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    public function hashPassword()
    {
        return function($event){
            $form = $event->getForm();
            if(!$form->isValid()){
                return;
            }

            $patient = $form->getData();
            $password = $form->get('motDePasse')->getData();
            if($password === null){
                return;
            }

            $hash = $this->passwordHasher->hashPassword($patient, $password);
            $patient->setMotDePasse($hash);
        };
    }
}
