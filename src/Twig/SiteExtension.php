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

        return [
            'site' => [
                'title' => $title,
                'footer' => '© ' . date('Y') . ' Мій Блог',
                'navigation' => [
                    ['url' => '/about', 'label' => 'about'],
                    ['url' => '/posts', 'label' => 'posts'],
                    ['url' => '/tools/mermaid', 'label' => 'mermaid'],
                    ['url' => '/tools/ul-generator', 'label' => 'ul-generator'],
                ]
            ]
        ];
    }
}
