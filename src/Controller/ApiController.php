<?php

namespace App\Controller;

use App\Service\FileManager;
use App\Controller\BaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends BaseController
{
    private $fileManager;

    // вообще, обычно такие вещи делаются в ApiKeyAuthenticator, но тут я сделал максимально просто
    function __construct(FileManager $fm, ContainerInterface $container)
    {
        $this->container = $container;
        $request = Request::createFromGlobals();
        $token = $request->headers->get('token');

        if (empty($token)) {
            $this->errorResponse('Токен пустой', Response::HTTP_BAD_REQUEST)->send();
            die();
        }

        $user = $this->findUserByToken($token);
        if (empty($user)) {
            $this->errorResponse('Пользователь не найден', Response:: HTTP_NOT_FOUND)->send();
            die();
        }

        $this->fileManager = $fm;
    }

    /**
     * @Route("/api/v1/file/create")
     */
    public function create(Request $request)
    {
        $fullName   = $request->request->get('full_name');
        $accessType = $request->request->get('access_type');
        $file       = $request->files->get('file');

        if (!$this->fileManager->create($fullName, $accessType, $file)) {
            return $this->errorResponse(
                sprintf('Не удалось создать файл: %s', $this->fileManager->getError()),
                $this->fileManager->getErrorStatus()
            );
        }
        return $this->successResponse('Файл создан');
    }

    /**
     * @Route("/api/v1/file/delete")
     */
    public function delete(Request $request)
    {
        $fullName = $request->request->get('full_name');

        if (!$this->fileManager->delete($fullName)) {
            return $this->errorResponse(
                sprintf('Не удалось удалить файл: %s', $this->fileManager->getError()),
                $this->fileManager->getErrorStatus()
            );
        }
        return $this->successResponse('Файл удалён');
    }

    /**
     * @Route("/api/v1/file/list")
     */
    public function list(Request $request)
    {
        $path = $request->request->get('path');

        $files = $this->fileManager->list($path);
        return $this->successResponse($files);
    }

    /**
     * @Route("/api/v1/file/setAccessType")
     */
    public function setAccessType(Request $request)
    {
        $fullName   = $request->request->get('full_name');
        $accessType = $request->request->get('access_type');

        if (!$this->fileManager->setAccessType($fullName, $accessType)) {
            return $this->errorResponse(
                sprintf('Не удалось обновить тип доступа: %s', $this->fileManager->getError()),
                $this->fileManager->getErrorStatus()
            );
        }
        return $this->successResponse('Тип доступа обновлён');
    }


    private function successResponse($data) : JsonResponse
    {
        return $this->json(['error' => null, 'result' => $data]);
    }

    private function errorResponse(string $message, int $status) : JsonResponse
    {
        return $this->json(['error' => $message, 'result' => null])->setStatusCode($status);
    }
}
