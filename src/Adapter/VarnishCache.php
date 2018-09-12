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

namespace Sonata\CacheBundle\Adapter;

use Sonata\Cache\CacheAdapterInterface;
use Sonata\Cache\CacheElement;
use Sonata\Cache\CacheElementInterface;
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
     * @param string $purgeInstruction The purge instruction (purge in Varnish 2, ban in Varnish 3)
     */
    public function __construct(
        string $token,
        array $servers,
        RouterInterface $router,
        string $purgeInstruction,
        ControllerResolverInterface $resolver,
        ArgumentResolverInterface $argumentResolver
    ) {
        $this->token = $token;
        $this->servers = $servers;
        $this->router = $router;
        $this->purgeInstruction = $purgeInstruction;
        $this->resolver = $resolver;
        $this->argumentResolver = $argumentResolver;
    }

    public function flushAll(): bool
    {
        return $this->runCommand(
            'ban' === $this->purgeInstruction ? 'ban.url' : 'purge',
            'ban' === $this->purgeInstruction ? '.*' : 'req.url ~ .*'
        );
    }

    public function flush(array $keys = []): bool
    {
        $parameters = [];
        foreach ($keys as $key => $value) {
            $parameters[] = sprintf('obj.http.%s ~ %s', $this->normalize($key), $value);
        }

        $purge = implode(' && ', $parameters);

        return $this->runCommand($this->purgeInstruction, $purge);
    }

    public function has(array $keys): bool
    {
        return true;
    }

    /**
     * @throws \RuntimeException
     */
    public function get(array $keys): CacheElementInterface
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

    public function set(
        array $keys,
        $data,
        int $ttl = CacheElement::DAY,
        array $contextualKeys = []
    ): CacheElementInterface {
        return new CacheElement($keys, $data, $ttl, $contextualKeys);
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws \UnexpectedValueException
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
        if (!$controller) {
            throw new \UnexpectedValueException(
                'Could not find a controller for this subrequest.'
            );
        }

        $subRequest->attributes->add(['_controller' => $parameters['controller']]);
        $subRequest->attributes->add($parameters['parameters']);

        $arguments = $this->argumentResolver->getArguments($subRequest, $controller);

        return \call_user_func_array($controller, $arguments);
    }

    public function isContextual(): bool
    {
        return true;
    }

    protected function runCommand(string $command, string $expression): bool
    {
        $return = true;

        foreach ($this->servers as $server) {
            $process = new Process(str_replace(
                ['{{ COMMAND }}', '{{ EXPRESSION }}'],
                [$command, $expression],
                $server
            ));

            if (0 === $process->run()) {
                continue;
            }

            $return = false;
        }

        return $return;
    }

    protected function getUrl(array $keys): ?string
    {
        $parameters = [
            'token' => $this->computeHash($keys),
            'parameters' => $keys,
        ];

        return $this->router->generate('sonata_cache_esi', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    protected function computeHash(array $keys): string
    {
        ksort($keys);

        return hash('sha256', $this->token.serialize($keys));
    }

    protected function normalize(string $key): string
    {
        return sprintf('x-sonata-cache-%s', str_replace(['_', '\\'], '-', strtolower($key)));
    }
}
