<?php


namespace App\Entity;

/**
 * Class Post
 * @package App\Entity
 */
class Post
{
    /**
     * Url адреса
     * @var string
     */
    private string $url;

    /**
     * Название рубрики
     * @var string
     */
    private string $rubric;

    /**
     * @return string
     */
    public function getRubric(): string
    {
        return $this->rubric;
    }

    /**
     * @param string $rubric
     * @return Post
     */
    public function setRubric(string $rubric): self
    {
        $this->rubric = $rubric;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Post
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }
}