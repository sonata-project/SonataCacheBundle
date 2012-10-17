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

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

use Sonata\CacheBundle\Cache\CacheInterface;
use Sonata\CacheBundle\Cache\CacheElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * http://www.varnish-cache.org/docs/2.1/reference/varnishadm.html
 *  echo vcl.use foo | varnishadm -T localhost:999 -S /var/db/secret
 *  echo vcl.use foo | ssh vhost varnishadm -T localhost:999 -S /var/db/secret
 *
 *  in the config.yml file :
 *     echo %s "%s" | varnishadm -T localhost:999 -S /var/db/secret
 *     echo %s "%s" | ssh vhost varnishadm -T localhost:999 -S /var/db/secret
 */
class EsiCache implements CacheInterface
{
    protected $router;

    protected $servers;

    protected $resolver;

    protected $token;

    /**
     * @param $token
     * @param array $servers
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param null|\Symfony\Component\HttpKernel\Controller\ControllerResolverInterface $resolver
     */
    public function __construct($token, array $servers = array(), RouterInterface $router, ControllerResolverInterface $resolver = null)
    {
        $this->token    = $token;
        $this->servers  = $servers;
        $this->router   = $router;
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->runCommand('purge', 'req.url ~ .*');
    }

    /**
     * @param string $command
     * @param string $expression
     *
     * @return bool
     */
    protected function runCommand($command, $expression)
    {
        $return = true;
        foreach ($this->servers as $server) {
            $command = str_replace(array('{{ COMMAND }}', '{{ EXPRESSION }}'), array($command, $expression), $server);

            $process = new Process($command);
            if ($process->run() == 0) {
                continue;
            }

            $return = false;
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array())
    {
        $parameters = array();
        foreach ($keys as $key => $value) {
            $parameters[] = sprintf('obj.http.%s ~ %s', $this->normalize($key), $value);
        }

        $purge = implode(" && ", $parameters);

        return $this->runCommand('purge', $purge);
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys)
    {
        if (!isset($keys['controller'])) {
            throw new \RuntimeException('Please define a controller key');
        }

        if (!isset($keys['parameters'])) {
            throw new \RuntimeException('Please define a parameters key');
        }

        $content = sprintf('<esi:include src="%s"/>', $this->getUrl($keys));

        return new CacheElement($keys, new Response($content));
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array())
    {
        return new CacheElement($keys, $data, $ttl, $contextualKeys);
    }

    /**
     * @param array $keys
     * @return string
     */
    protected function getUrl(array $keys)
    {
        $parameters = array(
            'token'      => $this->computeHash($keys),
            'parameters' => $keys
        );

        return $this->router->generate('sonata_cache_esi', $parameters, false);
    }

    /**
     * @param array $keys
     *
     * @return string
     */
    protected function computeHash(array $keys)
    {
        ksort($keys);

        return hash('sha256', $this->token.serialize($keys));
    }

    /**
     * @param $key
     * @return string
     */
    protected function normalize($key)
    {
        return sprintf('x-sonata-cache-%s', str_replace(array('_', '\\'), '-', strtolower($key)));
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public function cacheAction(Request $request)
    {
        $parameters = $request->get('parameters', array());
        
        if ($request->get('token') != $this->computeHash($parameters)) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        $subRequest = Request::create('', 'get', $parameters, $request->cookies->all(), array(), $request->server->all());

        $controller = $this->resolver->getController($subRequest);

        $subRequest->attributes->add(array('_controller' => $parameters['controller']));
        $subRequest->attributes->add($parameters['parameters']);

        $arguments = $this->resolver->getArguments($subRequest, $controller);

        // call controller
        return call_user_func_array($controller, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return true;
    }
}
