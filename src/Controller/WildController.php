<?php

// src/Controller/WildController.php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Episode;
use App\Entity\Program;
use App\Entity\Season;
use App\Entity\User;
use App\Form\CategoryType;
use App\Form\CommentType;
use App\Form\ProgramSearchType;
use App\Service\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/wild")
 **/
class WildController extends AbstractController
{
    /**
     * @Route("/", name="wild_index")
     *
     *@return Response
     */
    public function index() :Response
    {
        $programs = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findAllWithCategoriesAndActors();
        if(!$programs) {
            throw $this->createNotFoundException(
                "No program found in program's table"
            );
        }

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        return $this->render("wild/index.html.twig", [
                "programs" => $programs,
                'form' => $form->createView(),
                ]
        );
    }

    /**
     * @param string $slug The slugger
     * @Route("/show/{slug<^[a-z0-9-]+$>}", defaults={"slug" = null}, name="wild_show")
     * @return Response
     */
    public function showByProgram(?string $slug):Response
    {
        if (!$slug) {
            throw $this
                ->createNotFoundException('No slug has been sent to find a program in program\'s table.');
        }
        $slug = preg_replace(
            '/-/',
            ' ', ucwords(trim(strip_tags($slug)), "-")
        );
        $program = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findOneBy(['title' => mb_strtolower($slug)]);
        if (!$program) {
            throw $this->createNotFoundException(
                'No program with '.$slug.' title, found in program\'s table.'
            );
        }

        $seasons = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findBy([
                'program' => $program,
            ]);

        return $this->render('wild/show.html.twig', [
            'program' => $program,
            'slug'  => $slug,
            'seasons' => $seasons,
        ]);
    }

    /**
     * @Route("/category/{categoryName}", name="show_category")
     * @return Response
     */
    public function showByCategory(string $categoryName)
    {
        if (!$categoryName) {
            throw $this
                ->createNotFoundException('No category has been sent to find a category.');
        }
        $categoryName = preg_replace(
            '/-/',
            ' ', ucwords(trim(strip_tags($categoryName)), "-")
        );
        $categoryName = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findBy(
                ['name' => $categoryName]
            );
        $program = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findBy(
                ["category" => $categoryName],
                ["id" => "DESC"],
                3
            );
        return $this->render('wild/category.html.twig', [
            'category' => $categoryName,
            'programs' => $program
        ]);
    }

    /**
     * @param int $id
     * @return Response
     * @Route("/season/{id<^[0-9-]+$>}", defaults={"id" = null}, name="show_season")
     */
    public function showBySeason(int $id) : Response
    {
        if (!$id) {
            throw $this
                ->createNotFoundException('No season has been find in season\'s table.');
        }
        $season = $this->getDoctrine()
            ->getRepository(Season::class)
            ->find($id);
        $program = $season->getProgram();
        $episodes = $season->getEpisodes();
        if (!$season) {
            throw $this->createNotFoundException(
                'No season with '.$id.' season, found in Season\'s table.'
            );
        }
        return $this->render('wild/season.html.twig', [
            'season'   => $season,
            'program'  => $program,
            'episodes' => $episodes,
        ]);
    }

    /**
     *
     * @Route("/episode/{slug}",  name="show_episode")
     *
     */
    public function showEpisode(Episode $episode): Response
    {
        $comments = $episode->getComments();
        $season   = $episode->getSeason();
        $program  = $season->getProgram();
        return $this->render('wild/episode.html.twig', [
            'episode'  => $episode,
            'season'   => $season,
            'program'  => $program,
            'comments' => $comments,
        ]);
    }

    /**
     *
     * @Route("/actor/{slug}", name="show_actor").
     */
    public function showActor(Actor $actor) :Response
    {
        $programs = $actor->getPrograms();
        return $this->render('wild/actor.html.twig', [
            'programs' => $programs,
            'actor' => $actor,
        ]);
    }

    /**
     * @Route("/comment/{id}", name="wild_comment", methods={"GET","POST"})
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function newComment(Request $request, $id): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $author = $this->getUser();
            $comment->setUser($author);
            $episode = $this->getDoctrine()->getRepository(Episode::class)->find($id);
            $comment->setEpisode($episode);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
            $slug = $episode->getSlug();
            return $this->redirectToRoute('show_episode', ['slug' => $slug]);
        }

        return $this->render('form/formComment.html.twig', [
            'comment' => $comment,
            'form'    => $form->createView(),
        ]);
    }

    /**
     * @Route("/deleteComment/{id}", name="wild_delete", methods={"DELETE", "GET"})
     * @param EntityManagerInterface $entityManager
     * @param int $id
     * @return Response
     */
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        $comment = $this->getDoctrine()->getRepository(Comment::class)->find($id);
        $episode= $comment->getEpisode();
        $slug = $episode->getSlug();
        $entityManager->remove($comment);
        $entityManager->flush();
        return $this->redirectToRoute('show_episode', ['slug' => $slug]);
    }
}
