<?php

namespace App\Twig;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getGlobals(): array
    {
        $settings = $this->em->getRepository(Setting::class)->findAll();
        $site = [
            'title' => 'Мій Блог',
            'footer' => '© ' . date('Y') . ' Мій Блог',
            'navigation' => [
                ['url' => '/', 'label' => 'Головна'],
                ['url' => '/posts', 'label' => 'Всі пости'],
                ['url' => '/about', 'label' => 'Про автора'],
                ['url' => '/contact', 'label' => 'Контакти']
            ]
        ];

        foreach ($settings as $setting) {
            if ($setting->getSettingName() === 'blog_title') {
                $site['title'] = $setting->getSettingValue();
            } elseif ($setting->getSettingName() === 'footer_text') {
                $site['footer'] = str_replace('{year}', date('Y'), $setting->getSettingValue());
            } elseif ($setting->getSettingName() === 'navigation') {
                $site['navigation'] = json_decode($setting->getSettingValue(), true);
            }
        }

        return [
            'site' => $site,
        ];
    }
}
