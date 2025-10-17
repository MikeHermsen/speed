@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <style>
        body {
            background: radial-gradient(circle at top, rgba(59, 130, 246, 0.15), transparent 55%),
                radial-gradient(circle at bottom, rgba(16, 185, 129, 0.1), transparent 45%),
                #f8fafc;
        }

        #calendar .fc-event {
            backdrop-filter: saturate(140%) blur(0.5px);
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 25px -12px rgba(15, 23, 42, 0.4);
        }

        #calendar .fc-timegrid-slot:hover {
            background-color: rgba(14, 165, 233, 0.08);
        }

        #calendar .fc-timegrid-now-indicator-line,
        #calendar .fc-timegrid-now-indicator-arrow {
            border-color: rgba(56, 189, 248, 0.85);
        }

        #calendar .fc-daygrid-day.fc-day-today,
        #calendar .fc-timegrid-col.fc-day-today {
            background: linear-gradient(180deg, rgba(56, 189, 248, 0.08), rgba(56, 189, 248, 0));
        }

        #calendar .fc-scrollgrid {
            border-radius: 28px;
            overflow: hidden;
        }

        #calendar .fc-toolbar-title {
            font-weight: 700;
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
    </style>
@endpush

<x-layouts.app title="Planning">
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
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
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm">
                                <button type="button" class="rounded-full bg-gradient-to-r from-sky-500 to-blue-600 px-3 py-1 text-xs font-semibold text-white shadow-sm transition hover:from-sky-600 hover:to-blue-700" data-calendar-nav="prev">Vorige</button>
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-sky-400 hover:text-sky-600" data-calendar-nav="today">Vandaag</button>
                                <button type="button" class="rounded-full bg-gradient-to-r from-sky-500 to-blue-600 px-3 py-1 text-xs font-semibold text-white shadow-sm transition hover:from-sky-600 hover:to-blue-700" data-calendar-nav="next">Volgende</button>
                            </div>
                            <div class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                                <button type="button" class="rounded-full px-3 py-1 transition" data-calendar-view="timeGridDay">Dag</button>
                                <button type="button" class="rounded-full px-3 py-1 transition" data-calendar-view="timeGridWeek">Week</button>
                                <button type="button" class="rounded-full px-3 py-1 transition" data-calendar-view="dayGridMonth">Maand</button>
                                @if ($user->isAdmin())
                                    <button type="button" class="rounded-full px-3 py-1 transition" data-calendar-view="resourceTimeGridWeek">Per instructeur</button>
                                @endif
                            </div>
                            <button
                                type="button"
                                data-open-student-modal
                                class="fancy-chip rounded-full bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-sky-600 shadow-lg shadow-sky-100 transition hover:text-sky-700"
                            >
                                Nieuwe leerling toevoegen
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
                        data-planning-config='{{ e(json_encode($planningConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }}'
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
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <input type="search" id="student-search" placeholder="Zoek op naam of e-mail" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                <div id="student-results" class="mt-3 max-h-52 space-y-2 overflow-y-auto"></div>
                                <div
                                    id="selected-student"
                                    class="mt-3 hidden rounded-2xl bg-white/90 px-4 py-4 text-sm text-slate-700 shadow-lg shadow-slate-200"
                                >
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div id="selected-student-details" class="space-y-1"></div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                id="delete-student"
                                                class="fancy-chip rounded-full border border-transparent bg-rose-500/90 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-rose-600"
                                            >
                                                Leerling verwijderen
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                                <label for="vehicle" class="block text-sm font-medium text-slate-700">Voertuig</label>
                                <input id="vehicle" name="vehicle" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
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
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">E-mail ouder/verzorger</label>
                                    <div class="mt-2 space-y-2">
                                        <div id="parent-email-display" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <a id="parent-email-link" href="#" data-empty-label="Geen e-mail" class="flex-1 truncate text-sm font-medium text-slate-400" target="_blank" rel="noopener">Geen e-mail</a>
                                            <button type="button" id="toggle-parent-email-edit" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-sky-400 hover:text-sky-600">Bewerk</button>
                                        </div>
                                        <input id="parent_email" name="parent_email" type="email" class="hidden w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                    </div>
                                    <label class="mt-2 flex items-center gap-2 text-xs font-medium text-slate-600">
                                        <input id="notify-parent-email" name="notify_parent_email" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                        <span>Ouder ontvangt e-mails</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Telefoon ouder/verzorger</label>
                                    <div class="mt-2 space-y-2">
                                        <div id="parent-phone-display" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <a id="parent-phone-link" href="#" data-empty-label="Geen telefoon" class="flex-1 truncate text-sm font-medium text-slate-400">Geen telefoon</a>
                                            <button type="button" id="toggle-parent-phone-edit" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-sky-400 hover:text-sky-600">Bewerk</button>
                                        </div>
                                        <input id="parent_phone" name="parent_phone" type="tel" class="hidden w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                                    </div>
                                    <label class="mt-2 flex items-center gap-2 text-xs font-medium text-slate-600">
                                        <input id="notify-parent-phone" name="notify_parent_phone" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                        <span>Ouder ontvangt telefoontjes</span>
                                    </label>
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
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</p>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li class="flex items-center justify-between"><span class="text-slate-500">Student</span><span id="summary-student" class="font-medium text-slate-800">-</span></li>
                                <li class="flex items-center justify-between"><span class="text-slate-500">Instructeur</span><span id="summary-instructor" class="font-medium text-slate-800">-</span></li>
                                <li class="flex items-center justify-between"><span class="text-slate-500">Status</span><span id="summary-status" class="font-medium text-slate-800">-</span></li>
                                <li class="flex items-center justify-between"><span class="text-slate-500">Locatie</span><span id="summary-location" class="font-medium text-slate-800">-</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" data-close-modal class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300">Annuleren</button>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-200 transition hover:from-sky-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2">Opslaan</button>
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
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_parent_email" class="block text-sm font-medium text-slate-700">E-mail ouder/verzorger</label>
                        <input id="student_parent_email" name="parent_email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                    <div>
                        <label for="student_parent_phone" class="block text-sm font-medium text-slate-700">Telefoon ouder/verzorger</label>
                        <input id="student_parent_phone" name="parent_phone" type="tel" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
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
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_parent_email" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                        <span>Ouder e-mail</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_parent_phone" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                        <span>Ouder telefoon</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" data-close-student-modal class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300">Annuleren</button>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-200 transition hover:from-emerald-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">Opslaan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/resource-timegrid@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const calendarElement = document.getElementById('calendar');
            if (!calendarElement) {
                return;
            }
            const config = JSON.parse(calendarElement.getAttribute('data-planning-config') || '{}');
            if (!window.FullCalendar || !FullCalendar.Calendar) {
                console.error('FullCalendar kon niet geladen worden.');
                return;
            }
            const rangeLabel = document.getElementById('calendar-range');
            const modal = document.getElementById('event-modal');
            const studentModal = document.getElementById('student-modal');
            const modalForm = document.getElementById('event-form');
            const studentForm = document.getElementById('student-form');
            const closeButtons = modal.querySelectorAll('[data-close-modal]');
            const closeStudentButtons = studentModal.querySelectorAll('[data-close-student-modal]');
            const studentSearch = document.getElementById('student-search');
            const studentResults = document.getElementById('student-results');
            const selectedStudent = document.getElementById('selected-student');
            const selectedStudentDetails = document.getElementById('selected-student-details');
            const deleteStudentButton = document.getElementById('delete-student');
            const studentIdInput = document.getElementById('student_id');
            const eventIdInput = document.getElementById('event_id');
            const statusSelect = document.getElementById('status');
            const vehicleInput = document.getElementById('vehicle');
            const packageInput = document.getElementById('package');
            const locationInput = document.getElementById('location');
            const calendarError = document.getElementById('calendar-error');
            const emailInput = document.getElementById('email');
            const emailDisplay = document.getElementById('email-display');
            const emailLink = document.getElementById('email-link');
            const toggleEmailButton = document.getElementById('toggle-email-edit');
            const phoneInput = document.getElementById('phone');
            const phoneDisplay = document.getElementById('phone-display');
            const phoneLink = document.getElementById('phone-link');
            const togglePhoneButton = document.getElementById('toggle-phone-edit');
            const parentEmailInput = document.getElementById('parent_email');
            const parentEmailDisplay = document.getElementById('parent-email-display');
            const parentEmailLink = document.getElementById('parent-email-link');
            const toggleParentEmailButton = document.getElementById('toggle-parent-email-edit');
            const parentPhoneInput = document.getElementById('parent_phone');
            const parentPhoneDisplay = document.getElementById('parent-phone-display');
            const parentPhoneLink = document.getElementById('parent-phone-link');
            const toggleParentPhoneButton = document.getElementById('toggle-parent-phone-edit');
            const notifyStudentEmailInput = document.getElementById('notify-student-email');
            const notifyStudentPhoneInput = document.getElementById('notify-student-phone');
            const notifyParentEmailInput = document.getElementById('notify-parent-email');
            const notifyParentPhoneInput = document.getElementById('notify-parent-phone');
            const descriptionInput = document.getElementById('description');
            const startInput = document.getElementById('start_time');
            const endInput = document.getElementById('end_time');
            const openStudentModalButtons = document.querySelectorAll('[data-open-student-modal]');
            const modalTitle = document.getElementById('event-modal-title');
            const summaryStudent = document.getElementById('summary-student');
            const summaryInstructor = document.getElementById('summary-instructor');
            const summaryStatus = document.getElementById('summary-status');
            const summaryLocation = document.getElementById('summary-location');
            const instructorSelect = document.getElementById('instructor_id');
            const instructorFilter = document.getElementById('instructor-filter');
            const statusFilter = document.getElementById('status-filter');
            const instructorLookup = new Map((config.instructors || []).map((instructor) => [String(instructor.id), instructor.name]));
            let calendar;
            const dayGridPlugin = FullCalendar?.dayGridPlugin || FullCalendar?.DayGrid;
            const timeGridPlugin = FullCalendar?.timeGridPlugin || FullCalendar?.TimeGrid;
            const interactionPlugin = FullCalendar?.interactionPlugin || FullCalendar?.Interaction;
            const resourceTimeGridPlugin = FullCalendar?.resourceTimeGridPlugin || FullCalendar?.ResourceTimeGrid;
            const hasResourceSupport = Boolean(resourceTimeGridPlugin);

            const plugins = [];
            if (dayGridPlugin) plugins.push(dayGridPlugin);
            if (timeGridPlugin) plugins.push(timeGridPlugin);
            if (interactionPlugin) plugins.push(interactionPlugin);
            if (config.userRole === 'admin' && hasResourceSupport && resourceTimeGridPlugin) {
                plugins.push(resourceTimeGridPlugin);
            }

            if (config.userRole === 'admin' && !hasResourceSupport) {
                document.querySelector('[data-calendar-view="resourceTimeGridWeek"]')?.remove();
                console.warn('FullCalendar resource plug-in niet beschikbaar; standaardweergave wordt gebruikt.');
            }

            const colorByStatus = {
                les: '#10b981',
                proefles: '#0ea5e9',
                examen: '#f59e0b',
                ziek: '#f43f5e',
            };

            const statusLabels = {
                les: 'Les',
                proefles: 'Proefles',
                examen: 'Examen',
                ziek: 'Ziek',
            };

            let selectedStudentData = null;

            function escapeHtml(value) {
                if (value === undefined || value === null) {
                    return '';
                }
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function normalisePhoneHref(value) {
                return `tel:${(value || '').replace(/[^0-9+]/g, '')}`;
            }

            function createContactEditor({ input, display, link, toggleButton, editLabel, saveLabel, hrefFormatter }) {
                let editing = false;

                function applyDisplay(rawValue) {
                    const value = rawValue || '';
                    const hasValue = value.trim() !== '';
                    const emptyLabel = link.dataset.emptyLabel || 'Niet ingesteld';
                    link.textContent = hasValue ? value : emptyLabel;
                    link.href = hasValue ? hrefFormatter(value) : '#';
                    if (hasValue) {
                        link.classList.add('text-sky-600');
                        link.classList.remove('text-slate-400');
                    } else {
                        link.classList.add('text-slate-400');
                        link.classList.remove('text-sky-600');
                    }
                }

                function setValue(rawValue) {
                    input.value = rawValue || '';
                    applyDisplay(input.value);
                    if (editing) {
                        editing = false;
                        input.classList.add('hidden');
                        display.classList.remove('hidden');
                        toggleButton.textContent = editLabel;
                    }
                }

                function toggle(force) {
                    const nextState = typeof force === 'boolean' ? force : !editing;
                    if (nextState === editing) {
                        if (!nextState) {
                            applyDisplay(input.value);
                        }
                        return;
                    }
                    editing = nextState;
                    input.classList.toggle('hidden', !editing);
                    display.classList.toggle('hidden', editing);
                    toggleButton.textContent = editing ? saveLabel : editLabel;
                    if (editing) {
                        input.focus();
                    } else {
                        applyDisplay(input.value);
                    }
                }

                toggleButton.addEventListener('click', () => toggle());
                link.addEventListener('click', (event) => {
                    if (!input.value) {
                        event.preventDefault();
                    }
                });

                return {
                    setValue,
                    toggle,
                    getValue: () => input.value || '',
                    ensureView: () => {
                        if (editing) {
                            toggle(false);
                        } else {
                            applyDisplay(input.value);
                        }
                    },
                };
            }

            const studentEmailEditor = createContactEditor({
                input: emailInput,
                display: emailDisplay,
                link: emailLink,
                toggleButton: toggleEmailButton,
                editLabel: 'Bewerk',
                saveLabel: 'Opslaan e-mail',
                hrefFormatter: (value) => `mailto:${value}`,
            });

            const studentPhoneEditor = createContactEditor({
                input: phoneInput,
                display: phoneDisplay,
                link: phoneLink,
                toggleButton: togglePhoneButton,
                editLabel: 'Bewerk',
                saveLabel: 'Opslaan nummer',
                hrefFormatter: (value) => normalisePhoneHref(value),
            });

            const parentEmailEditor = createContactEditor({
                input: parentEmailInput,
                display: parentEmailDisplay,
                link: parentEmailLink,
                toggleButton: toggleParentEmailButton,
                editLabel: 'Bewerk',
                saveLabel: 'Opslaan e-mail',
                hrefFormatter: (value) => `mailto:${value}`,
            });

            const parentPhoneEditor = createContactEditor({
                input: parentPhoneInput,
                display: parentPhoneDisplay,
                link: parentPhoneLink,
                toggleButton: toggleParentPhoneButton,
                editLabel: 'Bewerk',
                saveLabel: 'Opslaan nummer',
                hrefFormatter: (value) => normalisePhoneHref(value),
            });

            studentEmailEditor.setValue('');
            studentPhoneEditor.setValue('');
            parentEmailEditor.setValue('');
            parentPhoneEditor.setValue('');

            function formatDisplayDate(dateString) {
                if (!dateString) {
                    return null;
                }
                const date = new Date(dateString);
                if (Number.isNaN(date.getTime())) {
                    return null;
                }
                return date.toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' });
            }

            function renderSelectedStudent(student) {
                if (!student) {
                    selectedStudentDetails.innerHTML = '';
                    selectedStudent.classList.add('hidden');
                    deleteStudentButton?.classList.add('hidden');
                    return;
                }

                const contactParts = [student.email ?? 'Geen e-mail', student.phone ?? 'Geen telefoon'];
                const metaParts = [];
                const birth = formatDisplayDate(student.birth_date);
                if (birth) {
                    metaParts.push(`Geboren: ${birth}`);
                }
                const parentParts = [];
                if (student.parent_email) {
                    parentParts.push(`Ouder e-mail: ${student.parent_email}`);
                }
                if (student.parent_phone) {
                    parentParts.push(`Ouder tel: ${student.parent_phone}`);
                }
                if (parentParts.length) {
                    metaParts.push(parentParts.join(' · '));
                }

                selectedStudentDetails.innerHTML = `
                    <div class="font-semibold text-slate-800">${escapeHtml(student.full_name)}</div>
                    <div class="text-xs text-slate-500">${escapeHtml(contactParts.join(' · '))}</div>
                    ${metaParts.length ? `<div class="text-[11px] text-slate-400">${escapeHtml(metaParts.join(' · '))}</div>` : ''}
                `;
                selectedStudent.classList.remove('hidden');
                deleteStudentButton?.classList.remove('hidden');
            }

            function setSelectedStudent(student, options = {}) {
                const preserveContact = options.preserveContact ?? false;
                selectedStudentData = student;
                if (student) {
                    studentIdInput.value = student.id;
                    renderSelectedStudent(student);
                    if (!preserveContact) {
                        vehicleInput.value = student.vehicle || '';
                        packageInput.value = student.package || '';
                        locationInput.value = student.location || '';
                        studentEmailEditor.setValue(student.email || '');
                        studentPhoneEditor.setValue(student.phone || '');
                        parentEmailEditor.setValue(student.parent_email || '');
                        parentPhoneEditor.setValue(student.parent_phone || '');
                        notifyStudentEmailInput.checked = student.notify_student_email ?? true;
                        notifyStudentPhoneInput.checked = student.notify_student_phone ?? true;
                        notifyParentEmailInput.checked = student.notify_parent_email ?? false;
                        notifyParentPhoneInput.checked = student.notify_parent_phone ?? false;
                    }
                } else {
                    studentIdInput.value = '';
                    renderSelectedStudent(null);
                    if (!preserveContact) {
                        vehicleInput.value = '';
                        packageInput.value = '';
                        locationInput.value = '';
                        studentEmailEditor.setValue('');
                        studentPhoneEditor.setValue('');
                        parentEmailEditor.setValue('');
                        parentPhoneEditor.setValue('');
                        notifyStudentEmailInput.checked = true;
                        notifyStudentPhoneInput.checked = true;
                        notifyParentEmailInput.checked = false;
                        notifyParentPhoneInput.checked = false;
                    }
                }
                studentSearch.focus();
                refreshSummary();
            }

            function toLocalInputValue(dateString) {
                if (!dateString) {
                    return '';
                }
                const date = new Date(dateString);
                const tzOffset = date.getTimezoneOffset() * 60000;
                return new Date(date.getTime() - tzOffset).toISOString().slice(0, 16);
            }

            function toIsoString(value) {
                if (!value) {
                    return null;
                }
                const date = new Date(value);
                return Number.isNaN(date.getTime()) ? null : date.toISOString();
            }

            function openModal(mode, payload) {
                modal.dataset.mode = mode;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modalForm.reset();
                descriptionInput.value = '';
                studentResults.innerHTML = '';
                studentSearch.value = '';
                setSelectedStudent(null);
                studentEmailEditor.ensureView();
                studentPhoneEditor.ensureView();
                parentEmailEditor.ensureView();
                parentPhoneEditor.ensureView();

                if (mode === 'create') {
                    modalTitle.textContent = 'Afspraak plannen';
                    eventIdInput.value = '';
                    const { start, end, instructorId } = payload;
                    startInput.value = toLocalInputValue(start.toISOString());
                    endInput.value = toLocalInputValue(end.toISOString());
                    if (config.userRole === 'admin') {
                        const initialInstructor = instructorId || (config.instructors[0] ? config.instructors[0].id : '');
                        if (instructorSelect) {
                            instructorSelect.value = initialInstructor ? String(initialInstructor) : '';
                        }
                    }
                    summaryInstructor.textContent = instructorSelect ? instructorSelect.options[instructorSelect.selectedIndex]?.textContent ?? '-' : payload.instructorName || '-';
                    summaryStatus.textContent = statusLabels[statusSelect.value] ?? statusSelect.value;
                    summaryLocation.textContent = locationInput.value || '-';
                    summaryStudent.textContent = '-';
                } else if (mode === 'edit') {
                    modalTitle.textContent = 'Afspraak bewerken';
                    const { event } = payload;
                    const props = event.extendedProps;
                    eventIdInput.value = event.id;
                    startInput.value = toLocalInputValue(event.startStr);
                    endInput.value = toLocalInputValue(event.endStr || event.startStr);
                    statusSelect.value = props.status;
                    vehicleInput.value = props.vehicle || '';
                    packageInput.value = props.package || '';
                    locationInput.value = props.location || '';
                    descriptionInput.value = props.description || '';
                    summaryStudent.textContent = props.student_name || '-';
                    summaryInstructor.textContent = props.instructor_name || '-';
                    summaryStatus.textContent = statusLabels[props.status] ?? props.status;
                    summaryLocation.textContent = props.location || '-';
                    if (config.userRole === 'admin' && instructorSelect) {
                        instructorSelect.value = String(props.instructor_id);
                    }
                    setSelectedStudent({
                        id: props.student_id,
                        full_name: props.student_name,
                        email: props.student_email,
                        phone: props.student_phone,
                        parent_email: props.student_parent_email,
                        parent_phone: props.student_parent_phone,
                        birth_date: props.student_birth_date,
                        package: props.package,
                        vehicle: props.vehicle,
                        location: props.location,
                        notify_student_email: props.student_notify_student_email,
                        notify_student_phone: props.student_notify_student_phone,
                        notify_parent_email: props.student_notify_parent_email,
                        notify_parent_phone: props.student_notify_parent_phone,
                    }, { preserveContact: true });
                    studentEmailEditor.setValue(props.email ?? props.student_email ?? '');
                    studentPhoneEditor.setValue(props.phone ?? props.student_phone ?? '');
                    parentEmailEditor.setValue(props.parent_email ?? props.student_parent_email ?? '');
                    parentPhoneEditor.setValue(props.parent_phone ?? props.student_parent_phone ?? '');
                    notifyStudentEmailInput.checked = props.notify_student_email ?? props.student_notify_student_email ?? true;
                    notifyStudentPhoneInput.checked = props.notify_student_phone ?? props.student_notify_student_phone ?? true;
                    notifyParentEmailInput.checked = props.notify_parent_email ?? props.student_notify_parent_email ?? false;
                    notifyParentPhoneInput.checked = props.notify_parent_phone ?? props.student_notify_parent_phone ?? false;
                    studentEmailEditor.ensureView();
                    studentPhoneEditor.ensureView();
                    parentEmailEditor.ensureView();
                    parentPhoneEditor.ensureView();
                }
                refreshSummary();
            }

            function closeModal() {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            function openStudentModal() {
                studentModal.classList.remove('hidden');
                studentModal.classList.add('flex');
                studentForm.reset();
                document.getElementById('student_first_name').focus();
            }

            function closeStudentModal() {
                studentModal.classList.remove('flex');
                studentModal.classList.add('hidden');
            }

            openStudentModalButtons.forEach((button) => {
                button.addEventListener('click', openStudentModal);
            });

            closeStudentButtons.forEach((button) => {
                button.addEventListener('click', closeStudentModal);
            });

            studentModal.addEventListener('click', (event) => {
                if (event.target === studentModal) {
                    closeStudentModal();
                }
            });

            let searchTimeout;

            async function fetchStudents(params = {}) {
                const searchParams = new URLSearchParams();
                if (params.query) {
                    searchParams.set('query', params.query);
                }
                if (params.initial) {
                    searchParams.set('initial', '1');
                }
                const response = await fetch(`/students/search?${searchParams.toString()}`);
                if (!response.ok) {
                    return [];
                }
                return response.json();
            }

            async function deleteStudent(studentId) {
                const response = await fetch(`/students/${studentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                        Accept: 'application/json',
                    },
                });
                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    throw new Error(error.message ?? 'Kon leerling niet verwijderen.');
                }
            }

            function renderStudentResults(students) {
                studentResults.innerHTML = '';
                if (!students.length) {
                    const empty = document.createElement('p');
                    empty.className = 'rounded-xl bg-white px-4 py-3 text-xs text-slate-500 shadow-inner';
                    empty.textContent = 'Geen leerlingen gevonden.';
                    studentResults.appendChild(empty);
                    return;
                }

                students.forEach((student) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'fancy-chip w-full rounded-xl border border-slate-200 bg-white/90 px-4 py-3 text-left text-sm text-slate-700 transition hover:border-sky-400 hover:bg-sky-50';
                    const contactLine = `${student.email ?? 'Geen e-mail'} · ${student.phone ?? 'Geen telefoon'}`;
                    const detailParts = [];
                    const birth = formatDisplayDate(student.birth_date);
                    if (birth) {
                        detailParts.push(`Geboren: ${birth}`);
                    }
                    if (student.parent_email) {
                        detailParts.push(`Ouder e-mail: ${student.parent_email}`);
                    }
                    if (student.parent_phone) {
                        detailParts.push(`Ouder tel: ${student.parent_phone}`);
                    }
                    const detailLine = detailParts.length
                        ? `<div class="text-[11px] text-slate-400">${escapeHtml(detailParts.join(' · '))}</div>`
                        : '';
                    button.innerHTML = `
                        <div class="font-semibold">${escapeHtml(student.full_name)}</div>
                        <div class="text-xs text-slate-500">${escapeHtml(contactLine)}</div>
                        ${detailLine}
                    `;
                    button.addEventListener('click', () => {
                        setSelectedStudent(student);
                        studentResults.innerHTML = '';
                        refreshSummary();
                    });
                    studentResults.appendChild(button);
                });
            }

            studentSearch.addEventListener('focus', async () => {
                if (studentResults.childElementCount === 0) {
                    const students = await fetchStudents({ initial: true });
                    renderStudentResults(students);
                }
            });

            studentSearch.addEventListener('input', () => {
                const value = studentSearch.value.trim();
                clearTimeout(searchTimeout);
                if (value.length < 2) {
                    studentResults.innerHTML = '';
                    return;
                }
                searchTimeout = setTimeout(async () => {
                    const students = await fetchStudents({ query: value });
                    renderStudentResults(students);
                }, 250);
            });

            studentForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(studentForm);
                const response = await fetch('/students', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: formData,
                });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    alert(errorData.message ?? 'Kon leerling niet opslaan.');
                    return;
                }
                const student = await response.json();
                setSelectedStudent(student);
                studentSearch.value = '';
                studentResults.innerHTML = '';
                closeStudentModal();
            });

            deleteStudentButton?.addEventListener('click', async () => {
                if (!selectedStudentData?.id) {
                    return;
                }
                const confirmed = window.confirm(
                    'Weet je zeker dat je deze leerling wilt verwijderen? Bestaande afspraken voor deze leerling worden ook verwijderd.',
                );
                if (!confirmed) {
                    return;
                }
                const previousLabel = deleteStudentButton.textContent;
                try {
                    deleteStudentButton.disabled = true;
                    deleteStudentButton.textContent = 'Verwijderen...';
                    await deleteStudent(selectedStudentData.id);
                    alert('Leerling is verwijderd.');
                    setSelectedStudent(null);
                    studentSearch.value = '';
                    studentResults.innerHTML = '';
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                } catch (error) {
                    alert(error.message);
                } finally {
                    deleteStudentButton.disabled = false;
                    deleteStudentButton.textContent = previousLabel;
                }
            });

            function refreshSummary() {
                summaryStudent.textContent = selectedStudentData?.full_name ?? '-';
                if (config.userRole === 'admin' && instructorSelect) {
                    summaryInstructor.textContent = instructorSelect.value
                        ? instructorSelect.options[instructorSelect.selectedIndex]?.textContent ?? '-'
                        : '-';
                }
                summaryStatus.textContent = statusLabels[statusSelect.value] ?? statusSelect.value;
                summaryLocation.textContent = locationInput.value || '-';
            }

            statusSelect.addEventListener('change', refreshSummary);
            locationInput.addEventListener('input', refreshSummary);
            instructorSelect?.addEventListener('change', refreshSummary);

            function getActiveInstructorIds() {
                if (!instructorFilter) {
                    return [];
                }
                const activeButtons = [...instructorFilter.querySelectorAll('[data-instructor-filter]')].filter((button) => button.dataset.active === 'true');
                return activeButtons.map((button) => Number.parseInt(button.dataset.instructorFilter, 10));
            }

            function getActiveStatuses() {
                const activeButtons = [...statusFilter.querySelectorAll('[data-status-filter]')].filter((button) => button.dataset.active === 'true');
                return activeButtons.map((button) => button.dataset.statusFilter);
            }

            const baseFilterClasses = 'fancy-chip flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold transition backdrop-blur';
            const activeFilterClasses = 'border-sky-500 bg-white/90 text-sky-700 shadow-sm shadow-sky-100';
            const inactiveFilterClasses = 'border-slate-200 bg-white/60 text-slate-500 hover:border-slate-300 hover:text-slate-700';

            function updateFilterButton(button, active) {
                button.dataset.active = active ? 'true' : 'false';
                button.className = `${baseFilterClasses} ${active ? activeFilterClasses : inactiveFilterClasses}`;
            }

            function attachFilterHandlers(container) {
                container?.querySelectorAll('button').forEach((button) => {
                    updateFilterButton(button, button.dataset.active !== 'false');
                    button.addEventListener('click', () => {
                        const currentlyActive = button.dataset.active === 'true';
                        updateFilterButton(button, !currentlyActive);
                        if (calendar) {
                            updateResources();
                            calendar.refetchEvents();
                        }
                    });
                });
            }

            attachFilterHandlers(instructorFilter);
            attachFilterHandlers(statusFilter);

            function updateResources() {
                if (!hasResourceSupport || !calendar || !calendar.view || !calendar.view.type.includes('resource')) {
                    return;
                }
                if (!instructorFilter) {
                    return;
                }
                const activeIds = getActiveInstructorIds().map((id) => String(id));
                const activeSet = new Set(activeIds);
                calendar.getResources().forEach((resource) => {
                    if (!activeSet.has(resource.id)) {
                        resource.remove();
                    }
                });
                activeIds.forEach((id) => {
                    if (!calendar.getResourceById(id)) {
                        const title = instructorLookup.get(id) ?? `Instructeur ${id}`;
                        calendar.addResource({ id, title });
                    }
                });
            }

            const viewButtons = document.querySelectorAll('[data-calendar-view]');
            const baseViewClasses = 'fancy-chip rounded-full px-3 py-1 transition backdrop-blur';
            const activeViewClasses = 'bg-white/90 text-slate-900 shadow-sm shadow-slate-200';
            const inactiveViewClasses = 'text-slate-600 hover:text-slate-900';

            function updateViewButtons(activeView) {
                viewButtons.forEach((button) => {
                    const isActive = button.dataset.calendarView === activeView;
                    button.className = `${baseViewClasses} ${isActive ? activeViewClasses : inactiveViewClasses}`;
                });
            }

            function formatRange() {
                const calendarDate = calendar.getDate();
                const view = calendar.view.type;
                if (view === 'dayGridMonth') {
                    rangeLabel.textContent = calendarDate.toLocaleDateString('nl-NL', { month: 'long', year: 'numeric' });
                } else if (view === 'timeGridDay') {
                    rangeLabel.textContent = calendarDate.toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                } else {
                    const start = calendar.view.currentStart;
                    const end = new Date(calendar.view.currentEnd.getTime() - 86400000);
                    const sameYear = start.getFullYear() === end.getFullYear();
                    const startFormatter = new Intl.DateTimeFormat('nl-NL', sameYear ? { day: 'numeric', month: 'long' } : { day: 'numeric', month: 'long', year: 'numeric' });
                    const endFormatter = new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' });
                    rangeLabel.textContent = `${startFormatter.format(start)} – ${endFormatter.format(end)}`;
                }
            }

            function mapEventToCalendar(event) {
                return {
                    id: event.id,
                    title: event.student_name ?? 'Onbekende leerling',
                    start: event.start_time,
                    end: event.end_time,
                    resourceId: event.instructor_id ? String(event.instructor_id) : undefined,
                    backgroundColor: colorByStatus[event.status] ?? '#1f2937',
                    borderColor: colorByStatus[event.status] ?? '#1f2937',
                    extendedProps: {
                        status: event.status,
                        instructor_id: event.instructor_id,
                        instructor_name: event.instructor_name,
                        student_id: event.student_id,
                        student_name: event.student_name,
                        student_email: event.student_email,
                        student_phone: event.student_phone,
                        student_parent_email: event.student_parent_email,
                        student_parent_phone: event.student_parent_phone,
                        student_birth_date: event.student_birth_date,
                        student_notify_student_email: event.student_notify_student_email,
                        student_notify_parent_email: event.student_notify_parent_email,
                        student_notify_student_phone: event.student_notify_student_phone,
                        student_notify_parent_phone: event.student_notify_parent_phone,
                        vehicle: event.vehicle,
                        package: event.package,
                        email: event.email,
                        phone: event.phone,
                        parent_email: event.parent_email,
                        parent_phone: event.parent_phone,
                        notify_student_email: event.notify_student_email,
                        notify_parent_email: event.notify_parent_email,
                        notify_student_phone: event.notify_student_phone,
                        notify_parent_phone: event.notify_parent_phone,
                        location: event.location,
                        description: event.description,
                    },
                };
            }

            async function saveEvent(eventId, payload) {
                const url = eventId ? `/events/${eventId}` : '/events';
                const method = eventId ? 'PATCH' : 'POST';
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    throw new Error(error.message ?? 'Er ging iets mis.');
                }
                return response.json();
            }

            async function handleEventMove(info) {
                try {
                    const event = info.event;
                    const props = event.extendedProps;
                    let instructorId = props.instructor_id;
                    if (info.newResource) {
                        instructorId = Number.parseInt(info.newResource.id, 10);
                        props.instructor_id = instructorId;
                        props.instructor_name = info.newResource.title;
                        event.setExtendedProp('instructor_id', instructorId);
                        event.setExtendedProp('instructor_name', info.newResource.title);
                        event.setProp('resourceId', String(instructorId));
                    }
                    const payload = {
                        student_id: props.student_id,
                        status: props.status,
                        start_time: event.start.toISOString(),
                        end_time: (event.end ?? new Date(event.start.getTime() + 60 * 60 * 1000)).toISOString(),
                        vehicle: props.vehicle,
                        package: props.package,
                        email: props.email,
                        phone: props.phone,
                        parent_email: props.parent_email,
                        parent_phone: props.parent_phone,
                        notify_student_email: props.notify_student_email,
                        notify_parent_email: props.notify_parent_email,
                        notify_student_phone: props.notify_student_phone,
                        notify_parent_phone: props.notify_parent_phone,
                        location: props.location,
                        description: props.description,
                    };
                    if (config.userRole === 'admin') {
                        payload.instructor_id = instructorId;
                    }
                    await saveEvent(event.id, payload);
                    calendar.refetchEvents();
                } catch (error) {
                    info.revert();
                    alert(error.message);
                }
            }

            const calendarOptions = {
                locale: 'nl',
                initialView: config.userRole === 'admin' && hasResourceSupport ? 'resourceTimeGridWeek' : 'timeGridWeek',
                height: 'auto',
                headerToolbar: false,
                slotMinTime: '06:00:00',
                slotMaxTime: '21:00:00',
                selectable: true,
                selectMirror: true,
                nowIndicator: true,
                expandRows: true,
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                buttonText: {
                    today: 'Vandaag',
                },
                events: async (fetchInfo, successCallback, failureCallback) => {
                    try {
                        const params = new URLSearchParams({
                            start: fetchInfo.startStr,
                            end: fetchInfo.endStr,
                        });
                        const instructorIds = getActiveInstructorIds();
                        if (instructorFilter && instructorFilter.querySelectorAll('button').length && instructorIds.length === 0) {
                            calendarError?.classList.add('hidden');
                            successCallback([]);
                            return;
                        }
                        if (instructorIds.length) {
                            instructorIds.forEach((id) => params.append('instructor_ids[]', id));
                        }
                        const statuses = getActiveStatuses();
                        if (statusFilter && statuses.length === 0) {
                            calendarError?.classList.add('hidden');
                            successCallback([]);
                            return;
                        }
                        if (statuses.length) {
                            statuses.forEach((status) => params.append('statuses[]', status));
                        }
                        const response = await fetch(`/events?${params.toString()}`);
                        if (!response.ok) {
                            throw new Error('Kon afspraken niet laden.');
                        }
                        const data = await response.json();
                        calendarError?.classList.add('hidden');
                        successCallback(data.map(mapEventToCalendar));
                    } catch (error) {
                        calendarError?.classList.remove('hidden');
                        console.error(error);
                        failureCallback(error);
                    }
                },
                select: (selectionInfo) => {
                    const resource = selectionInfo.resource;
                    const resourceId = resource ? Number.parseInt(resource.id, 10) : null;
                    const resourceTitle = resource ? resource.title : null;
                    const fallbackInstructorId = config.userRole === 'admin'
                        ? (getActiveInstructorIds()[0] ?? (config.instructors[0]?.id ?? null))
                        : (config.instructors[0]?.id ?? null);
                    const fallbackInstructorName = resourceTitle
                        ?? (config.userRole === 'admin'
                            ? (instructorSelect?.options[instructorSelect.selectedIndex]?.textContent
                                ?? (fallbackInstructorId ? instructorLookup.get(String(fallbackInstructorId)) ?? '-' : '-'))
                            : (config.instructors[0]?.name ?? '-'));
                    openModal('create', {
                        start: selectionInfo.start,
                        end: selectionInfo.end,
                        instructorId: resourceId ?? fallbackInstructorId,
                        instructorName: resourceTitle ?? fallbackInstructorName,
                    });
                    calendar.unselect();
                },
                eventClick: (info) => {
                    openModal('edit', { event: info.event });
                },
                eventDrop: handleEventMove,
                eventResize: handleEventMove,
                eventClassNames: () => ['rounded-2xl', 'border-0', 'px-3', 'py-2', 'shadow-lg', 'text-white', 'text-sm', 'leading-tight'],
                eventContent: (arg) => {
                    const props = arg.event.extendedProps;
                    const start = arg.timeText;
                    const statusLabel = statusLabels[props.status] ?? props.status;
                    const locationLine = props.location ? `<div class="text-[11px] opacity-90">${props.location}</div>` : '';
                    return {
                        html: `
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold">${arg.event.title}</span>
                                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] uppercase tracking-wide">${statusLabel}</span>
                                </div>
                                <div class="text-[12px] opacity-90">${start}</div>
                                ${locationLine}
                            </div>
                        `,
                    };
                },
                datesSet: () => {
                    formatRange();
                    updateResources();
                },
            };

            if (plugins.length) {
                calendarOptions.plugins = plugins;
            }

            if (config.userRole === 'admin' && hasResourceSupport) {
                calendarOptions.schedulerLicenseKey = 'GPL-My-Project-Is-Open-Source';
                calendarOptions.resources = (config.instructors || []).map((instructor) => ({
                    id: String(instructor.id),
                    title: instructor.name,
                }));
            }

            calendar = new FullCalendar.Calendar(calendarElement, calendarOptions);

            calendar.render();
            formatRange();
            updateResources();
            updateViewButtons(calendar.view.type);

            document.querySelectorAll('[data-calendar-nav]').forEach((button) => {
                button.addEventListener('click', () => {
                    const action = button.dataset.calendarNav;
                    if (action === 'prev') {
                        calendar.prev();
                    } else if (action === 'next') {
                        calendar.next();
                    } else if (action === 'today') {
                        calendar.today();
                    }
                    formatRange();
                });
            });

            document.querySelectorAll('[data-calendar-view]').forEach((button) => {
                button.addEventListener('click', () => {
                    const view = button.dataset.calendarView;
                    calendar.changeView(view);
                    formatRange();
                    updateResources();
                    updateViewButtons(view);
                });
            });

            modalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!studentIdInput.value) {
                    alert('Selecteer eerst een leerling.');
                    return;
                }
                if (!startInput.value || !endInput.value) {
                    alert('Vul start- en eindtijd in.');
                    return;
                }
                const payload = {
                    student_id: Number.parseInt(studentIdInput.value, 10),
                    status: statusSelect.value,
                    start_time: toIsoString(startInput.value),
                    end_time: toIsoString(endInput.value),
                    vehicle: vehicleInput.value || null,
                    package: packageInput.value || null,
                    location: locationInput.value || null,
                    email: studentEmailEditor.getValue() || null,
                    phone: studentPhoneEditor.getValue() || null,
                    parent_email: parentEmailEditor.getValue() || null,
                    parent_phone: parentPhoneEditor.getValue() || null,
                    notify_student_email: notifyStudentEmailInput.checked,
                    notify_parent_email: notifyParentEmailInput.checked,
                    notify_student_phone: notifyStudentPhoneInput.checked,
                    notify_parent_phone: notifyParentPhoneInput.checked,
                    description: descriptionInput.value || null,
                };
                if (config.userRole === 'admin' && instructorSelect) {
                    if (!instructorSelect.value) {
                        alert('Selecteer een instructeur.');
                        return;
                    }
                    payload.instructor_id = Number.parseInt(instructorSelect.value, 10);
                }
                const eventId = eventIdInput.value || null;
                try {
                    await saveEvent(eventId, payload);
                    closeModal();
                    calendar.refetchEvents();
                } catch (error) {
                    alert(error.message);
                }
            });
        });
    </script>
</x-layouts.app>
