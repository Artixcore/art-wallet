<?php

namespace App\Domain\Workflows\Services;

use InvalidArgumentException;

final class WorkflowDefinitionValidator
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function validate(array $definition): void
    {
        if (($definition['version'] ?? null) === null) {
            throw new InvalidArgumentException('Workflow definition requires version.');
        }
        $nodes = $definition['nodes'] ?? null;
        $edges = $definition['edges'] ?? null;
        if (! is_array($nodes) || $nodes === []) {
            throw new InvalidArgumentException('Workflow requires a non-empty nodes array.');
        }
        if (! is_array($edges)) {
            throw new InvalidArgumentException('Workflow requires edges array.');
        }
        if (count($nodes) > 64) {
            throw new InvalidArgumentException('Workflow node count exceeds maximum.');
        }

        $ids = [];
        foreach ($nodes as $n) {
            if (! is_array($n) || ! isset($n['id'], $n['type'])) {
                throw new InvalidArgumentException('Each node must have id and type.');
            }
            $ids[(string) $n['id']] = true;
        }

        foreach ($edges as $e) {
            if (! is_array($e) || ! isset($e['from'], $e['to'])) {
                throw new InvalidArgumentException('Each edge must have from and to.');
            }
            if (! isset($ids[(string) $e['from']]) || ! isset($ids[(string) $e['to']])) {
                throw new InvalidArgumentException('Edge references unknown node.');
            }
        }
    }
}
