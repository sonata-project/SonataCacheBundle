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
use Symfony\Component\EventDispatcher\EventDispatcher;

class CacheFlushCommand extends BaseCacheCommand
{
    public function configure(): void
    {
        $this->setName('sonata:cache:flush');
        $this->setDescription('Flush information');

        $this->addOption(
            'keys',
            null,
            InputOption::VALUE_REQUIRED,
            'Flush all elements matching the providing keys (json format)'
        );
        $this->addOption(
            'cache',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Flush elements stored in given cache'
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $keys = @json_decode($input->getOption('keys'), true);

        if (!\is_array($keys)) {
            throw new \RuntimeException('The provided keys cannot be decoded, please provide a valid json string.');
        }

        foreach ($this->getManager()->getCacheServices() as $name => $cache) {
            if ($input->getOption('cache') && !\in_array($name, $input->getOption('cache'), true)) {
                continue;
            }

            $output->write(sprintf(' > %s : starting .... ', $name));
            $cache->flush($keys);
            $output->writeln('Ok');
        }

        if ($input->getOption('cache') && \in_array('sonata.cache.symfony', $input->getOption('cache'), true)) {
            // The current event dispatcher is stale, let's not use it anymore
            $this->getApplication()->setDispatcher(new EventDispatcher());
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }
}
