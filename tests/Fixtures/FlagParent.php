<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class FlagParent
{
    #[VOM\Property(flag: true)]
    public bool $singleFlag;

    #[VOM\Property]
    public CommonFlags $commonFlags;

    #[VOM\Property]
    public ModelFlagsContainer $labeledFlagsArray;

    #[VOM\Property]
    public ModelFlagsContainer $labeledFlagsObject;
}
