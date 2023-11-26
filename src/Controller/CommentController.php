<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommentController extends AbstractController
{
    #[Route('/comment/desactivate/{id}', name: 'app_comment_desactive')]
    public function desactivate(Request $request, EntityManagerInterface $em, $id): Response
    {
        $comment = $em->getRepository(Comment::class)->find($id);
        $comment->setState('desactivated');
        $em->flush();
        $message = $this->translator->trans('CommentDesactivated');
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_article_show', ['id' => $comment->getArticle()->getId()]);
    }

    #[Route('/comment/activate/{id}', name: 'app_comment_active')]
    public function activate(Request $request, EntityManagerInterface $em, $id): Response
    {
        $comment = $em->getRepository(Comment::class)->find($id);
        $comment->setState('active');
        $em->flush();
        $message = $this->translator->trans('CommentActivated');
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_article_show', ['id' => $comment->getArticle()->getId()]);
    }
}
