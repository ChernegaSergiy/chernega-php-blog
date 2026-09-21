<?php

namespace App\Controller;

use App\Entity\MediaFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/media', name: 'app_admin_media_')]
class AdminMediaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $files = $em->getRepository(MediaFile::class)->findBy([], ['created_at' => 'DESC']);
        $totalSize = array_reduce($files, fn($sum, $f) => $sum + $f->getSizeBytes(), 0);

        return $this->render('admin/media/index.html.twig', [
            'files' => $files,
            'media_stats' => [
                'total_count' => count($files),
                'limit' => 50,
                'total_size_human' => round($totalSize / 1024 / 1024, 2) . ' MB'
            ],
            'pagination' => [
                'start' => 1,
                'end' => count($files),
                'current_page' => 1,
                'total_pages' => 1,
                'has_previous' => false,
                'has_next' => false,
                'previous_url' => '',
                'next_url' => ''
            ]
        ]);
}

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(): Response
    {
        return $this->redirectToRoute('app_admin_media_index');
    }

    #[Route('/cleanup', name: 'cleanup', methods: ['POST'])]
    public function cleanup(): Response
    {
        return $this->redirectToRoute('app_admin_media_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(): Response
    {
        return $this->redirectToRoute('app_admin_media_index');
    }
}
