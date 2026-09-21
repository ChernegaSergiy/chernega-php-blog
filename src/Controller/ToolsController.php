<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tools', name: 'app_tools_')]
class ToolsController extends AbstractController
{
    #[Route('/mermaid', name: 'mermaid', methods: ['GET'])]
    public function mermaid(): Response
    {
        return $this->render('tools/mermaid.html.twig', [
            'page_title' => 'Mermaid Live Editor',
        ]);
    }

    #[Route('/ul-generator', name: 'ul_generator', methods: ['GET'])]
    public function ulGenerator(): Response
    {
        return $this->render('tools/license-generator.html.twig', [
            'page_title' => 'CSSM Unlimited License Generator',
        ]);
    }
}
