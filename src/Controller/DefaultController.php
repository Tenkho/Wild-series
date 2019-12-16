<?php

namespace App\Controller;

use App\Repository\ProgramRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="app_index")
     */
    public function index(ProgramRepository $programRepository): Response
    {
        $programs = $programRepository->findBy([], ['id' => 'DESC'], 6);
        return $this->render('default.html.twig',[
            'programs' => $programs
        ]);
    }


    /**
     * @Route("/my-profil", name="my_profil")
     * @return Response
     */
    public function profilUser(): Response
    {
        return $this->render('security/profil.html.twig');
    }
}
