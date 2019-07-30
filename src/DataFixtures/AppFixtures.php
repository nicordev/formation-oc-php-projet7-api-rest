<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use App\Helper\ObjectEditorTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
{
    use ObjectEditorTrait;

    private $productsCount = 100;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    private $manager;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->loadProducts();
        $this->loadUsers();
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
     * Generate fake users and load them in the database
     */
    private function loadUsers()
    {
        $userNames = [
            "Extenso Telecom",
            "AD Com",
            "Phonever",
            "Coriolis Telecom",
            "Radiotel",
            "Phone Store"
        ];

        foreach ($userNames as $userName) {
            $user = new User();
            $user->setEmail($this->generateEmail($userName))
                ->setPassword("mdp")
                ->setRoles(["ROLE_USER"])
                ->setApiToken("test_token");
            $this->manager->persist($user);
        }

        $this->manager->flush();
    }

    private function generateEmail(string $name)
    {
        $email = strtolower($name);
        $email = str_replace(" ", ".", $email);
        $domain = explode(".", $email)[0] . '.com';

        return $email . '@' . $domain;
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
