<?php

namespace Soara\Larastrom\Traits;

trait WithBuilder
{
    public function scopeAllowSearch($query)
    {
        if (!request()->searchField) return;
        $query->where(function ($q) {
            foreach (request()->searchField ?? [] as $key => $value) {
                if (!is_null($value) && $value !== '') {
                    $key = $key === '_index' ? 'id' : $key;
                    $q->orWhere($key, 'like', '%' . $value . '%');
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
}
