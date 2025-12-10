<?php

namespace App\Controller\Admin;

use App\Entity\House;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;


class HouseCrudController extends AbstractCrudController
{

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getEntityFqcn(): string
    {
        return House::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title'),
            TextField::new('description'),
            NumberField::new('price'),
            TextField::new('address'),
            NumberField::new('surface'),
            NumberField::new('rooms'),
            AssociationField::new('owner'),
            // Upload field (NOT mapped automatically)
            TextField::new('imageUpload')
                ->setLabel('Upload Image')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'mapped'   => false,
                    'required' => false,
                ])
                ->onlyOnForms(),

            // Display image on index
            ImageField::new('image')
                ->setBasePath('/uploads/images')
                ->onlyOnIndex(),

        ];
    }

    // Handle create
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->handleImageUpload($entityInstance);
        parent::persistEntity($em, $entityInstance);
    }

    // Handle update
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->handleImageUpload($entityInstance);
        parent::updateEntity($em, $entityInstance);
    }

    private function handleImageUpload(House $house): void
    {
        $request = $this->requestStack->getCurrentRequest();

        // Get uploaded file name inside the form
        $uploadedFile = $request->files->get('House')['imageUpload'] ?? null;

        if ($uploadedFile) {
            $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();
            $uploadedFile->move(
                $this->getParameter('house_images_dir'),
                $newFilename
            );
            $house->setImage($newFilename);
        }
    }
    
}
