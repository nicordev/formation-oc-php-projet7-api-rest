<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "product_show_id",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      ),
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(null === object.getId())")
 * )
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "product_show_model",
 *          parameters = { "model" = "expr(object.getModel())" },
 *          absolute = true
 *      ),
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(null === object.getId())")
 * )
 * @Hateoas\Relation(
 *      "edit",
 *      href = @Hateoas\Route(
 *          "product_edit",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      ),
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(null === object.getId())")
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "product_delete",
 *          parameters = { "id" = "expr(object.getId())" },
 *          absolute = true
 *      ),
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(null === object.getId())")
 * )
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Since("1.0")
     * @Serializer\Type("integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     * @Serializer\Type("string")
     */
    private $model;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     * @Serializer\Type("string")
     */
    private $brand;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     * @Serializer\Type("integer")
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"Create"}
     * )
     * @Serializer\Type("integer")
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
