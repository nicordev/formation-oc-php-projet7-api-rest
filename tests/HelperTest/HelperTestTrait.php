<?php

namespace App\Tests\HelperTest;


trait HelperTestTrait
{
    protected $keyHeaderToken = "HTTP_X-AUTH-TOKEN";
    protected $testToken = "test_token";

    /**
     * Check if an object is an instance of the same class than the expected entity and can check if its values are the same than the expected object's values
     *
     * @param $entity
     * @param $expectedEntity
     * @param bool $checkValues
     */
    protected function checkEntity($entity, $expectedEntity, bool $checkValues = true)
    {
        $this->assertInstanceOf(get_class($expectedEntity), $entity);
        if ($checkValues) {
            $this->assertEquals(true, $entity == $expectedEntity);
        }
    }

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