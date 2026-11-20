<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $service)
    {
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $registration = $this->service->register($event, $request->user());
        $registration->load('event.category', 'event.organizer');

        return (new RegistrationResource($registration))
            ->response()
            ->setStatusCode(201);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        $registrations = $request->user()
            ->registrations()
            ->with('event.category', 'event.organizer')
            ->orderByDesc('created_at')
            ->get();

        return RegistrationResource::collection($registrations);
    }

    public function destroy(Registration $registration): Response
    {
        $this->authorize('delete', $registration);

        $this->service->cancel($registration);

        return response()->noContent();
    }

    public function indexForEvent(Event $event): AnonymousResourceCollection
    {
        $this->authorize('viewRegistrations', $event);

        $registrations = $event->registrations()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return RegistrationResource::collection($registrations);
    }

    public function checkIn(Request $request, Event $event): RegistrationResource
    {
        $this->authorize('checkIn', $event);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ], [
            'code.required' => 'Escribe el código de la inscripción.',
        ]);

        $registration = $this->service->checkIn($event, $data['code']);
        $registration->load('user');

        return new RegistrationResource($registration);
    }
}
