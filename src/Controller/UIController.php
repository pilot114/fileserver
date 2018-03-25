<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use App\Controller\BaseController;

class UIController extends BaseController
{
    /**
     * @Route("/", name="ui")
     */
    public function index()
    {
        return $this->json([
        ]);
    }
}
