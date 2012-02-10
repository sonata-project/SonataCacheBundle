<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class CacheFlushCommand extends BaseCacheCommand
{
    public function configure()
    {
        $this->setName('sonata:cache:flush');
        $this->setDescription('Flush information');

        $this->addArgument('keys', InputArgument::REQUIRED, 'Flush all elements matching the providing keys (json format)');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $keys = json_decode($input->getOption('keys'), true);

        if (!is_array($keys)) {
            $output->writeln('<error>the provided keys cannot be decoded, please provide a valid json string</error>');
        }

        foreach ($this->getManager()->getCacheServices() as $name => $cache) {
            $output->write(sprintf(' > %s : starting .... ', $name));
            $cache->flush($keys);
            $output->writeln('OK');
        }

        $output->writeln('<info>done!</info>');
    }
}