<?php

declare(strict_types=1);

namespace Zolex\VOM\Serializer;

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

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        if (!isset($context['resource_class'])) {
            return $context;
        }
        $metadata = $this->modelMetadataFactory->create($context['resource_class']);

        // default context groups
        $context['groups'] ??= [];

        // add request groups
        if ($groups = $request->get($this->groupsQueryParam)) {
            if (!is_array($groups)) {
                $groups = [$groups];
            }
            $context['groups'] = $groups;
            $context['groups'][] = 'id';
        }

        // add static groups
        if (isset($context['static-groups'])) {
            if (!is_array($context['static-groups'])) {
                $context['static-groups'] = [$context['static-groups']];
            }

            $context['groups'] = array_merge($context['groups'], $context['static-groups']);
        }

        // resolve presets to groups
        $effectiveGroups = [];
        foreach ($context['groups'] as $group) {
            if ($preset = $metadata->getPreset($group)) {
                if (!is_array($preset)) {
                    $preset = [$preset];
                }
                $effectiveGroups = array_merge($effectiveGroups, $preset);
            } else {
                $effectiveGroups[] = $group;
            }
        }

        $context['groups'] = $effectiveGroups;

        // add parent groups
        foreach ($context['groups'] as $group) {
            if ($pos = strpos($group, '.')) {
                $parent = substr($group, 0, $pos);
                $context['groups'][] = $parent;
            }
        }

        return $context;
    }
}
