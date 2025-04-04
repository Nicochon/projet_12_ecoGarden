<?php

namespace App\Controller\Admin;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;



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

//    public function configureActions(Actions $actions): Actions
//    {
//        return $actions
//            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
//                return $action->setHtmlAttributes(['formmethod' => 'GET']); // Forcer POST
//            });
//    }

    public function deleteAction(int $id, AdviceRepository $adviceRepository,  EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            return new RedirectResponse(
                $adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Crud::PAGE_INDEX)
                    ->generateUrl()
            );
        }

        $entityManager->remove($advice);
        $entityManager->flush();

        return new RedirectResponse(
            $adminUrlGenerator
                ->setController(self::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl()
        );
    }

}
