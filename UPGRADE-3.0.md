# UPGRADE FROM 2.x to 3.0

## Deprecations

All the deprecated code introduced on 2.x is removed on 3.0.

Please read [the 2.x upgrade guide](./UPGRADE-2.x.md) for more information.

See also the [diff](https://github.com/sonata-project/SonataCacheBundle/compare/2.x...3.0.0)

## Type hinting

Now that only PHP 7 is supported, many signatures have changed: type hinting
was added for the parameters or the return value.

## Twig older than 2.4.0 support dropped

Twig 1 is no longer supported. As a result,
`Sonata\CacheBundle\Twig\TwigTemplate14` was renamed to
`Sonata\CacheBundle\Twig\TwigTemplate` to avoid confusion.
