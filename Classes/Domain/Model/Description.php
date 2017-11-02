<?php

namespace Subugoe\Nkwgok\Domain\Model;

class Description
{
    /**
     * @var string
     */
    private $description = '';

    /**
     * @var string
     */
    private $descriptionEnglish = '';

    /**
     * @var string
     */
    private $alternate = '';

    /**
     * @var string
     */
    private $alternateEnglish = '';

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Description
     */
    public function setDescription(string $description): Description
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescriptionEnglish(): string
    {
        return $this->descriptionEnglish;
    }

    /**
     * @param string $descriptionEnglish
     *
     * @return Description
     */
    public function setDescriptionEnglish(string $descriptionEnglish): Description
    {
        $this->descriptionEnglish = $descriptionEnglish;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlternate(): string
    {
        return $this->alternate;
    }

    /**
     * @param string $alternate
     *
     * @return Description
     */
    public function setAlternate(string $alternate): Description
    {
        $this->alternate = $alternate;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlternateEnglish(): string
    {
        return $this->alternateEnglish;
    }

    /**
     * @param string $alternateEnglish
     *
     * @return Description
     */
    public function setAlternateEnglish(string $alternateEnglish): Description
    {
        $this->alternateEnglish = $alternateEnglish;

        return $this;
    }
}
