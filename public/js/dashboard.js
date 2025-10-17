(function () {
    const STATUS_CONFIG = {
        les: { label: 'Les', color: '#10b981', bg: 'rgba(16, 185, 129, 0.18)' },
        proefles: { label: 'Proefles', color: '#0ea5e9', bg: 'rgba(14, 165, 233, 0.18)' },
        examen: { label: 'Examen', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.18)' },
        ziek: { label: 'Ziek', color: '#f43f5e', bg: 'rgba(244, 63, 94, 0.18)' },
    };

    const VIEW_MAP = {
        timeGridWeek: 'week',
        timeGridDay: 'day',
        dayGridMonth: 'month',
    };

    const SUPPORTS_MATCH_MEDIA = typeof window !== 'undefined' && typeof window.matchMedia === 'function';
    const COMPACT_WEEKDAY_MEDIA = SUPPORTS_MATCH_MEDIA ? window.matchMedia('(max-width: 640px)') : null;

    const DATE_FORMATTER = new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'long' });
    const MONTH_FORMATTER = new Intl.DateTimeFormat('nl-NL', { month: 'long', year: 'numeric' });
    const WEEKDAY_FORMATTER = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric' });
    const TIME_FORMATTER = new Intl.DateTimeFormat('nl-NL', { hour: '2-digit', minute: '2-digit' });
    const BIRTHDATE_FORMATTER = new Intl.DateTimeFormat('nl-NL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
    const WEEKDAY_SHORT_FORMATTER = new Intl.DateTimeFormat('nl-NL', { weekday: 'short' });

    function padNumber(value, length = 2) {
        return String(value).padStart(length, '0');
    }

    function normaliseDate(date) {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
            throw new Error('Ongeldige datum opgegeven.');
        }
        return new Date(date.getTime());
    }

    function formatForInput(date) {
        const safeDate = normaliseDate(date);
        const year = safeDate.getFullYear();
        const month = padNumber(safeDate.getMonth() + 1);
        const day = padNumber(safeDate.getDate());
        const hours = padNumber(safeDate.getHours());
        const minutes = padNumber(safeDate.getMinutes());
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function formatForServer(date) {
        const safeDate = normaliseDate(date);
        const trimmed = new Date(safeDate.getTime());
        trimmed.setSeconds(0, 0);
        const year = trimmed.getFullYear();
        const month = padNumber(trimmed.getMonth() + 1);
        const day = padNumber(trimmed.getDate());
        const hours = padNumber(trimmed.getHours());
        const minutes = padNumber(trimmed.getMinutes());
        const seconds = padNumber(trimmed.getSeconds());
        return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    }

    function parseLocalDate(value) {
        if (!value) {
            throw new Error('Datum ontbreekt.');
        }
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            throw new Error('Kan datum niet lezen.');
        }
        return parsed;
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function cloneDate(date) {
        return new Date(date.getTime());
    }

    function startOfDay(date) {
        const result = cloneDate(date);
        result.setHours(0, 0, 0, 0);
        return result;
    }

    function endOfDay(date) {
        const result = startOfDay(date);
        result.setDate(result.getDate() + 1);
        return result;
    }

    function startOfWeek(date) {
        const result = startOfDay(date);
        const day = (result.getDay() + 6) % 7;
        result.setDate(result.getDate() - day);
        return result;
    }

    function endOfWeek(date) {
        const result = startOfWeek(date);
        result.setDate(result.getDate() + 7);
        return result;
    }

    function startOfMonth(date) {
        const result = startOfDay(date);
        result.setDate(1);
        return result;
    }

    function endOfMonth(date) {
        const result = startOfMonth(date);
        result.setMonth(result.getMonth() + 1);
        return result;
    }

    function startOfMonthGrid(date) {
        return startOfWeek(startOfMonth(date));
    }

    function endOfMonthGrid(date) {
        return endOfWeek(endOfMonth(date));
    }

    function isSameDay(a, b) {
        return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function differenceInMinutes(a, b) {
        return Math.round((a.getTime() - b.getTime()) / 60000);
    }

    function addMinutes(date, minutes) {
        return new Date(date.getTime() + minutes * 60000);
    }

    function formatDateRange(start, end) {
        if (start.getFullYear() === end.getFullYear()) {
            if (start.getMonth() === end.getMonth()) {
                return `${start.getDate()} – ${end.getDate()} ${MONTH_FORMATTER.format(start)}`;
            }
            return `${DATE_FORMATTER.format(start)} – ${DATE_FORMATTER.format(end)}`;
        }
        return `${DATE_FORMATTER.format(start)} – ${DATE_FORMATTER.format(end)}`;
    }

    function formatBirthDate(value) {
        if (!value) {
            return '';
        }
        const parsed = new Date(`${value}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }
        return BIRTHDATE_FORMATTER.format(parsed);
    }

    function eventsOverlap(a, b) {
        return a.start < b.end && b.start < a.end;
    }

    function buildElement(tag, className, children) {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (typeof children === 'string') {
            element.innerHTML = children;
        } else if (Array.isArray(children)) {
            children.forEach((child) => {
                if (child instanceof Node) {
                    element.appendChild(child);
                }
            });
        }
        return element;
    }

    function capitalize(text) {
        if (!text) {
            return text;
        }
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalisePhoneForTel(value = '') {
        return value.replace(/[^0-9+]/g, '');
    }

    function buildContactSummaryHtml(email, phone, { clickable = true } = {}) {
        const safeEmail = email ? escapeHtml(email) : null;
        const safePhone = phone ? escapeHtml(phone) : null;
        const emailLink = safeEmail
            ? clickable
                ? `<a href="mailto:${encodeURIComponent(email)}" class="text-sky-600 hover:underline">${safeEmail}</a>`
                : `<span class="text-sky-600">${safeEmail}</span>`
            : 'Geen e-mail';
        const normalisedPhone = phone ? normalisePhoneForTel(phone) : '';
        const phoneLink = normalisedPhone
            ? clickable
                ? `<a href="tel:${normalisedPhone}" class="text-sky-600 hover:underline">${safePhone}</a>`
                : `<span class="text-sky-600">${safePhone}</span>`
            : 'Geen telefoon';
        return `${emailLink} • ${phoneLink}`;
    }

    function formatWeekday(date) {
        return capitalize(WEEKDAY_FORMATTER.format(date));
    }

    function formatShortWeekday(date) {
        const raw = WEEKDAY_SHORT_FORMATTER.format(date);
        return capitalize(raw.replace('.', ''));
    }

    function formatTime(date) {
        return TIME_FORMATTER.format(date);
    }

    class PlannerCalendar {
        constructor(element, options = {}) {
            const fc = window.FullCalendar;
            if (!fc || !fc.Calendar) {
                throw new Error('FullCalendar is niet geladen.');
            }
            this.element = element;
            this.options = {
                onSelect: () => {},
                onEventClick: () => {},
                onEventMove: () => {},
                onEventResize: () => {},
                onRangeChange: () => {},
                initialView: 'timeGridWeek',
                initialDate: new Date().toISOString(),
                ...options,
            };
            this.events = [];
            this.element.classList.add('planner-root');
            this.element.style.position = 'relative';
            this.loadingOverlay = buildElement('div', 'planner-loading hidden', [
                buildElement('div', 'planner-loading__spinner', ''),
            ]);

            const initialDate = this.options.initialDate ? new Date(this.options.initialDate) : new Date();
            const requestedInitialView = this.options.initialView || 'timeGridWeek';

            const plugins = [
                fc.dayGridPlugin,
                fc.timeGridPlugin,
                fc.interactionPlugin,
            ].filter(Boolean);

            if (!plugins.length) {
                console.warn('FullCalendar plugins niet gevonden; beperktere weergaven beschikbaar.');
            }

            const hasTimeGrid = plugins.includes(fc.timeGridPlugin);
            const initialView = hasTimeGrid ? requestedInitialView : 'dayGridMonth';

            this.calendar = new fc.Calendar(this.element, {
                plugins,
                locale: 'nl',
                buttonText: {
                    today: 'Vandaag',
                    month: 'Maand',
                    week: 'Week',
                    day: 'Dag',
                    list: 'Agenda'
                },
                initialView,
                initialDate,
                firstDay: 1,
                height: 'auto',
                expandRows: true,
                stickyHeaderDates: true,
                nowIndicator: true,
                selectable: true,
                selectMirror: true,
                editable: true,
                eventStartEditable: true,
                eventDurationEditable: true,
                slotDuration: '00:15:00',
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                headerToolbar: false,
                dayHeaderContent: (arg) => this.getDayHeaderText(arg.date),
                select: (info) => this.handleSelect(info),
                dateClick: (info) => this.handleDateClick(info),
                eventClick: (info) => this.handleEventClick(info),
                eventDrop: (info) => this.handleEventUpdate(info, 'move'),
                eventResize: (info) => this.handleEventUpdate(info, 'resize'),
                datesSet: (info) => {
                    this.view = VIEW_MAP[info.view.type] || this.view || 'week';
                    this.refreshWeekdayHeaders();
                    this.notifyRangeChange();
                },
                eventClassNames: (info) => this.getEventClasses(info),
                eventDidMount: (info) => this.decorateEvent(info),
                eventDisplay: 'block',
                dayMaxEvents: false,
                moreLinkClick: 'popover',
                eventOverlap: true,
                selectOverlap: true,
                // Mobile responsive settings
                views: {
                    timeGridWeek: {
                        dayHeaderFormat: { weekday: 'short', day: 'numeric' }
                    },
                    timeGridDay: {
                        dayHeaderFormat: { weekday: 'long', day: 'numeric', month: 'long' }
                    },
                    dayGridMonth: {
                        dayHeaderFormat: { weekday: 'short' }
                    }
                },
                // Touch device optimalisaties
                longPressDelay: 500,
                eventLongPressDelay: 500
            });

            this.view = VIEW_MAP[this.calendar.view.type] || 'week';
            this.calendar.render();
            this.element.appendChild(this.loadingOverlay);
        }

        handleSelect(info) {
            this.calendar.unselect();
            const start = new Date(info.start.getTime());
            let end = info.end ? new Date(info.end.getTime()) : addMinutes(start, 60);
            
            // Voor all-day events of maand view, set standaard tijd
            if (info.allDay || info.view?.type === 'dayGridMonth') {
                start.setHours(9, 0, 0, 0);
                end = addMinutes(start, 60);
            }
            
            // Zorg ervoor dat eind tijd niet voor start tijd is
            if (end <= start) {
                end = addMinutes(start, 60);
            }
            
            this.options.onSelect({
                start,
                end,
                instructorId: null,
            });
        }

        handleDateClick(info) {
            this.calendar.unselect();
            const start = new Date(info.date.getTime());
            if (info.allDay || info.view?.type === 'dayGridMonth') {
                start.setHours(9, 0, 0, 0);
            }
            const end = addMinutes(start, 60);
            this.options.onSelect({
                start,
                end,
                instructorId: null,
            });
        }

        handleEventClick(info) {
            info.jsEvent?.preventDefault();
            const original = info.event.extendedProps?.original;
            if (original) {
                this.options.onEventClick({ event: { ...original } });
            }
        }

        handleEventUpdate(info, kind) {
            const original = info.event.extendedProps?.original;
            if (!original) {
                return;
            }
            this.syncEventInstance(info.event, original);
            const payload = {
                event: original,
                start: new Date(original.start.getTime()),
                end: new Date(original.end.getTime()),
            };
            if (kind === 'move') {
                this.options.onEventMove(payload);
            } else {
                this.options.onEventResize(payload);
            }
        }

        getEventClasses(info) {
            const original = info.event.extendedProps?.original;
            if (!original || !original.status) {
                return ['fc-event-status-default'];
            }
            return [`fc-event-status-${original.status}`];
        }

        decorateEvent(info) {
            const original = info.event.extendedProps?.original;
            if (!original) {
                return;
            }
            
            // Tooltip met event details
            const tooltipLines = [
                `${formatTime(original.start)} – ${formatTime(original.end)}`,
                original.student_name || original.title || 'Afspraak',
            ];
            if (original.location) {
                tooltipLines.push(`Locatie: ${original.location}`);
            }
            if (original.vehicle) {
                tooltipLines.push(`Voertuig: ${original.vehicle}`);
            }
            info.el.title = tooltipLines.join('\n');
            
            // Cursor pointer voor klikbare events
            info.el.style.cursor = 'pointer';
        }

        syncEventInstance(eventInstance, original) {
            const currentDuration = Math.max(
                15,
                differenceInMinutes(original.end ?? addMinutes(original.start, 60), original.start),
            );
            if (eventInstance.start) {
                original.start = new Date(eventInstance.start.getTime());
            }
            if (eventInstance.end) {
                original.end = new Date(eventInstance.end.getTime());
            } else {
                original.end = addMinutes(original.start, currentDuration);
            }
            original.start_time = formatForServer(original.start);
            original.end_time = formatForServer(original.end);
        }

        setLoading(active) {
            this.loadingOverlay.classList.toggle('hidden', !active);
        }

        getCurrentRange() {
            const view = this.calendar.view;
            return {
                start: new Date(view.activeStart.getTime()),
                end: new Date(view.activeEnd.getTime()),
            };
        }

        getViewInfo() {
            const range = this.getCurrentRange();
            return {
                type: this.calendar.view.type,
                currentStart: range.start,
                currentEnd: range.end,
            };
        }

        notifyRangeChange() {
            if (typeof this.options.onRangeChange === 'function') {
                const range = this.getCurrentRange();
                this.options.onRangeChange({ ...range });
            }
        }

        next() {
            this.calendar.next();
            this.view = VIEW_MAP[this.calendar.view.type] || this.view;
        }

        prev() {
            this.calendar.prev();
            this.view = VIEW_MAP[this.calendar.view.type] || this.view;
        }

        today() {
            this.calendar.today();
            this.view = VIEW_MAP[this.calendar.view.type] || this.view;
        }

        changeView(nextViewName) {
            this.calendar.changeView(nextViewName);
            this.view = VIEW_MAP[this.calendar.view.type] || this.view;
            this.refreshWeekdayHeaders();
        }

        setEvents(events) {
            this.calendar.removeAllEvents();
            this.events = [];
            events.forEach((event) => {
                const stored = {
                    ...event,
                    start: new Date(event.start.getTime()),
                    end: new Date(event.end.getTime()),
                };
                this.events.push(stored);
                const status = STATUS_CONFIG[stored.status] ?? STATUS_CONFIG.les;
                this.calendar.addEvent({
                    id: String(stored.id),
                    title: stored.title,
                    start: stored.start,
                    end: stored.end,
                    allDay: false,
                    backgroundColor: status.bg,
                    borderColor: status.color,
                    textColor: '#0f172a',
                    extendedProps: { ...stored, original: stored },
                });
            });
            this.refreshMonthPreviewLimits();
        }

        refreshMonthPreviewLimits() {
            if (typeof this.calendar.updateSize === 'function') {
                this.calendar.updateSize();
            }
        }

        shouldUseCompactWeekdayLabels() {
            return Boolean(COMPACT_WEEKDAY_MEDIA && COMPACT_WEEKDAY_MEDIA.matches);
        }

        getDayHeaderText(date) {
            if (this.shouldUseCompactWeekdayLabels()) {
                return `${formatShortWeekday(date).toUpperCase()} ${date.getDate()}`;
            }
            return formatWeekday(date);
        }

        refreshWeekdayHeaders() {
            this.calendar.setOption('dayHeaderContent', (arg) => this.getDayHeaderText(arg.date));
            if (typeof this.calendar.rerenderDates === 'function') {
                this.calendar.rerenderDates();
            }
        }

        unselect() {
            this.calendar.unselect();
        }
    }

    function normaliseSearchTerm(value) {
        return value
            .toLowerCase()
            .replace(/[^0-9a-z\u00c0-\u017e]+/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
        });
        if (!response.ok) {
            let errorData = {};
            try {
                errorData = await response.json();
            } catch (parseError) {
                const fallback = await response.text().catch(() => '');
                if (fallback) {
                    errorData = { message: fallback };
                }
            }
            const error = new Error(errorData.message || 'Onbekende fout');
            error.status = response.status;
            throw error;
        }
        if (response.status === 204) {
            return null;
        }
        const text = await response.text();
        if (!text) {
            return null;
        }
        try {
            return JSON.parse(text);
        } catch (parseError) {
            throw new Error('Onverwachte serverrespons.');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const calendarElement = document.getElementById('calendar');
        if (!calendarElement) {
            return;
        }

        const configScript = document.getElementById('planning-config');
        let config = {};
        if (configScript) {
            try {
                config = JSON.parse(configScript.textContent || '{}');
            } catch (error) {
                console.error('Kon planningconfig niet laden', error);
            }
        }

        const rangeLabel = document.getElementById('calendar-range');
        const calendarError = document.getElementById('calendar-error');
        const modal = document.getElementById('event-modal');
        const studentModal = document.getElementById('student-modal');
        const modalForm = document.getElementById('event-form');
        const studentForm = document.getElementById('student-form');
        const studentSearch = document.getElementById('student-search');
        const studentResults = document.getElementById('student-results');
        const selectedStudent = document.getElementById('selected-student');
        const selectedStudentName = document.getElementById('selected-student-name');
        const deleteStudentButton = document.getElementById('delete-student');
        const eventIdInput = document.getElementById('event_id');
        const studentIdInput = document.getElementById('student_id');
        const statusSelect = document.getElementById('status');
        const vehicleInput = document.getElementById('vehicle');
        const packageInput = document.getElementById('package');
        const locationInput = document.getElementById('location');
        const descriptionInput = document.getElementById('description');
        const startInput = document.getElementById('start_time');
        const endInput = document.getElementById('end_time');
        const instructorSelect = document.getElementById('instructor_id');
        const instructorFilter = document.getElementById('instructor-filter');
        const statusFilter = document.getElementById('status-filter');
        const studentDependentSections = document.querySelectorAll('[data-student-dependent]');
        const modalTitle = document.getElementById('event-modal-title');
        const quickCreateButton = document.getElementById('quick-create-event');
        const studentBirthDateInput = document.getElementById('student_birth_date');
        const openStudentButtons = document.querySelectorAll('[data-open-student-modal]');
        const closeModalButtons = modal.querySelectorAll('[data-close-modal]');
        const closeStudentButtons = studentModal.querySelectorAll('[data-close-student-modal]');
        const notifyStudentEmailInput = document.getElementById('notify-student-email');
        const notifyStudentPhoneInput = document.getElementById('notify-student-phone');
        const notifyGuardianEmailInput = document.getElementById('notify-guardian-email');
        const notifyGuardianPhoneInput = document.getElementById('notify-guardian-phone');
        const hasGuardianInput = document.getElementById('has_guardian');
        const guardianSection = document.getElementById('guardian-section');
        const studentHasGuardianInput = document.getElementById('student_has_guardian');
        const studentGuardianFields = document.getElementById('student-guardian-fields');
        const studentGuardianPrefToggles = studentModal.querySelectorAll('[data-student-guardian-pref]');
        const studentNotifyGuardianEmailInput = document.getElementById('student_notify_guardian_email');
        const studentNotifyGuardianPhoneInput = document.getElementById('student_notify_guardian_phone');
        const studentNotifyStudentEmailInput = studentForm.querySelector('[name="notify_student_email"]');
        const studentNotifyStudentPhoneInput = studentForm.querySelector('[name="notify_student_phone"]');

        let selectedStudentData = null;
        let searchTimeout = null;

        const contactEditors = buildContactEditors();

        function setStudentDependentVisibility(visible) {
            studentDependentSections.forEach((section) => {
                section.classList.toggle('hidden', !visible);
                section.setAttribute('aria-hidden', visible ? 'false' : 'true');
            });
        }

        setStudentDependentVisibility(false);

        function buildContactEditors() {
            function createContactEditor({ inputId, displayId, linkId, toggleId, emptyLabel, hrefFormatter }) {
                const input = document.getElementById(inputId);
                const display = document.getElementById(displayId);
                const link = document.getElementById(linkId);
                const toggle = document.getElementById(toggleId);
                if (!input || !display || !link || !toggle) {
                    return null;
                }
                let editing = false;
                function apply(value) {
                    const trimmed = value?.trim();
                    const hasValue = Boolean(trimmed);
                    link.textContent = hasValue ? trimmed : emptyLabel;
                    link.href = hasValue ? hrefFormatter(trimmed) : '#';
                    link.classList.toggle('text-sky-600', hasValue);
                    link.classList.toggle('text-slate-400', !hasValue);
                }
                toggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    editing = !editing;
                    input.classList.toggle('hidden', !editing);
                    display.classList.toggle('hidden', editing);
                    toggle.textContent = editing ? 'Opslaan' : 'Wijzig';
                    if (!editing) {
                        apply(input.value);
                    } else {
                        input.focus();
                    }
                });
                apply(input.value || '');
                return {
                    setValue(value) {
                        input.value = value || '';
                        apply(input.value);
                        if (editing) {
                            toggle.click();
                        }
                    },
                    getValue() {
                        return input.value?.trim() || '';
                    },
                };
            }

            return {
                studentEmail: createContactEditor({
                    inputId: 'email',
                    displayId: 'email-display',
                    linkId: 'email-link',
                    toggleId: 'toggle-email-edit',
                    emptyLabel: 'Geen e-mailadres',
                    hrefFormatter: (value) => `mailto:${value}`,
                }),
                studentPhone: createContactEditor({
                    inputId: 'phone',
                    displayId: 'phone-display',
                    linkId: 'phone-link',
                    toggleId: 'toggle-phone-edit',
                    emptyLabel: 'Geen telefoonnummer',
                    hrefFormatter: (value) => `tel:${normalisePhoneForTel(value)}`,
                }),
                guardianEmail: createContactEditor({
                    inputId: 'guardian_email',
                    displayId: 'guardian-email-display',
                    linkId: 'guardian-email-link',
                    toggleId: 'toggle-guardian-email-edit',
                    emptyLabel: 'Geen voogd e-mail',
                    hrefFormatter: (value) => `mailto:${value}`,
                }),
                guardianPhone: createContactEditor({
                    inputId: 'guardian_phone',
                    displayId: 'guardian-phone-display',
                    linkId: 'guardian-phone-link',
                    toggleId: 'toggle-guardian-phone-edit',
                    emptyLabel: 'Geen voogd telefoon',
                    hrefFormatter: (value) => `tel:${normalisePhoneForTel(value)}`,
                }),
            };
        }

        function updateGuardianVisibility() {
            if (guardianSection) {
                guardianSection.classList.toggle('hidden', !hasGuardianInput.checked);
            }
        }

        function updateStudentGuardianVisibility() {
            if (studentGuardianFields) {
                studentGuardianFields.classList.toggle('hidden', !studentHasGuardianInput.checked);
            }
            studentGuardianPrefToggles.forEach((element) => {
                element.classList.toggle('hidden', !studentHasGuardianInput.checked);
            });
        }

        hasGuardianInput?.addEventListener('change', updateGuardianVisibility);
        studentHasGuardianInput?.addEventListener('change', updateStudentGuardianVisibility);
        updateGuardianVisibility();
        updateStudentGuardianVisibility();

        function getActiveInstructorIds() {
            if (!instructorFilter) {
                return [];
            }
            return Array.from(instructorFilter.querySelectorAll('button'))
                .filter((button) => button.dataset.active !== 'false')
                .map((button) => Number.parseInt(button.dataset.instructorFilter, 10))
                .filter((value) => Number.isFinite(value));
        }

        function getActiveStatuses() {
            return Array.from(statusFilter?.querySelectorAll('button') ?? [])
                .filter((button) => button.dataset.active !== 'false')
                .map((button) => button.dataset.statusFilter)
                .filter(Boolean);
        }

        // Declareer planner variabele vooraf
        let planner;

        // Functie declaraties VOOR planner instantie
        function updateRangeLabel() {
            if (!planner) return;
            const info = planner.getViewInfo();
            const start = info.currentStart;
            const end = addMinutes(info.currentEnd, -1);
            if (planner.view === 'month') {
                rangeLabel.textContent = MONTH_FORMATTER.format(start);
            } else {
                rangeLabel.textContent = formatDateRange(start, end);
            }
        }

        async function refreshEvents() {
            if (!planner) return;
            const { start, end } = planner.getCurrentRange();
            const params = new URLSearchParams();
            params.set('start', start.toISOString());
            params.set('end', end.toISOString());
            getActiveInstructorIds().forEach((id) => params.append('instructor_ids[]', String(id)));
            getActiveStatuses().forEach((status) => params.append('statuses[]', status));
            try {
                planner.setLoading(true);
                const events = await fetchJson(`/events?${params.toString()}`);
                const prepared = events.map((event) => ({
                    ...event,
                    title: event.student_name || 'Afspraak',
                    start: new Date(event.start_time),
                    end: new Date(event.end_time),
                }));
                planner.setEvents(prepared);
                calendarError.classList.add('hidden');
            } catch (error) {
                console.error(error);
                calendarError.classList.remove('hidden');
            } finally {
                planner.setLoading(false);
            }
        }

        function handleRangeChange() {
            updateRangeLabel();
            updateViewButtons();
            refreshEvents();
        }

        function updateViewButtons() {
            if (!planner) return;
            const activeType = planner.getViewInfo().type;
            document.querySelectorAll('[data-calendar-view]').forEach((button) => {
                const isActive = button.dataset.calendarView === activeType;
                ['bg-white', 'text-sky-600', 'shadow-sm'].forEach((klass) => {
                    button.classList.toggle(klass, isActive);
                });
                button.classList.toggle('text-slate-500', !isActive);
            });
        }

        // Initialiseer planner
        planner = new PlannerCalendar(calendarElement, {
            initialView: config.initialView || 'timeGridWeek',
            initialDate: config.initialDate || new Date().toISOString(),
            onSelect: handleSelect,
            onEventClick: ({ event }) => openModalForEvent(event),
            onEventMove: handleEventMove,
            onEventResize: handleEventResize,
            onRangeChange: handleRangeChange,
        });

        const responsiveMedia = [COMPACT_WEEKDAY_MEDIA].filter(Boolean);
        const handleViewportChange = () => {
            planner.refreshWeekdayHeaders();
            planner.refreshMonthPreviewLimits();
        };
        responsiveMedia.forEach((media) => {
            if (!media) {
                return;
            }
            if (typeof media.addEventListener === 'function') {
                media.addEventListener('change', handleViewportChange);
            } else if (typeof media.addListener === 'function') {
                media.addListener(handleViewportChange);
            }
        });
        let viewportResizeTimer = null;
        window.addEventListener('resize', () => {
            window.clearTimeout(viewportResizeTimer);
            viewportResizeTimer = window.setTimeout(handleViewportChange, 150);
        });
        handleViewportChange();

        // Modal functies
        function openModal() {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            modalForm.reset();
            eventIdInput.value = '';
            planner.unselect();
            setSelectedStudent(null, { clearForm: true });
            if (studentResults) {
                studentResults.innerHTML = '';
            }
        }

        function openStudentModal() {
            studentModal.classList.remove('hidden');
            studentModal.setAttribute('aria-hidden', 'false');
        }

        function closeStudentModal() {
            studentModal.classList.add('hidden');
            studentModal.setAttribute('aria-hidden', 'true');
            studentForm.reset();
        }

        closeModalButtons.forEach((button) => button.addEventListener('click', closeModal));
        closeStudentButtons.forEach((button) => button.addEventListener('click', closeStudentModal));
        openStudentButtons.forEach((button) => button.addEventListener('click', openStudentModal));

        // ESC key support voor modals
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                if (!modal.classList.contains('hidden')) {
                    closeModal();
                }
                if (!studentModal.classList.contains('hidden')) {
                    closeStudentModal();
                }
            }
        });

        // Click outside modal support
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        studentModal.addEventListener('click', (event) => {
            if (event.target === studentModal) {
                closeStudentModal();
            }
        });
        quickCreateButton?.addEventListener('click', () => {
            modalTitle.textContent = 'Nieuwe afspraak';
            const now = new Date();
            now.setMinutes(Math.ceil(now.getMinutes() / 30) * 30, 0, 0);
            const end = new Date(now.getTime() + 60 * 60 * 1000);
            setSelectedStudent(null, { clearForm: true });
            populateEventForm({
                start: now,
                end,
                status: 'les',
                notify_student_email: true,
                notify_student_phone: true,
            });
            if (config.userRole === 'admin' && instructorSelect) {
                const active = getActiveInstructorIds();
                if (active.length === 1) {
                    instructorSelect.value = String(active[0]);
                } else if (!instructorSelect.value && config.instructors?.length === 1) {
                    instructorSelect.value = String(config.instructors[0].id);
                }
            }
            openModal();
        });
        function clearStudentFormFields() {
            contactEditors.studentEmail?.setValue('');
            contactEditors.studentPhone?.setValue('');
            contactEditors.guardianEmail?.setValue('');
            contactEditors.guardianPhone?.setValue('');
            notifyStudentEmailInput.checked = true;
            notifyStudentPhoneInput.checked = true;
            notifyGuardianEmailInput.checked = false;
            notifyGuardianPhoneInput.checked = false;
            hasGuardianInput.checked = false;
            updateGuardianVisibility();
            if (studentBirthDateInput) {
                studentBirthDateInput.value = '';
            }
        }

        function setSelectedStudent(student, { applyDefaults = false, clearForm = false } = {}) {
            if (clearForm) {
                clearStudentFormFields();
            }
            selectedStudentData = student || null;
            setStudentDependentVisibility(Boolean(student));
            if (!student) {
                studentIdInput.value = '';
                if (selectedStudent) {
                    selectedStudent.classList.add('hidden');
                    selectedStudent.setAttribute('aria-label', 'Geen leerling geselecteerd');
                }
                if (selectedStudentName) {
                    selectedStudentName.textContent = '';
                }
                deleteStudentButton?.classList.add('hidden');
                if (studentSearch) {
                    studentSearch.classList.remove('hidden');
                    studentSearch.value = '';
                }
                if (studentBirthDateInput) {
                    studentBirthDateInput.value = '';
                }
                if (studentResults) {
                    studentResults.innerHTML = '';
                }
                return;
            }

            studentIdInput.value = student.id;
            if (studentBirthDateInput) {
                studentBirthDateInput.value = student.birth_date ?? '';
            }
            if (selectedStudentName) {
                selectedStudentName.textContent = student.full_name || '';
            }
            if (selectedStudent) {
                selectedStudent.classList.remove('hidden');
                selectedStudent.setAttribute(
                    'aria-label',
                    `Leerling ${student.full_name || ''} geselecteerd. Klik om een andere leerling te kiezen.`,
                );
            }
            if (studentSearch) {
                studentSearch.classList.add('hidden');
                studentSearch.value = '';
            }
            if (studentResults) {
                studentResults.innerHTML = '';
            }
            deleteStudentButton?.classList.remove('hidden');

            if (applyDefaults) {
                contactEditors.studentEmail?.setValue(student.email || '');
                contactEditors.studentPhone?.setValue(student.phone || '');

                const hasGuardian = Boolean(student.has_guardian);
                hasGuardianInput.checked = hasGuardian;
                contactEditors.guardianEmail?.setValue(hasGuardian ? student.guardian_email || '' : '');
                contactEditors.guardianPhone?.setValue(hasGuardian ? student.guardian_phone || '' : '');

                notifyStudentEmailInput.checked = student.notify_student_email ?? true;
                notifyStudentPhoneInput.checked = student.notify_student_phone ?? true;
                notifyGuardianEmailInput.checked = hasGuardian ? Boolean(student.notify_guardian_email) : false;
                notifyGuardianPhoneInput.checked = hasGuardian ? Boolean(student.notify_guardian_phone) : false;
                updateGuardianVisibility();
            }
        }

        function populateEventForm(event) {
            eventIdInput.value = event?.id ?? '';
            statusSelect.value = event?.status ?? 'les';
            if (vehicleInput?.tagName === 'SELECT') {
                const incoming = event?.vehicle ?? '';
                const hasOption = Array.from(vehicleInput.options).some((option) => option.value === incoming);
                if (incoming && !hasOption) {
                    vehicleInput.add(new Option(incoming, incoming, true, true));
                }
                vehicleInput.value = incoming;
            } else if (vehicleInput) {
                vehicleInput.value = event?.vehicle ?? '';
            }
            packageInput.value = event?.package ?? '';
            locationInput.value = event?.location ?? '';
            descriptionInput.value = event?.description ?? '';
            startInput.value = event?.start ? formatForInput(event.start) : '';
            endInput.value = event?.end ? formatForInput(event.end) : '';
            notifyStudentEmailInput.checked = Boolean(event?.notify_student_email ?? true);
            notifyStudentPhoneInput.checked = Boolean(event?.notify_student_phone ?? false);
            notifyGuardianEmailInput.checked = Boolean(event?.notify_guardian_email ?? false);
            notifyGuardianPhoneInput.checked = Boolean(event?.notify_guardian_phone ?? false);
            hasGuardianInput.checked = Boolean(event?.has_guardian ?? false);
            updateGuardianVisibility();
            contactEditors.studentEmail?.setValue(event?.email ?? '');
            contactEditors.studentPhone?.setValue(event?.phone ?? '');
            contactEditors.guardianEmail?.setValue(event?.guardian_email ?? '');
            contactEditors.guardianPhone?.setValue(event?.guardian_phone ?? '');
            if (config.userRole === 'admin' && instructorSelect) {
                instructorSelect.value = event?.instructor_id ?? '';
            }
        }

        function openModalForEvent(event) {
            modalTitle.textContent = 'Afspraak bewerken';
            const enriched = {
                ...event,
                start: new Date(event.start_time || event.start),
                end: new Date(event.end_time || event.end),
            };
            populateEventForm(enriched);
            setSelectedStudent(
                {
                id: event.student_id,
                full_name: event.student_name,
                email: event.student_email,
                phone: event.student_phone,
                birth_date: event.student_birth_date,
                has_guardian: event.student_has_guardian,
                guardian_email: event.student_guardian_email,
                guardian_phone: event.student_guardian_phone,
                notify_student_email: event.student_notify_student_email,
                notify_student_phone: event.student_notify_student_phone,
                notify_guardian_email: event.student_notify_guardian_email,
                notify_guardian_phone: event.student_notify_guardian_phone,
            },
                { applyDefaults: false },
            );
            openModal();
        }

        function handleSelect({ start, end, instructorId }) {
            modalTitle.textContent = 'Nieuwe afspraak';
            setSelectedStudent(null, { clearForm: true });
            populateEventForm({ start, end });
            if (config.userRole === 'admin' && instructorSelect && instructorId) {
                instructorSelect.value = String(instructorId);
            }
            openModal();
        }

        async function saveEvent(eventId, payload) {
            const url = eventId ? `/events/${eventId}` : '/events';
            const method = eventId ? 'PATCH' : 'POST';
            return fetchJson(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify(payload),
            });
        }

        async function deleteStudent(studentId) {
            await fetchJson(`/students/${studentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                },
            });
        }

        async function fetchStudents({ query = '', initial = false } = {}) {
            const params = new URLSearchParams();
            if (query) {
                params.set('query', query);
            }
            if (initial) {
                params.set('initial', '1');
            }
            return fetchJson(`/students/search?${params.toString()}`);
        }

        function renderStudentResults(students) {
            studentResults.innerHTML = '';
            if (!students.length) {
                studentResults.innerHTML =
                    '<div class="rounded-xl bg-white px-4 py-2 text-sm text-slate-500">Geen leerlingen gevonden.</div>';
                return;
            }
            const fragment = document.createDocumentFragment();
            students.forEach((student) => {
                const birthDateLabel = student.birth_date ? formatBirthDate(student.birth_date) : '';
                const nameLine = birthDateLabel
                    ? `<div class="font-semibold text-slate-800">${escapeHtml(student.full_name)} <span class="ml-1 text-xs font-medium text-slate-500">(${escapeHtml(birthDateLabel)})</span></div>`
                    : `<div class="font-semibold text-slate-800">${escapeHtml(student.full_name)}</div>`;
                const item = buildElement(
                    'div',
                    'cursor-pointer rounded-xl border border-transparent px-4 py-2 text-sm transition hover:border-sky-200 hover:bg-slate-100',
                    nameLine +
                        `<div class="text-xs text-slate-500">${buildContactSummaryHtml(student.email, student.phone, { clickable: false })}</div>`,
                );
                item.addEventListener('click', () => {
                    setSelectedStudent(student, { applyDefaults: true });
                    studentResults.innerHTML = '';
                });
                fragment.appendChild(item);
            });
            studentResults.appendChild(fragment);
        }

        studentSearch?.addEventListener('focus', async () => {
            if (!studentResults.childElementCount) {
                const students = await fetchStudents({ initial: true });
                renderStudentResults(students);
            }
        });

        studentSearch?.addEventListener('input', () => {
            const value = normaliseSearchTerm(studentSearch.value);
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
            formData.set('has_guardian', studentHasGuardianInput?.checked ? '1' : '0');
            if (studentNotifyStudentEmailInput) {
                formData.set('notify_student_email', studentNotifyStudentEmailInput.checked ? '1' : '0');
            }
            if (studentNotifyStudentPhoneInput) {
                formData.set('notify_student_phone', studentNotifyStudentPhoneInput.checked ? '1' : '0');
            }
            if (studentNotifyGuardianEmailInput) {
                formData.set('notify_guardian_email', studentNotifyGuardianEmailInput.checked ? '1' : '0');
            }
            if (studentNotifyGuardianPhoneInput) {
                formData.set('notify_guardian_phone', studentNotifyGuardianPhoneInput.checked ? '1' : '0');
            }
            try {
                const student = await fetchJson('/students', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: formData,
                });
                setSelectedStudent(student, { applyDefaults: true });
                studentSearch.value = '';
                studentResults.innerHTML = '';
                closeStudentModal();
            } catch (error) {
                alert(error.message || 'Kon leerling niet opslaan.');
            }
        });

        function reopenStudentSearch() {
            setSelectedStudent(null, { clearForm: true });
            if (studentResults) {
                studentResults.innerHTML = '';
            }
            if (studentSearch) {
                studentSearch.classList.remove('hidden');
                studentSearch.focus();
                studentSearch.dispatchEvent(new Event('focus'));
            }
        }

        selectedStudent?.addEventListener('click', reopenStudentSearch);
        selectedStudent?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar' || event.key === 'Space') {
                event.preventDefault();
                reopenStudentSearch();
            }
        });

        deleteStudentButton?.addEventListener('click', async () => {
            if (!selectedStudentData?.id) {
                return;
            }
            if (!window.confirm('Leerling verwijderen? Bestaande afspraken worden ook verwijderd.')) {
                return;
            }
            try {
                deleteStudentButton.disabled = true;
                deleteStudentButton.textContent = 'Verwijderen...';
                const removedId = selectedStudentData.id;
                await deleteStudent(removedId);
                setSelectedStudent(null, { clearForm: true });
                planner.setEvents(planner.events.filter((event) => event.student_id !== removedId));
                refreshEvents();
            } catch (error) {
                alert(error.message || 'Kon leerling niet verwijderen.');
            } finally {
                deleteStudentButton.disabled = false;
                deleteStudentButton.textContent = 'Leerling verwijderen';
            }
        });

        function buildPayload() {
            if (!studentIdInput.value) {
                throw new Error('Selecteer eerst een leerling.');
            }
            if (!startInput.value || !endInput.value) {
                throw new Error('Vul start- en eindtijd in.');
            }
            const startDate = parseLocalDate(startInput.value);
            const endDate = parseLocalDate(endInput.value);
            const guardianEnabled = hasGuardianInput.checked;
            const payload = {
                student_id: Number.parseInt(studentIdInput.value, 10),
                status: statusSelect.value,
                start_time: formatForServer(startDate),
                end_time: formatForServer(endDate),
                vehicle: vehicleInput.value || null,
                package: packageInput.value || null,
                location: locationInput.value || null,
                description: descriptionInput.value || null,
                email: contactEditors.studentEmail?.getValue() || null,
                phone: contactEditors.studentPhone?.getValue() || null,
                has_guardian: guardianEnabled ? 1 : 0,
                guardian_email: guardianEnabled
                    ? contactEditors.guardianEmail?.getValue() || null
                    : null,
                guardian_phone: guardianEnabled
                    ? contactEditors.guardianPhone?.getValue() || null
                    : null,
                notify_student_email: notifyStudentEmailInput.checked ? 1 : 0,
                notify_student_phone: notifyStudentPhoneInput.checked ? 1 : 0,
                notify_guardian_email:
                    guardianEnabled && notifyGuardianEmailInput ? (notifyGuardianEmailInput.checked ? 1 : 0) : 0,
                notify_guardian_phone:
                    guardianEnabled && notifyGuardianPhoneInput ? (notifyGuardianPhoneInput.checked ? 1 : 0) : 0,
            };
            if (config.userRole === 'admin' && instructorSelect) {
                if (!instructorSelect.value) {
                    throw new Error('Selecteer een instructeur.');
                }
                payload.instructor_id = Number.parseInt(instructorSelect.value, 10);
            }
            return payload;
        }

        modalForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = buildPayload();
                await saveEvent(eventIdInput.value || null, payload);
                closeModal();
                refreshEvents();
            } catch (error) {
                alert(error.message || 'Kon afspraak niet opslaan.');
            }
        });

        function handleEventMove({ event, start, end }) {
            const payload = {
                student_id: event.student_id,
                status: event.status,
                start_time: formatForServer(start),
                end_time: formatForServer(end),
                vehicle: event.vehicle,
                package: event.package,
                location: event.location,
                description: event.description,
                email: event.email,
                phone: event.phone,
                has_guardian: event.has_guardian ? 1 : 0,
                guardian_email: event.guardian_email,
                guardian_phone: event.guardian_phone,
                notify_student_email: event.notify_student_email ? 1 : 0,
                notify_student_phone: event.notify_student_phone ? 1 : 0,
                notify_guardian_email: event.notify_guardian_email ? 1 : 0,
                notify_guardian_phone: event.notify_guardian_phone ? 1 : 0,
            };
            event.start = new Date(start.getTime());
            event.end = new Date(end.getTime());
            event.start_time = payload.start_time;
            event.end_time = payload.end_time;
            const local = planner.events.find((item) => item.id === event.id);
            if (local) {
                Object.assign(local, event);
            }
            if (config.userRole === 'admin' && instructorSelect) {
                payload.instructor_id = event.instructor_id;
            }
            saveEvent(event.id, payload)
                .then(refreshEvents)
                .catch((error) => alert(error.message || 'Kon afspraak niet bijwerken.'));
        }

        function handleEventResize(details) {
            handleEventMove(details);
        }

        document.querySelectorAll('[data-calendar-nav]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.calendarNav;
                if (action === 'prev') {
                    planner.prev();
                } else if (action === 'next') {
                    planner.next();
                } else if (action === 'today') {
                    planner.today();
                }
            });
        });

        // View switcher buttons
        document.querySelectorAll('[data-calendar-view]').forEach((button) => {
            button.addEventListener('click', () => {
                const view = button.dataset.calendarView;
                planner.changeView(view);
                updateViewButtons();
            });
        });

        updateViewButtons();

        function updateFilterButton(button, active) {
            button.dataset.active = active ? 'true' : 'false';
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        function bindFilterButtons(container) {
            container?.querySelectorAll('button').forEach((button) => {
                updateFilterButton(button, button.dataset.active !== 'false');
                button.addEventListener('click', () => {
                    const next = button.dataset.active !== 'true';
                    updateFilterButton(button, next);
                    refreshEvents();
                });
            });
        }

        bindFilterButtons(instructorFilter);
        bindFilterButtons(statusFilter);

        updateRangeLabel();
        refreshEvents();
    });
})();
