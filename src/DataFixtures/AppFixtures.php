<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Product;
use App\Helper\ObjectEditorTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
{
    use ObjectEditorTrait;

    private $productsCount = 100;
    private $customersCount = 100;
    private $manager;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->loadProducts();
        $this->loadCustomers();
    }

    private function loadCustomers()
    {
        $faker = Factory::create("fr_FR");

        for ($i = 0; $i < $this->customersCount; $i++) {
            $customer = new Customer();
            $customer->setName($faker->firstName);
            $customer->setSurname($faker->lastName);
            $customer->setEmail($faker->email);
            $customer->setAddress($faker->address);

            $this->manager->persist($customer);
        }

        $this->manager->flush();
    }

    /**
     * Generate fake products and load them in the database
     *
     * @throws \ReflectionException
     */
    private function loadProducts()
    {
        $products = $this->getProductsFromDemo();

        foreach ($products as $product) {
            $this->manager->persist($product);
        }

        $this->manager->flush();
    }

    /**
     * Read the demo file and return products
     *
     * @return array
     * @throws \ReflectionException
     */
    private function getProductsFromDemo()
    {
        $rootDirectory = $this->getRootDirectory();
        $rawJson = file_get_contents("$rootDirectory/demo/products.json");
        $data = \json_decode($rawJson);
        $products = [];

        foreach ($data as $datum) {
            $products[] = $this->generateProduct($datum);
        }

        return $products;
    }

    /**
     * Generate a product from data
     *
     * @param $datum
     * @return Product
     * @throws \ReflectionException
     */
    private function generateProduct($datum)
    {
        $product = new Product();

        $this->updateProperties($product, $datum);
        $product->setModel(str_replace(" ", "-", $product->getModel()));
        $product->setQuantity(mt_rand(10, 10000));
        $product->setPrice(mt_rand(10000, 100000));

        return $product;
    }

    /**
     * Return the root directory
     *
     * @return mixed
     */
    private function getRootDirectory()
    {
        return $this->parameterBag->get('kernel.project_dir');
    }
}
