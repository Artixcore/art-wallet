<?php

namespace App\Domain\Agents\Enums;

enum AgentRunOutcome: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';
}
