<?php

namespace App\Places;

use JsonSerializable;

final class PlaceSuggestion implements JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $main,
        public readonly ?string $secondary = null,
    ) {}

    /** @return array{id: string, label: string, main: string, secondary: ?string} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'main' => $this->main,
            'secondary' => $this->secondary,
        ];
    }
}
