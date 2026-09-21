<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegacyRedirectController extends AbstractController
{
    #[Route('/mermaid-diagrams.php', name: 'app_legacy_mermaid', methods: ['GET'])]
    public function mermaidRedirect(): Response
    {
        return $this->redirectToRoute('app_tools_mermaid', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/ul-generator.php', name: 'app_legacy_ul', methods: ['GET'])]
    public function ulRedirect(): Response
    {
        return $this->redirectToRoute('app_home', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/posts.php', name: 'app_legacy_posts', methods: ['GET'])]
    public function postsRedirect(): Response
    {
        return $this->redirectToRoute('app_post_index', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/about.php', name: 'app_legacy_about', methods: ['GET'])]
    public function aboutRedirect(): Response
    {
        return $this->redirectToRoute('app_page_about', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/contact.php', name: 'app_legacy_contact', methods: ['GET'])]
    public function contactRedirect(): Response
    {
        return $this->redirectToRoute('app_page_contact', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/post.php', name: 'app_legacy_post', methods: ['GET'])]
    public function postRedirect(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->query->get('id');
        if ($id) {
            $post = $em->getRepository(Post::class)->find($id);
            if ($post) {
                return $this->redirectToRoute('app_post_show', ['slug' => $post->getSlug()], Response::HTTP_MOVED_PERMANENTLY);
            }
        }
        return $this->redirectToRoute('app_home', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
