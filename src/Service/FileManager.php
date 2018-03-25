<?php

namespace App\Service;


class FileManager
{
    public function getError() : string
    {
        return 'error';
    }

    public function getErrorStatus() : int
    {
        return 403;
    }

    public function create($fullName, $accessType, $file) : bool
    {
        return true;
    }

    public function delete($fullName) : bool
    {
        return true;
    }

    public function list($path) : array
    {
        return [];
    }

    public function setAccessType($fullName, $accessType) : bool
    {
        return true;
    }
}