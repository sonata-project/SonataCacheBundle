<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Adapter;

use Sonata\Cache\Adapter\Cache\ApcCache as BaseApcCache;
use Symfony\Component\Routing\RouterInterface;

class ApcCache extends BaseApcCache
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $token;

    /**
     * Constructor
     *
     * @param RouterInterface $router  A router instance
     * @param string          $token   A token to clear the related cache
     * @param string          $prefix  A prefix to avoid clash between instances
     * @param array           $servers An array of servers
     */
    public function __construct(RouterInterface $router, $token, $prefix, array $servers)
    {
        parent::__construct(null, $prefix, $servers);

        $this->router = $router;
        $this->token  = $token;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUrl()
    {
        $this->router->generate('sonata_cache_apc', array('token' => $this->token));
    }
}
