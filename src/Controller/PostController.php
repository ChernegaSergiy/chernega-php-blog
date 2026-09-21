<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/posts', name: 'app_post_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search');
        $category = $request->query->get('category');
        $page = max(1, $request->query->getInt('page', 1));
        // TODO: Get limit from settings
        $limit = 10;

        $qb = $entityManager->getRepository(Post::class)->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.title LIKE :search OR p.content LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        $qb->orderBy('p.created_at', 'DESC');

        // Pagination calculations
        $totalItemsQb = clone $qb;
        $totalItems = count($totalItemsQb->select('p.id')->getQuery()->getSingleColumnResult());
        $totalPages = ceil($totalItems / $limit);

        $posts = $qb->setFirstResult(($page - 1) * $limit)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        $categories = $entityManager->getRepository(Post::class)->createQueryBuilder('p')
            ->select('p.category')
            ->distinct()
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        $commandDisplay = 'list';
        if ($category) {
            $commandDisplay = "list --category \"$category\"";
        } elseif ($search) {
            $commandDisplay = "search \"$search\"";
        }

        return $this->render('posts/index.html.twig', [
            'posts' => $posts,
            'search_query' => $search,
            'category_filter' => $category,
            'categories' => $categories,
            'command_display' => $commandDisplay,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/post/{slug}', name: 'app_post_show', methods: ['GET'])]
    public function show(string $slug, EntityManagerInterface $entityManager, Request $request): Response
    {
        $post = $entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug]);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        return $this->render('posts/show.html.twig', [
            'post' => $post,
            'canonical_url' => $request->getSchemeAndHttpHost() . $this->generateUrl('app_post_show', ['slug' => $slug]),
        ]);
    }
}
