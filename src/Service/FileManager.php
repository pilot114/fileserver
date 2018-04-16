<?php

namespace App\Service;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use League\Flysystem\MountManager;

/**
 * FileManager инкапсулирует работу с файлами в различных файловых системах,
 * а также определяет особенности их хранения в зависимости от типа доступа.
 *
 * public - файлы доступны по прямой ссылке, ссылка соотвествует пути и имени, задданными пользователем
 * protected - тоже самое, но в ссылке присутствует уникальный хэш
 * private - для файлов, которые не должны быть доступны напрямую
 */
class FileManager
{
    const PUBLIC_ACCESS = 'public';
    const PROTECTED_ACCESS = 'protected';
    const PRIVATE_ACCESS = 'private';

    private $fs;
    private $private_fs;
    private $mm;

    private $userInfo;
    private $host;

    public function __construct(ContainerInterface $container)
    {
        $this->fs = $container->get('public_fs');
        $this->private_fs = $container->get('private_fs');

        // для перемещениями между двумя файловыми системами применяется MountManager
        $this->mm = new MountManager([
            'public_protected' => $this->fs,
            'private' => $this->private_fs,
        ]);

        $this->host = Request::createFromGlobals()->getSchemeAndHttpHost();
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

        if ($accessType == self::PRIVATE_ACCESS) {
            $this->fs = $this->private_fs;
        }

        $realPath = sprintf(
            '%s/%s/%s',
            $this->userInfo['name'],
            $path,
            $file->getClientOriginalName()
        );

        $stream = fopen($file->getRealPath(), 'r+');
        $this->fs->writeStream($realPath, $stream);
        fclose($stream);
    }

    public function delete($path, $filename)
    {
        // TODO: глобально фильтровать $path
        // тримим весь мусор в пути до файла
        $path = trim($path, "./ \t\n\r\0\x0B");

        $simplePath = sprintf('%s/%s/%s', $this->userInfo['name'], $path, $filename);

        // пробуем удалить файл - приватный
        if ($this->private_fs->has($simplePath)) {
            $this->private_fs->delete($simplePath);
            return;
        }

        // пробуем удалить файл - публичный или защищённый
        if ($this->fs->has($simplePath)) {
            $this->fs->delete($simplePath);
        } else {
            $path = sprintf('%s/%s', $path, $this->generateSecretForFile($path . $filename));
            $protectedPath = sprintf('%s/%s/%s', $this->userInfo['name'], $path, $filename);

            $this->fs->delete($protectedPath);
            // временную директорию тоже удаляем
            $this->fs->deleteDir($this->userInfo['name'] . '/' . $path);
        }
    }

    public function list($path) : array
    {
        $pathWithUser = $this->userInfo['name'] . '/' . $path;
        $files = $this->fs->listContents($pathWithUser);

        $list = [];
        foreach ($files as $file) {
            // защищенные файлы
            if ($this->isProtectedDir($file)) {
                $realFile = $this->fs->listContents($pathWithUser . '/' . $file['basename'])[0];
                $list[] = [
                    'path' => $path . '/' . $realFile['basename'],
                    'url' => $this->host . '/files/' . $pathWithUser . '/' . $file['basename'] . '/' . $realFile['basename'],
                    'access_type' => self::PROTECTED_ACCESS,
                    'timestamp' => $realFile['timestamp'],
                    'size' => $realFile['size'],
                    'extension' => $realFile['extension'],
                ];
                continue;
            }
            // публичные файлы и папки
            $publicFile = [
                'path' => $path ? $path . '/' . $file['basename'] : $file['basename'],
            ];
            if ($file['type'] == 'file') {
                $publicFile['url'] = $this->host . '/files/' . $pathWithUser . '/' . $file['basename'];
                $publicFile['access_type'] = self::PUBLIC_ACCESS;
                $publicFile['timestamp'] = $file['timestamp'];
                $publicFile['size'] = $file['size'];
                $publicFile['extension'] = $file['extension'];

            }
            $list[] = $publicFile;
        }

        // приватные файлы также добавляем
        $files = $this->private_fs->listContents($pathWithUser);
        foreach ($files as $file) {
            $privateFile = [
                'path' => $path ? $path . '/' . $file['basename'] : $file['basename'],
            ];
            if ($file['type'] == 'file') {
                $privateFile['url'] = $this->host . '/files/' . $pathWithUser . '/' . $file['basename'];
                $privateFile['access_type'] = self::PRIVATE_ACCESS;
                $privateFile['timestamp'] = $file['timestamp'];
                $privateFile['size'] = $file['size'];
                $privateFile['extension'] = $file['extension'];

            }
            $list[] = $privateFile;
        }

        return $list;
    }

    /**
     * Желательно получать файлы через php только в случае, если они приватные
     */
    public function get($url)
    {
        // TODO
//        $path = $privateUrl;
        $path = 'portal/mario/test3/ptd_67e2e4/DevIL.dll';
        return [
            'content' => $this->fs->read($path),
            'name' => 'DevIL.dll'
        ];
    }

    public function setAccessType($path, $filename, $accessType)
    {
        $pathWithUser = $this->userInfo['name'] . '/' . $path;
        $files = $this->fs->listContents($pathWithUser);

        foreach ($files as $file) {
            // защищенные файлы
            if ($this->isProtectedDir($file)) {
                $realFile = $this->fs->listContents($pathWithUser . '/' . $file['basename'])[0];
                if ($realFile['basename'] === $filename) {
                    // меняем на публичный
                    $newPath = $pathWithUser . '/' . $realFile['basename'];
                    if ($accessType == self::PUBLIC_ACCESS) {
                        $this->fs->rename($realFile['path'], $newPath);
                        // временную директорию удаляем
                        $this->fs->deleteDir($pathWithUser . '/' . $file['basename']);
                    }
                    if ($accessType == self::PRIVATE_ACCESS) {
                        $this->mm->move('public_protected://' . $realFile['path'], 'private://' . $newPath);

                        // временную директорию удаляем
                        $this->fs->deleteDir($pathWithUser . '/' . $file['basename']);
                    }
                    break;
                }
            }

            // публичные файлы
            if ($file['type'] == 'file' && $file['basename'] === $filename) {
                // меняем на защищенный
                if ($accessType == self::PROTECTED_ACCESS) {
                    $dir = $this->generateSecretForFile($pathWithUser . $file['basename']);
                    $this->fs->rename($file['path'], $pathWithUser . '/' . $dir . '/' . $file['basename']);
                }
                // меняем на приватный
                if ($accessType == self::PRIVATE_ACCESS) {
                    $this->mm->move(
                        'public_protected://' . $realFile['path'],
                        'private://' . $pathWithUser . '/' . $file['basename']
                    );
                }
                break;
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