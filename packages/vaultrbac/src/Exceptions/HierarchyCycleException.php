<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions;

/**
 * Role hierarchy would contain a cycle; graph updates must be rolled back.
 */
final class HierarchyCycleException extends VaultRbacException {}
