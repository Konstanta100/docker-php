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
     * Создание записи
     * @var string
     */
    private string $date;

    /**
     * Краткое описание
     * @var string
     */
    private string $description;

    /**
     * Content html
     * @var string
     */
    private string $html;

    /**
     * Title
     * @var string
     */
    private string $title;

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

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Post
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     * @return Post
     */
    public function setDate(string $date): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @param string $html
     * @return Post
     */
    public function setHtml(string $html): self
    {
        $this->html = $html;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Post
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
}