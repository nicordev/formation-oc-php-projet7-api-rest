<?php

namespace App\Tests\TestHelperTrait;


use App\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trait UnitTestHelperTrait
{
    private function setId($entity, int $id)
    {
        $reflectionEntity = new \ReflectionObject($entity);
        $reflectionId = $reflectionEntity->getProperty("id");
        $reflectionId->setAccessible(true);
        $reflectionId->setValue($entity, $id);
    }

    private function createSecurityContainerMock(
        User $user,
        $entity = null,
        ?string $voterAction = null,
        bool $isGranted = true
    ) {
        // Token
        $tokenMock = $this->prophesize(TokenInterface::class);
        $tokenMock
            ->getUser()
            ->willReturn($user)
        ;
        $tokenStorageMock = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageMock
            ->getToken()
            ->willReturn($tokenMock)
        ;
        $containerMock = $this->prophesize(ContainerInterface::class);
        $containerMock
            ->has('security.token_storage')
            ->willReturn(true)
        ;
        $containerMock
            ->get('security.token_storage')
            ->willReturn($tokenStorageMock)
        ;

        // Authorization
        if ($voterAction) {
            $authorizationCheckerMock = $this->prophesize(AuthorizationCheckerInterface::class);
            $authorizationCheckerMock
                ->isGranted($voterAction, $entity)
                ->willReturn($isGranted)
            ;
            $containerMock
                ->has('security.authorization_checker')
                ->willReturn(true)
            ;
            $containerMock
                ->get('security.authorization_checker')
                ->willReturn($authorizationCheckerMock)
            ;
        }

        return $containerMock;
    }
}