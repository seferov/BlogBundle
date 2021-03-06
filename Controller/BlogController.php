<?php

/*
 * This file is part of the SeferovBlogBundle package.
 *
 * (c) Farhad Safarov <https://farhadsafarov.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seferov\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BlogController
 * @package Seferov\BlogBundle\Controller
 */
class BlogController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $page = is_numeric($request->query->get('page'))
            ? (int)$request->query->get('page')
            : 1;

        $posts = $this->getDoctrine()
            ->getManager()
            ->getRepository('SeferovBlogBundle:Post')
            ->getListPosts($page, $this->getParameter('seferov_blog_config')['max_posts_per_page']);

        if (!$posts) {
            throw $this->createNotFoundException();
        }

        // SEO
        $this->get('sonata.seo.page.default')->addTitle(ucfirst($this->get('translator')->trans('common.blog')));

        $this->get("white_october_breadcrumbs")
            ->addRouteItem('common.blog', 'seferov_blog_homepage');

        return $this->render('SeferovBlogBundle:Blog:index.html.twig', [
            'posts' => $posts
        ]);
    }

    /**
     * @param $year
     * @param $month
     * @param $titleSlug
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function showAction($year, $month, $titleSlug)
    {
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository('SeferovBlogBundle:Post')->findOneBy([
            'year' => $year,
            'month' => $month,
            'title_slug' => $titleSlug
        ]);

        if (!$post) {
            throw $this->createNotFoundException();
        }

        // Redirect to the original post if there is any
        if ($post->getRedirectTo()) {
            $originalPost = $em->getRepository('SeferovBlogBundle:Post')->find($post->getRedirectTo());

            if (!$originalPost) {
                throw $this->createNotFoundException();
            }

            return $this->redirectToRoute('seferov_blog_show', [
               'year' => $originalPost->getYear(),
                'month' => $originalPost->getMonth(),
                'titleSlug' => $originalPost->getTitleSlug()
            ], 301);
        }

        // SEO
        $this->get('sonata.seo.page.default')->addTitle(
            $this->get('translator')->trans($post->getTitle()).' - '.
            ucfirst($this->get('translator')->trans('common.blog'))
        );

        $this->get("white_october_breadcrumbs")
            ->addRouteItem('common.blog', 'seferov_blog_homepage')
            ->addItem($post->getTitle());

        return $this->render('SeferovBlogBundle:Post:show.html.twig', [
            'post' => $post
        ]);
    }
}
