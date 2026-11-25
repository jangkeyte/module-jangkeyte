<?php

namespace JangKeyte\Support;

class MenuRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $items = [];

    public function register(string $key, array $item): void
    {
        $this->items[$key] = array_merge([
            'key' => $key,
            'label' => $key,
            'icon' => 'bi bi-circle',
            'route' => null,
            'permission' => null,
            'active_patterns' => [],
            'order' => 50,
            'children' => [],
        ], $item);
    }

    public function registerMany(array $items): void
    {
        foreach ($items as $key => $item) {
            $itemKey = is_string($key) ? $key : ($item['key'] ?? uniqid('menu_', true));
            $this->register($itemKey, $item);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $sorted = array_values($this->items);
        usort($sorted, static fn (array $a, array $b): int => ($a['order'] <=> $b['order']));

        return $sorted;
    }
}
