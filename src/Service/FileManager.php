<?php

namespace App\Service;


use League\Flysystem\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\Filesystem;

class FileManager
{
    const PUBLIC_ACCESS = 'public';
    const PROTECTED_ACCESS = 'protected';

    private $fs;
    private $userInfo;

    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }

    public function setUserInfo(array $userInfo)
    {
        $this->userInfo = $userInfo;
    }

    public function create($path, UploadedFile $file, $accessType)
    {
        // чекаем что файл полностью загружен
        if (!$file->isValid()) {
            throw new \Exception("Не удалось загрузить файл");
        }
        // тримим весь мусор в пути до файла
        $path = trim($path, "./ \t\n\r\0\x0B");

        if ($accessType == self::PROTECTED_ACCESS) {
            $path = sprintf('%s/%s', $path, $this->generateSecretForFile($path . $file->getClientOriginalName()));
        }

        $realPath = sprintf(
            '%s/%s/%s',
            $this->userInfo['username'],
            $path,
            $file->getClientOriginalName()
        );

        $stream = fopen($file->getRealPath(), 'r+');
        $this->fs->writeStream($realPath, $stream);
        fclose($stream);
    }

    public function delete($path, $filename)
    {
        // тримим весь мусор в пути до файла
        $path = trim($path, "./ \t\n\r\0\x0B");

        // пробуем удалить файл - публичный или защищённый
        $publicPath = sprintf('%s/%s/%s', $this->userInfo['username'], $path, $filename);
        if ($this->fs->has($publicPath)) {
            $this->fs->delete($publicPath);
        } else {
            $path = sprintf('%s/%s', $path, $this->generateSecretForFile($path . $filename));
            $privatePath = sprintf('%s/%s/%s', $this->userInfo['username'], $path, $filename);

            $this->fs->delete($privatePath);
            // временную директорию тоже удаляем
            $this->fs->deleteDir($this->userInfo['username'] . '/' . $path);
        }
    }

    public function list($path) : array
    {
        $path = 'mario/test3';
        $pathWithUser = $this->userInfo['username'] . '/' . $path;
        $files = $this->fs->listContents($pathWithUser);

        $list = [];
        foreach ($files as $file) {
            // защищенные файлы
            if ($this->isProtectedDir($file)) {
                $realFile = $this->fs->listContents($pathWithUser . '/' . $file['basename'])[0];
                $list[] = [
                    'path' => $path . '/' . $realFile['basename'],
                    'url' => 'http://fileserver.local/files/' . $pathWithUser . '/' . $file['basename'] . '/' . $realFile['basename'],
                    'access_type' => self::PROTECTED_ACCESS
                ];
                continue;
            }
            // публичные файлы и папки
            $publicFile = [
                'path' => $path ? $path . '/' . $file['basename'] : $file['basename'],
            ];
            if ($file['type'] == 'file') {
                $publicFile['url'] = 'http://fileserver.local/files/' . $pathWithUser . '/' . $file['basename'];
                $publicFile['access_type'] = self::PUBLIC_ACCESS;
            }
            $list[] = $publicFile;
        }
        return $list;
    }

    public function setAccessType($path, $filename, $accessType)
    {
        $path = 'mario/test3';
        $filename = "/DevIL.dll";

        $pathWithUser = $this->userInfo['username'] . '/' . $path;
        $files = $this->fs->listContents($pathWithUser);

        foreach ($files as $file) {
            // защищенные файлы
            if ($this->isProtectedDir($file)) {
                // ...
            }
            // публичные файлы
            if ($file['type'] == 'file') {
                // ..
            }
        }
    }

    private function generateSecretForFile(string $fullName) : string
    {
        return 'ptd_' . substr(md5($fullName . $this->userInfo['secret']), 0, 6);
    }

    private function isProtectedDir($file)
    {
        return strlen($file['basename']) === 10 && substr($file['basename'], 0, 4) === 'ptd_';
    }
}