<?php

namespace App\Http\Controllers;

use App\Models\MarketingPhrase;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarketingPhraseController extends Controller
{
    private const LOGIN_GRACE_MINUTES = 5;

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('MKT'), 403);

        $phrases = MarketingPhrase::query()
            ->with('sender:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return view('mkt.phrases.index', [
            'phrases' => $phrases,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('MKT'), 403);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'phrase' => ['required', 'string', 'max:255'],
            'delivery_mode' => ['required', 'string', 'in:immediate,scheduled,daily_login'],
            'scheduled_for' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
                Rule::requiredIf($request->input('delivery_mode') === 'scheduled'),
                Rule::when($request->input('delivery_mode') === 'scheduled', 'after:now'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'scheduled_for.required' => 'Indica la fecha y hora exacta para publicar la frase.',
            'scheduled_for.date_format' => 'El formato de la fecha programada no es válido.',
            'scheduled_for.after' => 'La fecha programada debe ser posterior a la hora actual.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            if ((bool) ($validated['is_active'] ?? false)) {
                MarketingPhrase::query()->update(['is_active' => false]);
            }

            $startsAt = $this->resolveStartsAt(
                $validated['delivery_mode'],
                $validated['scheduled_for'] ?? null
            );

            MarketingPhrase::create([
                'sender_user_id' => $user->id,
                'title' => filled($validated['title']) ? trim((string) $validated['title']) : null,
                'phrase' => trim((string) $validated['phrase']),
                'delivery_mode' => $validated['delivery_mode'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'starts_at' => $startsAt,
                'ends_at' => null,
            ]);
        });

        return redirect()->route('mkt.phrases.index')->with('success', 'Frase guardada correctamente.');
    }

    public function toggle(Request $request, MarketingPhrase $phrase): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('MKT'), 403);

        DB::transaction(function () use ($phrase) {
            MarketingPhrase::query()->update(['is_active' => false]);

            $startsAt = now();

            $phrase->forceFill([
                'is_active' => true,
                'delivery_mode' => 'immediate',
                'starts_at' => $startsAt,
                'ends_at' => null,
            ])->save();
        });

        return redirect()->route('mkt.phrases.index')->with('success', 'Frase publicada correctamente.');
    }

    public function update(Request $request, MarketingPhrase $phrase): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('MKT'), 403);

        $validated = $request->validate([
            'delivery_mode' => ['required', 'string', 'in:immediate,scheduled,daily_login'],
            'scheduled_for' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
                Rule::requiredIf($request->input('delivery_mode') === 'scheduled'),
                Rule::when($request->input('delivery_mode') === 'scheduled', 'after:now'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'scheduled_for.required' => 'Indica la fecha y hora exacta para publicar la frase.',
            'scheduled_for.date_format' => 'El formato de la fecha programada no es válido.',
            'scheduled_for.after' => 'La fecha programada debe ser posterior a la hora actual.',
        ]);

        DB::transaction(function () use ($phrase, $validated) {
            $shouldActivate = (bool) ($validated['is_active'] ?? false);
            $scheduledStartsAt = $validated['delivery_mode'] === 'scheduled'
                ? $validated['scheduled_for']
                : null;

            if ($shouldActivate) {
                MarketingPhrase::query()
                    ->whereKeyNot($phrase->id)
                    ->update(['is_active' => false]);
            }

            $startsAt = $shouldActivate
                ? $this->resolveStartsAt($validated['delivery_mode'], $scheduledStartsAt)
                : ($validated['delivery_mode'] === 'scheduled' ? $scheduledStartsAt : $phrase->starts_at);

            $phrase->forceFill([
                'delivery_mode' => $validated['delivery_mode'],
                'is_active' => $shouldActivate,
                'starts_at' => $startsAt,
                'ends_at' => null,
            ])->save();
        });

        return redirect()->route('mkt.phrases.index')->with('success', 'Configuración de la frase actualizada.');
    }

    public function pulse(Request $request): JsonResponse
    {
        abort_unless($request->user(), 403);

        $phrase = MarketingPhrase::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->latest()
            ->first();

        if (
            $phrase
            && ($phrase->delivery_mode ?? 'immediate') !== 'daily_login'
            && $phrase->starts_at
            && !$this->canReceiveTimedPhrase($request, $phrase)
        ) {
            $phrase = null;
        }

        $todayKey = now()->setTimezone(config('app.timezone'))->format('Y-m-d');
        $publishKey = $phrase?->starts_at?->setTimezone(config('app.timezone'))->format('YmdHis')
            ?: optional($phrase?->updated_at)->setTimezone(config('app.timezone'))->format('YmdHis')
            ?: 'na';

        return response()->json([
            'ok' => true,
            'phrase' => $phrase ? [
                'id' => $phrase->id,
                'title' => $phrase->title ?: 'Impulso del día',
                'phrase' => $phrase->phrase,
                'delivery_mode' => $phrase->delivery_mode ?: 'immediate',
                'storage_key' => match ($phrase->delivery_mode) {
                    'daily_login' => 'marketing-phrase-'.$phrase->id.'-'.$todayKey,
                    default => 'marketing-phrase-'.$phrase->id.'-'.$publishKey,
                },
            ] : null,
        ]);
    }

    private function resolveStartsAt(string $deliveryMode, mixed $scheduledFor = null)
    {
        return $deliveryMode === 'scheduled' && $scheduledFor
            ? Carbon::parse($scheduledFor)
            : now();
    }

    private function canReceiveTimedPhrase(Request $request, MarketingPhrase $phrase): bool
    {
        $sessionAuthenticatedAt = $this->resolveSessionAuthenticatedAt($request);

        if (!$sessionAuthenticatedAt || !$phrase->starts_at) {
            return true;
        }

        if ($sessionAuthenticatedAt->lessThanOrEqualTo($phrase->starts_at)) {
            return true;
        }

        return $sessionAuthenticatedAt->lessThanOrEqualTo(
            $phrase->starts_at->copy()->addMinutes(self::LOGIN_GRACE_MINUTES)
        );
    }

    private function resolveSessionAuthenticatedAt(Request $request): ?Carbon
    {
        $authenticatedAt = $request->session()->get('crm_authenticated_at');

        if (!$authenticatedAt) {
            return null;
        }

        try {
            return Carbon::parse($authenticatedAt);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
