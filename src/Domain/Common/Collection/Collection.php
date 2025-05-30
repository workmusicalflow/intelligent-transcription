<?php

namespace Domain\Common\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use InvalidArgumentException;

abstract class Collection implements IteratorAggregate, Countable
{
    protected array $items = [];
    
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }
    
    abstract protected function type(): string;
    
    protected function guardType($item): void
    {
        $expectedType = $this->type();
        
        if (!is_object($item) || !($item instanceof $expectedType)) {
            throw new InvalidArgumentException(
                sprintf('Collection only accepts items of type %s', $expectedType)
            );
        }
    }
    
    public function add($item): void
    {
        $this->guardType($item);
        $this->items[] = $item;
    }
    
    public function remove($item): void
    {
        $key = array_search($item, $this->items, true);
        if ($key !== false) {
            unset($this->items[$key]);
            $this->items = array_values($this->items);
        }
    }
    
    public function contains($item): bool
    {
        return in_array($item, $this->items, true);
    }
    
    public function count(): int
    {
        return count($this->items);
    }
    
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
    
    public function toArray(): array
    {
        return $this->items;
    }
    
    public function first()
    {
        return reset($this->items) ?: null;
    }
    
    public function last()
    {
        return end($this->items) ?: null;
    }
    
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
    
    public function filter(callable $callback): static
    {
        return new static(array_filter($this->items, $callback));
    }
    
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }
}