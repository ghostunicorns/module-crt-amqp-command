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
use GhostUnicorns\CrtAmqp\Model\Data\RefinerInfoFactory;
use GhostUnicorns\CrtAmqp\Model\Consumer\RefinerConsumer;
use GhostUnicorns\CrtBase\Logger\Handler\Console;

class RefinerDequeueCommand extends Command
{
    const ENTITY_IDENTIFIER = 'entity_identifier';
    const ACTIVITY_ID = 'activity_id';

    /**
     * @var Console
     */
    private $consoleLogger;

    /**
     * @var RefinerInfoFactory
     */
    private $refinerInfoFactory;

    /**
     * @var RefinerConsumer
     */
    private $refinerConsumer;

    /**
     * @param null $name
     * @param Console $consoleLogger
     * @param RefinerInfoFactory $refinerInfoFactory
     * @param RefinerConsumer $refinerConsumer
     */
    public function __construct(
        Console $consoleLogger,
        RefinerInfoFactory $refinerInfoFactory,
        RefinerConsumer $refinerConsumer,
        $name = null
    ) {
        parent::__construct($name);
        $this->consoleLogger = $consoleLogger;
        $this->refinerInfoFactory = $refinerInfoFactory;
        $this->refinerConsumer = $refinerConsumer;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Crt: Refiner dequeue for a specific ActivityId and EntityIdentifier');
        $this->addArgument(
            self::ACTIVITY_ID,
            InputArgument::REQUIRED,
            'ActivityId'
        );
        $this->addArgument(
            self::ENTITY_IDENTIFIER,
            InputArgument::REQUIRED,
            'EntityIdentifier'
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
        $entityIdentifier = $input->getArgument(self::ENTITY_IDENTIFIER);

        $refinerInfo = $this->refinerInfoFactory->create(
            [
                'activity_id' => $activityId,
                'entity_identifier' => $entityIdentifier
            ]
        );

        $this->refinerConsumer->process($refinerInfo);
    }
}
