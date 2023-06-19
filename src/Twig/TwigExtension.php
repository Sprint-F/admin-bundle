<?php

namespace SprintF\Bundle\Admin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class TwigExtension extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', $this->isInstanceOf(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('count', count(...)),
            new TwigFilter('values', array_values(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dump', function (...$args) { return dump(...$args); }),
            new TwigFunction('dd', function (...$args) { return dd(...$args); }),
            new TwigFunction('call', function (callable $f, ...$args) { return $f(...$args); }),
        ];
    }

    public function isInstanceOf($value, $type): bool
    {
        return $value instanceof $type;
    }
}
