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
// Importez ChoiceField pour les rôles
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

class PatientCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    // Constructeur pour injecter le service UserPasswordHasherInterface
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getEntityFqcn(): string
    {
        return Patient::class;
    }

    /**
     * Filter to show only actual patients (exclude admins)
     */
    public function createIndexQueryBuilder($searchDto, $entityDto, $context, $repository): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $context, $repository);
        
        // Filter out users with ROLE_ADMIN by checking the roles column
        // SQLite, MySQL compatible approach
        $qb->andWhere('entity.roles NOT LIKE :admin_role')
           ->setParameter('admin_role', '%ROLE_ADMIN%');
        
        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('prenom'),
            EmailField::new('email'),
            // Le champ mot de passe est maintenant visible mais non obligatoire
            TextField::new('motDePasse')->setFormType(RepeatedType::class)->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Password', 'row_attr'=>[ 'class'=>"col-md-6 col-xxl-5"]],
                'second_options' => ['label' => 'Confirm Password', 'row_attr'=>[ 'class'=>"col-md-6 col-xxl-5"]],
                'mapped' => false,
            ])
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms(),
            DateTimeField::new('dateNaissance'),
            TextField::new('adresse'),
            TextField::new('telephone'),
            // C'est la partie CORRIGÉE pour le champ 'roles'
            ChoiceField::new('roles')
                ->setChoices([
                    'Patient' => 'ROLE_PATIENT',
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

    public function addPasswordEventListener(FormBuilderInterface $formBuilder){
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    // Modification de la méthode hashPassword()
    public function hashPassword(){
        return function($event){
            $form = $event->getForm();
            if(!$form->isValid()){
                return;
            }

            // Récupérer l'objet Patient depuis les données du formulaire
            $patient = $form->getData();

            // Vérifier si le mot de passe est renseigné
            $password = $form->get('motDePasse')->getData();
            if($password === null){
                return;
            }

            // Hacher le mot de passe et le setter
            $hash = $this->passwordHasher->hashPassword($patient, $password);
            $patient->setMotDePasse($hash); // Le mot de passe haché est défini ici
        };
    }
}