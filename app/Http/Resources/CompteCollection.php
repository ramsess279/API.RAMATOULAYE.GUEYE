<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CompteCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'currentPage' => $this->resource->currentPage(),
                'totalPages' => $this->resource->lastPage(),
                'totalItems' => $this->resource->total(),
                'itemsPerPage' => $this->resource->perPage(),
                'hasNext' => $this->resource->hasMorePages(),
                'hasPrevious' => $this->resource->currentPage() > 1,
            ],
            'links' => [
                'self' => $this->resource->url($this->resource->currentPage()),
                'next' => $this->resource->nextPageUrl(),
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'previous' => $this->resource->previousPageUrl(),
            ],
        ];
    }
}