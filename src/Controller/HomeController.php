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
        $settingRepo = $entityManager->getRepository(\App\Entity\Setting::class);
        $limitSetting = $settingRepo->findOneBy(['setting_name' => 'posts_per_page']);
        $limit = $limitSetting ? (int)$limitSetting->getSettingValue() : 5;

        $posts = $entityManager->getRepository(Post::class)->findBy(['status' => 'published'], ['created_at' => 'DESC'], $limit);
        $totalPosts = $entityManager->getRepository(Post::class)->count(['status' => 'published']);

        return $this->render('home.html.twig', [
            'posts' => $posts,
            'show_all_posts_link' => $totalPosts > 5,
        ]);
    }
}
