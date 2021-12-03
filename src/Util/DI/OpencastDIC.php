<?php

namespace srag\Plugins\Opencast\Util\DI;

use ILIAS\DI\Container as DIC;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use Pimple\Container;
use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Cache\CacheFactory;
use srag\Plugins\Opencast\Model\API\ACL\AclApiRepository;
use srag\Plugins\Opencast\Model\API\Agent\AgentApiRepository;
use srag\Plugins\Opencast\Model\API\Agent\AgentParser;
use srag\Plugins\Opencast\Model\API\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataAPIRepository;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\API\Publication\PublicationAPIRepository;
use srag\Plugins\Opencast\Model\API\Scheduling\SchedulingParser;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\UI\FormBuilderEvent;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDFormItemBuilder;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDPrefiller;
use srag\Plugins\Opencast\Model\Metadata\MetadataService;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\Model\WorkflowParameter\WorkflowParameterParser;
use srag\Plugins\Opencast\Util\Upload\OpencastIngestService;
use srag\Plugins\Opencast\Util\Upload\UploadStorageService;
use xoctFileUploadHandler;

class OpencastDIC
{
    /**
     * @var Container
     */
    private $container;
    /**
     * @var DIC
     */
    private $dic;

    public function __construct(DIC $dic)
    {
        $this->container = new Container();
        $this->init();
        $this->dic = $dic;
    }

    private function init(): void
    {
        $this->container['event_repository'] = $this->container->factory(function ($c) {
            return new EventAPIRepository($c['cache'],
                $c['md_parser'],
                $c['md_repository'],
                $c['ingest_service'],
                $c['acl_repository'],
                $c['publication_repository']);
        });
        $this->container['acl_repository'] = $this->container->factory(function ($c) {
            return new AclApiRepository($c['cache']);
        });
        $this->container['cache'] = $this->container->factory(function ($c) {
            return CacheFactory::getInstance();
        });
        $this->container['ingest_service'] = $this->container->factory(function ($c) {
            return new OpencastIngestService($c['upload_storage_service']);
        });
        $this->container['publication_repository'] = $this->container->factory(function ($c) {
            return new PublicationAPIRepository($c['cache']);
        });
        $this->container['upload_storage_service'] = $this->container->factory(function ($c) {
            return new UploadStorageService($this->dic->filesystem()->temp(), $this->dic->upload());
        });
        $this->container['upload_handler'] = $this->container->factory(function ($c) {
            return new xoctFileUploadHandler($c['upload_storage_service']);
        });
        $this->container['agent_repository'] = $this->container->factory(function ($c) {
            return new AgentApiRepository($c['agent_parser']);
        });
        $this->container['agent_parser'] = $this->container->factory(function($c) {
            return new AgentParser();
        });
        $this->container['md_repository'] = $this->container->factory(function ($c) {
            return new MetadataAPIRepository(
                $c['cache'],
                $c['md_parser']);
        });
        $this->container['md_parser'] = $this->container->factory(function ($c) {
            return new MDParser(
                $c['md_catalogue_factory'],
                $c['md_factory']
            );
        });
        $this->container['md_catalogue_factory'] = $this->container->factory(function ($c) {
            return new MDCatalogueFactory();
        });
        $this->container['md_factory'] = $this->container->factory(function ($c) {
            return new MetadataFactory($c['md_catalogue_factory']);
        });
        $this->container['md_prefiller'] = $this->container->factory(function ($c) {
            return new MDPrefiller();
        });
        $this->container['md_conf_repository_event'] = $this->container->factory(function ($c) {
            return new MDFieldConfigEventRepository();
        });
        $this->container['md_conf_repository_series'] = $this->container->factory(function ($c) {
            return new MDFieldConfigSeriesRepository();
        });
        $this->container['md_form_item_builder_event'] = $this->container->factory(function ($c) {
            return new MDFormItemBuilder(
                $c['md_catalogue_factory']->event(),
                $c['md_conf_repository_event'],
                $c['md_prefiller'],
                $this->dic->ui()->factory(),
                $this->dic->refinery(),
                $c['agent_repository']
            );
        });
        $this->container['md_form_item_builder_series'] = $this->container->factory(function ($c) {
            return new MDFormItemBuilder(
                $c['md_catalogue_factory']->series(),
                $c['md_conf_repository_series'],
                $c['md_prefiller'],
                $this->dic->ui()->factory(),
                $this->dic->refinery()
            );
        });
        $this->container['workflow_parameter_conf_repository'] = $this->container->factory(function ($c) {
            return new SeriesWorkflowParameterRepository($this->dic->ui()->factory());
        });
        $this->container['workflow_parameter_parser'] = $this->container->factory(function ($c) {
            return new WorkflowParameterParser();
        });
        $this->container['scheduling_parser'] = $this->container->factory(function($c) {
            return new SchedulingParser();
        });
        $this->container['form_builder_event'] = $this->container->factory(function ($c) {
            return new FormBuilderEvent($this->dic->ui()->factory(),
                $this->dic->refinery(),
                $c['md_form_item_builder_event'],
                $c['workflow_parameter_conf_repository'],
                $c['upload_storage_service'],
                $c['upload_handler'],
                $c['md_parser'],
                $c['workflow_parameter_parser'],
                $c['scheduling_parser']
            );
        });
//        $this->container['form_builder_series'] = $this->container->factory(function ($c) {
//            return new FormBuilderSeries($this->dic->ui()->factory(),
//                $this->dic->refinery(),
//                $c['md_form_item_builder_series'],
//            );
//        });
    }

    public function event_repository(): EventAPIRepository
    {
        return $this->container['event_repository'];
    }

    public function cache(): Cache
    {
        return $this->container['cache'];
    }

    public function acl_repository(): AclApiRepository
    {
        return $this->container['acl_repository'];
    }

    public function ingest_service(): OpencastIngestService
    {
        return $this->container['ingest_service'];
    }

    public function publication_repository(): PublicationAPIRepository
    {
        return $this->container['publication_repository'];
    }

    public function upload_storage_service(): UploadStorageService
    {
        return $this->container['upload_storage_service'];
    }

    public function upload_handler(): UploadHandler
    {
        return $this->container['upload_handler'];
    }

    public function form_builder_event(): FormBuilderEvent
    {
        return $this->container['form_builder_event'];
    }

    public function workflow_parameter_conf_repository(): SeriesWorkflowParameterRepository
    {
        return $this->container['workflow_parameter_conf_repository'];
    }

    public function workflow_parameter_parser(): WorkflowParameterParser
    {
        return $this->container['workflow_parameter_parser'];
    }

    public function metadata(): MetadataService
    {
        return new MetadataService($this->container);
    }

}