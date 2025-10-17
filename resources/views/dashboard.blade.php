@push('head')
    <style>
        body {
            background: radial-gradient(circle at top, rgba(59, 130, 246, 0.15), transparent 55%),
                radial-gradient(circle at bottom, rgba(16, 185, 129, 0.1), transparent 45%),
                #f8fafc;
        }

        .fancy-chip {
            position: relative;
            overflow: hidden;
            z-index: 0;
        }

        .fancy-chip::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: 0;
            background: linear-gradient(120deg, rgba(59, 130, 246, 0.25), rgba(14, 165, 233, 0.15));
            transition: opacity 150ms ease;
            z-index: -1;
            pointer-events: none;
        }

        .fancy-chip:hover::before,
        .fancy-chip[data-active="true"]::before {
            opacity: 1;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 9999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(255, 255, 255, 0.78);
            color: #475569;
            transition: all 120ms ease;
        }

        .filter-chip.active {
            border-color: rgba(14, 165, 233, 0.6);
            color: #0369a1;
            background: rgba(224, 242, 254, 0.85);
            box-shadow: 0 6px 16px -14px rgba(14, 116, 144, 0.6);
        }

        .planner-root {
            position: relative;
        }

        .planner-header {
            gap: 1.5rem;
        }

        .planner-toolbar > * {
            flex-shrink: 0;
        }

        .planner-view {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .planner-loading {
            position: absolute;
            inset: 0;
            background: rgba(248, 250, 252, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 30;
            backdrop-filter: blur(2px);
        }

        .planner-loading.hidden {
            display: none;
        }

        .planner-loading__spinner {
            width: 3rem;
            height: 3rem;
            border-radius: 9999px;
            border: 4px solid rgba(148, 163, 184, 0.35);
            border-top-color: rgba(14, 165, 233, 0.8);
            animation: planner-spin 900ms linear infinite;
        }

        @keyframes planner-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .planner-time-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .planner-time-grid {
            --planner-column-count: 1;
            --planner-column-min: 180px;
        }

        .planner-time-grid__header {
            display: grid;
            grid-template-columns: 80px repeat(
                var(--planner-column-count, 1),
                minmax(var(--planner-column-min, 180px), 1fr)
            );
            gap: 1rem;
            align-items: end;
            min-width: calc(80px + var(--planner-column-count, 1) * var(--planner-column-min, 180px));
        }

        .planner-time-grid__header-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            padding-bottom: 0.25rem;
            color: #0f172a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.75rem;
        }

        .planner-time-grid__header-cell:first-child {
            justify-content: flex-end;
            color: #64748b;
        }

        .planner-time-grid__header-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 9999px;
            background: linear-gradient(130deg, rgba(59, 130, 246, 0.14), rgba(14, 165, 233, 0.22));
            color: #0f172a;
            padding: 0.5rem 1rem;
        }

        .planner-time-grid__header-cell[data-weekend="true"] .planner-time-grid__header-label {
            background: linear-gradient(130deg, rgba(99, 102, 241, 0.12), rgba(14, 165, 233, 0.15));
        }

        .planner-time-grid__body {
            display: grid;
            grid-template-columns: 80px repeat(
                var(--planner-column-count, 1),
                minmax(var(--planner-column-min, 180px), 1fr)
            );
            gap: 1rem;
            min-width: calc(80px + var(--planner-column-count, 1) * var(--planner-column-min, 180px));
        }

        .planner-time-grid__times {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            color: #94a3b8;
            font-size: 0.75rem;
            position: relative;
        }

        .planner-time-grid__time-label {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            padding-right: 0.75rem;
            box-sizing: border-box;
        }

        .planner-time-grid__column {
            position: relative;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 250, 252, 0.85));
            border-radius: 1.75rem;
            padding: 0.75rem;
            box-shadow: inset 0 1px 0 rgba(148, 163, 184, 0.2);
            overflow: hidden;
            min-height: 64rem;
        }

        .planner-time-grid__column[data-weekend="true"] {
            background: linear-gradient(180deg, rgba(224, 231, 255, 0.75), rgba(248, 250, 252, 0.85));
        }

        .planner-time-grid__column[data-drag-target="true"] {
            box-shadow: inset 0 0 0 2px rgba(14, 165, 233, 0.55);
        }

        .planner-drop-placeholder {
            position: absolute;
            left: 0;
            right: 0;
            border-radius: 1.25rem;
            border: 2px dashed rgba(14, 165, 233, 0.55);
            background: rgba(224, 242, 254, 0.55);
            padding: 0.75rem 1rem;
            color: #0f172a;
            font-size: 0.75rem;
            font-weight: 600;
            pointer-events: none;
            opacity: 0;
            transform: scaleY(0.96);
            transition: opacity 140ms ease, transform 140ms ease;
            z-index: 5;
        }

        .planner-drop-placeholder[data-visible="true"] {
            opacity: 1;
            transform: scaleY(1);
        }

        .planner-drop-placeholder__time {
            display: block;
            color: #0284c7;
            letter-spacing: 0.05em;
        }

        .planner-time-grid__background {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .planner-time-grid__hour-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 0;
            border-top: 1px solid rgba(148, 163, 184, 0.25);
        }

        .planner-event {
            position: absolute;
            padding: 0.85rem 1rem;
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 18px 36px -20px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(148, 163, 184, 0.25);
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            cursor: pointer;
            transition: transform 120ms ease, box-shadow 150ms ease;
            overflow: hidden;
            touch-action: none;
        }

        .planner-event::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 1.2rem;
            background: var(--planner-event-bg, rgba(14, 165, 233, 0.2));
            opacity: 0.6;
            z-index: -1;
        }

        .planner-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 40px -24px rgba(15, 23, 42, 0.4);
        }

        .planner-event__time {
            font-size: 0.75rem;
            font-weight: 600;
            color: #0f172a;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .planner-event__title {
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .planner-event__meta {
            font-size: 0.8rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .planner-event__status {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .planner-event--condensed .planner-event__meta {
            display: none;
        }

        .planner-event--minimal .planner-event__status {
            display: none;
        }

        .planner-event__resize {
            position: absolute;
            left: 18%;
            right: 18%;
            height: 10px;
            background: transparent;
            border: none;
            cursor: ns-resize;
        }

        .planner-event__resize--start {
            top: 4px;
        }

        .planner-event__resize--end {
            bottom: 4px;
        }

        .planner-event--dragging {
            opacity: 0.85;
            box-shadow: 0 24px 48px -24px rgba(14, 165, 233, 0.45);
        }

        @media (max-width: 1280px) {
            .planner-time-grid {
                --planner-column-min: 160px;
            }
        }

        @media (max-width: 1024px) {
            .planner-time-grid {
                --planner-column-min: 150px;
            }

            .planner-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .planner-header form {
                width: 100%;
                display: flex;
                justify-content: flex-end;
            }

            .planner-header form button {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .planner-time-grid {
                --planner-column-min: 135px;
            }

            .planner-event {
                border-radius: 1rem;
                padding: 0.75rem;
            }

            .planner-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .planner-toolbar > * {
                width: 100%;
            }

            .planner-toolbar > div {
                justify-content: center;
                flex-wrap: wrap;
            }

            .planner-toolbar [data-calendar-nav],
            .planner-toolbar [data-calendar-view] {
                flex: 1 1 0;
            }

            .planner-toolbar [data-open-student-modal],
            .planner-toolbar #quick-create-event {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            .planner-time-grid {
                --planner-column-min: 120px;
            }

            .planner-time-grid__column {
                min-height: 52rem;
                padding: 0.65rem;
            }

            .planner-event__time {
                font-size: 0.7rem;
            }

            .planner-event__title {
                font-size: 0.85rem;
            }

            .planner-month__header,
            .planner-month__grid {
                gap: 0.5rem;
            }

            #instructor-filter,
            #status-filter {
                width: 100%;
            }

            #instructor-filter .filter-chip,
            #status-filter .filter-chip {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .planner-time-grid {
                --planner-column-min: 105px;
            }

            .planner-time-grid__header {
                gap: 0.5rem;
            }

            .planner-time-grid__body {
                gap: 0.5rem;
            }

            .planner-time-grid__column {
                min-height: 48rem;
            }
        }

        .planner-month {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .planner-month__header,
        .planner-month__grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .planner-month__header-cell {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .planner-month__header-cell[data-weekend="true"] {
            color: #4338ca;
        }

        .planner-month__cell {
            border-radius: 1.5rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.95));
            padding: 1rem;
            box-shadow: inset 0 1px 0 rgba(148, 163, 184, 0.2);
            min-height: 140px;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .planner-month__cell[data-weekend="true"] {
            background: linear-gradient(180deg, rgba(224, 231, 255, 0.7), rgba(241, 245, 249, 0.95));
        }

        .planner-month__cell--muted {
            opacity: 0.55;
        }

        .planner-month__cell[data-expanded="true"] {
            box-shadow: 0 16px 40px -28px rgba(15, 23, 42, 0.45);
        }

        .planner-month__cell[data-drag-target="true"] {
            outline: 2px dashed rgba(14, 165, 233, 0.5);
            outline-offset: 6px;
        }

        .planner-month__cell-header {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            color: #0f172a;
        }

        .planner-month__events {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.8rem;
            max-height: 160px;
            overflow: hidden;
        }

        .planner-month__cell[data-expanded="true"] .planner-month__events {
            max-height: 260px;
            overflow-y: auto;
        }

        .planner-month__event {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.5rem;
            border-radius: 0.85rem;
            background: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            transition: background 120ms ease, transform 120ms ease;
        }

        .planner-month__dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 9999px;
        }

        .planner-month__event:hover {
            background: rgba(224, 242, 254, 0.9);
            transform: translateY(-1px);
        }

        .planner-month__title {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .planner-month__time {
            font-size: 0.75rem;
            color: #64748b;
        }

        .planner-month__more-item {
            display: flex;
        }

        .planner-month__more {
            margin-top: 0.25rem;
            width: 100%;
            border-radius: 9999px;
            padding: 0.4rem 0.75rem;
            border: 1px dashed rgba(148, 163, 184, 0.55);
            background: rgba(241, 245, 249, 0.9);
            font-size: 0.75rem;
            font-weight: 600;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            cursor: pointer;
            transition: all 120ms ease;
        }

        .planner-month__more:hover {
            background: rgba(224, 242, 254, 0.95);
            border-color: rgba(14, 165, 233, 0.5);
        }

        .planner-empty {
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
            padding: 2rem;
            border-radius: 1.5rem;
            background: rgba(226, 232, 240, 0.35);
        }
    </style>
@endpush

<x-layouts.app title="Planning">
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="planner-header mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Planningsoverzicht</h1>
                    <p class="text-sm text-slate-500">Beheer afspraken met een Google Agenda-achtige ervaring.</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden text-sm text-slate-600 sm:block">Ingelogd als <strong>{{ $user->name }}</strong> ({{ $user->role }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-sky-400 hover:text-sky-600">
                            Uitloggen
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex flex-col gap-4">
                        <div class="planner-toolbar flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm">
                                <button type="button" class="rounded-full bg-gradient-to-r from-sky-500 to-blue-600 px-3 py-1 text-xs font-semibold text-white shadow-sm transition hover:from-sky-600 hover:to-blue-700" data-calendar-nav="prev">Vorige</button>
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-sky-400 hover:text-sky-600" data-calendar-nav="today">Vandaag</button>
                                <button type="button" class="rounded-full bg-gradient-to-r from-sky-500 to-blue-600 px-3 py-1 text-xs font-semibold text-white shadow-sm transition hover:from-sky-600 hover:to-blue-700" data-calendar-nav="next">Volgende</button>
                            </div>
                            <div class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                                <button type="button" class="rounded-full px-3 py-1 transition" data-calendar-view="timeGridDay">Dag</button>
                                <button type="button" class="rounded-full px-3 py-1 transition" data-calendar-view="timeGridWeek">Week</button>
                                <button type="button" class="rounded-full px-3 py-1 transition" data-calendar-view="dayGridMonth">Maand</button>
                            </div>
                            <button
                                type="button"
                                data-open-student-modal
                                class="fancy-chip rounded-full bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-sky-600 shadow-lg shadow-sky-100 transition hover:text-sky-700"
                            >
                                Nieuwe leerling toevoegen
                            </button>
                            <button
                                type="button"
                                id="quick-create-event"
                                class="fancy-chip rounded-full bg-gradient-to-r from-emerald-500 to-green-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-lg shadow-emerald-200 transition hover:from-emerald-600 hover:to-green-700"
                            >
                                Afspraak plannen
                            </button>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Datum bereik</p>
                            <p id="calendar-range" class="text-lg font-semibold text-slate-900">&nbsp;</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>Les</span>
                            <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-sky-400"></span>Proefles</span>
                            <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Examen</span>
                            <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>Ziek</span>
                        </div>
                    </div>
                    <div class="flex w-full flex-col gap-4 lg:w-80">
                        @if ($user->isAdmin())
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Instructeurs filteren</p>
                                <div id="instructor-filter" class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($instructors as $instructor)
                                        <button type="button" data-instructor-filter="{{ $instructor['id'] }}" data-active="true" class="filter-chip fancy-chip active">
                                            <span class="h-2.5 w-2.5 rounded-full bg-gradient-to-br from-sky-400 to-blue-500"></span>
                                            <span>{{ $instructor['name'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Statussen</p>
                            <div id="status-filter" class="mt-2 flex flex-wrap gap-2">
                                @foreach ([
                                    ['value' => 'les', 'label' => 'Les', 'color' => 'bg-emerald-500'],
                                    ['value' => 'proefles', 'label' => 'Proefles', 'color' => 'bg-sky-500'],
                                    ['value' => 'examen', 'label' => 'Examen', 'color' => 'bg-amber-500'],
                                    ['value' => 'ziek', 'label' => 'Ziek', 'color' => 'bg-rose-500'],
                                ] as $status)
                                    <button type="button" data-status-filter="{{ $status['value'] }}" data-active="true" class="filter-chip fancy-chip active">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $status['color'] }}"></span>
                                        <span>{{ $status['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200 shadow-inner">
                    <div
                        id="calendar"
                        class="min-h-[700px]"
                    ></div>
                </div>
                <p
                    id="calendar-error"
                    class="mt-4 hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
                >
                    Kon afspraken niet laden. Vernieuw de pagina of probeer het later opnieuw.
                </p>
            </div>
        </main>
    </div>

    <div id="event-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="event-modal-title" class="text-lg font-semibold text-slate-900">Afspraak plannen</h3>
                    <p class="text-sm text-slate-500">Zoek of maak een leerling en vul de details in.</p>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100" data-close-modal>
                    <span class="sr-only">Sluiten</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10 8.586 4.757 3.343 3.343 4.757 8.586 10l-5.243 5.243 1.414 1.414L10 11.414l5.243 5.243 1.414-1.414L11.414 10l5.243-5.243-1.414-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <form id="event-form" class="mt-6 space-y-6">
                @csrf
                <input type="hidden" name="event_id" id="event_id" />
                <input type="hidden" name="student_id" id="student_id" />
                <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div class="space-y-6">
                        @if ($user->isAdmin())
                            <div>
                                <label for="instructor_id" class="block text-sm font-medium text-slate-700">Instructeur</label>
                                <select id="instructor_id" name="instructor_id" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                                    <option value="">Selecteer instructeur</option>
                                    @foreach ($instructors as $instructor)
                                        <option value="{{ $instructor['id'] }}">{{ $instructor['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="block text-sm font-medium text-slate-700">Leerling zoeken</label>
                                <button type="button" data-open-student-modal class="text-xs font-semibold text-sky-600 transition hover:text-sky-700">Nieuwe leerling</button>
                            </div>
                            <div class="mt-2 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <input type="search" id="student-search" placeholder="Zoek op naam, e-mail of telefoon" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                <div
                                    id="selected-student"
                                    class="hidden cursor-pointer rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition hover:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300"
                                    tabindex="0"
                                    role="button"
                                    aria-label="Geen leerling geselecteerd"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-sky-500 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.07 7.127a1 1 0 0 1-1.427.007L3.29 9.91a1 1 0 0 1 1.414-1.414l4.01 4.01 6.364-6.364a1 1 0 0 1 1.414-.007z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p id="selected-student-name" class="truncate text-sm font-semibold text-slate-900"></p>
                                            <p class="text-xs text-slate-500">Klik om een andere leerling te kiezen</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-slate-400">
                                            <path fill-rule="evenodd" d="M7.22 3.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.44 9.5 7.22 5.28a.75.75 0 0 1 0-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div id="student-results" class="max-h-52 space-y-2 overflow-y-auto"></div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                                <select id="status" name="status" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                                    <option value="les">Les</option>
                                    <option value="proefles">Proefles</option>
                                    <option value="examen">Examen</option>
                                    <option value="ziek">Ziek</option>
                                </select>
                            </div>
                            <div>
                                <label for="vehicle" class="block text-sm font-medium text-slate-700">Type voertuig</label>
                                <select id="vehicle" name="vehicle" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                                    <option value="">Selecteer type</option>
                                    <option value="Automaat auto">Automaat auto</option>
                                    <option value="Brommer">Brommer</option>
                                    <option value="Motor">Motor</option>
                                    <option value="Aanhanger">Aanhanger</option>
                                </select>
                            </div>
                            <div>
                                <label for="package" class="block text-sm font-medium text-slate-700">Pakket</label>
                                <input id="package" name="package" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                            </div>
                            <div>
                                <label for="location" class="block text-sm font-medium text-slate-700">Exacte locatie</label>
                                <input id="location" name="location" type="text" placeholder="Bijvoorbeeld: Stationsstraat 12, Utrecht" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="selected_student_birth_date" class="block text-sm font-medium text-slate-700">Geboortedatum</label>
                                    <input id="selected_student_birth_date" name="student_birth_date" type="date" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">E-mail leerling</label>
                                    <div class="mt-2 space-y-2">
                                        <div id="email-display" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <a id="email-link" href="#" data-empty-label="Geen e-mail" class="flex-1 truncate text-sm font-medium text-slate-400" target="_blank" rel="noopener">Geen e-mail</a>
                                            <button type="button" id="toggle-email-edit" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-sky-400 hover:text-sky-600">Bewerk</button>
                                        </div>
                                        <input id="email" name="email" type="email" class="hidden w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                    </div>
                                    <label class="mt-2 flex items-center gap-2 text-xs font-medium text-slate-600">
                                        <input id="notify-student-email" name="notify_student_email" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked />
                                        <span>Student ontvangt e-mails</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Telefoon leerling</label>
                                    <div class="mt-2 space-y-2">
                                        <div id="phone-display" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <a id="phone-link" href="#" data-empty-label="Geen telefoon" class="flex-1 truncate text-sm font-medium text-slate-400">Geen telefoon</a>
                                            <button type="button" id="toggle-phone-edit" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-sky-400 hover:text-sky-600">Bewerk</button>
                                        </div>
                                        <input id="phone" name="phone" type="tel" class="hidden w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                    </div>
                                    <label class="mt-2 flex items-center gap-2 text-xs font-medium text-slate-600">
                                        <input id="notify-student-phone" name="notify_student_phone" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked />
                                        <span>Student ontvangt telefoontjes</span>
                                    </label>
                                </div>
                            </div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contactvoorkeuren</p>
                            <div class="pt-2">
                                <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <input id="has_guardian" name="has_guardian" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    <span>Voogd-contact toevoegen</span>
                                </label>
                                <div id="guardian-section" class="mt-4 hidden grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">E-mail voogd</label>
                                        <div class="mt-2 space-y-2">
                                            <div id="guardian-email-display" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                <a id="guardian-email-link" href="#" data-empty-label="Geen e-mail" class="flex-1 truncate text-sm font-medium text-slate-400" target="_blank" rel="noopener">Geen e-mail</a>
                                                <button type="button" id="toggle-guardian-email-edit" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-sky-400 hover:text-sky-600">Bewerk</button>
                                            </div>
                                            <input id="guardian_email" name="guardian_email" type="email" class="hidden w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                        </div>
                                        <label class="mt-2 flex items-center gap-2 text-xs font-medium text-slate-600">
                                            <input id="notify-guardian-email" name="notify_guardian_email" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                            <span>Voogd ontvangt e-mails</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Telefoon voogd</label>
                                        <div class="mt-2 space-y-2">
                                            <div id="guardian-phone-display" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                <a id="guardian-phone-link" href="#" data-empty-label="Geen telefoon" class="flex-1 truncate text-sm font-medium text-slate-400">Geen telefoon</a>
                                                <button type="button" id="toggle-guardian-phone-edit" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-sky-400 hover:text-sky-600">Bewerk</button>
                                            </div>
                                            <input id="guardian_phone" name="guardian_phone" type="tel" class="hidden w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                        </div>
                                        <label class="mt-2 flex items-center gap-2 text-xs font-medium text-slate-600">
                                            <input id="notify-guardian-phone" name="notify_guardian_phone" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                            <span>Voogd ontvangt telefoontjes</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700">Omschrijving</label>
                            <textarea id="description" name="description" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200"></textarea>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tijd</p>
                            <div class="mt-3 space-y-3">
                                <div>
                                    <label for="start_time" class="block text-xs font-medium text-slate-500">Start</label>
                                    <input id="start_time" name="start_time" type="datetime-local" step="900" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                </div>
                                <div>
                                    <label for="end_time" class="block text-xs font-medium text-slate-500">Einde</label>
                                    <input id="end_time" name="end_time" type="datetime-local" step="900" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button
                        type="button"
                        id="delete-student"
                        class="hidden rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 shadow-sm transition hover:border-rose-300 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-200"
                    >
                        Leerling verwijderen
                    </button>
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center sm:justify-end sm:gap-4">
                        <button type="button" data-close-modal class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300">Annuleren</button>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-200 transition hover:from-sky-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2">Opslaan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="student-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Nieuwe leerling</h3>
                    <p class="text-sm text-slate-500">Voeg een leerling toe om direct in te plannen.</p>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100" data-close-student-modal>
                    <span class="sr-only">Sluiten</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M10 8.586 4.757 3.343 3.343 4.757 8.586 10l-5.243 5.243 1.414 1.414L10 11.414l5.243 5.243 1.414-1.414L11.414 10l5.243-5.243-1.414-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <form id="student-form" class="mt-6 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_first_name" class="block text-sm font-medium text-slate-700">Voornaam</label>
                        <input id="student_first_name" name="first_name" type="text" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                    <div>
                        <label for="student_last_name" class="block text-sm font-medium text-slate-700">Achternaam</label>
                        <input id="student_last_name" name="last_name" type="text" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_birth_date" class="block text-sm font-medium text-slate-700">Geboortedatum</label>
                        <input id="student_birth_date" name="birth_date" type="date" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                    <div>
                        <label for="student_location" class="block text-sm font-medium text-slate-700">Exacte locatie</label>
                        <input id="student_location" name="location" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_email" class="block text-sm font-medium text-slate-700">E-mail</label>
                        <input id="student_email" name="email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                    <div>
                        <label for="student_phone" class="block text-sm font-medium text-slate-700">Telefoon</label>
                        <input id="student_phone" name="phone" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_vehicle" class="block text-sm font-medium text-slate-700">Voertuig</label>
                        <input id="student_vehicle" name="vehicle" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                    <div>
                        <label for="student_package" class="block text-sm font-medium text-slate-700">Pakket</label>
                        <input id="student_package" name="package" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                </div>
                <div class="pt-2">
                    <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <input id="student_has_guardian" name="has_guardian" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                        <span>Voogd-contact toevoegen</span>
                    </label>
                    <div id="student-guardian-fields" class="mt-3 hidden grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="student_guardian_email" class="block text-sm font-medium text-slate-700">E-mail voogd</label>
                            <input id="student_guardian_email" name="guardian_email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                        </div>
                        <div>
                            <label for="student_guardian_phone" class="block text-sm font-medium text-slate-700">Telefoon voogd</label>
                            <input id="student_guardian_phone" name="guardian_phone" type="tel" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4 text-xs font-medium text-slate-600">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_student_email" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked />
                        <span>Student e-mail</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_student_phone" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked />
                        <span>Student telefoon</span>
                    </label>
                    <label class="flex items-center gap-2 hidden" data-student-guardian-pref>
                        <input type="checkbox" id="student_notify_guardian_email" name="notify_guardian_email" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                        <span>Voogd e-mail</span>
                    </label>
                    <label class="flex items-center gap-2 hidden" data-student-guardian-pref>
                        <input type="checkbox" id="student_notify_guardian_phone" name="notify_guardian_phone" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                        <span>Voogd telefoon</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" data-close-student-modal class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300">Annuleren</button>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-200 transition hover:from-emerald-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">Opslaan</button>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
    <script id="planning-config" type="application/json">
        @json($planningConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
    </script>
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
@endpush
</x-layouts.app>
