<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Adapter;

use Sonata\Cache\CacheAdapterInterface;
use Sonata\Cache\CacheElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * http://www.varnish-cache.org/docs/2.1/reference/varnishadm.html
 *  echo vcl.use foo | varnishadm -T localhost:999 -S /var/db/secret
 *  echo vcl.use foo | ssh vhost varnishadm -T localhost:999 -S /var/db/secret.
 *
 *  in the config.yml file :
 *     echo %s "%s" | varnishadm -T localhost:999 -S /var/db/secret
 *     echo %s "%s" | ssh vhost "varnishadm -T localhost:999 -S /var/db/secret {{ COMMAND }} '{{ EXPRESSION }}'"
 */
class VarnishCache implements CacheAdapterInterface
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var array
     */
    protected $servers;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $purgeInstruction;

    /**
     * @var ControllerResolverInterface
     */
    protected $resolver;

    /**
     * @var ArgumentResolverInterface
     */
    private $argumentResolver;

    /**
     * Constructor.
     *
     * @param string                           $token            A token
     * @param array                            $servers          An array of servers
     * @param RouterInterface                  $router           A router instance
     * @param string                           $purgeInstruction The purge instruction (purge in Varnish 2, ban in Varnish 3)
     * @param null|ControllerResolverInterface $resolver         A controller resolver instance
     * @param null|ArgumentResolverInterface   $argumentResolver
     */
    public function __construct(
        $token,
        array $servers,
        RouterInterface $router,
        $purgeInstruction,
        ControllerResolverInterface $resolver = null,
        ArgumentResolverInterface $argumentResolver = null
    ) {
        if (interface_exists(ArgumentResolverInterface::class) && !$argumentResolver) {
            @trigger_error(sprintf(
                'Not providing a "%s" instance to "%s" is deprecated since 3.x and will not be possible in 4.0',
                ArgumentResolverInterface::class,
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->token = $token;
        $this->servers = $servers;
        $this->router = $router;
        $this->purgeInstruction = $purgeInstruction;
        $this->resolver = $resolver;
        $this->argumentResolver = $argumentResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->runCommand(
            'ban' == $this->purgeInstruction ? 'ban.url' : 'purge',
            'ban' == $this->purgeInstruction ? '.*' : 'req.url ~ .*'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = [])
    {
        $parameters = [];
        foreach ($keys as $key => $value) {
            $parameters[] = sprintf('obj.http.%s ~ %s', $this->normalize($key), $value);
        }

        $purge = implode(' && ', $parameters);

        return $this->runCommand($this->purgeInstruction, $purge);
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
    public function set(array $keys, $data, $ttl = CacheElement::DAY, array $contextualKeys = [])
    {
        return new CacheElement($keys, $data, $ttl, $contextualKeys);
    }

    /**
     * Cache action.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function cacheAction(Request $request)
    {
        $parameters = $request->get('parameters', []);

        if ($request->get('token') != $this->computeHash($parameters)) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        $subRequest = Request::create('', 'get', $parameters, $request->cookies->all(), [], $request->server->all());

        $controller = $this->resolver->getController($subRequest);

        $subRequest->attributes->add(['_controller' => $parameters['controller']]);
        $subRequest->attributes->add($parameters['parameters']);

        $arguments = $this->argumentResolver ?
            $this->argumentResolver->getArguments($subRequest, $controller) :
            $this->resolver->getArguments($subRequest, $controller);

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
            $process = new Process(str_replace(
                ['{{ COMMAND }}', '{{ EXPRESSION }}'],
                [$command, $expression],
                $server
            ));

            if (0 == $process->run()) {
                continue;
            }

            $return = false;
        }

        return $return;
    }

    /**
     * Gets the URL by the given keys.
     *
     * @param array $keys
     *
     * @return string
     */
    protected function getUrl(array $keys)
    {
        $parameters = [
            'token' => $this->computeHash($keys),
            'parameters' => $keys,
        ];

        return $this->router->generate('sonata_cache_esi', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Computes the given keys.
     *
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
     * Normalizes the given key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function normalize($key)
    {
        return sprintf('x-sonata-cache-%s', str_replace(['_', '\\'], '-', strtolower($key)));
    }
}
