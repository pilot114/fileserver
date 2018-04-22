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
            'password' => '123456',
            'secret'   => '5ebe2294ecd0e0f08eab7690d2a6ee69',
            'token'    => '94a08da1fecbb6e8b46990538c7b50b2',
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