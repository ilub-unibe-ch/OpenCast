<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series;

use srag\Plugins\Opencast\Model\ACL\ACLParser;
use stdClass;

class SeriesParser
{
    public function __construct(private readonly ACLParser $ACLParser)
    {
    }

    public function parseAPIResponse(stdClass $data): Series
    {
        $series = new Series();
        $series->setIdentifier($data->identifier);
        $series->setAccessPolicies($this->ACLParser->parseAPIResponse($data->acl ?? []));
        if (isset($data->metadata)) {
            $series->setMetadata($data->metadata);
        }
        if (isset($data->theme) && is_int($data->theme)) {
            $series->setTheme($data->theme);
        }
        return $series;
    }
}
