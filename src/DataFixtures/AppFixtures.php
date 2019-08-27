<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use App\Helper\ObjectHelperTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
{
    use ObjectHelperTrait;

    private $productsCount = 100;
    private $customersCount = 100;
    private $manager;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    private $domains = [
        "gmail.com",
        "yahoo.com",
        "hotmail.com",
        "orange.com"
    ];
    private $users = [];

    private const HASHED_PASSWORD = '$2y$13$qACYre5/bO7y2jW4n8S.m.Es6vjYpz7x8XBhZxBvckcr.VoC5cvqq'; // pwdSucks!0

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->loadUsers();
        $this->loadProducts();
        $this->loadCustomers();
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

        for ($i = 0, $size = count($userNames); $i < $size; $i++) {
            $user = new User();
            $user->setEmail($this->generateEmail($userNames[$i]))
                ->setPassword(self::HASHED_PASSWORD)
                ->setName($userNames[$i])
                ->setRoles(["ROLE_USER"]);
            $this->manager->persist($user);
            $this->users[] = $user;
        }

        // Easy to get user
        $testUser = new User();
        $testUser->setEmail("user@easy.com")
            ->setPassword(self::HASHED_PASSWORD)
            ->setName("Easy User")
            ->setRoles(["ROLE_USER"]);
        $this->manager->persist($testUser);

        $this->manager->flush();

        // Test admin
        $testAdmin = new User();
        $testAdmin->setEmail("admin@easy.com")
            ->setPassword(self::HASHED_PASSWORD)
            ->setName("Easy Admin")
            ->setRoles(["ROLE_ADMIN"]);
        $this->manager->persist($testAdmin);

        $this->manager->flush();
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
            $customer->setUser($this->users[mt_rand(0, count($this->users) - 1)]);

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
        $datum->detail = (array) $datum->detail;
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

    /**
     * Generate an email from a name
     *
     * @param string $name
     * @return string
     */
    private function generateEmail(string $name)
    {
        $nameParts = explode(" ", $name);
        $domain = $this->domains[mt_rand(0, count($this->domains) - 1)];
        $emailFirstPart = strtolower(implode(".", $nameParts));

        return  "$emailFirstPart@$domain";
    }
}
