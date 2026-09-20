<?php

declare(strict_types=1);

use Livewire\Component;

new class extends Component {
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
};
