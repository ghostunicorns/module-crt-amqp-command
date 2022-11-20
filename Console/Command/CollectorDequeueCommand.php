<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqpCommand\Console\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GhostUnicorns\CrtAmqp\Model\Data\CollectorInfoFactory;
use GhostUnicorns\CrtAmqp\Model\Consumer\CollectorConsumer;
use GhostUnicorns\CrtBase\Logger\Handler\Console;

class CollectorDequeueCommand extends Command
{
    const TYPE = 'type';
    const ACTIVITY_ID = 'activity_id';

    /**
     * @var Console
     */
    private $consoleLogger;

    /**
     * @var CollectorInfoFactory
     */
    private $collectorInfoFactory;

    /**
     * @var CollectorConsumer
     */
    private $collectorConsumer;

    /**
     * @param null $name
     * @param Console $consoleLogger
     * @param CollectorInfoFactory $collectorInfoFactory
     * @param CollectorConsumer $collectorConsumer
     */
    public function __construct(
        Console $consoleLogger,
        CollectorInfoFactory $collectorInfoFactory,
        CollectorConsumer $collectorConsumer,
        $name = null
    ) {
        parent::__construct($name);
        $this->consoleLogger = $consoleLogger;
        $this->collectorInfoFactory = $collectorInfoFactory;
        $this->collectorConsumer = $collectorConsumer;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Crt: Collect dequeue for a specific ActivityId and Type');
        $this->addArgument(
            self::ACTIVITY_ID,
            InputArgument::REQUIRED,
            'ActivityId'
        );
        $this->addArgument(
            self::TYPE,
            InputArgument::REQUIRED,
            'CollectorList Type'
        );
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->consoleLogger->setConsoleOutput($output);
        $activityId = (int)$input->getArgument(self::ACTIVITY_ID);
        $type = $input->getArgument(self::TYPE);

        $collectorInfo = $this->collectorInfoFactory->create(
            [
                'activity_id' => $activityId,
                'collector_type' => $type
            ]
        );

        $this->collectorConsumer->process($collectorInfo);
    }
}
