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
            ->addOption('commit', null, InputOption::VALUE_NONE, 'Commit changes. Otherwise only changes are shown.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gc = $this->getContainer()->get('phlexible_suggest.garbage_collector');
        $messagePoster = $this->getContainer()->get('phlexible_message.message_poster');

        $this->getContainer()->get('doctrine.orm.default_entity_manager')->getConnection()->getConfiguration()->setSQLLogger(null);

        $commit = $input->getOption('commit');
        $datasourceId = $input->getArgument('datasource');

        if ($datasourceId) {
            $dataSource = $this->getContainer()->get('phlexible_suggest.data_source_manager')->find($datasourceId);

            if (!$dataSource) {
                $output->writeln("<error>Data Source {$datasourceId} not found.</>");

                return 1;
            }

            $results = $gc->runDataSource($dataSource, $commit);
        } else {
            $results = $gc->run($commit);
        }

        $subjects = array();

        foreach ($results as $result) {
            $cntNew = count($result->getNewValues());
            $cntKeep = count($result->getExistingValues());
            $cntObsolete = count($result->getObsoleteValues());

            if (!$commit) {
                $output->writeln(sprintf(
                    "Garbage collection of data source <fg=cyan>%s</> in language "
                    ."<fg=cyan>%s</> would add <fg=green>%d</> new, "
                    ."keep <fg=yellow>%d</> existing, "
                    ."and remove <fg=red>%d</> obsolete values.".PHP_EOL
                    ."Memory usage was <fg=cyan>%.2f</> MB.",
                    $result->getDataSource()->getTitle(),
                    $result->getLanguage(),
                    $cntNew,
                    $cntKeep,
                    $cntObsolete,
                    memory_get_peak_usage(true)
                ));

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    if (count($result->getNewValues())) {
                        $output->writeln(' New: '.json_encode($result->getNewValues()->getValues()));
                    }
                    if (count($result->getObsoleteValues())) {
                        $output->writeln(' Obsolete: '.json_encode($result->getObsoleteValues()->getValues()));
                    }
                }
            } else {
                $subject = sprintf(
                    "Garbage collection of data source <fg=cyan>%s</> in "
                    ."language <fg=cyan>%s</> added <fg=green>%i</> new, "
                    ."kept <fg=yellow>%i</> existing "
                    ."and removed <fg=red>%i</> obsolete values.".PHP_EOL
                    ."Memory usage was <fg=cyan>%.2f</> MB.",
                    $result->getDataSource()->getTitle(),
                    $result->getLanguage(),
                    $cntNew,
                    $cntKeep,
                    $cntObsolete,
                    memory_get_peak_usage(true)
                );

                $output->writeln($subject);

                if ($cntNew || $cntObsolete) {
                    $subjects[] = $subject;
                }
            }
        }

        if ($commit && count($subjects)) {
            $message = SuggestMessage::create(
                'Garbage collection run on '.count($results).' data sources / languages.',
                implode(PHP_EOL, $subjects)
            );
            $messagePoster->post($message);
        }

        return 0;
    }
}
