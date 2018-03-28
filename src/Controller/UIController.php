<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\BaseController;

class UIController extends BaseController
{
    /**
     * @Route("/")
     */
    public function index()
    {
        $html = $this->getParameter('kernel.project_dir') . '/public/index.html';
        return new Response(file_get_contents($html));
    }
}
