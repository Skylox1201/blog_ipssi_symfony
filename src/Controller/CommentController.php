<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    #[Route('/comment/desactivate/{id}', name: 'app_comment_desactive')]
    public function desactivate(Request $request, EntityManagerInterface $em, $id): Response
    {
        $comment = $em->getRepository(Comment::class)->find($id);
        $comment->setState('desactivated');
        $em->flush();
        $this->addFlash('success', 'Comment desactivated!');
        return $this->redirectToRoute('app_article_show', ['id' => $comment->getArticle()->getId()]);
    }

    #[Route('/comment/activate/{id}', name: 'app_comment_active')]
    public function activate(Request $request, EntityManagerInterface $em, $id): Response
    {
        $comment = $em->getRepository(Comment::class)->find($id);
        $comment->setState('active');
        $em->flush();
        $this->addFlash('success', 'Comment activated!');
        return $this->redirectToRoute('app_article_show', ['id' => $comment->getArticle()->getId()]);
    }
}
