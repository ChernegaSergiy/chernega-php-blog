<?php

namespace App\Controller;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/settings', name: 'app_admin_settings_')]
class AdminSettingsController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $settingsRaw = $em->getRepository(Setting::class)->findAll();
        $settings = [];
        foreach ($settingsRaw as $setting) {
            $settings[$setting->getSettingName()] = $setting->getSettingValue();
        }

        return $this->render('admin/settings.html.twig', [
            'settings' => $settings,
            'errors' => []
        ]);
    }
}
