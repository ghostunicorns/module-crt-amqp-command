<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqpCommand\Console\Command;

use Exception;
use GhostUnicorns\CrtActivity\Model\HasRunningActivity;
use GhostUnicorns\CrtAmqp\Model\Config;
use GhostUnicorns\CrtBase\Api\CrtConfigInterface;
use GhostUnicorns\CrtBase\Api\CrtListInterface;
use GhostUnicorns\CrtBase\Logger\Handler\Console;
use GhostUnicorns\CrtBase\Model\Run\RunAsync;
use GhostUnicorns\CrtBase\Model\Run\RunSync;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GhostUnicorns\CrtAmqp\Model\GenerateAndPublishAmqpCollectors;
use GhostUnicorns\CrtAmqp\Model\ResourceModel\SetAmqpActivity;

class EnqueueCommand extends Command
{
    /** @var string */
    const EXTRA = 'extra';

    /** @var string */
    const TYPE = 'type';

    /** @var string */
    const FORCE = 'force';

    /**
     * @var CrtConfigInterface
     */
    private $crtConfig;

    /**
     * @var Config
     */
    private $amqpConfig;

    /**
     * @var Console
     */
    private $consoleLogger;

    /**
     * @var CrtListInterface
     */
    private $crtList;

    /**
     * @var HasRunningActivity
     */
    private $hasRunningActivity;

    /**
     * @var SetAmqpActivity
     */
    private $setAmqpActivity;

    /**
     * @var GenerateAndPublishAmqpCollectors
     */
    private $generateAmqpCollectors;

    /**
     * @param CrtConfigInterface $crtConfig
     * @param Config $amqpConfig
     * @param Console $consoleLogger
     * @param CrtListInterface $crtList
     * @param HasRunningActivity $hasRunningActivity
     * @param SetAmqpActivity $setAmqpActivity
     * @param GenerateAndPublishAmqpCollectors $generateAmqpCollectors
     * @param null $name
     */
    public function __construct(
        CrtConfigInterface $crtConfig,
        Config $amqpConfig,
        Console $consoleLogger,
        CrtListInterface $crtList,
        HasRunningActivity $hasRunningActivity,
        SetAmqpActivity $setAmqpActivity,
        GenerateAndPublishAmqpCollectors $generateAmqpCollectors,
        $name = null
    ) {
        parent::__construct($name);
        $this->crtConfig = $crtConfig;
        $this->amqpConfig = $amqpConfig;
        $this->consoleLogger = $consoleLogger;
        $this->crtList = $crtList;
        $this->hasRunningActivity = $hasRunningActivity;
        $this->setAmqpActivity = $setAmqpActivity;
        $this->generateAmqpCollectors = $generateAmqpCollectors;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        $text = [];
        $text[] = __('Available CollectorList types: ')->getText();
        $allDownlaoderList = $this->crtList->getAllCollectorList();
        foreach ($allDownlaoderList as $name => $downlaoderList) {
            $text[] = $name;
            $text[] = ', ';
        }
        $text[] = __('Available RefinerList types: ')->getText();
        $allRefinerList = $this->crtList->getAllRefinerList();
        foreach ($allRefinerList as $name => $refinerList) {
            $text[] = $name;
            $text[] = ', ';
        }
        $text[] = __('Available TransferorList types: ')->getText();
        $allUplaoderList = $this->crtList->getAllTransferorList();
        foreach ($allUplaoderList as $name => $uplaoderList) {
            $text[] = $name;
            $text[] = ', ';
        }
        array_pop($text);
        return implode('', $text);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Crt: Collect + Refine + Transfer for a specific Type');
        $this->addArgument(
            self::TYPE,
            InputArgument::REQUIRED,
            'Type name'
        );

        $this->addArgument(
            self::EXTRA,
            InputArgument::OPTIONAL,
            'Extra data',
            ''
        );

        $this->addOption(
            self::FORCE,
            'f',
            InputOption::VALUE_NONE,
            'Force if already there is a running activity',
            null
        );
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->consoleLogger->setConsoleOutput($output);
        $type = $input->getArgument(self::TYPE);
        $extra = $input->getArgument(self::EXTRA);
        $force = (bool)$input->getOption(self::FORCE);

        if (!$this->crtConfig->isEnabled()) {
            $output->writeln(
                'Please enable Crt to continue: ' .
                'Stores -> Configurations -> CRT -> Base -> General -> Enalbe Crt'
            );
            return;
        }

        if (!$this->amqpConfig->isEnabled()) {
            throw new NoSuchEntityException(
                __(
                    'Please enable the Amqp Crt functionality to continue: ' .
                    'Stores -> Configurations -> CRT -> Base -> General -> Enalbe Amqp',
                    $type
                )
            );
        }

        if (!$force && $this->hasRunningActivity->execute($type)) {
            throw new NoSuchEntityException(
                __(
                    'There is an activity with type:%1 that is already running',
                    $type
                )
            );
        }

        try {
            $activityId = $this->setAmqpActivity->execute($type, $extra);
            $this->generateAmqpCollectors->execute($activityId);
            $output->writeln('Added to queue.');
        } catch (\Exception $exception) {
            $output->writeln(__('Error: %1', $exception->getMessage()));
        }
    }
}
