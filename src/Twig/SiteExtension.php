<?php

namespace App\Twig;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SiteExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getGlobals(): array
    {
        $repo = $this->em->getRepository(Setting::class);
        
        $title = $repo->findOneBy(['setting_name' => 'blog_title'])?->getSettingValue() ?? '~/chernega.blog';
        $footer = $repo->findOneBy(['setting_name' => 'footer_text'])?->getSettingValue() ?? '© ' . date('Y') . ' Мій Блог';

        return [
            'site' => [
                'title' => $title,
                'footer' => $footer,
                'navigation' => [
                    ['url' => '/posts', 'label' => 'posts'],
                    ['url' => '/about', 'label' => 'about'],
                    ['url' => '/contact', 'label' => 'contact'],
                ]
            ]
        ];
    }
}
