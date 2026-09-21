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
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('settings', $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
            } else {
                $repo = $em->getRepository(Setting::class);
                
                $blogTitle = $request->request->get('blog_title');
                $postsPerPage = $request->request->get('posts_per_page');
                
                $titleSetting = $repo->findOneBy(['setting_name' => 'blog_title']);
                if ($titleSetting) $titleSetting->setSettingValue($blogTitle);
                
                $limitSetting = $repo->findOneBy(['setting_name' => 'posts_per_page']);
                if ($limitSetting) $limitSetting->setSettingValue($postsPerPage);
                
                $em->flush();
                $this->addFlash('success', 'Settings updated successfully.');
                return $this->redirectToRoute('app_admin_settings_index');
            }
        }

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
