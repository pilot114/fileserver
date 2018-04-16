<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{
    /**
     * pass   - для логина в UI
     * secret - для внутреннего использования
     * token  - для доступа по API
     */
    protected $inMemoryUsers = [
        [
            'name' => 'portal',
            'password' => '1234',
            'secret'   => '1234',
            'token'    => '1234',
        ],
    ];

    protected function findUserByToken(string $token)
    {
        foreach ($this->inMemoryUsers as $user) {
            if ($token == $user['token']) {
                return $user;
            }
        }
        return null;
    }

    protected function findUserByPassword(string $password)
    {
        foreach ($this->inMemoryUsers as $user) {
            if ($password == $user['password']) {
                return $user;
            }
        }
        return null;
    }
}