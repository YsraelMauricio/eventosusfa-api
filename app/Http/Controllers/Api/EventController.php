<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListEventsRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function index(ListEventsRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        $query = Event::query()
            ->published()
            ->with(['category', 'organizer'])
            ->withCount(['confirmedRegistrations as registrations_count']);

        if (! empty($data['from']) || ! empty($data['to'])) {
            if (! empty($data['from'])) {
                $query->where('starts_at', '>=', Carbon::parse($data['from'])->startOfDay());
            }
            if (! empty($data['to'])) {
                $query->where('starts_at', '<=', Carbon::parse($data['to'])->endOfDay());
            }
        } else {
            $query->upcoming();
        }

        if (! empty($data['q'])) {
            $term = '%'.mb_strtolower($data['q']).'%';
            $query->whereRaw('LOWER(title) LIKE ?', [$term]);
        }

        if (! empty($data['category'])) {
            $query->whereHas('category', fn (Builder $builder) => $builder->where('slug', $data['category']));
        }

        $direction = ($data['sort'] ?? 'starts_at') === '-starts_at' ? 'desc' : 'asc';
        $query->orderBy('starts_at', $direction);

        return EventResource::collection(
            $query->paginate((int) ($data['per_page'] ?? 9))->withQueryString(),
        );
    }

    public function show(Request $request, Event $event): EventResource
    {
        $user = $request->user('sanctum');
        $visible = $event->status === EventStatus::Published
            || ($user?->canManage($event) ?? false);

        abort_unless($visible, 404, 'Evento no encontrado.');

        $event->load(['category', 'organizer'])
            ->loadCount(['confirmedRegistrations as registrations_count']);

        return new EventResource($event);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organizer_id'] = $request->user()->id;
        $data['status'] ??= EventStatus::Published->value;

        $event = Event::create($data);
        $event->load('category', 'organizer');

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        $this->authorize('update', $event);

        $data = $request->validated();

        if (isset($data['capacity'])) {
            $registered = $event->confirmedRegistrations()->count();

            if ($data['capacity'] < $registered) {
                throw BusinessRuleException::capacityBelowRegistered();
            }
        }

        $event->update($data);
        $event->load('category', 'organizer')
            ->loadCount(['confirmedRegistrations as registrations_count']);

        return new EventResource($event);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        if ($event->registrations()->exists()) {
            throw BusinessRuleException::eventHasRegistrations();
        }

        $event->delete();

        return response()->json(null, 204);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->isOrganizer(), 403, 'Solo los organizadores tienen panel de eventos.');

        $events = $request->user()
            ->organizedEvents()
            ->with(['category', 'organizer'])
            ->withCount(['confirmedRegistrations as registrations_count'])
            ->orderByDesc('starts_at')
            ->get();

        return EventResource::collection($events);
    }
}
