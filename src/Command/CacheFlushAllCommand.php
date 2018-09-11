<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheFlushAllCommand extends BaseCacheCommand
{
    public function configure(): void
    {
        $this->setName('sonata:cache:flush-all');
        $this->setDescription('Flush all information set in cache managers');

        $this->addOption(
            'cache',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Flush elements stored in given cache'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('<info>Clearing cache information.</info>');

        foreach ($this->getManager()->getCacheServices() as $name => $cache) {
            if ($input->getOption('cache') && !\in_array($name, $input->getOption('cache'))) {
                continue;
            }

            $output->write(sprintf(' > %s : starting .... ', $name));

            if (true === $cache->flushAll()) {
                $output->writeln('<info>Ok</info>');
            } else {
                $output->writeln('<error>Failed!</error>');
            }
        }

        $output->writeln('<info>Done!</info>');
    }
}
