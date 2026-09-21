<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $posts = $em->getRepository(Post::class)->findBy([], ['created_at' => 'DESC']);
        $auditLogs = $em->getRepository(AuditLog::class)->findBy([], ['created_at' => 'DESC'], 10);

        return $this->render('admin/dashboard.html.twig', [
            'posts' => $posts,
            'audit_logs' => $auditLogs,
            'current_admin' => $this->getUser(),
        ]);
    }
}
