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
use GhostUnicorns\CrtAmqp\Model\Data\TransferorInfoFactory;
use GhostUnicorns\CrtAmqp\Model\Consumer\TransferorConsumer;
use GhostUnicorns\CrtBase\Logger\Handler\Console;

class TransferorDequeueCommand extends Command
{
    const TRANSFEROR_TYPE = 'transferor_type';
    const ACTIVITY_ID = 'activity_id';

    /**
     * @var Console
     */
    private $consoleLogger;

    /**
     * @var TransferorInfoFactory
     */
    private $transferorInfoFactory;

    /**
     * @var TransferorConsumer
     */
    private $transferorConsumer;

    /**
     * @param null $name
     * @param Console $consoleLogger
     * @param TransferorInfoFactory $transferorInfoFactory
     * @param TransferorConsumer $transferorConsumer
     */
    public function __construct(
        Console $consoleLogger,
        TransferorInfoFactory $transferorInfoFactory,
        TransferorConsumer $transferorConsumer,
        $name = null
    ) {
        parent::__construct($name);
        $this->consoleLogger = $consoleLogger;
        $this->transferorInfoFactory = $transferorInfoFactory;
        $this->transferorConsumer = $transferorConsumer;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Crt: Transfer dequeue for a specific ActivityId and TransferorType');
        $this->addArgument(
            self::ACTIVITY_ID,
            InputArgument::REQUIRED,
            'ActivityId'
        );
        $this->addArgument(
            self::TRANSFEROR_TYPE,
            InputArgument::REQUIRED,
            'TransferorType'
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
        $transferorType = $input->getArgument(self::TRANSFEROR_TYPE);

        $transferorInfo = $this->transferorInfoFactory->create(
            [
                'activity_id' => $activityId,
                'transferor_type' => $transferorType
            ]
        );

        $this->transferorConsumer->process($transferorInfo);
    }
}
