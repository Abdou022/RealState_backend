<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserCrudController extends AbstractCrudController
{
    private RequestStack $requestStack;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(RequestStack $requestStack, UserPasswordHasherInterface $passwordHasher)
    {
        $this->requestStack = $requestStack;
        $this->passwordHasher = $passwordHasher;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('email'),
            TextField::new('password')
                ->hideOnIndex()
                ->setHelp('Enter a new password only if you want to change it.'),

            TextField::new('fullName'),
            TextField::new('phone'),
            ArrayField::new('roles'),

            TextField::new('imageUpload')
                ->setLabel('User Image')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'mapped' => false,
                    'required' => false,
                ])
                ->onlyOnForms(),

            ImageField::new('image')
                ->setBasePath('/uploads/profiles')
                ->onlyOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->handlePasswordHashing($entityInstance);
            $this->handleImageUpload($entityInstance);

        }

        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->handlePasswordHashing($entityInstance);
        $this->handleImageUpload($entityInstance);

        parent::updateEntity($em, $entityInstance);
    }

    private function handlePasswordHashing(User $user): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $formData = $request->request->all()['User'] ?? null;

        if (!$formData || empty($formData['password'])) {
            return; // no password entered → do not change it
        }

        $hashed = $this->passwordHasher->hashPassword($user, $formData['password']);
        $user->setPassword($hashed);
    }

    private function handleImageUpload(User $user): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $uploadedFile = $request->files->get('User')['imageUpload'] ?? null;

        if ($uploadedFile) {
            $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();
            $uploadedFile->move(
                $this->getParameter('profiles_directory'),
                $newFilename
            );
            $user->setImage($newFilename);
        }
    }
}
