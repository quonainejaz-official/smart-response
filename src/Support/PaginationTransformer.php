<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginationTransformer
{
    /**
     * @return array{data: mixed, meta: array<string, mixed>}
     */
    public function transform(mixed $data): array
    {
        if ($data instanceof ResourceCollection) {
            $paginator = $data->resource;

            if ($paginator instanceof AbstractPaginator) {
                return [
                    'data' => $data->resolve(),
                    'meta' => $this->metaFromPaginator($paginator),
                ];
            }
        }

        if ($data instanceof JsonResource && $data->resource instanceof AbstractPaginator) {
            return [
                'data' => $data->resolve(),
                'meta' => $this->metaFromPaginator($data->resource),
            ];
        }

        if ($data instanceof AbstractCursorPaginator) {
            return [
                'data' => $data->items(),
                'meta' => $this->metaFromCursorPaginator($data),
            ];
        }

        if ($data instanceof AbstractPaginator) {
            return [
                'data' => $data->items(),
                'meta' => $this->metaFromPaginator($data),
            ];
        }

        return [
            'data' => $data,
            'meta' => [],
        ];
    }

    /**
     * @param AbstractPaginator<int, mixed> $paginator
     * @return array<string, mixed>
     */
    private function metaFromPaginator(AbstractPaginator $paginator): array
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'path' => $paginator->path(),
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta['total'] = $paginator->total();
            $meta['last_page'] = $paginator->lastPage();
        }

        return array_filter($meta, static fn ($value) => $value !== null);
    }

    /**
     * @param AbstractCursorPaginator<int, mixed> $paginator
     * @return array<string, mixed>
     */
    private function metaFromCursorPaginator(AbstractCursorPaginator $paginator): array
    {
        return array_filter([
            'per_page' => $paginator->perPage(),
            'path' => $paginator->path(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'has_more' => $paginator->nextCursor() !== null,
        ], static fn ($value) => $value !== null);
    }
}
