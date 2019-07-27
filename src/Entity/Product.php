<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "product_show_id",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      )
 * )
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "product_show_model",
 *          parameters = { "model" = "expr(object.getModel())" },
 *          absolute = true
 *      )
 * )
 * @Hateoas\Relation(
 *     "brand",
 *     embedded = @Hateoas\Embedded("expr(object.getBrand())")
 * )
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     */
    private $model;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     */
    private $brand;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     */
    private $quantity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }
}
