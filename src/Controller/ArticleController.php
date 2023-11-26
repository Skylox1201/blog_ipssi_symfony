<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Entity\Category;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleController extends AbstractController
{

    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    #[Route('/', name: 'app_article')]
    public function index(EntityManagerInterface $em): Response
    {
        $articles = $em->getRepository(Article::class)->findBy(['state' => 'public']);
        $categories = $em->getRepository(Category::class)->findAll();
        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/article/list', name: 'app_article_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $articles = $em->getRepository(Article::class)->findAll();
        $categories = $em->getRepository(Category::class)->findAll();
        return $this->render('article/list.html.twig', [
            'articles' => $articles,
            'categories' => $categories
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/article/create', name: 'app_article_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $article = new Article($user);
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($article);
            $em->flush();

            $message = $this->translator->trans('ArticleCreated');
            $this->addFlash('success', $message);

            if ($article->getState() == 'public')
                return $this->redirectToRoute('app_article');
            else
                return $this->redirectToRoute('app_article_edit', ['id' => $article->getId()]);
        } else {
            return $this->render('article/create.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }

    #[Route('/article/edit/{id}', name: 'app_article_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, EntityManagerInterface $em, $id): Response
    {
        $article = $em->getRepository(Article::class)->find($id);
        if (!$article) {
            $message = $this->translator->trans('ArticleDoesNotExist');
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_article_list');
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('state')->getData() == 'public') {
                $article->setPublicationDate(new \DateTimeImmutable());
            }
            $em->flush();
            $message = $this->translator->trans('ArticleUpdated');
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_article_edit', ['id' => $article->getId()]);
        } else {
            return $this->render('article/edit.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }

    #[Route('/article/show/{id}', name: 'app_article_show')]
    public function show(EntityManagerInterface $em, Request $request, $id): Response
    {
        $article = $em->getRepository(Article::class)->find($id);
        if (!$article || $article->getState() == 'draft') {
            $message = $this->translator->trans('ArticleDoesNotExist');
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_article_list');
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $comments = $em->getRepository(Comment::class)->findBy(['article' => $article]);
        } else {
            $comments = $em->getRepository(Comment::class)->findBy(['article' => $article, 'state' => 'active']);
        }
        $commentForm = $this->createForm(CommentType::class);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $comment->setArticle($article);
            $comment->setAuthor($this->getUser());

            $em->persist($comment);
            $em->flush();
            
            $message = $this->translator->trans('CommentCreated');
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'commentForm' => $commentForm->createView(),
            'comments' => $comments
        ]);
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/article/delete/{id}', name: 'app_article_delete')]
    public function delete(EntityManagerInterface $em, $id): Response
    {
        $article = $em->getRepository(Article::class)->find($id);
        if (!$article) {
            $message = $this->translator->trans('ArticleDoesNotExist');
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_article_list');
        }

        $em->remove($article);
        $em->flush();
        $message = this->translator->trans('ArticleDeleted');
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_article_list');
    }

    #[Route('/article/public/category/{id}', name: 'app_article_public_category')]
    public function category(EntityManagerInterface $em, SerializerInterface $serializer, $id): JsonResponse
    {
        if ($id == 'all') {
            $articles = $em->getRepository(Article::class)->findBy(['state' => 'public']);
        } else {
            $category = $em->getRepository(Category::class)->find($id);
            if (!$category) {
                return new JsonResponse(['error' => 'This category does not exist.'], JsonResponse::HTTP_NOT_FOUND);
            }
            $articles = $em->getRepository(Article::class)->findBy(['category' => $category, 'state' => 'public']);
        }

        $jsonContent = $serializer->serialize($articles, 'json', ['groups' => 'article:read']);

        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/article/draft/category/{id}', name: 'app_article_draft_category')]
    public function draftCategory(EntityManagerInterface $em, SerializerInterface $serializer, $id): JsonResponse
    {
        if ($id == 'all') {
            $articles = $em->getRepository(Article::class)->findBy(['state' => 'draft']);
        } else {
            $category = $em->getRepository(Category::class)->find($id);
            if (!$category) {
                return new JsonResponse(['error' => 'This category does not exist.'], JsonResponse::HTTP_NOT_FOUND);
            }
            $articles = $em->getRepository(Article::class)->findBy(['category' => $category, 'state' => 'draft']);
        }

        $jsonContent = $serializer->serialize($articles, 'json', ['groups' => 'article:read']);

        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/article/all/category/{id}', name: 'app_article_all_category')]
    public function allCategory(EntityManagerInterface $em, SerializerInterface $serializer, $id): JsonResponse
    {
        if ($id == 'all') {
            $articles = $em->getRepository(Article::class)->findAll();
        } else {
            $category = $em->getRepository(Category::class)->find($id);
            if (!$category) {
                return new JsonResponse(['error' => 'This category does not exist.'], JsonResponse::HTTP_NOT_FOUND);
            }
            $articles = $em->getRepository(Article::class)->findBy(['category' => $category]);
        }

        $jsonContent = $serializer->serialize($articles, 'json', ['groups' => 'article:read']);

        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }
}
