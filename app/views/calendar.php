<?php
require __DIR__ . '/../bootstrap.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        window.APP_USER = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: 80px repeat(7, minmax(160px, 1fr));
        }
        .calendar-grid .time-cell {
            border-right: 1px solid #e2e8f0;
        }
        .calendar-grid .day-column {
            position: relative;
            border-left: 1px solid #f1f5f9;
        }
        .calendar-grid .day-column::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(to bottom, rgba(226,232,240,0.35) 1px, transparent 1px);
            background-size: 100% 56px;
            pointer-events: none;
        }
        .event-card {
            position: absolute;
            left: 8px;
            right: 8px;
            border-radius: 14px;
            padding: 10px 12px;
            color: #0f172a;
            background: linear-gradient(135deg, rgba(165,180,252,0.95), rgba(129,140,248,0.95));
            box-shadow: 0 20px 45px -24px rgba(79,70,229,0.45);
            display: flex;
            flex-direction: column;
            gap: 4px;
            cursor: pointer;
        }
        .event-card .title {
            font-weight: 600;
        }
        .event-card .meta {
            font-size: 12px;
            color: rgba(15,23,42,0.65);
        }
        .modal-backdrop {
            background: rgba(15, 23, 42, 0.45);
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen" style="font-family: 'Inter', sans-serif;">
    <div class="max-w-7xl mx-auto py-10 px-6 space-y-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-widest text-slate-400 font-semibold">Planning</p>
                <h1 class="text-3xl font-semibold text-slate-900">Welkom terug, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-slate-500">Bekijk en plan lessen supersoepel in een kalenderervaring zoals Google Agenda.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars(strtoupper($user['role']), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-slate-400"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white flex items-center justify-center text-lg font-semibold">
                    <?= strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <a href="/logout" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-white hover:shadow-sm transition text-sm font-medium">Uitloggen</a>
            </div>
        </header>

        <section class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-4 p-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <button id="prevWeek" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                        <span class="sr-only">Vorige week</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.25 19.25L8.75 12l6.5-7.25" /></svg>
                    </button>
                    <button id="nextWeek" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                        <span class="sr-only">Volgende week</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.75 4.75L15.25 12l-6.5 7.25" /></svg>
                    </button>
                    <div class="pl-2">
                        <p class="text-xs uppercase text-slate-400 tracking-widest">Week</p>
                        <p class="text-xl font-semibold text-slate-900" id="weekLabel"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button id="todayButton" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium shadow-sm hover:bg-slate-800 transition">Vandaag</button>
                    <button id="newEventButton" class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-500 text-white text-sm font-medium shadow-indigo-200 hover:shadow-lg transition">Nieuw moment</button>
                </div>
            </div>

            <div class="calendar-grid relative">
                <div class="sticky top-0 bg-white z-10 border-r border-slate-200">
                    <div class="h-20 border-b border-slate-100"></div>
                    <?php for ($hour = 6; $hour <= 22; $hour++): ?>
                        <div class="h-14 flex items-start justify-end pr-3 text-xs text-slate-400 font-medium"><?= sprintf('%02d:00', $hour); ?></div>
                    <?php endfor; ?>
                </div>
                <?php for ($day = 0; $day < 7; $day++): ?>
                    <div class="day-column">
                        <div class="h-20 border-b border-slate-100 flex flex-col items-center justify-center" data-day-header></div>
                        <div class="relative" data-day="<?= $day; ?>" style="height: calc((22 - 6 + 1) * 56px);"></div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-xl p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Aankomende lessen</h2>
                <div id="upcomingList" class="space-y-3 max-h-72 overflow-y-auto pr-2 scrollbar-hide text-sm text-slate-600"></div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-xl p-6" id="instructorPanel">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Planning per instructeur</h2>
                <div id="instructorList" class="space-y-4 text-sm text-slate-600"></div>
            </div>
        </section>
    </div>

    <div id="eventModal" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <p class="text-xs uppercase tracking-widest text-indigo-400 font-semibold">Nieuwe planning</p>
                    <h3 class="text-xl font-semibold text-slate-900">Plan leerling in</h3>
                </div>
                <button id="closeModal" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                    <span class="sr-only">Sluiten</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="eventForm" class="px-6 py-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Start</label>
                        <input type="datetime-local" id="startInput" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Einde</label>
                        <input type="datetime-local" id="endInput" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Locatie</label>
                        <input type="text" id="locationInput" placeholder="Bijv. Rotterdam" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Voertuig</label>
                        <input type="text" id="vehicleInput" placeholder="Bijv. Automaat" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Pakket</label>
                        <input type="text" id="packageInput" placeholder="Bijv. Intensief" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div id="instructorSelectWrapper" class="hidden">
                        <label class="text-sm font-medium text-slate-600">Instructeur</label>
                        <select id="instructorSelect" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400"></select>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-600">Omschrijving</label>
                    <textarea id="descriptionInput" rows="3" placeholder="Bijzonderheden of doelen" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-slate-600">Leerling zoeken</label>
                        <span class="text-xs text-slate-400" id="selectedStudentLabel">Geen leerling geselecteerd</span>
                    </div>
                    <input type="search" id="studentSearch" placeholder="Zoek op naam" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400" autocomplete="off">
                    <div id="studentResults" class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-44 overflow-y-auto pr-1 scrollbar-hide"></div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <span class="text-sm text-slate-400" id="formMessage"></span>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-500 text-white font-medium shadow-indigo-200 hover:shadow-lg transition">Opslaan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const state = {
            current: new Date(),
            weekStart: null,
            events: [],
            selectedStudent: null,
        };

        const hours = Array.from({ length: 17 }, (_, i) => i + 6);
        const dayNames = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];

        const weekLabelEl = document.getElementById('weekLabel');
        const dayHeaders = document.querySelectorAll('[data-day-header]');
        const dayColumns = document.querySelectorAll('[data-day]');
        const prevWeekBtn = document.getElementById('prevWeek');
        const nextWeekBtn = document.getElementById('nextWeek');
        const todayBtn = document.getElementById('todayButton');
        const newEventButton = document.getElementById('newEventButton');
        const modal = document.getElementById('eventModal');
        const closeModalButton = document.getElementById('closeModal');
        const eventForm = document.getElementById('eventForm');
        const startInput = document.getElementById('startInput');
        const endInput = document.getElementById('endInput');
        const locationInput = document.getElementById('locationInput');
        const vehicleInput = document.getElementById('vehicleInput');
        const packageInput = document.getElementById('packageInput');
        const descriptionInput = document.getElementById('descriptionInput');
        const studentSearchInput = document.getElementById('studentSearch');
        const studentResults = document.getElementById('studentResults');
        const selectedStudentLabel = document.getElementById('selectedStudentLabel');
        const formMessage = document.getElementById('formMessage');
        const upcomingList = document.getElementById('upcomingList');
        const instructorPanel = document.getElementById('instructorPanel');
        const instructorList = document.getElementById('instructorList');
        const instructorSelectWrapper = document.getElementById('instructorSelectWrapper');
        const instructorSelect = document.getElementById('instructorSelect');

        const isAdmin = window.APP_USER.role === 'admin';

        if (!isAdmin) {
            instructorPanel.classList.add('hidden');
        }

        function startOfWeek(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? -6 : 1);
            d.setDate(diff);
            d.setHours(0,0,0,0);
            return d;
        }

        function endOfWeek(date) {
            const start = startOfWeek(date);
            const end = new Date(start);
            end.setDate(end.getDate() + 6);
            end.setHours(23,59,59,999);
            return end;
        }

        function formatLocalInput(date) {
            const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
            return local.toISOString().slice(0,16);
        }

        function formatDate(date) {
            return date.toLocaleDateString('nl-NL', { day: '2-digit', month: 'short' });
        }

        function updateWeekLabel() {
            const start = state.weekStart;
            const end = endOfWeek(start);
            weekLabelEl.textContent = `${formatDate(start)} – ${formatDate(end)}`;
        }

        function renderHeaders() {
            dayHeaders.forEach((el, index) => {
                const date = new Date(state.weekStart);
                date.setDate(date.getDate() + index);
                el.innerHTML = `
                    <p class="text-xs uppercase text-slate-400 tracking-widest">${dayNames[index]}</p>
                    <p class="text-lg font-semibold text-slate-900">${date.getDate()}</p>
                `;
            });
        }

        function clearEvents() {
            dayColumns.forEach(col => {
                col.innerHTML = '';
            });
        }

        function renderEvents() {
            clearEvents();
            const startWeek = startOfWeek(state.weekStart);

            state.events.forEach(event => {
                const start = new Date(event.start);
                const end = new Date(event.end);
                const dayIndex = (start.getDay() + 6) % 7;
                const column = document.querySelector(`[data-day="${dayIndex}"]`);
                if (!column) return;

                const totalMinutes = (end - start) / 60000;
                const startMinutes = (start.getHours() - 6) * 60 + start.getMinutes();
                const height = Math.max(totalMinutes / 60 * 56, 48);
                const offset = startMinutes / 60 * 56;

                const card = document.createElement('div');
                card.className = 'event-card group';
                card.style.top = `${offset}px`;
                card.style.height = `${height}px`;
                card.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="title">${event.title}</span>
                        <span class="text-xs text-slate-600">${start.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                    <div class="meta">${event.location || 'Locatie onbekend'}</div>
                    <div class="meta">${event.vehicle || 'Voertuig n.b.'} • ${event.package || 'Pakket n.b.'}</div>
                    <div class="meta">${event.student.email}</div>
                    <div class="meta">${event.student.phone}</div>
                    <div class="text-xs text-indigo-700 font-medium">${event.instructor}</div>
                `;

                column.appendChild(card);
            });
        }

        function renderUpcoming() {
            if (!state.events.length) {
                upcomingList.innerHTML = '<p class="text-slate-400 text-sm">Nog geen lessen gepland.</p>';
                return;
            }

            const items = state.events
                .slice()
                .sort((a, b) => new Date(a.start) - new Date(b.start))
                .slice(0, 6)
                .map(event => {
                    const start = new Date(event.start);
                    return `
                        <div class="p-4 rounded-2xl border border-slate-100 hover:border-indigo-200 hover:shadow-sm transition">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-slate-900">${event.title}</p>
                                <span class="text-xs font-semibold text-indigo-500">${start.toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' })}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">${start.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })} • ${event.instructor}</p>
                            <p class="text-xs text-slate-400 mt-1">${event.location || 'Locatie onbekend'}</p>
                        </div>
                    `;
                }).join('');

            upcomingList.innerHTML = items;
        }

        function renderInstructorGroups() {
            if (!isAdmin) return;
            const grouped = {};
            state.events.forEach(event => {
                if (!grouped[event.instructor]) grouped[event.instructor] = [];
                grouped[event.instructor].push(event);
            });

            const html = Object.entries(grouped).map(([instructor, events]) => {
                const total = events.length;
                const upcoming = events.slice().sort((a,b) => new Date(a.start) - new Date(b.start))[0];
                const nextInfo = upcoming ? `${new Date(upcoming.start).toLocaleDateString('nl-NL', { day: 'numeric', month: 'short' })} • ${new Date(upcoming.start).toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })}` : 'Nog niets gepland';
                return `
                    <div class="p-4 rounded-2xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-slate-900">${instructor}</p>
                            <span class="text-xs bg-indigo-50 text-indigo-600 font-medium px-3 py-1 rounded-full">${total} lessen</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Volgende: ${nextInfo}</p>
                    </div>
                `;
            }).join('');

            instructorList.innerHTML = html || '<p class="text-slate-400 text-sm">Nog geen lessen zichtbaar.</p>';
        }

        async function fetchInstructors() {
            if (!isAdmin) return;
            try {
                const response = await fetch('/events?meta=instructors');
                if (!response.ok) throw new Error('Kan instructeurs niet laden');
                const data = await response.json();
                if (Array.isArray(data.instructors)) {
                    instructorSelect.innerHTML = data.instructors.map(inst => `<option value="${inst.id}">${inst.name}</option>`).join('');
                    if (instructorSelect.options.length) {
                        instructorSelect.value = instructorSelect.options[0].value;
                    }
                }
            } catch (error) {
                console.error(error);
            }
        }

        async function loadEvents() {
            const params = new URLSearchParams();
            params.set('start', formatLocalInput(state.weekStart));
            params.set('end', formatLocalInput(endOfWeek(state.weekStart)));
            const response = await fetch(`/events?${params.toString()}`);
            if (!response.ok) {
                console.error('Kan events niet laden');
                return;
            }
            const data = await response.json();
            state.events = data.events || [];
            renderEvents();
            renderUpcoming();
            renderInstructorGroups();
        }

        function showModal(defaultDate) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            formMessage.textContent = '';
            eventForm.reset();
            selectedStudentLabel.textContent = 'Geen leerling geselecteerd';
            state.selectedStudent = null;
            studentResults.innerHTML = '';

            if (isAdmin) {
                instructorSelectWrapper.classList.remove('hidden');
            }

            const start = defaultDate || new Date();
            const end = new Date(start.getTime() + 60 * 60 * 1000);
            startInput.value = formatLocalInput(start);
            endInput.value = formatLocalInput(end);
        }

        function hideModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function attachSlotListeners() {
            dayColumns.forEach(column => {
                column.addEventListener('click', event => {
                    const rect = column.getBoundingClientRect();
                    const offsetY = event.clientY - rect.top;
                    const hourIndex = Math.floor(offsetY / 56);
                    const minutes = Math.floor(((offsetY % 56) / 56) * 60 / 15) * 15;
                    const start = new Date(state.weekStart);
                    const dayIndex = parseInt(column.dataset.day, 10);
                    start.setDate(start.getDate() + dayIndex);
                    start.setHours(hours[0] + hourIndex, minutes, 0, 0);
                    showModal(start);
                });
            });
        }

        async function searchStudents(term) {
            if (!term) {
                studentResults.innerHTML = '';
                return;
            }
            const response = await fetch(`/students?q=${encodeURIComponent(term)}`);
            if (!response.ok) return;
            const data = await response.json();
            studentResults.innerHTML = data.students.map(student => `
                <button type="button" data-student="${encodeURIComponent(JSON.stringify(student))}" class="text-left p-3 rounded-2xl border border-slate-200 hover:border-indigo-200 hover:shadow-sm transition">
                    <p class="font-semibold text-slate-900">${student.name}</p>
                    <p class="text-xs text-slate-500">${student.email}</p>
                    <p class="text-xs text-slate-400">${student.vehicle} • ${student.package}</p>
                </button>
            `).join('');
        }

        studentResults.addEventListener('click', event => {
            const button = event.target.closest('button[data-student]');
            if (!button) return;
            const student = JSON.parse(decodeURIComponent(button.dataset.student));
            state.selectedStudent = student;
            selectedStudentLabel.textContent = `${student.name} (${student.email})`;
            if (!vehicleInput.value) vehicleInput.value = student.vehicle || '';
            if (!packageInput.value) packageInput.value = student.package || '';
            studentResults.querySelectorAll('button').forEach(btn => btn.classList.remove('border-indigo-300', 'bg-indigo-50'));
            button.classList.add('border-indigo-300', 'bg-indigo-50');
        });

        studentSearchInput.addEventListener('input', event => {
            searchStudents(event.target.value);
        });

        prevWeekBtn.addEventListener('click', () => {
            state.weekStart.setDate(state.weekStart.getDate() - 7);
            updateWeekLabel();
            renderHeaders();
            loadEvents();
        });

        nextWeekBtn.addEventListener('click', () => {
            state.weekStart.setDate(state.weekStart.getDate() + 7);
            updateWeekLabel();
            renderHeaders();
            loadEvents();
        });

        todayBtn.addEventListener('click', () => {
            state.weekStart = startOfWeek(new Date());
            updateWeekLabel();
            renderHeaders();
            loadEvents();
        });

        newEventButton.addEventListener('click', () => showModal(new Date()));
        closeModalButton.addEventListener('click', hideModal);
        modal.addEventListener('click', event => {
            if (event.target === modal) hideModal();
        });

        eventForm.addEventListener('submit', async event => {
            event.preventDefault();
            if (!state.selectedStudent) {
                formMessage.textContent = 'Selecteer eerst een leerling.';
                formMessage.className = 'text-sm text-red-500';
                return;
            }

            const payload = {
                start: startInput.value,
                end: endInput.value,
                student_id: state.selectedStudent.id,
                vehicle: vehicleInput.value,
                package: packageInput.value,
                location: locationInput.value,
                description: descriptionInput.value,
            };

            if (isAdmin) {
                payload.instructor_id = instructorSelect.value;
            }

            try {
                const response = await fetch('/events', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Kan niet opslaan');
                }

                formMessage.textContent = 'Planning opgeslagen!';
                formMessage.className = 'text-sm text-emerald-500';
                setTimeout(() => {
                    hideModal();
                    loadEvents();
                }, 600);
            } catch (error) {
                formMessage.textContent = error.message;
                formMessage.className = 'text-sm text-red-500';
            }
        });

        function setWeek(date) {
            state.weekStart = startOfWeek(date);
            updateWeekLabel();
            renderHeaders();
            loadEvents();
        }

        async function init() {
            setWeek(new Date());
            attachSlotListeners();
            await loadEvents();
            if (isAdmin) {
                instructorSelectWrapper.classList.remove('hidden');
                await fetchInstructors();
            }
        }

        init();
    </script>
</body>
</html>
