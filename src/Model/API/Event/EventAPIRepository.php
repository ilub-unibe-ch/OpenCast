<?php

namespace srag\Plugins\Opencast\Model\API\Event;

use Opis\Closure\SerializableClosure;
use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\API\ACL\ACL;
use srag\Plugins\Opencast\Model\API\ACL\AclApiRepository;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataAPIRepository;
use srag\Plugins\Opencast\Model\API\Publication\PublicationAPIRepository;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\MetadataDIC;
use srag\Plugins\Opencast\UI\Input\Plupload;
use srag\Plugins\Opencast\Util\Transformator\ACLtoXML;
use srag\Plugins\Opencast\Util\Transformator\MetadataToXML;
use stdClass;
use xoct;
use xoctConf;
use xoctEvent;
use xoctEventAdditions;
use xoctException;
use xoctInvitation;
use xoctRequest;
use xoctUploadFile;

/**
 * Class EventRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Event
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class EventAPIRepository
{
    const CACHE_PREFIX = 'event-';

    public static $load_md_separate = true;
    public static $load_acl_separate = false;
    public static $load_pub_separate = true;

    /**
     * @var Cache
     */
    protected $cache;
    /**
     * @var MDParser
     */
    protected $md_parser;
    /**
     * @var MetadataAPIRepository
     */
    protected $md_repository;
    /**
     * @var AclApiRepository
     */
    protected $acl_repository;
    /**
     * @var PublicationAPIRepository
     */
    protected $publication_repository;


    public function __construct(Cache                     $cache,
                                MetadataDIC               $metadataDIC,
                                ?AclApiRepository         $acl_repository = null,
                                ?PublicationAPIRepository $publication_repository = null)
    {
        $this->cache = $cache;
        $this->md_parser = $metadataDIC->parser();
        $this->md_repository = $metadataDIC->apiRepository();
        $this->acl_repository = $acl_repository ?? new AclApiRepository($cache);
        $this->publication_repository = $publication_repository ?? new PublicationAPIRepository($cache);
    }

    public function find(string $identifier): xoctEvent
    {
        return $this->cache->get(self::CACHE_PREFIX . $identifier)
            ?? $this->fetch($identifier);
    }

    private function fetch(string $identifier): xoctEvent
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->get());
        $event = $this->buildEventFromStdClass($data, $identifier);
        $this->cache->set(self::CACHE_PREFIX . $event->getIdentifier(), $event);
        return $event;
    }

    public function delete(string $identifier) : bool
    {
        xoctRequest::root()->events($identifier)->delete();
        foreach (xoctInvitation::where(array('event_identifier' => $identifier))->get() as $invitation) {
            $invitation->delete();
        }
        return true;
    }

    /**
     * @param stdClass $data
     * @param string $identifier
     * @return xoctEvent
     * @throws xoctException
     */
    private function buildEventFromStdClass(stdClass $data, string $identifier): xoctEvent
    {
        $event = new xoctEvent();
        $event->setPublicationStatus($data->publication_status);
        $event->setProcessingState($data->processing_state);
        $event->setStatus($data->status);
        $event->setHasPreviews($data->has_previews);
        $event->setXoctEventAdditions(xoctEventAdditions::findOrGetInstance($identifier));

        if (isset($data->metadata)) {
            $event->setMetadata($this->md_parser->parseAPIResponseEvent($data->metadata));
        } else {
            // lazy loading
            $event->setMetadataReference(new SerializableClosure(function () use ($identifier) {
                return $this->md_repository->find($identifier);
            }));
        }

        if (isset($data->acl)) {
            $event->setAcl(ACL::fromResponse($data->acl));
        } else {
            // lazy loading
            $event->setAclReference(new SerializableClosure(function () use ($identifier) {
                return $this->acl_repository->find($identifier);
            }));
        }

        if (isset($data->publications)) {
            $event->publications()->loadFromArray($data->publications);
        } else {
            // lazy loading
            $event->publications()->setReference(new SerializableClosure(function () use ($identifier) {
                return $this->publication_repository->find($identifier);
            }));
        }
        return $event;
    }


    /**
     * @throws xoctException
     */
    public function upload(UploadEventRequest $uploadEventRequest): void
    {
        if (xoctConf::getConfig(xoctConf::F_INGEST_UPLOAD)) {
            $this->ingest($uploadEventRequest);
        } else {
            json_decode(xoctRequest::root()->events()
                ->post($uploadEventRequest->getPayload()->jsonSerialize()));
        }
    }


    /**
     * @throws xoctException
     */
    private function ingest(UploadEventRequest $uploadEventRequest) : void
    {
        $payload = $uploadEventRequest->getPayload();
        $ingest_node_url = $this->getIngestNodeURL();

        // create media package
        $media_package = xoctRequest::root()->ingest()->createMediaPackage()->get([], '', $ingest_node_url);

        // Metadata
        $media_package = xoctRequest::root()->ingest()->addDCCatalog()->post([
            'dublinCore' => (new MetadataToXML($payload->getMetadata()))->getXML(),
            'mediaPackage' => $media_package,
            'flavor' => 'dublincore/episode'
        ], [], '', $ingest_node_url);

        // ACLs (as attachment)
        $media_package = xoctRequest::root()->ingest()->addAttachment()->postFiles([
            'mediaPackage' => $media_package,
            'flavor' => 'security/xacml+episode'
        ], [$this->buildACLUploadFile($payload->getAcl())], [], '', $ingest_node_url);

        // track
        $media_package = xoctRequest::root()->ingest()->addTrack()->postFiles([
            'mediaPackage' => $media_package,
            'flavor' => 'presentation/source'
        ], [$payload->getPresentation()], [], '', $ingest_node_url);

        // ingest
        $post_params = [
            'mediaPackage' => $media_package,
            'workflowDefinitionId' => $payload->getProcessing()->getWorkflow()
        ];
        $post_params = array_merge($post_params, $payload->getProcessing()->getConfiguration());
        $response = xoctRequest::root()->ingest()->ingest()->post($post_params, [], '', $ingest_node_url);
    }


    private function buildACLUploadFile(ACL $acl): xoctUploadFile
    {
        $plupload = new Plupload();
        $tmp_name = uniqid('tmp');
        file_put_contents($plupload->getTargetDir() . '/' . $tmp_name, (new ACLtoXML($acl))->getXML());
        $upload_file = new xoctUploadFile();
        $upload_file->setFileSize(filesize($plupload->getTargetDir() . '/' . $tmp_name));
        $upload_file->setPostVar('attachment');
        $upload_file->setTitle('attachment');
        $upload_file->setTmpName($tmp_name);
        return $upload_file;
    }


    /**
     * @param array $filter
     * @param string $for_user
     * @param array $roles
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @param bool $as_object
     *
     * @return xoctEvent[] | array
     * @throws xoctException
     */
    public function getFiltered(array $filter, $for_user = '', $roles = [], $offset = 0, $limit = 1000, $sort = '', $as_object = false)
    {
        /**
         * @var $event xoctEvent
         */
        $request = xoctRequest::root()->events();
        if ($filter) {
            $filter_string = '';
            foreach ($filter as $k => $v) {
                $filter_string .= $k . ':' . $v . ',';
            }
            $filter_string = rtrim($filter_string, ',');

            $request->parameter('filter', $filter_string);
        }

        $request->parameter('offset', $offset);
        $request->parameter('limit', $limit);

        if ($sort) {
            $request->parameter('sort', $sort);
        }

        if (!self::$load_md_separate) {
            $request->parameter('withmetadata', true);
        }

        if (!self::$load_acl_separate) {
            $request->parameter('withacl', true);
        }

        if (!self::$load_pub_separate) {
            $request->parameter('withpublications', true);
        }

        if (xoct::isApiVersionGreaterThan('v1.1.0')) {
            $request->parameter('withscheduling', true);
        }

        if (xoctConf::getConfig(xoctConf::F_PRESIGN_LINKS)) {
            $request->parameter('sign', true);
        }

        $data = json_decode($request->get($roles, $for_user)) ?: [];
        $return = array();

        foreach ($data as $d) {
            $event = $this->buildEventFromStdClass($d, $d->identifier);
            if (!in_array($event->getProcessingState(), [xoctEvent::STATE_SUCCEEDED, xoctEvent::STATE_OFFLINE])) {
                xoctEvent::removeFromCache($event->getIdentifier());
            }
            $return[] = $as_object ? $event : $event->getArrayForTable();
        }

        return $return;
    }

    /**
     * @return string
     * @throws xoctException
     */
    private function getIngestNodeURL(): string
    {
        $nodes = json_decode(xoctRequest::root()->services()->available('org.opencastproject.ingest')->get(), true);
        if (!is_array($nodes)
            || !isset($nodes['services'])
            || !isset($nodes['services']['service'])
            || empty($nodes['services']['service'])
        ) {
            throw new xoctException(xoctException::API_CALL_STATUS_500, 'no available ingest nodes found');
        }
        $available_hosts = [];
        $services = $nodes['services']['service'];
        $services = isset($services['type']) ? [$services] : $services; // only one service?
        foreach ($services as $node) {
            if ($node['active'] && $node['host']) {
                $available_hosts[] = $node['host'];
            }
        }
        if (count($available_hosts) === 0) {
            throw new xoctException(xoctException::API_CALL_STATUS_500, 'no available ingest nodes found');
        }
        return array_rand(array_flip($available_hosts));
    }

    public function update(UpdateEventRequest $updateEventRequest) : void
    {
        xoctRequest::root()->events($updateEventRequest->getIdentifier())
            ->post($updateEventRequest->getPayload()->jsonSerialize());
        $this->cache->delete(self::CACHE_PREFIX . $updateEventRequest->getIdentifier());
    }

    public function schedule(ScheduleEventRequest $scheduleEventRequest) : string
    {
        $response = json_decode(xoctRequest::root()->events()->post($scheduleEventRequest->getPayload()->jsonSerialize()));
        return is_array($response) ? $response[0]->identifier : $response->identifier;
    }
}
