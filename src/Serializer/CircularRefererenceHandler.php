<?php

namespace App\Serializer;

use App\Entity\User;
use Symfony\Component\Routing\RouterInterface;

class CircularRefererenceHandler
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function __invoke($object)
    {
        switch ($object) {
            case $object instanceof User:
                return $this->router->generate('app_api_v1_user_currentUser');
        }
        return $object->getId();
    }
}