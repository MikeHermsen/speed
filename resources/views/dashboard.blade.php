<x-layouts.app title="Planning">
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Planningsoverzicht</h1>
                    <p class="text-sm text-slate-500">Week van <span id="week-range"></span></p>
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
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $user->isAdmin() ? 'Overzicht per instructeur' : 'Jouw lessen' }}</h2>
                        <p class="text-sm text-slate-500">Klik op een tijdslot om een leerling in te plannen.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                        <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>Les</span>
                        <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-sky-400"></span>Proefles</span>
                        <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Examen</span>
                        <span class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>Ziek</span>
                    </div>
                </div>

                <div id="calendar" class="space-y-10"></div>
            </div>
        </main>
    </div>

    <div id="event-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/30 p-4">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Nieuwe planning</h3>
                    <p class="text-sm text-slate-500">Selecteer een leerling en vul de details in.</p>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100" data-close-modal>
                    <span class="sr-only">Sluiten</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                        <path
                            fill-rule="evenodd"
                            d="M10 8.586 4.757 3.343 3.343 4.757 8.586 10l-5.243 5.243 1.414 1.414L10 11.414l5.243 5.243 1.414-1.414L11.414 10l5.243-5.243-1.414-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>

            <form id="event-form" class="mt-6 space-y-6">
                @csrf
                <input type="hidden" name="student_id" id="student_id" />
                <input type="hidden" name="start_time" id="start_time" />
                <input type="hidden" name="end_time" id="end_time" />
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
                    <label class="block text-sm font-medium text-slate-700">Leerling zoeken</label>
                    <input
                        type="search"
                        id="student-search"
                        placeholder="Zoek op naam of e-mail"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    />
                    <div id="student-results" class="mt-2 max-h-48 space-y-2 overflow-y-auto"></div>
                    <p id="selected-student" class="mt-2 hidden rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700"></p>
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
                        <label for="location" class="block text-sm font-medium text-slate-700">Locatie</label>
                        <input id="location" name="location" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">E-mail</label>
                        <input id="email" name="email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700">Telefoon</label>
                        <input id="phone" name="phone" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" />
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">Omschrijving</label>
                    <textarea id="description" name="description" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" data-close-modal class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300">Annuleren</button>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-200 transition hover:from-sky-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2">
                        Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.planningConfig = {
            csrfToken: '{{ csrf_token() }}',
            userRole: '{{ $user->role }}',
            weekStart: '{{ $weekStart }}',
            instructors: @json($instructors),
            events: @json($events),
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const config = window.planningConfig;
            const calendarRoot = document.getElementById('calendar');
            const modal = document.getElementById('event-modal');
            const modalForm = document.getElementById('event-form');
            const closeButtons = modal.querySelectorAll('[data-close-modal]');
            const studentSearch = document.getElementById('student-search');
            const studentResults = document.getElementById('student-results');
            const selectedStudent = document.getElementById('selected-student');
            const studentIdInput = document.getElementById('student_id');
            const startInput = document.getElementById('start_time');
            const endInput = document.getElementById('end_time');
            const statusSelect = document.getElementById('status');
            const vehicleInput = document.getElementById('vehicle');
            const packageInput = document.getElementById('package');
            const locationInput = document.getElementById('location');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const descriptionInput = document.getElementById('description');
            const instructorSelect = document.getElementById('instructor_id');
            const weekRangeLabel = document.getElementById('week-range');

            const hourSlots = Array.from({ length: 12 }, (_, index) => index + 7); // 07:00 - 18:00
            const dayFormatter = new Intl.DateTimeFormat('nl-NL', { weekday: 'short', day: 'numeric', month: 'long' });
            const timeFormatter = new Intl.DateTimeFormat('nl-NL', { hour: '2-digit', minute: '2-digit' });
            const colorByStatus = {
                les: 'bg-emerald-500',
                proefles: 'bg-sky-500',
                examen: 'bg-amber-500',
                ziek: 'bg-rose-500',
            };

            const weekStartDate = new Date(config.weekStart);
            const weekDays = Array.from({ length: 7 }, (_, index) => {
                const date = new Date(weekStartDate);
                date.setDate(weekStartDate.getDate() + index);
                return date;
            });

            const weekEndDate = new Date(weekDays[6]);
            weekRangeLabel.textContent = `${dayFormatter.format(weekStartDate)} – ${dayFormatter.format(weekEndDate)}`;

            let currentEvents = [...config.events];

            function slotKey(date) {
                const d = new Date(date);
                return [
                    d.getFullYear(),
                    String(d.getMonth() + 1).padStart(2, '0'),
                    String(d.getDate()).padStart(2, '0'),
                    String(d.getHours()).padStart(2, '0'),
                ].join('-');
            }

            function buildCalendar() {
                calendarRoot.innerHTML = '';

                config.instructors.forEach((instructor) => {
                    const card = document.createElement('section');
                    card.className = 'rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-inner';

                    const heading = document.createElement('div');
                    heading.className = 'flex items-center justify-between gap-4';
                    heading.innerHTML = `
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">${instructor.name}</h3>
                            <p class="text-xs text-slate-500">Weekoverzicht</p>
                        </div>
                    `;
                    card.appendChild(heading);

                    const grid = document.createElement('div');
                    grid.className = 'mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white';

                    const headerRow = document.createElement('div');
                    headerRow.className = 'grid grid-cols-[80px_repeat(7,minmax(0,1fr))] bg-slate-100 text-xs font-medium uppercase text-slate-500';
                    headerRow.appendChild(document.createElement('div'));
                    weekDays.forEach((day) => {
                        const cell = document.createElement('div');
                        cell.className = 'border-l border-slate-200 px-3 py-2';
                        cell.textContent = dayFormatter.format(day);
                        headerRow.appendChild(cell);
                    });
                    grid.appendChild(headerRow);

                    hourSlots.forEach((hour) => {
                        const row = document.createElement('div');
                        row.className = 'grid grid-cols-[80px_repeat(7,minmax(0,1fr))]';

                        const timeCell = document.createElement('div');
                        timeCell.className = 'flex h-24 items-start justify-end border-t border-slate-100 px-3 pt-4 text-xs text-slate-400';
                        timeCell.textContent = `${String(hour).padStart(2, '0')}:00`;
                        row.appendChild(timeCell);

                        weekDays.forEach((day) => {
                            const slot = document.createElement('button');
                            slot.type = 'button';
                            slot.className = 'relative flex h-24 flex-col border-l border-t border-slate-100 bg-white px-2 py-2 text-left transition hover:bg-sky-50';
                            const slotDate = new Date(day);
                            slotDate.setHours(hour, 0, 0, 0);
                            slot.dataset.key = `${instructor.id}-${slotKey(slotDate)}`;
                            slot.dataset.instructorId = instructor.id;
                            slot.dataset.startIso = slotDate.toISOString();
                            const endDate = new Date(slotDate);
                            endDate.setHours(endDate.getHours() + 1);
                            slot.dataset.endIso = endDate.toISOString();

                            slot.addEventListener('click', () => openModal({
                                instructorId: instructor.id,
                                start: slot.dataset.startIso,
                                end: slot.dataset.endIso,
                            }));

                            row.appendChild(slot);
                        });

                        grid.appendChild(row);
                    });

                    card.appendChild(grid);
                    calendarRoot.appendChild(card);
                });

                renderEvents();
            }

            function renderEvents() {
                document.querySelectorAll('[data-event-pill]').forEach((element) => element.remove());

                currentEvents.forEach((event) => {
                    const key = `${event.instructor_id}-${slotKey(event.start_time)}`;
                    const slot = calendarRoot.querySelector(`[data-key="${key}"]`);
                    if (!slot) {
                        return;
                    }

                    const pill = document.createElement('div');
                    pill.dataset.eventPill = 'true';
                    pill.className = `pointer-events-none absolute inset-1 rounded-2xl px-3 py-2 text-xs text-white shadow ${colorByStatus[event.status] ?? 'bg-slate-500'}`;
                    pill.innerHTML = `
                        <div class="font-semibold leading-tight">${event.student_name ?? 'Onbekend'}</div>
                        <div class="leading-tight opacity-80">${timeFormatter.format(new Date(event.start_time))} – ${timeFormatter.format(new Date(event.end_time))}</div>
                        <div class="leading-tight opacity-80 capitalize">${event.status}</div>
                    `;
                    slot.appendChild(pill);
                });
            }

            function openModal({ instructorId, start, end }) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                startInput.value = start;
                endInput.value = end;
                studentIdInput.value = '';
                studentSearch.value = '';
                selectedStudent.classList.add('hidden');
                selectedStudent.textContent = '';
                studentResults.innerHTML = '';
                statusSelect.value = 'les';
                vehicleInput.value = '';
                packageInput.value = '';
                locationInput.value = '';
                emailInput.value = '';
                phoneInput.value = '';
                descriptionInput.value = '';
                if (instructorSelect) {
                    instructorSelect.value = instructorId ?? '';
                }
                studentSearch.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            closeButtons.forEach((button) => button.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            let searchTimeout;
            studentSearch.addEventListener('input', () => {
                const value = studentSearch.value.trim();
                clearTimeout(searchTimeout);

                if (value.length < 2) {
                    studentResults.innerHTML = '';
                    return;
                }

                searchTimeout = setTimeout(async () => {
                    const response = await fetch(`/students/search?query=${encodeURIComponent(value)}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });
                    if (!response.ok) {
                        return;
                    }
                    const students = await response.json();
                    studentResults.innerHTML = '';
                    if (students.length === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'rounded-xl bg-slate-100 px-3 py-2 text-xs text-slate-500';
                        empty.textContent = 'Geen leerlingen gevonden.';
                        studentResults.appendChild(empty);
                        return;
                    }

                    students.forEach((student) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-700 transition hover:border-sky-400 hover:bg-sky-50';
                        button.innerHTML = `
                            <div class="font-semibold">${student.full_name}</div>
                            <div class="text-xs text-slate-500">${student.email ?? 'Geen e-mail'} · ${student.phone ?? 'Geen telefoon'}</div>
                        `;
                        button.addEventListener('click', () => {
                            studentIdInput.value = student.id;
                            selectedStudent.textContent = `${student.full_name} (${student.email ?? 'geen e-mail'})`;
                            selectedStudent.classList.remove('hidden');
                            studentResults.innerHTML = '';
                            vehicleInput.value = student.vehicle ?? '';
                            packageInput.value = student.package ?? '';
                            locationInput.value = student.location ?? '';
                            emailInput.value = student.email ?? '';
                            phoneInput.value = student.phone ?? '';
                        });
                        studentResults.appendChild(button);
                    });
                }, 250);
            });

            modalForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (!studentIdInput.value) {
                    alert('Selecteer eerst een leerling.');
                    return;
                }

                const formData = new FormData(modalForm);
                const response = await fetch('/events', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                        Accept: 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    alert(errorData.message ?? 'Er is iets misgegaan bij het opslaan.');
                    return;
                }

                const savedEvent = await response.json();
                currentEvents.push(savedEvent);
                renderEvents();
                closeModal();
            });

            buildCalendar();
        });
    </script>
</x-layouts.app>
