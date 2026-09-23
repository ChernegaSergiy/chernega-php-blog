<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\MediaFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/admin/posts', name: 'app_admin_post_')]
class AdminPostController extends AbstractController
{
    private function processPostForm(Request $request, Post $post, EntityManagerInterface $em): array
    {
        $errors = [];
        
        if (!$this->isCsrfTokenValid('post_form', $request->request->get('_token'))) {
            $errors[] = 'Invalid CSRF token.';
            return $errors;
        }

        $title = $request->request->get('title');
        $content = $request->request->get('content');
        $category = $request->request->get('category');
        
        if (!$title || !$content || !$category) {
            $errors[] = 'Title, content and category are required.';
            return $errors;
        }

        $slugger = new AsciiSlugger();
        $slug = $request->request->get('slug');
        if (!$slug) {
            $slug = strtolower($slugger->slug($title)->toString());
        }

        $post->setTitle($title);
        $post->setContent($content);

        // Process categories (comma-separated)
        $categoryNames = array_map('trim', explode(',', $category));
        $categoryRepo = $em->getRepository(\App\Entity\Category::class);
        
        // Remove old categories not in the new list
        foreach ($post->getCategories() as $existingCat) {
            if (!in_array($existingCat->getName(), $categoryNames)) {
                $post->removeCategory($existingCat);
            }
        }
        
        // Add new categories
        foreach ($categoryNames as $catName) {
            if (!$catName) continue;
            
            $catEntity = $categoryRepo->findOneBy(['name' => $catName]);
            if (!$catEntity) {
                $catEntity = new \App\Entity\Category();
                $catEntity->setName($catName);
                $em->persist($catEntity);
            }
            $post->addCategory($catEntity);
        }
        $post->setStatus(in_array($request->request->get('status'), ['published', 'draft']) ? $request->request->get('status') : 'draft');
        $post->setSlug($slug);
        $post->setMetaTitle($request->request->get('meta_title'));
        $post->setMetaDescription($request->request->get('meta_description'));
        $post->setArticleImage($request->request->get('article_image'));
        
        $createdAtStr = $request->request->get('created_at');
        if ($createdAtStr) {
            try {
                $post->setCreatedAt(new \DateTimeImmutable($createdAtStr));
            } catch (\Exception $e) {
                // Ignore invalid dates
            }
        }
        
        $post->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($post);
        $em->flush();

        return [];
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $post = new Post();
        $errors = [];

        if ($request->isMethod('POST')) {
            $errors = $this->processPostForm($request, $post, $em);
            if (empty($errors)) {
                $this->addFlash('success', 'Post created successfully.');
                return $this->redirectToRoute('app_admin_dashboard');
            }
        }

        return $this->render('admin/post_form.html.twig', [
            'post' => $post,
            'nav_active' => 'create',
            'form_title' => 'Create Post',
            'submit_label' => 'Create',
            'errors' => $errors,
            'recent_media' => $em->getRepository(MediaFile::class)->findBy([], ['created_at' => 'DESC'], 12)
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $errors = $this->processPostForm($request, $post, $em);
            if (empty($errors)) {
                $this->addFlash('success', 'Post updated successfully.');
                return $this->redirectToRoute('app_admin_dashboard');
            }
        }

        return $this->render('admin/post_form.html.twig', [
            'post' => $post,
            'nav_active' => 'dashboard',
            'form_title' => 'Edit Post',
            'submit_label' => 'Update',
            'errors' => $errors,
            'recent_media' => $em->getRepository(MediaFile::class)->findBy([], ['created_at' => 'DESC'], 12)
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Post deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
