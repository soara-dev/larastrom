<?php

namespace Soara\Larastrom\Traits;

use Illuminate\Support\Str;

trait WithBuilder
{
    public function scopeAllowSearch($query)
    {
        $searchFields = request()->searchField;

        if (!$searchFields || !is_array($searchFields)) return $query;

        $query->where(function ($q) use ($searchFields) {
            foreach ($searchFields as $key => $value) {
                if (!is_null($value) && $value !== '') {
                    $key = $key === '_index' ? 'id' : $key;

                    if (!is_null($value) && $value !== '') {
                        $key = $key === '_index' ? 'id' : $key;

                        if (str_contains($key, '.')) {
                            $parts = explode('.', $key);
                            $column = array_pop($parts);
                            $relations = array_map(fn($part) => Str::camel($part), $parts);

                            $q->orWhereHasNested($relations, $column, $value);
                        } else {
                            $q->orWhere($key, 'like', '%' . $value . '%');
                        }
                    }
                }
            }
        });

        return $query;
    }


    public function scopeAllowOrder($query)
    {
        if (!request()->sortField) return;
        foreach (request()->sortField ?? [] as $key => $value) {
            if (!is_null($value) && $value !== '') {
                $key = $key === '_index' ? 'id' : $key;
                $query->orderBy($key, $value);
            }
        }
        return $query;
    }

    public function scopeAllowInteraction($query)
    {
        return $query->allowSearch()->allowOrder();
    }

    public function scopeFetch($query)
    {
        $pageSize = request()->pageSize;
        $sortOrder = request()->sortField['_index'] ?? 'asc';

        if ($pageSize) {
            $res = $query->paginate($pageSize);

            $currentPage = $res->currentPage();
            $perPage = $res->perPage();
            $total = $res->total();

            $res->getCollection()->transform(function ($item, $key) use ($sortOrder, $currentPage, $perPage, $total) {
                if ($sortOrder === 'desc') {
                    $item->_index = $total - (($currentPage - 1) * $perPage + $key);
                } else {
                    $item->_index = ($currentPage - 1) * $perPage + $key + 1;
                }
                return $item;
            });

            return $res;
        } else {
            $res = $query->get();
        }

        return $res;
    }

    protected static function applyNestedOrWhereHas($query, $relations, $column, $value)
    {
        $relation = array_shift($relations);

        $query->orWhereHas($relation, function ($q) use ($relations, $column, $value) {
            if (count($relations)) {
                self::applyNestedOrWhereHas($q, $relations, $column, $value);
            } else {
                $q->where($column, 'like', '%' . $value . '%');
            }
        });
    }
}
