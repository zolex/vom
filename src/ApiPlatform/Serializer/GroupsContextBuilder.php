<?php

declare(strict_types=1);

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\ApiPlatform\Serializer;

use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;

final class GroupsContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private readonly SerializerContextBuilderInterface $decorated,
        private readonly ModelMetadataFactoryInterface $modelMetadataFactory,
        private readonly string $groupsQueryParam,
    ) {
    }

    /**
     * Looks at the symfony normalization context to see if the API-Platform resource class is present
     * and if so, it applies groups from several sources, including a mapping for presets from the VOM\Model.
     * as well as static-groups that are always added (for example 'id' would make sense here).
     */
    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        if (!isset($context['resource_class'])) {
            return $context;
        }

        if (!$metadata = $this->modelMetadataFactory->getMetadataFor($context['resource_class'])) {
            return $context;
        }

        // default context groups
        $context['groups'] ??= [];

        // add request groups
        if ($groups = $request->get($this->groupsQueryParam)) {
            if (!\is_array($groups)) {
                $groups = [$groups];
            }
            $context['groups'] = $groups;
        }

        // add static groups
        if (isset($context['static-groups'])) {
            if (!\is_array($context['static-groups'])) {
                $context['static-groups'] = [$context['static-groups']];
            }

            $context['groups'] = array_merge($context['groups'], $context['static-groups']);
        }

        // resolve presets to groups
        $effectiveGroups = [];
        foreach ($context['groups'] as $group) {
            if ($preset = $metadata->getPreset($group)) {
                if (!\is_array($preset)) {
                    $preset = [$preset];
                }
                $effectiveGroups = array_merge($effectiveGroups, $preset);
            } else {
                $effectiveGroups[] = $group;
            }
        }

        $context['groups'] = $effectiveGroups;

        if (!\count($context['groups'])) {
            unset($context['groups']);
        }

        return $context;
    }
}
