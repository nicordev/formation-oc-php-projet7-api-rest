<?php

namespace App\tests\HelperTest;


trait HelperTestTrait
{
    protected $keyHeaderToken = "HTTP_X-AUTH-TOKEN";
    protected $testToken = "test_token";

    /**
     * Fetch an entity from the database
     *
     * @param string $entityClassName
     * @param string $criteria
     * @param $value
     * @return mixed
     */
    protected function getEntity(string $entityClassName, string $criteria, $value)
    {
        return $this->client->getContainer()->get('doctrine')->getRepository($entityClassName)->findOneBy([
            $criteria => $value
        ]);
    }

    /**
     * Save an entity in the database
     *
     * @param $entity
     * @return mixed
     */
    protected function saveEntity($entity)
    {
        $manager = $this->client->getContainer()->get('doctrine')->getManager();
        $manager->persist($entity);
        $manager->flush();

        return $entity;
    }

    /**
     * Delete an entity in the database
     *
     * @param $entity
     * @return mixed
     */
    protected function deleteEntity($entity)
    {
        $manager = $this->client->getContainer()->get('doctrine')->getManager();
        $manager->remove($entity);
        $manager->flush();

        return $entity;
    }
}