<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoryController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/category/list', name: 'app_category')]
    public function index(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findAll();
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/category/edit/{id}', name: 'app_category_edit')]
    public function edit(EntityManagerInterface $em, Request $request, $id): Response
    {
        $category = $em->getRepository(Category::class)->find($id);
        if (!$category) {
            $message = $this->translator->trans('CommentDoesNotExist');
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_category');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $message = $this->translator->trans('CategoryUpdated');
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_category', ['id' => $category->getId()]);
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/category/create', name: 'app_category_create')]
    public function create(EntityManagerInterface $em, Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $message = $this->translator->trans('CategoryCreated');
            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/category/delete/{id}', name: 'app_category_delete')]
    public function delete(EntityManagerInterface $em, $id): Response
    {
        $category = $em->getRepository(Category::class)->find($id);
        $em->remove($category);
        $em->flush();

        $message = $this->translator->trans('CategoryDeleted');
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_category');
    }
}
