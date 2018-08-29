<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\FileManager;

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
        $this->fileManager->setUserInfo($user);
    }

    /**
     * @Route("/api/v1/file/create")
     */
    public function create(Request $request)
    {
        $path = $request->request->get('path');
        $file = $request->files->get('file');
        $accessType = $request->request->get('access_type');

        try {
            $this->fileManager->create($path, $file, $accessType);
        } catch (\Exception $e) {
            return $this->errorResponse(
                sprintf('Не удалось создать файл: %s', $e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->successResponse('Файл создан');
    }

    /**
     * @Route("/api/v1/file/get")
     */
    public function getFile(Request $request)
    {
        $url = $request->request->get('url');
        $format = $request->request->get('format');

        try {
            $file = $this->fileManager->get($url);
        } catch (\Exception $e) {
            return $this->errorResponse(
                sprintf('Не удалось получить файл: %s', $e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($format == 'raw') {
            $response = new Response($file['content']);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $file['name']
            );
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        }

        if ($format == 'base64') {
            return $this->successResponse(['file' => base64_encode($file['content'])]);
        }

        return $this->errorResponse(
            sprintf('Неподдерживаемый формат: %s', $format),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @Route("/api/v1/file/list")
     */
    public function list(Request $request)
    {
        $path = $request->request->get('path');

        try {
            $files = $this->fileManager->list($path);
        } catch (\Exception $e) {
            return $this->errorResponse(
                sprintf('Не удалось получить список файлов: %s', $e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->successResponse($files);
    }

    /**
     * @Route("/api/v1/file/delete")
     */
    public function delete(Request $request)
    {
        $path = $request->request->get('path');
        $filename = $request->request->get('filename');

        try {
            $this->fileManager->delete($path, $filename);
        } catch (\Exception $e) {
            return $this->errorResponse(
                sprintf('Не удалось удалить файл: %s', $e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->successResponse('Файл удалён');
    }

    /**
     * @Route("/api/v1/file/setAccessType")
     */
    public function setAccessType(Request $request)
    {
        $path = $request->request->get('path');
        $filename = $request->request->get('filename');
        $accessType = $request->request->get('access_type');

        try {
            $this->fileManager->setAccessType($path, $filename, $accessType);
        } catch (\Exception $e) {
            return $this->errorResponse(
                sprintf('Не удалось обновить тип доступа: %s', $e->getMessage()),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->successResponse('Тип доступа обновлён');
    }


    private function successResponse($data): JsonResponse
    {
        return $this->json(['error' => null, 'result' => $data]);
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message, 'result' => null])->setStatusCode($status);
    }
}
