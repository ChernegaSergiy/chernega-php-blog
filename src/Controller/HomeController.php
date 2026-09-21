<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // TODO: get limit from settings if available
        $posts = $entityManager->getRepository(Post::class)->findBy([], ['created_at' => 'DESC'], 5);
        $totalPosts = $entityManager->getRepository(Post::class)->count([]);

        return $this->render('home.html.twig', [
            'posts' => $posts,
            'show_all_posts_link' => $totalPosts > 5,
        ]);
    }
}
