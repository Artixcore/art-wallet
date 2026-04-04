<?php

namespace App\Domain\Tools;

use App\Domain\Tools\Contracts\AgentToolInterface;
use App\Infrastructure\Tools\MessagingDraftTool;
use App\Infrastructure\Tools\WalletSummaryTool;
use InvalidArgumentException;

final class ToolRegistry
{
    /**
     * @var array<string, AgentToolInterface>
     */
    private array $tools;

    public function __construct(
        WalletSummaryTool $walletSummaryTool,
        MessagingDraftTool $messagingDraftTool,
    ) {
        $this->tools = [
            $walletSummaryTool::key() => $walletSummaryTool,
            $messagingDraftTool::key() => $messagingDraftTool,
        ];
    }

    public function get(string $key): AgentToolInterface
    {
        if (! isset($this->tools[$key])) {
            throw new InvalidArgumentException('Unknown tool: '.$key);
        }

        return $this->tools[$key];
    }

    /**
     * @return list<array{key: string, risk_tier: string}>
     */
    public function catalog(): array
    {
        $out = [];
        foreach ($this->tools as $tool) {
            $out[] = [
                'key' => $tool::key(),
                'risk_tier' => $tool::riskTier(),
            ];
        }

        return $out;
    }
}
