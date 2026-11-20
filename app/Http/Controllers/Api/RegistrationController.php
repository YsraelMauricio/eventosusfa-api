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


    public function stats(Event $event): JsonResponse
    {
        $this->authorize('viewRegistrations', $event);

        $confirmed = $event->registrations()->where('status', 'confirmed');
        $confirmedCount = (clone $confirmed)->count();
        $checkedInCount = (clone $confirmed)->whereNotNull('checked_in_at')->count();

        return response()->json(['data' => [
            'event_id' => $event->id,
            'confirmed_count' => $confirmedCount,
            'checked_in_count' => $checkedInCount,
            'attendance_percent' => $confirmedCount === 0 ? 0 : round($checkedInCount * 100 / $confirmedCount, 1),
            'capacity' => $event->capacity,
        ]]);
    }

    public function exportCsv(Event $event)
    {
        $this->authorize('viewRegistrations', $event);

        return response()->streamDownload(function () use ($event): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Código', 'Nombre', 'Correo', 'Estado', 'Check-in'], ',', '"', '\\');
            $event->registrations()->with('user')->orderBy('created_at')->each(function ($registration) use ($stream): void {
                $status = $registration->status->value;
                $values = [$registration->code, $registration->user->name, $registration->user->email, $status,
                    $registration->checked_in_at?->toIso8601String() ?? ''];
                fputcsv($stream, array_map([$this, 'safeCsvCell'], $values), ',', '"', '\\');
            });
            fclose($stream);
        }, "evento-{$event->id}-inscritos.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^\s*[=+@-]/u', $value) ? "'".$value : $value;
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
