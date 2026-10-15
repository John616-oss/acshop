<?php
/** FIFO queue used by the restocking request workflow. */
class RestockQueue {
    private array $items = [];
    public function enqueue(array $request): void { $this->items[] = $request; }
    public function dequeue(): ?array { return array_shift($this->items) ?: null; }
    public function isEmpty(): bool { return count($this->items) === 0; }
    public function all(): array { return $this->items; }
}
/** LIFO stack used to review recent stock adjustments before an authorized reversal. */
class StockAdjustmentStack {
    private array $items = [];
    public function push(array $adjustment): void { $this->items[] = $adjustment; }
    public function pop(): ?array { return array_pop($this->items) ?: null; }
    public function peek(): ?array { return $this->items ? $this->items[count($this->items)-1] : null; }
    public function all(): array { return array_reverse($this->items); }
}
/** Hash-table style lookup keyed by product code. */
class ProductLookup {
    private array $byCode = [];
    public function add(array $product): void { $this->byCode[$product['product_code']] = $product; }
    public function find(string $code): ?array { return $this->byCode[$code] ?? null; }
}
