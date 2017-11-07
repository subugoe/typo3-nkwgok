<?php

namespace Subugoe\Nkwgok\Domain\Model;

class Term
{
    /**
     * @var string
     */
    private $ppn;

    /**
     * @var string
     */
    private $search = '';

    /**
     * @var Description
     */
    private $description;

    /**
     * @var string
     */
    private $parent;

    /**
     * @var int
     */
    private $hierarchy = 0;

    /**
     * @var int
     */
    private $childCount;

    /**
     * @var int
     */
    private $hitCount = -1;

    /**
     * @var int
     */
    private $statusId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $notation = '';

    /**
     * @var string
     */
    private $tags = '';

    /**
     * @var int
     */
    private $totalHitCounts = -1;

    /**
     * @return string
     */
    public function getPpn(): string
    {
        return $this->ppn;
    }

    /**
     * @param string $ppn
     *
     * @return Term
     */
    public function setPpn(string $ppn): self
    {
        $this->ppn = $ppn;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearch(): string
    {
        return $this->search;
    }

    /**
     * @param string $search
     *
     * @return Term
     */
    public function setSearch(string $search): self
    {
        $this->search = $search;

        return $this;
    }

    /**
     * @return Description
     */
    public function getDescription(): Description
    {
        return $this->description;
    }

    /**
     * @param Description $description
     *
     * @return Term
     */
    public function setDescription(Description $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getParent(): string
    {
        return $this->parent;
    }

    /**
     * @param string $parent
     *
     * @return Term
     */
    public function setParent(string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return int
     */
    public function getHierarchy(): int
    {
        return $this->hierarchy;
    }

    /**
     * @param int $hierarchy
     *
     * @return Term
     */
    public function setHierarchy(int $hierarchy): self
    {
        $this->hierarchy = $hierarchy;

        return $this;
    }

    /**
     * @return int
     */
    public function getChildCount(): int
    {
        return $this->childCount;
    }

    /**
     * @param int $childCount
     *
     * @return Term
     */
    public function setChildCount(int $childCount): self
    {
        $this->childCount = $childCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    /**
     * @param int $hitCount
     *
     * @return Term
     */
    public function setHitCount(int $hitCount): self
    {
        $this->hitCount = $hitCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusId(): int
    {
        return $this->statusId;
    }

    /**
     * @param int $statusId
     *
     * @return Term
     */
    public function setStatusId(int $statusId): self
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Term
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotation(): string
    {
        return $this->notation;
    }

    /**
     * @param string $notation
     *
     * @return Term
     */
    public function setNotation(string $notation): self
    {
        $this->notation = $notation;

        return $this;
    }

    /**
     * @return string
     */
    public function getTags(): string
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     *
     * @return Term
     */
    public function setTags(string $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalHitCounts(): int
    {
        return $this->totalHitCounts;
    }

    /**
     * @param int $totalHitCounts
     *
     * @return Term
     */
    public function setTotalHitCounts(int $totalHitCounts): self
    {
        $this->totalHitCounts = $totalHitCounts;

        return $this;
    }
}
