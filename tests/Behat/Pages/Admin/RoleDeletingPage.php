<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RoleDeletingPage extends SymfonyPage implements RoleDeletingPageInterface
{
    private const FORMS_LIST_URL = 'gtt_sylius_rbac_admin_access_groups_index';

    public function getRouteName(): string
    {
        return 'gtt_sylius_rbac_admin_access_groups_delete';
    }

    public function delete(int $id): void
    {
        $route = $this->router->generate($this->getRouteName(), ['id' => $id]);
        $item = $this->getDocument()->find('css', sprintf('form[action="%s"]', $route));
        $item->pressButton('Delete');

        if ($this->getDriver()->getStatusCode() === Response::HTTP_FORBIDDEN) {
            throw new AccessDeniedHttpException('ACCESS DENIED', null, Response::HTTP_FORBIDDEN);
        }
    }

    public function open(array $urlParameters = []): void
    {
        parent::open($urlParameters);

        $id = $urlParameters['id'] ?? throw new LogicException('Delete action requires id parameter.');

        $route = $this->router->generate($this->getRouteName(), ['id' => $id]);
        $itemButton = $this->getDocument()->find('css', sprintf('form[action="%s"]', $route));

        if ($itemButton === null) {
            throw new AccessDeniedHttpException('ACCESS DENIED', null, Response::HTTP_FORBIDDEN);
        }
    }

    protected function getUrl(array $urlParameters = []): string
    {
        return $this->makePathAbsolute(
            $this->router->generate(
                self::FORMS_LIST_URL,
            ),
        );
    }
}
