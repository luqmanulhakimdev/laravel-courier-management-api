<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourierIndexRequest;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class CourierController extends Controller
{
    public function index(CourierIndexRequest $request): AnonymousResourceCollection
    {
        $query = Courier::query();
        $filters = $request->validated();

        if (!empty($filters['search'])) {
            foreach (preg_split('/\s+/', trim($filters['search']), -1, PREG_SPLIT_NO_EMPTY) as $term) {
                $query->where('name', 'LIKE', '%'.$term.'%');
            }
        }
        if (isset($filters['level'])) {
            $query->whereIn('level', array_values(array_unique($filters['level'])));
        }
        if (($filters['sort_by'] ?? null) === 'registered_at') {
            $query->orderByDesc('registered_at')->orderBy('id');
        } else {
            $query->orderBy('name')->orderBy('id');
        }

        return CourierResource::collection($query->paginate($filters['per_page'] ?? 10)->withQueryString());
    }

    public function store(StoreCourierRequest $request): CourierResource
    {
        $attributes = $request->validated();
        $attributes['registered_at'] = $attributes['registered_at'] ?? now();
        return new CourierResource(Courier::create($attributes));
    }

    public function show(Courier $courier): CourierResource
    {
        return new CourierResource($courier);
    }

    public function update(UpdateCourierRequest $request, Courier $courier): CourierResource
    {
        $attributes = $request->validated();
        if (array_key_exists('registered_at', $attributes)) {
            $attributes['registered_at'] = $attributes['registered_at'] === null ? null : Carbon::parse($attributes['registered_at']);
        }
        $courier->update($attributes);
        return new CourierResource($courier->refresh());
    }

    public function destroy(Courier $courier): \Illuminate\Http\Response
    {
        $courier->delete();
        return response()->noContent();
    }
}
