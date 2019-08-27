<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
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
     * @Serializer\Type("integer")
     * @Serializer\Since("1.0")
     * @Serializer\Groups({
     *     "product_list",
     *     "product_detail"
     * })
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Type("string")
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"product_create"}
     * )
     * @Serializer\Groups({
     *     "product_list",
     *     "product_detail"
     * })
     */
    private $model;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Type("string")
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"product_create"}
     * )
     * @Serializer\Groups({
     *     "product_list",
     *     "product_detail"
     * })
     */
    private $brand;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Type("integer")
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"product_create"}
     * )
     * @Serializer\Groups({
     *     "product_list",
     *     "product_detail"
     * })
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Type("integer")
     * @Serializer\Since("1.0")
     * @Assert\NotBlank(
     *     groups = {"product_create"}
     * )
     * @Serializer\Groups({
     *     "product_list",
     *     "product_detail"
     * })
     */
    private $quantity;

    /**
     * @ORM\Column(type="json")
     * @Serializer\Groups({"product_detail"})
     */
    private $detail = [];

    /**
     * @var \DateTime $createdAt
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"product_detail"})
     */
    private $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Groups({"product_detail"})
     */
    private $updatedAt;

    /**
     * @var \DateTime $contentChangedAt
     *
     * @ORM\Column(name="content_changed_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(
     *     on="change",
     *     field={
     *          "model",
     *          "brand",
     *          "quantity",
     *          "price",
     *          "detail"
     *      }
     * )
     * @Serializer\Groups({"product_detail"})
     */
    private $contentChangedAt;

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

    public function getDetail(): ?array
    {
        return $this->detail;
    }

    public function setDetail(array $detail): self
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getContentChangedAt(): \DateTime
    {
        return $this->contentChangedAt;
    }

    /**
     * @param \DateTime $contentChangedAt
     */
    public function setContentChangedAt(\DateTime $contentChangedAt): void
    {
        $this->contentChangedAt = $contentChangedAt;
    }
}
