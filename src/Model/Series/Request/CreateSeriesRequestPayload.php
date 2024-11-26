<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;

class CreateSeriesRequestPayload implements JsonSerializable
{
    use SanitizeSeriesMetadata;

    private Metadata $metadata;
    private ACL $acl;

    /**
     * @param int $theme
     */
    public function __construct(Metadata $metadata, ACL $acl)
    {
        $this->metadata = $metadata;
        $this->acl = $acl;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getAcl(): ACL
    {
        return $this->acl;
    }

    /**
     * @return array{metadata: string, acl: string}
     */
    public function jsonSerialize()
    {
        $this->saniziteMetadataFields($this->metadata->getFields()); // to prevent empty values
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
            'acl' => json_encode($this->acl),
        ];
    }
}
