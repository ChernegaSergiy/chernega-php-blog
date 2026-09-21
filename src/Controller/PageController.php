<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/about', name: 'app_page_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('static/about.html.twig');
    }

    #[Route('/contact', name: 'app_page_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('static/contact.html.twig');
    }
}
