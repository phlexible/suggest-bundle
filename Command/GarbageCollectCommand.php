<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Command;

use Phlexible\Bundle\SuggestBundle\SuggestMessage;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to start garbage collection.
 *
 * @author Phillip Look <pl@brainbits.net>
 */
class GarbageCollectCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('suggest:garbage-collect')
            ->setAliases(array('suggest:gc'))
            ->setDescription('Cleanup unused data source values')
            ->addArgument('datasource', InputArgument::OPTIONAL, 'Data source ID. If not stated, all datasources are garbage collected.')
            ->addOption('run', null, InputOption::VALUE_NONE, 'Execute. Otherwise only stats are shown.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gc = $this->getContainer()->get('phlexible_suggest.garbage_collector');
        $messagePoster = $this->getContainer()->get('phlexible_message.message_poster');

        $this->getContainer()->get('doctrine.orm.default_entity_manager')->getConnection()->getConfiguration()->setSQLLogger(null);

        $pretend = !$input->getOption('run');
        $datasourceId = $input->getArgument('datasource');

        if ($datasourceId) {
            $dataSource = $this->getContainer()->get('phlexible_suggest.data_source_manager')->find($datasourceId);

            if (!$dataSource) {
                $output->writeln("<error>Data Source {$datasourceId} not found.</>");

                return 1;
            }

            $results = $gc->runDataSource($dataSource, $pretend);
        } else {
            $results = $gc->run($pretend);
        }

        $subjects = array();

        foreach ($results as $result) {
            $cntActivate = $result->getValues()->countActiveValues();
            $cntRemove = $result->getValues()->countRemoveValues();

            if ($pretend) {
                $output->writeln(
                    "[{$result->getDataSource()->getName()}, {$result->getLanguage()}] Would store $cntActivate active, "
                    ."and remove $cntRemove values"
                );

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln(' Active: '.json_encode($result->getValues()->getActiveValues()));
                    $output->writeln(' Remove: '.json_encode($result->getValues()->getRemoveValues()));
                }
            } else {
                $subject = "[{$result->getDataSource()->getName()}, {$result->getLanguage()}] Stored $cntActivate active, "
                    ."and removed $cntRemove values";

                $output->writeln($subject);

                if ($cntActivate || $cntRemove) {
                    $subjects[] = $subject;
                }
            }
        }

        if (!$pretend && count($subjects)) {
            $message = SuggestMessage::create(
                'Garbage collection run on '.count($results).' data sources / languages.',
                implode(PHP_EOL, $subjects)
            );
            $messagePoster->post($message);
        }

        return 0;
    }
}
