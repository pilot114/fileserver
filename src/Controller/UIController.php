<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\BaseController;

class UIController extends BaseController
{
    /**
     * @Route("/", name="ui")
     */
    public function index()
    {
        return new Response('<form enctype="multipart/form-data" action="/api/v1/file/create" method="post"><input type="text" name="access_type"><input type="text" name="path"><input type="file" name="file"><input type="submit"></form>');
        return new Response('<form enctype="multipart/form-data" action="/api/v1/file/delete" method="post"><input type="text" name="filename"><input type="text" name="path"><input type="submit"></form>');
    }
}
