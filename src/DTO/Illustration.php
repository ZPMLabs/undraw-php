<?php

namespace Undraw\DTO;

final class Illustration
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $mediaUrl // direct SVG URL
    ) {}

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'title'    => $this->title,
            'slug'     => $this->slug,
            'mediaUrl' => $this->mediaUrl,
        ];
    }
}
