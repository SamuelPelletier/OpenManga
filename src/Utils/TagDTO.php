<?php
/**
 * Created by PhpStorm.
 * User: samue
 * Date: 10/09/2019
 * Time: 11:26
 */

namespace App\Utils;


use App\Entity\Tag;

/**
 * Class TagDTO
 * @package App\Utils
 */
class TagDTO
{
    private $tag;

    private $countUse;

    /**
     * TagDTO constructor.
     * @param $tag
     * @param $countUse
     */
    public function __construct(Tag $tag, int $countUse)
    {
        $this->tag = $tag;
        $this->countUse = $countUse;
    }

    /**
     * @return Tag
     */
    public function getTag(): Tag
    {
        return $this->tag;
    }

    /**
     * @param Tag $tag
     */
    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return int
     */
    public function getCountUse(): int
    {
        return $this->countUse;
    }

    /**
     * @param int $countUse
     */
    public function setCountUse(int $countUse): void
    {
        $this->countUse = $countUse;
    }


}