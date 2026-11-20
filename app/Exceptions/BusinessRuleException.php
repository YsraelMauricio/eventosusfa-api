<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessRuleException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function alreadyRegistered(): self
    {
        return new self('already_registered', 'Ya estás inscrito en este evento.');
    }

    public static function eventFull(): self
    {
        return new self('event_full', 'El evento no tiene cupos disponibles.');
    }

    public static function eventNotAvailable(): self
    {
        return new self('event_not_available', 'El evento no está disponible para inscripciones.');
    }

    public static function cancelWindowClosed(): self
    {
        return new self('cancel_window_closed', 'La inscripción solo se puede cancelar hasta 24 horas antes del inicio.');
    }

    public static function checkinWindowClosed(): self
    {
        return new self('checkin_window_closed', 'La asistencia solo se registra desde 2 horas antes del inicio hasta el fin del evento.');
    }

    public static function capacityBelowRegistered(): self
    {
        return new self('capacity_below_registered', 'El cupo no puede ser menor que las inscripciones confirmadas.');
    }

    public static function eventHasRegistrations(): self
    {
        return new self('event_has_registrations', 'No se puede eliminar un evento con inscripciones.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
        ], 409);
    }
}
