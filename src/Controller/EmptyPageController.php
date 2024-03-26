<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Controller;

use Symfony\Component\HttpFoundation\Response;

class EmptyPageController
{
    public function index(): Response
    {
        return new Response('', Response::HTTP_OK);
    }
}
