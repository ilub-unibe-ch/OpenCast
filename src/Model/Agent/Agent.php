<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace srag\Plugins\Opencast\Model\Agent;

use DateTimeImmutable;

/**
 * Class xoctAgent
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Agent
{
    private string $agent_id;
    /**
     * @var string[]
     */
    private array $inputs;
    private \DateTimeImmutable $update;
    private string $url;
    private string $status;

    /**
     * @param string[] $inputs
     */
    public function __construct(string $agent_id, string $status, array $inputs, DateTimeImmutable $update, string $url)
    {
        $this->agent_id = $agent_id;
        $this->status = $status;
        $this->inputs = $inputs;
        $this->update = $update;
        $this->url = $url;
    }

    public function getAgentId(): string
    {
        return $this->agent_id;
    }

    /**
     * @return string[]
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function getUpdate(): DateTimeImmutable
    {
        return $this->update;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
