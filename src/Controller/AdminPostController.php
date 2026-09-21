<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/posts', name: 'app_admin_post_')]
class AdminPostController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        // TODO: Implement form and logic
        return $this->render('admin/post_form.html.twig', [
            'post' => new Post(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        // TODO: Implement form and logic
        return $this->render('admin/post_form.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Post deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
