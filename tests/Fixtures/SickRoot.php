<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class SickRoot
{
    public int $number;

    #[VOM\Property('SINGLE')]
    public SickChild $singleChild;

    #[VOM\Property('ANOTHER')]
    public SickChild $anotherChild;

    #[VOM\Property(nested: false)]
    public SickSack $sickSack;

    /**
     * @var array|SickChild[]
     */
    #[VOM\Property()]
    public array $tooManyChildren;
}
