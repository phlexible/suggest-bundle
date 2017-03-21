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

        #$this->getContainer()->get('doctrine.orm.default_entity_manager')->getConnection()->getConfiguration()->setSQLLogger(null);

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
            $cntNew = count($result->getNewValues());
            $cntKeep = count($result->getExistingValues());
            $cntObsolete = count($result->getObsoleteValues());

            if ($pretend) {
                $output->writeln(
                    "Garbage collection of Data Source <fg=cyan>{$result->getDataSource()->getTitle()}</> in language "
                    ."<fg=cyan>{$result->getLanguage()}</> would add <fg=yellow>$cntNew</> new, "
                    ."keep <fg=green>$cntKeep</> existing, "
                    ."and remove <fg=red>$cntObsolete</> obsolete values"
                );

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    if (count($result->getNewValues())) {
                        $output->writeln(' New: '.json_encode($result->getNewValues()->getValues()));
                    }
                    if (count($result->getObsoleteValues())) {
                        $output->writeln(' Obsolete: '.json_encode($result->getObsoleteValues()->getValues()));
                    }
                }
            } else {
                $subject = "[{$result->getDataSource()->getTitle()}, {$result->getLanguage()}] Added $cntNew new, "
                    ."kept $cntKeep existing values "
                    ."and removed $cntObsolete obsolete values";

                $output->writeln($subject);

                if ($cntNew || $cntObsolete) {
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
