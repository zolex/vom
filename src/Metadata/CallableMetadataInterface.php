<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Metadata;

interface CallableMetadataInterface
{
    public function getCallable(): array;

    public function getArguments(): array;

    public function getClass(): string;

    public function getMethod(): string;

    public function getLongMethodName(): string;
}
