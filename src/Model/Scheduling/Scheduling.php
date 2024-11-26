<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace srag\Plugins\Opencast\Model\Scheduling;

use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use stdClass;

/**
 * Class xoctScheduling
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Scheduling implements JsonSerializable
{
    protected string $agent_id;
    protected \DateTimeImmutable $start;
    protected ?\DateTimeImmutable $end;
    protected ?int $duration;
    /**
     * @var string[]
     */
    protected ?array $inputs;
    protected ?RRule $rrule;

    public function __construct(
        string $agent_id,
        DateTimeImmutable $start,
        ?DateTimeImmutable $end = null,
        ?array $inputs = ['default'],
        ?int $duration = null,
        ?RRule $rrule = null
    ) {
        $this->agent_id = $agent_id;
        $this->start = $start;
        $this->end = $end;
        $this->duration = $duration;
        $this->rrule = $rrule;
        $this->inputs = $inputs;
    }

    public function toStdClass(): stdClass
    {
        $this->getStart()->setTimezone(new DateTimeZone('GMT'));
        $this->getEnd()->setTimezone(new DateTimeZone('GMT'));

        $stdClass = new stdClass();
        $stdClass->agent_id = $this->getAgentId();
        $stdClass->start = $this->getStart()->format('Y-m-d\TH:i:s\Z');
        if ($this->getEnd() instanceof \DateTimeImmutable) {
            $stdClass->end = $this->getEnd()->format('Y-m-d\TH:i:s\Z');
        }

        if ($this->getInputs()) {
            $stdClass->inputs = $this->getInputs();
        }

        if ($this->getRrule() instanceof RRule) {
            $stdClass->rrule = $this->rrule->getValue();

            if ($this->getDuration()) {
                $stdClass->duration = (string) $this->getDuration();
            }
        }

        return $stdClass;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getAgentId(): string
    {
        return $this->agent_id;
    }

    public function setAgentId(string $agent_id): void
    {
        $this->agent_id = $agent_id;
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(DateTimeImmutable $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    public function setEnd(DateTimeImmutable $end): void
    {
        $this->end = $end;
    }

    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }

    public function getRrule(): ?RRule
    {
        return $this->rrule;
    }

    public function setRRule(RRule $rrule): void
    {
        $this->rrule = $rrule;
    }

    public function jsonSerialize()
    {
        return $this->toStdClass();
    }
}
