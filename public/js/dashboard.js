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
        resourceTimeGridWeek: 'instructor',
    };

    const REVERSE_VIEW_MAP = {
        week: 'timeGridWeek',
        day: 'timeGridDay',
        month: 'dayGridMonth',
        instructor: 'resourceTimeGridWeek',
    };

    const SLOT_MINUTES = 15;
    const MINUTE_HEIGHT = 1.05;
    const MIN_EVENT_HEIGHT = 32;

    const DATE_FORMATTER = new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'long' });
    const MONTH_FORMATTER = new Intl.DateTimeFormat('nl-NL', { month: 'long', year: 'numeric' });
    const WEEKDAY_FORMATTER = new Intl.DateTimeFormat('nl-NL', { weekday: 'short', day: 'numeric' });
    const TIME_FORMATTER = new Intl.DateTimeFormat('nl-NL', { hour: '2-digit', minute: '2-digit' });

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

    function formatWeekday(date) {
        return WEEKDAY_FORMATTER.format(date);
    }

    function formatTime(date) {
        return TIME_FORMATTER.format(date);
    }
    class PlannerCalendar {
        constructor(element, options = {}) {
            this.element = element;
            this.options = options;
            this.view = VIEW_MAP[options.initialView] || 'week';
            this.currentDate = options.initialDate ? new Date(options.initialDate) : new Date();
            this.events = [];
            this.instructors = options.instructors || [];
            this.activeInstructorIds = options.activeInstructorIds || (() => []);
            this.onSelect = options.onSelect || (() => {});
            this.onEventClick = options.onEventClick || (() => {});
            this.onEventMove = options.onEventMove || (() => {});
            this.onEventResize = options.onEventResize || (() => {});
            this.onRangeChange = options.onRangeChange || (() => {});
            this.columnsMeta = [];
            this.dragState = null;
            this.element.classList.add('planner-root');
            this.loadingOverlay = buildElement('div', 'planner-loading hidden', [
                buildElement('div', 'planner-loading__spinner', ''),
            ]);
            this.element.appendChild(this.loadingOverlay);
        }

        setLoading(active) {
            this.loadingOverlay.classList.toggle('hidden', !active);
        }

        getCurrentRange() {
            switch (this.view) {
                case 'day': {
                    const start = startOfDay(this.currentDate);
                    return { start, end: addMinutes(start, 24 * 60) };
                }
                case 'week':
                case 'instructor': {
                    const start = startOfWeek(this.currentDate);
                    return { start, end: endOfWeek(this.currentDate) };
                }
                case 'month':
                default: {
                    const start = startOfMonthGrid(this.currentDate);
                    return { start, end: endOfMonthGrid(this.currentDate) };
                }
            }
        }

        getViewInfo() {
            const range = this.getCurrentRange();
            return {
                type: REVERSE_VIEW_MAP[this.view] || 'timeGridWeek',
                currentStart: range.start,
                currentEnd: range.end,
            };
        }

        getDate() {
            return new Date(this.currentDate.getTime());
        }

        changeView(nextViewName) {
            const nextView = VIEW_MAP[nextViewName] || 'week';
            if (nextView === this.view) {
                this.render();
                this.notifyRangeChange();
                return;
            }
            this.view = nextView;
            this.render();
            this.notifyRangeChange();
        }

        notifyRangeChange() {
            if (typeof this.onRangeChange === 'function') {
                const range = this.getCurrentRange();
                this.onRangeChange({ ...range });
            }
        }

        next() {
            switch (this.view) {
                case 'day':
                    this.currentDate.setDate(this.currentDate.getDate() + 1);
                    break;
                case 'week':
                case 'instructor':
                    this.currentDate.setDate(this.currentDate.getDate() + 7);
                    break;
                case 'month':
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                    break;
                default:
                    this.currentDate.setDate(this.currentDate.getDate() + 7);
            }
            this.render();
            this.notifyRangeChange();
        }

        prev() {
            switch (this.view) {
                case 'day':
                    this.currentDate.setDate(this.currentDate.getDate() - 1);
                    break;
                case 'week':
                case 'instructor':
                    this.currentDate.setDate(this.currentDate.getDate() - 7);
                    break;
                case 'month':
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                    break;
                default:
                    this.currentDate.setDate(this.currentDate.getDate() - 7);
            }
            this.render();
            this.notifyRangeChange();
        }

        today() {
            this.currentDate = new Date();
            this.render();
            this.notifyRangeChange();
        }

        render() {
            this.columnsMeta = [];
            const wrapper = buildElement('div', 'planner-view');
            const range = this.getCurrentRange();
            switch (this.view) {
                case 'day':
                    wrapper.appendChild(this.renderTimeGrid({
                        columns: this.buildDayColumns(range.start),
                        showDayNames: false,
                    }));
                    break;
                case 'week':
                    wrapper.appendChild(this.renderTimeGrid({
                        columns: this.buildWeekColumns(range.start),
                        showDayNames: true,
                    }));
                    break;
                case 'instructor':
                    wrapper.appendChild(this.renderInstructorView(range.start));
                    break;
                case 'month':
                default:
                    wrapper.appendChild(this.renderMonthView(range.start, range.end));
                    break;
            }
            this.element.querySelectorAll('.planner-view').forEach((child) => child.remove());
            this.element.insertBefore(wrapper, this.loadingOverlay);
        }

        setEvents(events) {
            this.events = events.map((event) => ({ ...event }));
            this.render();
        }

        buildDayColumns(start) {
            return [this.buildColumnData(start)];
        }

        buildWeekColumns(start) {
            const columns = [];
            for (let i = 0; i < 7; i += 1) {
                const date = new Date(start.getTime() + i * 86400000);
                columns.push(this.buildColumnData(date));
            }
            return columns;
        }

        buildColumnData(date, instructorId = null) {
            const start = startOfDay(date);
            const end = endOfDay(date);
            const events = this.events
                .filter((event) => {
                    if (instructorId !== null && Number(event.instructor_id) !== Number(instructorId)) {
                        return false;
                    }
                    return event.start < end && event.end > start;
                })
                .map((event) => ({ ...event }));
            return { date: start, events, instructorId };
        }
        renderTimeGrid({ columns, showDayNames }) {
            const container = buildElement('div', 'planner-time-grid');
            const header = buildElement('div', 'planner-time-grid__header');
            header.appendChild(buildElement('div', 'planner-time-grid__header-cell planner-time-grid__times-header'));
            columns.forEach((column, index) => {
                const label = showDayNames ? formatWeekday(column.date) : DATE_FORMATTER.format(column.date);
                header.appendChild(
                    buildElement(
                        'div',
                        'planner-time-grid__header-cell',
                        `<div class="planner-time-grid__header-label">${label}</div>`,
                    ),
                );
                this.columnsMeta.push({ index, date: column.date, instructorId: column.instructorId ?? null });
            });
            container.appendChild(header);

            const body = buildElement('div', 'planner-time-grid__body');
            const timesColumn = buildElement('div', 'planner-time-grid__times');
            for (let hour = 0; hour < 24; hour += 1) {
                const timeLabel = buildElement(
                    'div',
                    'planner-time-grid__time-label',
                    `<span>${`${hour}`.padStart(2, '0')}:00</span>`,
                );
                timeLabel.style.height = `${MINUTE_HEIGHT * 60}px`;
                timesColumn.appendChild(timeLabel);
            }
            body.appendChild(timesColumn);

            columns.forEach((column, columnIndex) => {
                const columnElement = buildElement('div', 'planner-time-grid__column');
                columnElement.dataset.columnIndex = String(columnIndex);
                columnElement.dataset.date = column.date.toISOString();
                if (column.instructorId !== null) {
                    columnElement.dataset.instructorId = String(column.instructorId);
                }

                const backdrop = buildElement('div', 'planner-time-grid__background');
                backdrop.style.height = `${MINUTE_HEIGHT * 60 * 24}px`;
                for (let hour = 0; hour <= 24; hour += 1) {
                    const line = buildElement('div', 'planner-time-grid__hour-line');
                    line.style.top = `${MINUTE_HEIGHT * 60 * hour}px`;
                    backdrop.appendChild(line);
                }
                columnElement.appendChild(backdrop);

                columnElement.addEventListener('click', (event) => {
                    if (event.target.closest('.planner-event')) {
                        return;
                    }
                    const rect = columnElement.getBoundingClientRect();
                    const offsetY = event.clientY - rect.top + columnElement.scrollTop;
                    const minutesFromTop = Math.max(0, Math.min(offsetY / MINUTE_HEIGHT, 24 * 60));
                    const start = addMinutes(column.date, Math.floor(minutesFromTop / SLOT_MINUTES) * SLOT_MINUTES);
                    const end = addMinutes(start, 60);
                    this.onSelect({ start, end, instructorId: column.instructorId ?? null });
                });

                const layouts = this.computeColumnLayouts(column.events);
                layouts.forEach((layout) => {
                    const eventElement = this.createEventElement(layout, column.date, columnIndex);
                    columnElement.appendChild(eventElement);
                });

                body.appendChild(columnElement);
            });

            container.appendChild(body);
            return container;
        }

        renderInstructorView(start) {
            const wrapper = buildElement('div', 'planner-instructor');
            const activeInstructorIds = this.activeInstructorIds();
            const weekStart = startOfWeek(start);
            this.instructors
                .filter((instructor) =>
                    !activeInstructorIds.length || activeInstructorIds.includes(Number(instructor.id)),
                )
                .forEach((instructor) => {
                    const section = buildElement('section', 'planner-instructor__section');
                    section.appendChild(
                        buildElement(
                            'header',
                            'planner-instructor__header',
                            `<div class="planner-instructor__title">${instructor.name}</div>` +
                                `<div class="planner-instructor__subtitle">${formatDateRange(
                                    weekStart,
                                    addMinutes(endOfWeek(weekStart), -1),
                                )}</div>`,
                        ),
                    );
                    const columns = [];
                    for (let i = 0; i < 7; i += 1) {
                        const date = new Date(weekStart.getTime() + i * 86400000);
                        columns.push(this.buildColumnData(date, instructor.id));
                    }
                    section.appendChild(
                        this.renderTimeGrid({
                            columns,
                            showDayNames: true,
                        }),
                    );
                    wrapper.appendChild(section);
                });
            if (!wrapper.childElementCount) {
                wrapper.appendChild(
                    buildElement('p', 'planner-empty', 'Geen instructeurs geselecteerd voor deze periode.'),
                );
            }
            return wrapper;
        }

        renderMonthView(start, end) {
            const container = buildElement('div', 'planner-month');
            const header = buildElement('div', 'planner-month__header');
            ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'].forEach((weekday) => {
                header.appendChild(buildElement('div', 'planner-month__header-cell', weekday));
            });
            container.appendChild(header);

            const grid = buildElement('div', 'planner-month__grid');
            const cursor = new Date(start.getTime());
            while (cursor < end) {
                const cell = buildElement('div', 'planner-month__cell');
                const cellHeader = buildElement(
                    'div',
                    'planner-month__cell-header',
                    `<span>${cursor.getDate()}</span>`,
                );
                if (cursor.getMonth() !== this.currentDate.getMonth()) {
                    cell.classList.add('planner-month__cell--muted');
                }
                cell.appendChild(cellHeader);

                const dayEvents = this.events.filter((event) => isSameDay(event.start, cursor));
                const list = buildElement('ul', 'planner-month__events');
                dayEvents.slice(0, 3).forEach((event) => {
                    const status = STATUS_CONFIG[event.status] ?? STATUS_CONFIG.les;
                    const item = buildElement(
                        'li',
                        'planner-month__event',
                        `<span class="planner-month__dot" style="background:${status.color}"></span>` +
                            `<span class="planner-month__title">${event.title}</span>` +
                            `<span class="planner-month__time">${formatTime(event.start)}</span>`,
                    );
                    item.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        evt.stopPropagation();
                        this.onEventClick({ event });
                    });
                    list.appendChild(item);
                });
                if (dayEvents.length > 3) {
                    list.appendChild(
                        buildElement(
                            'li',
                            'planner-month__more',
                            `+${dayEvents.length - 3} meer`,
                        ),
                    );
                }
                cell.appendChild(list);

                cell.addEventListener('click', () => {
                    const startTime = addMinutes(cursor, 9 * 60);
                    const endTime = addMinutes(startTime, 60);
                    this.onSelect({ start: startTime, end: endTime, instructorId: null });
                });

                grid.appendChild(cell);
                cursor.setDate(cursor.getDate() + 1);
            }
            container.appendChild(grid);
            return container;
        }

        computeColumnLayouts(events) {
            if (!events.length) {
                return [];
            }
            const sorted = [...events].sort((a, b) => a.start - b.start || a.end - b.end);
            const groups = new Map();
            sorted.forEach((event) => {
                groups.set(event, new Set([event]));
            });
            for (let i = 0; i < sorted.length; i += 1) {
                for (let j = i + 1; j < sorted.length; j += 1) {
                    if (eventsOverlap(sorted[i], sorted[j])) {
                        groups.get(sorted[i]).add(sorted[j]);
                        groups.get(sorted[j]).add(sorted[i]);
                    }
                }
            }
            const assignments = new Map();
            sorted.forEach((event) => {
                const used = new Set();
                groups.get(event).forEach((other) => {
                    if (assignments.has(other)) {
                        used.add(assignments.get(other));
                    }
                });
                let columnIndex = 0;
                while (used.has(columnIndex)) {
                    columnIndex += 1;
                }
                assignments.set(event, columnIndex);
            });
            return sorted.map((event) => {
                const group = groups.get(event);
                let max = 0;
                group.forEach((other) => {
                    const index = assignments.get(other) ?? 0;
                    if (index > max) {
                        max = index;
                    }
                });
                return {
                    event,
                    columnIndex: assignments.get(event) ?? 0,
                    columnCount: max + 1,
                };
            });
        }
        createEventElement(layout, columnDate, columnIndex) {
            const { event, columnIndex: colIndex, columnCount } = layout;
            const startMinutes = Math.max(0, differenceInMinutes(event.start, columnDate));
            const endMinutes = Math.min(24 * 60, differenceInMinutes(event.end, columnDate));
            const top = startMinutes * MINUTE_HEIGHT;
            const height = Math.max(MIN_EVENT_HEIGHT, (endMinutes - startMinutes) * MINUTE_HEIGHT);
            const widthPercent = 100 / columnCount;
            const leftPercent = colIndex * widthPercent;
            const status = STATUS_CONFIG[event.status] ?? STATUS_CONFIG.les;

            const element = buildElement(
                'div',
                'planner-event',
                `
                    <div class="planner-event__time">${formatTime(event.start)} – ${formatTime(event.end)}</div>
                    <div class="planner-event__title">${event.title}</div>
                    ${event.location ? `<div class="planner-event__meta">${event.location}</div>` : ''}
                    <div class="planner-event__status" style="color:${status.color}">${status.label}</div>
                    <button class="planner-event__resize planner-event__resize--start" type="button"></button>
                    <button class="planner-event__resize planner-event__resize--end" type="button"></button>
                `,
            );
            element.style.top = `${top}px`;
            element.style.height = `${height}px`;
            element.style.left = `${leftPercent}%`;
            element.style.width = `${widthPercent}%`;
            element.style.setProperty('--planner-event-color', status.color);
            element.style.setProperty('--planner-event-bg', status.bg);
            element.dataset.eventId = String(event.id);
            element.dataset.columnIndex = String(columnIndex);

            element.addEventListener('click', (evt) => {
                if (this.dragState) {
                    return;
                }
                if (evt.target.classList.contains('planner-event__resize')) {
                    return;
                }
                this.onEventClick({ event });
            });

            const moveHandler = (evt) => this.handlePointerMove(evt);
            const upHandler = (evt) => this.handlePointerUp(evt);

            element.addEventListener('pointerdown', (evt) => {
                if (evt.button !== 0) {
                    return;
                }
                const target = evt.target;
                if (target.classList.contains('planner-event__resize--start')) {
                    this.startDrag(evt, event, 'resize-start', columnIndex, columnDate);
                } else if (target.classList.contains('planner-event__resize--end')) {
                    this.startDrag(evt, event, 'resize-end', columnIndex, columnDate);
                } else {
                    this.startDrag(evt, event, 'move', columnIndex, columnDate);
                }
                if (this.dragState) {
                    element.setPointerCapture(evt.pointerId);
                    element.addEventListener('pointermove', moveHandler);
                    element.addEventListener('pointerup', upHandler, { once: true });
                }
            });

            return element;
        }

        startDrag(evt, event, type, columnIndex, columnDate) {
            this.dragState = {
                type,
                event,
                columnIndex,
                columnDate,
                pointerId: evt.pointerId,
                startY: evt.clientY,
                originalStart: new Date(event.start.getTime()),
                originalEnd: new Date(event.end.getTime()),
            };
            evt.preventDefault();
        }

        handlePointerMove(evt) {
            const drag = this.dragState;
            if (!drag || evt.pointerId !== drag.pointerId) {
                return;
            }
            const deltaY = evt.clientY - drag.startY;
            const minutesDelta = Math.round(deltaY / MINUTE_HEIGHT / SLOT_MINUTES) * SLOT_MINUTES;
            let newStart = new Date(drag.originalStart.getTime());
            let newEnd = new Date(drag.originalEnd.getTime());
            if (drag.type === 'move') {
                newStart = addMinutes(drag.originalStart, minutesDelta);
                newEnd = addMinutes(drag.originalEnd, minutesDelta);
            } else if (drag.type === 'resize-start') {
                newStart = addMinutes(drag.originalStart, minutesDelta);
                if (differenceInMinutes(newEnd, newStart) < SLOT_MINUTES) {
                    newStart = addMinutes(newEnd, -SLOT_MINUTES);
                }
            } else if (drag.type === 'resize-end') {
                newEnd = addMinutes(drag.originalEnd, minutesDelta);
                if (differenceInMinutes(newEnd, newStart) < SLOT_MINUTES) {
                    newEnd = addMinutes(newStart, SLOT_MINUTES);
                }
            }

            const columnStart = drag.columnDate;
            const columnEnd = addMinutes(columnStart, 24 * 60);
            if (newStart < columnStart) {
                const adjust = differenceInMinutes(columnStart, newStart);
                newStart = columnStart;
                if (drag.type === 'move') {
                    newEnd = addMinutes(newEnd, adjust);
                }
            }
            if (newEnd > columnEnd) {
                const adjust = differenceInMinutes(newEnd, columnEnd);
                newEnd = columnEnd;
                if (drag.type === 'move') {
                    newStart = addMinutes(newStart, -adjust);
                }
            }

            const eventElements = this.element.querySelectorAll(
                `.planner-event[data-event-id="${drag.event.id}"]`,
            );
            eventElements.forEach((element) => {
                const startMinutes = Math.max(0, differenceInMinutes(newStart, columnStart));
                const endMinutes = Math.min(24 * 60, differenceInMinutes(newEnd, columnStart));
                element.style.top = `${startMinutes * MINUTE_HEIGHT}px`;
                element.style.height = `${Math.max(
                    MIN_EVENT_HEIGHT,
                    (endMinutes - startMinutes) * MINUTE_HEIGHT,
                )}px`;
                element.classList.add('planner-event--dragging');
            });

            drag.previewStart = newStart;
            drag.previewEnd = newEnd;
        }

        handlePointerUp(evt) {
            const drag = this.dragState;
            if (!drag || evt.pointerId !== drag.pointerId) {
                return;
            }
            const eventElements = this.element.querySelectorAll(
                `.planner-event[data-event-id="${drag.event.id}"]`,
            );
            eventElements.forEach((element) => {
                element.classList.remove('planner-event--dragging');
                element.releasePointerCapture(drag.pointerId);
                element.removeEventListener('pointermove', this.handlePointerMove);
            });

            const newStart = drag.previewStart || drag.originalStart;
            const newEnd = drag.previewEnd || drag.originalEnd;
            this.dragState = null;

            if (
                newStart.getTime() === drag.originalStart.getTime() &&
                newEnd.getTime() === drag.originalEnd.getTime()
            ) {
                return;
            }

            if (drag.type === 'move') {
                this.onEventMove({ event: drag.event, start: newStart, end: newEnd });
            } else {
                this.onEventResize({ event: drag.event, start: newStart, end: newEnd });
            }
        }

        unselect() {}
    }
    function normaliseSearchTerm(value) {
        return value
            .toLowerCase()
            .replace(/[^0-9a-z\u00c0-\u017e]+/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, options);
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const error = new Error(errorData.message || 'Onbekende fout');
            error.status = response.status;
            throw error;
        }
        return response.json();
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
        const selectedStudentDetails = document.getElementById('selected-student-details');
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
        const modalTitle = document.getElementById('event-modal-title');
        const summaryStudent = document.getElementById('summary-student');
        const summaryInstructor = document.getElementById('summary-instructor');
        const summaryStatus = document.getElementById('summary-status');
        const summaryLocation = document.getElementById('summary-location');
        const openStudentButtons = document.querySelectorAll('[data-open-student-modal]');
        const closeModalButtons = modal.querySelectorAll('[data-close-modal]');
        const closeStudentButtons = studentModal.querySelectorAll('[data-close-student-modal]');
        const notifyStudentEmailInput = document.getElementById('notify-student-email');
        const notifyStudentPhoneInput = document.getElementById('notify-student-phone');
        const notifyParentEmailInput = document.getElementById('notify-parent-email');
        const notifyParentPhoneInput = document.getElementById('notify-parent-phone');
        const notifyGuardianEmailInput = document.getElementById('notify-guardian-email');
        const notifyGuardianPhoneInput = document.getElementById('notify-guardian-phone');
        const hasGuardianInput = document.getElementById('has_guardian');
        const guardianSection = document.getElementById('guardian-section');
        const studentHasGuardianInput = document.getElementById('student_has_guardian');
        const studentGuardianFields = document.getElementById('student-guardian-fields');
        const studentGuardianPrefToggles = studentModal.querySelectorAll('[data-student-guardian-pref]');
        const studentNotifyGuardianEmailInput = document.getElementById('student_notify_guardian_email');
        const studentNotifyGuardianPhoneInput = document.getElementById('student_notify_guardian_phone');

        let selectedStudentData = null;
        let searchTimeout = null;

        const contactEditors = buildContactEditors();

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
                    hrefFormatter: (value) => `tel:${value.replace(/[^0-9+]/g, '')}`,
                }),
                parentEmail: createContactEditor({
                    inputId: 'parent_email',
                    displayId: 'parent-email-display',
                    linkId: 'parent-email-link',
                    toggleId: 'toggle-parent-email-edit',
                    emptyLabel: 'Geen ouder e-mail',
                    hrefFormatter: (value) => `mailto:${value}`,
                }),
                parentPhone: createContactEditor({
                    inputId: 'parent_phone',
                    displayId: 'parent-phone-display',
                    linkId: 'parent-phone-link',
                    toggleId: 'toggle-parent-phone-edit',
                    emptyLabel: 'Geen ouder telefoon',
                    hrefFormatter: (value) => `tel:${value.replace(/[^0-9+]/g, '')}`,
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
                    hrefFormatter: (value) => `tel:${value.replace(/[^0-9+]/g, '')}`,
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

        const planner = new PlannerCalendar(calendarElement, {
            initialView: config.initialView || 'timeGridWeek',
            initialDate: config.initialDate || new Date().toISOString(),
            instructors: config.instructors || [],
            activeInstructorIds: getActiveInstructorIds,
            onSelect: handleSelect,
            onEventClick: ({ event }) => openModalForEvent(event),
            onEventMove: handleEventMove,
            onEventResize: handleEventResize,
            onRangeChange: handleRangeChange,
        });

        function updateRangeLabel() {
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
            refreshEvents();
        }

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
        function refreshSummary() {
            summaryStudent.textContent = selectedStudentData?.full_name ?? '-';
            summaryStatus.textContent = STATUS_CONFIG[statusSelect.value]?.label ?? statusSelect.value ?? '-';
            summaryLocation.textContent = locationInput.value || '-';
            if (config.userRole === 'admin' && summaryInstructor) {
                summaryInstructor.textContent = instructorSelect.value
                    ? instructorSelect.options[instructorSelect.selectedIndex]?.textContent ?? '-'
                    : '-';
            }
        }

        function setSelectedStudent(student) {
            selectedStudentData = student;
            if (!student) {
                studentIdInput.value = '';
                selectedStudent.classList.add('hidden');
                selectedStudentDetails.innerHTML = '';
                refreshSummary();
                return;
            }
            studentIdInput.value = student.id;
            selectedStudent.classList.remove('hidden');
            selectedStudentDetails.innerHTML = `
                <h4 class="text-sm font-semibold text-slate-900">${student.full_name}</h4>
                <p class="text-xs text-slate-500">${student.email || 'Geen e-mail'} • ${
                    student.phone || 'Geen telefoon'
                }</p>
            `;
            refreshSummary();
        }

        function populateEventForm(event) {
            eventIdInput.value = event?.id ?? '';
            statusSelect.value = event?.status ?? 'les';
            vehicleInput.value = event?.vehicle ?? '';
            packageInput.value = event?.package ?? '';
            locationInput.value = event?.location ?? '';
            descriptionInput.value = event?.description ?? '';
            startInput.value = event?.start ? event.start.toISOString().slice(0, 16) : '';
            endInput.value = event?.end ? event.end.toISOString().slice(0, 16) : '';
            notifyStudentEmailInput.checked = Boolean(event?.notify_student_email ?? true);
            notifyStudentPhoneInput.checked = Boolean(event?.notify_student_phone ?? false);
            notifyParentEmailInput.checked = Boolean(event?.notify_parent_email ?? false);
            notifyParentPhoneInput.checked = Boolean(event?.notify_parent_phone ?? false);
            notifyGuardianEmailInput.checked = Boolean(event?.notify_guardian_email ?? false);
            notifyGuardianPhoneInput.checked = Boolean(event?.notify_guardian_phone ?? false);
            hasGuardianInput.checked = Boolean(event?.has_guardian ?? false);
            updateGuardianVisibility();
            contactEditors.studentEmail?.setValue(event?.email ?? '');
            contactEditors.studentPhone?.setValue(event?.phone ?? '');
            contactEditors.parentEmail?.setValue(event?.parent_email ?? '');
            contactEditors.parentPhone?.setValue(event?.parent_phone ?? '');
            contactEditors.guardianEmail?.setValue(event?.guardian_email ?? '');
            contactEditors.guardianPhone?.setValue(event?.guardian_phone ?? '');
            if (config.userRole === 'admin' && instructorSelect) {
                instructorSelect.value = event?.instructor_id ?? '';
            }
            refreshSummary();
        }

        function openModalForEvent(event) {
            modalTitle.textContent = 'Afspraak bewerken';
            const enriched = {
                ...event,
                start: new Date(event.start_time || event.start),
                end: new Date(event.end_time || event.end),
            };
            populateEventForm(enriched);
            setSelectedStudent({
                id: event.student_id,
                full_name: event.student_name,
                email: event.student_email,
                phone: event.student_phone,
            });
            openModal();
        }

        function handleSelect({ start, end, instructorId }) {
            modalTitle.textContent = 'Nieuwe afspraak';
            setSelectedStudent(null);
            populateEventForm({ start, end });
            if (config.userRole === 'admin' && instructorSelect && instructorId) {
                instructorSelect.value = String(instructorId);
            }
            openModal();
        }

        async function saveEvent(eventId, payload) {
            const url = eventId ? `/events/${eventId}` : '/events';
            const method = eventId ? 'PUT' : 'POST';
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
            return fetchJson(`/students?${params.toString()}`);
        }

        function renderStudentResults(students) {
            studentResults.innerHTML = '';
            if (!students.length) {
                studentResults.innerHTML =
                    '<li class="px-4 py-2 text-sm text-slate-500">Geen leerlingen gevonden.</li>';
                return;
            }
            const fragment = document.createDocumentFragment();
            students.forEach((student) => {
                const item = buildElement(
                    'li',
                    'cursor-pointer px-4 py-2 text-sm hover:bg-slate-100',
                    `<div class="font-semibold text-slate-800">${student.full_name}</div>` +
                        `<div class="text-xs text-slate-500">${student.email || 'Geen e-mail'} • ${
                            student.phone || 'Geen telefoon'
                        }</div>`,
                );
                item.addEventListener('click', () => {
                    setSelectedStudent(student);
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
            try {
                const student = await fetchJson('/students', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: formData,
                });
                setSelectedStudent(student);
                studentSearch.value = '';
                studentResults.innerHTML = '';
                closeStudentModal();
            } catch (error) {
                alert(error.message || 'Kon leerling niet opslaan.');
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
                await deleteStudent(selectedStudentData.id);
                setSelectedStudent(null);
                planner.setEvents(
                    planner.events.filter((event) => event.student_id !== selectedStudentData.id),
                );
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
            const payload = {
                student_id: Number.parseInt(studentIdInput.value, 10),
                status: statusSelect.value,
                start_time: new Date(startInput.value).toISOString(),
                end_time: new Date(endInput.value).toISOString(),
                vehicle: vehicleInput.value || null,
                package: packageInput.value || null,
                location: locationInput.value || null,
                description: descriptionInput.value || null,
                email: contactEditors.studentEmail?.getValue() || null,
                phone: contactEditors.studentPhone?.getValue() || null,
                parent_email: contactEditors.parentEmail?.getValue() || null,
                parent_phone: contactEditors.parentPhone?.getValue() || null,
                has_guardian: hasGuardianInput.checked,
                guardian_email: hasGuardianInput.checked
                    ? contactEditors.guardianEmail?.getValue() || null
                    : null,
                guardian_phone: hasGuardianInput.checked
                    ? contactEditors.guardianPhone?.getValue() || null
                    : null,
                notify_student_email: notifyStudentEmailInput.checked,
                notify_student_phone: notifyStudentPhoneInput.checked,
                notify_parent_email: notifyParentEmailInput.checked,
                notify_parent_phone: notifyParentPhoneInput.checked,
                notify_guardian_email: hasGuardianInput.checked ? notifyGuardianEmailInput.checked : false,
                notify_guardian_phone: hasGuardianInput.checked ? notifyGuardianPhoneInput.checked : false,
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
                start_time: start.toISOString(),
                end_time: end.toISOString(),
                vehicle: event.vehicle,
                package: event.package,
                location: event.location,
                description: event.description,
                email: event.email,
                phone: event.phone,
                parent_email: event.parent_email,
                parent_phone: event.parent_phone,
                has_guardian: event.has_guardian,
                guardian_email: event.guardian_email,
                guardian_phone: event.guardian_phone,
                notify_student_email: event.notify_student_email,
                notify_student_phone: event.notify_student_phone,
                notify_parent_email: event.notify_parent_email,
                notify_parent_phone: event.notify_parent_phone,
                notify_guardian_email: event.notify_guardian_email,
                notify_guardian_phone: event.notify_guardian_phone,
            };
            event.start = start;
            event.end = end;
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

        document.querySelectorAll('[data-calendar-view]').forEach((button) => {
            button.addEventListener('click', () => {
                const view = button.dataset.calendarView;
                planner.changeView(view);
                document.querySelectorAll('[data-calendar-view]').forEach((other) => {
                    other.classList.toggle('bg-white text-sky-600 shadow-sm', other === button);
                    other.classList.toggle('text-slate-500', other !== button);
                });
                button.classList.add('bg-white', 'text-sky-600', 'shadow-sm');
            });
        });

        const initialViewButton = document.querySelector(
            `[data-calendar-view="${planner.getViewInfo().type}"]`,
        );
        initialViewButton?.classList.add('bg-white', 'text-sky-600', 'shadow-sm');

        function updateFilterButton(button, active) {
            button.dataset.active = active ? 'true' : 'false';
            button.classList.toggle('active', active);
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
