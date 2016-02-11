<?php

namespace Madkom\PactoClient\Application;

use Madkom\PactoClient\Domain\Matching\Content;
use Madkom\PactoClient\Domain\Matching\EachLike;
use Madkom\PactoClient\Domain\Matching\Like;
use Madkom\PactoClient\Domain\Matching\Term;

/**
 * Class Pact
 * @package Madkom\PactoClient\Application
 * @author  Dariusz Gafka <d.gafka@madkom.pl>
 */
class Pact
{

    /**
     * Returns new Term matching
     *
     * @param $generate
     * @param $matcher
     *
     * @return Term
     */
    public static function term($generate, $matcher)
    {
        return new Term($generate, $matcher);
    }

    /**
     * Returns new Like matching
     *
     * @param $likeValue
     *
     * @return Like
     */
    public static function like($likeValue)
    {
        return new Like($likeValue);
    }

    /**
     * Return new EachLike matching
     *
     * @param array $contents
     * @param int   $min
     *
     * @return EachLike
     */
    public static function eachLike($contents, $min = 1)
    {
        $transformedContents = [];
        foreach ($contents as $key => $value) {
            $transformedContents[] = new Content($key, $value);
        }

        return new EachLike($transformedContents, $min);
    }
}
