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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Not really a cache as it, depends on the reverse proxy feature.
 */
class SsiCache implements CacheAdapterInterface
{
    protected $router;

    protected $servers;

    protected $resolver;

    protected $token;

    /**
     * @var ArgumentsResolverInterface
     */
    private $argumentResolver;

    /**
     * NEXT_MAJOR: make $argumentResolver mandatory when dropping sf < 3.1.
     *
     * @param string                           $token
     * @param RouterInterface                  $router
     * @param null|ControllerResolverInterface $resolver
     * @param null|ArgumentsResolverInterface  $argumentResolver
     */
    public function __construct($token, RouterInterface $router, ControllerResolverInterface $resolver = null, ArgumentResolverInterface $argumentResolver = null)
    {
        if (interface_exists(ArgumentResolverInterface::class) && !$argumentResolver) {
            @trigger_error(sprintf(
                'Not providing a "%s" instance to "%s" is deprecated since 3.x and will not be possible in 4.0',
                ArgumentResolverInterface::class,
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->token = $token;
        $this->router = $router;
        $this->resolver = $resolver;
        $this->argumentResolver = $argumentResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return true; // nothing to flush
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = [])
    {
        return true; // still nothing to flush ...
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

        $content = sprintf('<!--# include virtual="%s" -->', $this->getUrl($keys));

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

        return $this->router->generate('sonata_cache_ssi', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
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
}
