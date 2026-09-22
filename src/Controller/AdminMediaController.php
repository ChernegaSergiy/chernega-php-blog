<?php

namespace App\Controller;

use App\Entity\MediaFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/admin/media', name: 'app_admin_media_')]
class AdminMediaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $files = $em->getRepository(MediaFile::class)->findBy([], ['created_at' => 'DESC']);
        $totalSize = array_reduce($files, fn($sum, $f) => $sum + $f->getSizeBytes(), 0);

        return $this->render('admin/media/index.html.twig', [
            'media_files' => $files,
            'media_stats' => [
                'total_count' => count($files),
                'limit' => 50,
                'offset' => 0,
                'total_size_human' => round($totalSize / 1024 / 1024, 2) . ' MB'
            ],
            'pagination' => [
                'start' => 1,
                'end' => count($files) > 0 ? count($files) : 0,
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
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('media', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_media_index');
        }

        $files = $request->files->get('media_files');
        if (!is_array($files)) {
            $files = [$files];
        }

        $slugger = new AsciiSlugger();
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/media';
        
        $year = date('Y');
        $month = date('m');
        $relativeDir = "{$year}/{$month}";
        $absoluteDir = "{$uploadDir}/{$relativeDir}";

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $successCount = 0;

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = bin2hex(random_bytes(8)) . '.' . $file->guessExtension();

            try {
                $file->move($absoluteDir, $newFilename);

                $media = new MediaFile();
                $media->setFilename($newFilename);
                $media->setOriginalFilename($newFilename);
                $media->setStoragePath("media/{$newFilename}");
                $media->setMimeType($file->getClientMimeType() ?? 'application/octet-stream');
                $media->setSizeBytes((int) filesize("{$absoluteDir}/{$newFilename}"));

                if (str_starts_with($media->getMimeType(), 'image/')) {
                    $dims = @getimagesize("{$absoluteDir}/{$newFilename}");
                    if ($dims) {
                        $media->setWidth($dims[0]);
                        $media->setHeight($dims[1]);
                    }
                }

                $em->persist($media);
                $successCount++;
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error uploading file: ' . $e->getMessage());
            }
        }

        if ($successCount > 0) {
            $em->flush();
            $this->addFlash('success', "Successfully uploaded {$successCount} file(s).");
        }

        return $this->redirectToRoute('app_admin_media_index');
    }

    #[Route('/cleanup', name: 'cleanup', methods: ['POST'])]
    public function cleanup(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('media', $request->request->get('_token'))) {
            return $this->redirectToRoute('app_admin_media_index');
        }

        $files = $em->getRepository(MediaFile::class)->findAll();
        $projectDir = $this->getParameter('kernel.project_dir');
        
        $removed = 0;
        foreach ($files as $file) {
            $absolutePath = $projectDir . '/public/' . ltrim($file->getStoragePath(), '/');
            if (!file_exists($absolutePath)) {
                $em->remove($file);
                $removed++;
            }
        }

        if ($removed > 0) {
            $em->flush();
            $this->addFlash('success', "Cleanup finished. Removed {$removed} orphaned database records.");
        } else {
            $this->addFlash('success', "Cleanup finished. No orphaned records found.");
        }

        return $this->redirectToRoute('app_admin_media_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, MediaFile $media, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('media', $request->request->get('_token'))) {
            $absolutePath = $this->getParameter('kernel.project_dir') . '/public/' . ltrim($media->getStoragePath(), '/');
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
            
            $em->remove($media);
            $em->flush();
            $this->addFlash('success', 'File deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_media_index');
    }
}
