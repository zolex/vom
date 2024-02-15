<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

class RepositoryWithFactory
{
    public static function createModel(
        #[VOM\Argument]
        string $name,
        #[VOM\Argument]
        string|int|null $group = null,
    ): ModelWithCallableFactory {
        $model = new ModelWithCallableFactory();
        $model->setName($name);
        if (null !== $group) {
            $model->setGroup($group);
        }

        return $model;
    }

    public function nonStaticMethod(): void
    {
    }

    private static function nonPublicMethod(): void
    {
    }
}
