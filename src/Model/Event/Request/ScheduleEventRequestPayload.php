<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;

class ScheduleEventRequestPayload implements JsonSerializable
{
    protected Metadata $metadata;
    protected ?ACL $acl;
    protected ?Scheduling $scheduling;
    protected ?Processing $processing;

    public function __construct(
        Metadata $metadata,
        ACL $acl = null,
        Scheduling $scheduling = null,
        Processing $processing = null
    ) {
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->scheduling = $scheduling;
        $this->processing = $processing;
    }

    /**
     * @return array{metadata: string, acl: string, scheduling: string, processing: string}
     */
    public function jsonSerialize()
    {
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
            'acl' => json_encode($this->acl),
            'scheduling' => json_encode($this->scheduling->jsonSerialize()),
            'processing' => json_encode($this->processing)
        ];
    }
}
