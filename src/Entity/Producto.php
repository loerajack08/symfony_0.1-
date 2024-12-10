<?php

namespace App\Entity;

use App\Repository\ProductoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\Table(name: 'producto')] 
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_producto')] // Nombre de la columna en la base de datos
    private ?int $id = null;

    #[ORM\Column(name: 'clave_producto', length: 100)] // Nombre de la columna en la base de datos
    private ?string $clave = null;

    #[ORM\Column(name: 'Nombre', length: 200)] // Nombre de la columna en la base de datos
    private ?string $nombre = null;

    #[ORM\Column(name: 'precio')] // Nombre de la columna en la base de datos
    private ?float $precio = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClave(): ?string
    {
        return $this->clave;
    }

    public function setClave(string $clave): static
    {
        $this->clave = $clave;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }
}
