<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PackageRepository")
 */
class Package
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dataNadania;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $kodKrajuNadania;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $kodKrajuPrzezn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $kodRodzPrzes;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $numer;

    /**
     * @ORM\Column(type="boolean")
     */
    private $zakonczonoObsluge;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $masa;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $format;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dataDoreczenia;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataNadania(): ?\DateTimeInterface
    {
        return $this->dataNadania;
    }

    public function setDataNadania(\DateTimeInterface $dataNadania): self
    {
        $this->dataNadania = $dataNadania;

        return $this;
    }

    public function getKodKrajuNadania(): ?string
    {
        return $this->kodKrajuNadania;
    }

    public function setKodKrajuNadania(?string $kodKrajuNadania): self
    {
        $this->kodKrajuNadania = $kodKrajuNadania;

        return $this;
    }

    public function getKodKrajuPrzezn(): ?string
    {
        return $this->kodKrajuPrzezn;
    }

    public function setKodKrajuPrzezn(?string $kodKrajuPrzezn): self
    {
        $this->kodKrajuPrzezn = $kodKrajuPrzezn;

        return $this;
    }

    public function getKodRodzPrzes(): ?string
    {
        return $this->kodRodzPrzes;
    }

    public function setKodRodzPrzes(string $kodRodzPrzes): self
    {
        $this->kodRodzPrzes = $kodRodzPrzes;

        return $this;
    }

    public function getNumer(): ?string
    {
        return $this->numer;
    }

    public function setNumer(string $numer): self
    {
        $this->numer = $numer;

        return $this;
    }

    public function getZakonczonoObsluge(): ?bool
    {
        return $this->zakonczonoObsluge;
    }

    public function setZakonczonoObsluge(bool $zakonczonoObsluge): self
    {
        $this->zakonczonoObsluge = $zakonczonoObsluge;

        return $this;
    }

    public function getMasa(): ?float
    {
        return $this->masa;
    }

    public function setMasa(?float $masa): self
    {
        $this->masa = $masa;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDataDoreczenia(): ?\DateTimeInterface
    {
        return $this->dataDoreczenia;
    }

    public function setDataDoreczenia(?\DateTimeInterface $dataDoreczenia): self
    {
        $this->dataDoreczenia = $dataDoreczenia;

        return $this;
    }
}