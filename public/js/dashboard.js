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

    const REVERSE_VIEW_MAP = {
        week: 'timeGridWeek',
        day: 'timeGridDay',
        month: 'dayGridMonth',
    };

    const SLOT_MINUTES = 15;
    const MINUTE_HEIGHT = 1.05;
    const MIN_EVENT_HEIGHT = 32;

    const DATE_FORMATTER = new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'long' });
    const MONTH_FORMATTER = new Intl.DateTimeFormat('nl-NL', { month: 'long', year: 'numeric' });
    const WEEKDAY_FORMATTER = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric' });
    const TIME_FORMATTER = new Intl.DateTimeFormat('nl-NL', { hour: '2-digit', minute: '2-digit' });
    const BIRTHDATE_FORMATTER = new Intl.DateTimeFormat('nl-NL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });

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
            this.onSelect = options.onSelect || (() => {});
            this.onEventClick = options.onEventClick || (() => {});
            this.onEventMove = options.onEventMove || (() => {});
            this.onEventResize = options.onEventResize || (() => {});
            this.onRangeChange = options.onRangeChange || (() => {});
            this.columnsMeta = [];
            this.monthCellsMeta = [];
            this.dragState = null;
            this.recentlyDragged = new Map();
            this.element.classList.add('planner-root');
            this.loadingOverlay = buildElement('div', 'planner-loading hidden', [
                buildElement('div', 'planner-loading__spinner', ''),
            ]);
            this.element.appendChild(this.loadingOverlay);
        }

        captureScrollState() {
            const state = {};
            const timeGrid = this.element.querySelector('.planner-time-grid');
            if (timeGrid) {
                state.timeGrid = {
                    scrollLeft: timeGrid.scrollLeft,
                    scrollTop: timeGrid.scrollTop,
                };
                const timeGridBody = timeGrid.querySelector('.planner-time-grid__body');
                if (timeGridBody) {
                    state.timeGridBody = {
                        scrollLeft: timeGridBody.scrollLeft,
                        scrollTop: timeGridBody.scrollTop,
                    };
                }
            }
            const monthGrid = this.element.querySelector('.planner-month__grid');
            if (monthGrid) {
                state.month = {
                    scrollLeft: monthGrid.scrollLeft,
                    scrollTop: monthGrid.scrollTop,
                };
            }
            return state;
        }

        restoreScrollState(state) {
            if (!state || typeof state !== 'object') {
                return;
            }
            const timeGrid = this.element.querySelector('.planner-time-grid');
            if (timeGrid && state.timeGrid) {
                if (typeof state.timeGrid.scrollLeft === 'number') {
                    timeGrid.scrollLeft = state.timeGrid.scrollLeft;
                }
                if (typeof state.timeGrid.scrollTop === 'number') {
                    timeGrid.scrollTop = state.timeGrid.scrollTop;
                }
            }
            const timeGridBody = this.element.querySelector('.planner-time-grid__body');
            if (timeGridBody && state.timeGridBody) {
                if (typeof state.timeGridBody.scrollLeft === 'number') {
                    timeGridBody.scrollLeft = state.timeGridBody.scrollLeft;
                }
                if (typeof state.timeGridBody.scrollTop === 'number') {
                    timeGridBody.scrollTop = state.timeGridBody.scrollTop;
                }
            }
            const monthGrid = this.element.querySelector('.planner-month__grid');
            if (monthGrid && state.month) {
                if (typeof state.month.scrollLeft === 'number') {
                    monthGrid.scrollLeft = state.month.scrollLeft;
                }
                if (typeof state.month.scrollTop === 'number') {
                    monthGrid.scrollTop = state.month.scrollTop;
                }
            }
        }

        markEventRecentlyDragged(eventId) {
            if (!eventId) {
                return;
            }
            const stamp = Date.now();
            this.recentlyDragged.set(eventId, stamp);
            window.setTimeout(() => {
                if (this.recentlyDragged.get(eventId) === stamp) {
                    this.recentlyDragged.delete(eventId);
                }
            }, 400);
        }

        shouldIgnoreClick(eventId) {
            if (!eventId) {
                return false;
            }
            const stamp = this.recentlyDragged.get(eventId);
            return typeof stamp === 'number' && Date.now() - stamp < 400;
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
                case 'week': {
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
            this.monthCellsMeta = [];
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
                case 'month':
                default:
                    wrapper.appendChild(this.renderMonthView(range.start, range.end));
                    break;
            }
            this.element.querySelectorAll('.planner-view').forEach((child) => child.remove());
            this.element.insertBefore(wrapper, this.loadingOverlay);
        }

        setEvents(events) {
            const scrollState = this.captureScrollState();
            this.events = events.map((event) => ({ ...event }));
            this.render();
            this.restoreScrollState(scrollState);
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
            const columnCount = Math.max(1, columns.length || 0);
            container.style.setProperty('--planner-column-count', String(columnCount));
            const header = buildElement('div', 'planner-time-grid__header');
            header.appendChild(
                buildElement(
                    'div',
                    'planner-time-grid__header-cell planner-time-grid__times-header planner-time-grid__times-header--leading',
                ),
            );
            columns.forEach((column, index) => {
                const label = showDayNames ? formatWeekday(column.date) : DATE_FORMATTER.format(column.date);
                const headerCell = buildElement(
                    'div',
                    'planner-time-grid__header-cell',
                    `<div class="planner-time-grid__header-label">${label}</div>`,
                );
                if (showDayNames) {
                    const day = column.date.getDay();
                    if (day === 0 || day === 6) {
                        headerCell.dataset.weekend = 'true';
                    }
                }
                header.appendChild(headerCell);
            });
            header.appendChild(
                buildElement(
                    'div',
                    'planner-time-grid__header-cell planner-time-grid__times-header planner-time-grid__times-header--trailing',
                ),
            );
            container.appendChild(header);

            const body = buildElement('div', 'planner-time-grid__body');
            const buildTimesColumn = (className) => {
                const column = buildElement('div', `planner-time-grid__times ${className || ''}`.trim());
                for (let hour = 0; hour < 24; hour += 1) {
                    const timeLabel = buildElement(
                        'div',
                        'planner-time-grid__time-label',
                        `<span>${`${hour}`.padStart(2, '0')}:00</span>`,
                    );
                    timeLabel.style.height = `${MINUTE_HEIGHT * 60}px`;
                    column.appendChild(timeLabel);
                }
                return column;
            };
            body.appendChild(buildTimesColumn('planner-time-grid__times--leading'));

            columns.forEach((column, columnIndex) => {
                const columnElement = buildElement('div', 'planner-time-grid__column');
                columnElement.dataset.columnIndex = String(columnIndex);
                columnElement.dataset.date = column.date.toISOString();
                if (column.instructorId !== null) {
                    columnElement.dataset.instructorId = String(column.instructorId);
                }
                const day = column.date.getDay();
                if (day === 0 || day === 6) {
                    columnElement.dataset.weekend = 'true';
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

                this.columnsMeta.push({
                    index: columnIndex,
                    date: column.date,
                    instructorId: column.instructorId ?? null,
                    element: columnElement,
                    rect: null,
                });

                body.appendChild(columnElement);
            });

            body.appendChild(buildTimesColumn('planner-time-grid__times--trailing'));

            container.appendChild(body);
            return container;
        }

        updateColumnRects() {
            this.columnsMeta.forEach((meta) => {
                if (meta.element) {
                    meta.rect = meta.element.getBoundingClientRect();
                }
            });
        }

        resolveColumnFromPoint(x) {
            let closest = null;
            let closestDistance = Infinity;
            this.columnsMeta.forEach((meta) => {
                if (!meta.rect) {
                    return;
                }
                if (x >= meta.rect.left && x <= meta.rect.right) {
                    closest = meta;
                    closestDistance = 0;
                } else {
                    const distance = Math.min(Math.abs(x - meta.rect.left), Math.abs(x - meta.rect.right));
                    if (distance < closestDistance) {
                        closestDistance = distance;
                        closest = meta;
                    }
                }
            });
            return closest;
        }

        highlightColumn(meta) {
            if (this.dragState?.activeColumnMeta === meta) {
                return;
            }
            if (this.dragState?.activeColumnMeta?.element) {
                this.dragState.activeColumnMeta.element.removeAttribute('data-drag-target');
            }
            if (meta?.element) {
                meta.element.setAttribute('data-drag-target', 'true');
            }
            if (this.dragState) {
                this.dragState.activeColumnMeta = meta || null;
            }
        }

        updateMonthCellRects() {
            this.monthCellsMeta.forEach((meta) => {
                if (meta.element) {
                    meta.rect = meta.element.getBoundingClientRect();
                }
            });
        }

        resolveMonthCellFromPoint(x, y) {
            let target = null;
            let distance = Infinity;
            this.monthCellsMeta.forEach((meta) => {
                if (!meta.rect) {
                    return;
                }
                const withinX = x >= meta.rect.left && x <= meta.rect.right;
                const withinY = y >= meta.rect.top && y <= meta.rect.bottom;
                if (withinX && withinY) {
                    target = meta;
                    distance = 0;
                } else {
                    const dx = withinX ? 0 : Math.min(Math.abs(x - meta.rect.left), Math.abs(x - meta.rect.right));
                    const dy = withinY ? 0 : Math.min(Math.abs(y - meta.rect.top), Math.abs(y - meta.rect.bottom));
                    const current = Math.hypot(dx, dy);
                    if (current < distance) {
                        distance = current;
                        target = meta;
                    }
                }
            });
            return target;
        }

        highlightMonthCell(meta) {
            if (this.dragState?.activeMonthMeta === meta) {
                return;
            }
            if (this.dragState?.activeMonthMeta?.element) {
                this.dragState.activeMonthMeta.element.removeAttribute('data-drag-target');
            }
            if (meta?.element) {
                meta.element.setAttribute('data-drag-target', 'true');
            }
            if (this.dragState) {
                this.dragState.activeMonthMeta = meta || null;
            }
        }

        startMonthDrag(evt, event, meta) {
            this.updateMonthCellRects();
            this.dragState = {
                kind: 'month',
                type: 'move',
                event,
                pointerId: evt.pointerId,
                originalStart: new Date(event.start.getTime()),
                originalEnd: new Date(event.end.getTime()),
                duration: Math.max(SLOT_MINUTES, differenceInMinutes(event.end, event.start)),
                originMeta: meta,
                previewStart: new Date(event.start.getTime()),
                previewEnd: new Date(event.end.getTime()),
                activeMonthMeta: meta,
            };
            meta?.element?.setAttribute('data-drag-target', 'true');
        }

        handleMonthDragMove(evt) {
            const drag = this.dragState;
            if (!drag) {
                return;
            }
            const meta = this.resolveMonthCellFromPoint(evt.clientX, evt.clientY) || drag.originMeta;
            if (!meta) {
                return;
            }
            this.highlightMonthCell(meta);

            const start = startOfDay(meta.date);
            start.setHours(drag.originalStart.getHours(), drag.originalStart.getMinutes(), 0, 0);
            const end = addMinutes(start, drag.duration);

            drag.previewStart = start;
            drag.previewEnd = end;
            drag.targetMonthMeta = meta;
        }

        finishMonthDrag(evt) {
            const drag = this.dragState;
            if (!drag) {
                return;
            }
            const meta = this.resolveMonthCellFromPoint(evt.clientX, evt.clientY) || drag.targetMonthMeta || drag.originMeta;
            if (drag.activeMonthMeta?.element) {
                drag.activeMonthMeta.element.removeAttribute('data-drag-target');
            }
            this.dragState = null;
            if (!meta) {
                return;
            }
            const newStart = drag.previewStart || drag.originalStart;
            const newEnd = drag.previewEnd || drag.originalEnd;
            if (newStart.getTime() === drag.originalStart.getTime() && newEnd.getTime() === drag.originalEnd.getTime()) {
                return;
            }
            this.markEventRecentlyDragged(drag.event.id);
            this.onEventMove({ event: drag.event, start: newStart, end: newEnd });
        }

        renderMonthView(start, end) {
            const container = buildElement('div', 'planner-month');
            const header = buildElement('div', 'planner-month__header');
            const monthWeekdays = [
                { label: 'Ma', title: 'Maandag' },
                { label: 'Di', title: 'Dinsdag' },
                { label: 'Wo', title: 'Woensdag' },
                { label: 'Do', title: 'Donderdag' },
                { label: 'Vr', title: 'Vrijdag' },
                { label: 'Za', title: 'Zaterdag', weekend: true },
                { label: 'Zo', title: 'Zondag', weekend: true },
            ];
            monthWeekdays.forEach((weekday) => {
                const cell = buildElement('div', 'planner-month__header-cell', weekday.label);
                cell.setAttribute('title', weekday.title);
                if (weekday.weekend) {
                    cell.dataset.weekend = 'true';
                }
                header.appendChild(cell);
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
                const weekday = cursor.getDay();
                if (weekday === 0 || weekday === 6) {
                    cell.dataset.weekend = 'true';
                }
                cell.appendChild(cellHeader);

                const dayEvents = this.events.filter((event) => isSameDay(event.start, cursor));
                const list = buildElement('ul', 'planner-month__events');
                const cellMeta = {
                    date: new Date(cursor.getTime()),
                    element: cell,
                    events: dayEvents,
                    listElement: list,
                    expanded: false,
                    rect: null,
                };
                this.monthCellsMeta.push(cellMeta);
                this.renderMonthCellEvents(cellMeta);
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

        renderMonthCellEvents(meta) {
            const { events, listElement, element } = meta;
            listElement.innerHTML = '';
            const collapsedOverflow = events.length > 3;
            const limit = meta.expanded ? events.length : 3;
            events.slice(0, limit).forEach((event) => {
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
                    if (this.shouldIgnoreClick(event.id)) {
                        return;
                    }
                    this.onEventClick({ event });
                });
                const moveHandler = (evt) => this.handlePointerMove(evt);
                const upHandler = (evt) => {
                    item.releasePointerCapture(evt.pointerId);
                    item.removeEventListener('pointermove', moveHandler);
                    this.handlePointerUp(evt);
                };
                item.addEventListener('pointerdown', (evt) => {
                    if (evt.button !== 0) {
                        return;
                    }
                    if (evt.target.closest('button')) {
                        return;
                    }
                    this.startMonthDrag(evt, event, meta);
                    if (this.dragState) {
                        item.setPointerCapture(evt.pointerId);
                        item.addEventListener('pointermove', moveHandler);
                        item.addEventListener('pointerup', upHandler, { once: true });
                        item.addEventListener('pointercancel', upHandler, { once: true });
                    }
                });
                listElement.appendChild(item);
            });

            element.dataset.expanded = meta.expanded ? 'true' : 'false';

            if (!meta.expanded && collapsedOverflow) {
                const wrapper = buildElement('li', 'planner-month__more-item');
                const button = buildElement('button', 'planner-month__more', `+${events.length - limit} meer`);
                button.type = 'button';
                button.addEventListener('click', (evt) => {
                    evt.preventDefault();
                    evt.stopPropagation();
                    meta.expanded = true;
                    this.renderMonthCellEvents(meta);
                });
                wrapper.appendChild(button);
                listElement.appendChild(wrapper);
            } else if (meta.expanded && collapsedOverflow) {
                const wrapper = buildElement('li', 'planner-month__more-item');
                const button = buildElement('button', 'planner-month__more', 'Minder tonen');
                button.type = 'button';
                button.addEventListener('click', (evt) => {
                    evt.preventDefault();
                    evt.stopPropagation();
                    meta.expanded = false;
                    this.renderMonthCellEvents(meta);
                });
                wrapper.appendChild(button);
                listElement.appendChild(wrapper);
            }
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
                    <div class="planner-event__title">${event.title}</div>
                    <div class="planner-event__status" style="color:${status.color}">${status.label}</div>
                    ${event.location ? `<div class="planner-event__meta">${event.location}</div>` : ''}
                    <div class="planner-event__time">${formatTime(event.start)} – ${formatTime(event.end)}</div>
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

            const tooltipLines = [
                `${formatTime(event.start)} – ${formatTime(event.end)}`,
                event.title,
            ];
            if (event.location) {
                tooltipLines.push(`Locatie: ${event.location}`);
            }
            tooltipLines.push(`Type: ${status.label}`);
            element.setAttribute('title', tooltipLines.join('\n'));

            if (height < 110) {
                element.classList.add('planner-event--condensed');
            }
            if (height < 80) {
                element.classList.add('planner-event--minimal');
            }

            element.addEventListener('click', (evt) => {
                if (this.dragState) {
                    return;
                }
                if (evt.target.classList.contains('planner-event__resize')) {
                    return;
                }
                if (this.shouldIgnoreClick(event.id)) {
                    return;
                }
                this.onEventClick({ event });
            });

            element.addEventListener('pointerdown', (evt) => {
                if (evt.button !== 0) {
                    return;
                }
                const target = evt.target;
                if (target.classList.contains('planner-event__resize--start')) {
                    this.startDrag(evt, event, 'resize-start', columnIndex, columnDate, element);
                } else if (target.classList.contains('planner-event__resize--end')) {
                    this.startDrag(evt, event, 'resize-end', columnIndex, columnDate, element);
                } else {
                    this.startDrag(evt, event, 'move', columnIndex, columnDate, element);
                }
                if (this.dragState) {
                    element.setPointerCapture(evt.pointerId);
                    const moveHandler = (moveEvent) => this.handlePointerMove(moveEvent);
                    element._plannerMoveHandler = moveHandler;
                    element.addEventListener('pointermove', moveHandler);
                    const upHandler = (upEvent) => this.handlePointerUp(upEvent);
                    element.addEventListener('pointerup', upHandler, { once: true });
                    element.addEventListener('pointercancel', upHandler, { once: true });
                }
            });

            return element;
        }

        startDrag(evt, event, type, columnIndex, columnDate, element) {
            const meta = this.columnsMeta.find((item) => item.index === columnIndex) || null;
            const columnElement = element.closest('.planner-time-grid__column');
            const styles = columnElement ? window.getComputedStyle(columnElement) : null;
            const paddingTop = styles ? Number.parseFloat(styles.paddingTop) || 0 : 0;
            const eventRect = element.getBoundingClientRect();
            const pointerOffsetMinutes = Math.round((evt.clientY - eventRect.top) / MINUTE_HEIGHT);
            const duration = Math.max(SLOT_MINUTES, differenceInMinutes(event.end, event.start));

            const baseColumnDate = meta?.date ? startOfDay(meta.date) : startOfDay(columnDate);

            let placeholder = null;
            let placeholderTime = null;
            if (columnElement) {
                placeholder = buildElement(
                    'div',
                    'planner-drop-placeholder',
                    `<div class="planner-drop-placeholder__time">${formatTime(event.start)} – ${formatTime(
                        event.end,
                    )}</div>`,
                );
                placeholderTime = placeholder.querySelector('.planner-drop-placeholder__time');
                columnElement.appendChild(placeholder);
                const startMinutes = Math.max(0, differenceInMinutes(event.start, baseColumnDate));
                const placeholderMinutes = Math.max(
                    SLOT_MINUTES,
                    Math.max(1, differenceInMinutes(event.end, event.start)),
                );
                placeholder.style.top = `${startMinutes * MINUTE_HEIGHT}px`;
                placeholder.style.height = `${Math.max(
                    MIN_EVENT_HEIGHT,
                    placeholderMinutes * MINUTE_HEIGHT,
                )}px`;
                placeholder.dataset.visible = 'true';
            }

            this.updateColumnRects();

            this.dragState = {
                kind: 'time-grid',
                type,
                event,
                columnIndex: meta?.index ?? columnIndex,
                columnDate: baseColumnDate,
                columnElement: meta?.element ?? columnElement,
                pointerId: evt.pointerId,
                originalStart: new Date(event.start.getTime()),
                originalEnd: new Date(event.end.getTime()),
                pointerOffsetMinutes: clamp(pointerOffsetMinutes, 0, duration),
                duration,
                columnPaddingTop: paddingTop,
                previewStart: new Date(event.start.getTime()),
                previewEnd: new Date(event.end.getTime()),
                activeColumnMeta: meta || null,
                placeholder,
                placeholderTime,
            };
            if (meta?.element) {
                meta.element.setAttribute('data-drag-target', 'true');
            }
        }

        handlePointerMove(evt) {
            const drag = this.dragState;
            if (!drag || evt.pointerId !== drag.pointerId) {
                return;
            }
            if (drag.kind === 'month') {
                this.handleMonthDragMove(evt);
                return;
            }

            this.updateColumnRects();

            const currentMeta = this.columnsMeta.find((meta) => meta.index === drag.columnIndex) || null;
            const activeMeta =
                drag.type === 'move'
                    ? this.resolveColumnFromPoint(evt.clientX) || currentMeta
                    : currentMeta;

            if (activeMeta?.element && activeMeta.element !== drag.columnElement) {
                drag.columnElement = activeMeta.element;
                drag.columnIndex = activeMeta.index;
                drag.columnDate = startOfDay(activeMeta.date);
                drag.columnPaddingTop = Number.parseFloat(
                    window.getComputedStyle(activeMeta.element).paddingTop,
                ) || 0;
                if (drag.placeholder && activeMeta.element && drag.placeholder.parentElement !== activeMeta.element) {
                    activeMeta.element.appendChild(drag.placeholder);
                }
            }

            if (activeMeta) {
                this.highlightColumn(activeMeta);
            }

            const columnRect = drag.columnElement?.getBoundingClientRect();
            const paddingTop = drag.columnPaddingTop || 0;
            const minutesFromTop = (() => {
                if (!columnRect) {
                    return 0;
                }
                const relative = evt.clientY - (columnRect.top + paddingTop);
                return clamp(Math.round(relative / MINUTE_HEIGHT), 0, 24 * 60);
            })();

            let newStart = new Date(drag.originalStart.getTime());
            let newEnd = new Date(drag.originalEnd.getTime());

            if (drag.type === 'move') {
                const startMinutes = clamp(
                    Math.round((minutesFromTop - drag.pointerOffsetMinutes) / SLOT_MINUTES) * SLOT_MINUTES,
                    0,
                    24 * 60 - drag.duration,
                );
                newStart = addMinutes(drag.columnDate, startMinutes);
                newEnd = addMinutes(newStart, drag.duration);
            } else if (drag.type === 'resize-start') {
                const startMinutes = Math.round(minutesFromTop / SLOT_MINUTES) * SLOT_MINUTES;
                newStart = addMinutes(
                    drag.columnDate,
                    clamp(startMinutes, 0, differenceInMinutes(drag.originalEnd, drag.columnDate) - SLOT_MINUTES),
                );
                if (differenceInMinutes(newEnd, newStart) < SLOT_MINUTES) {
                    newStart = addMinutes(newEnd, -SLOT_MINUTES);
                }
            } else if (drag.type === 'resize-end') {
                const endMinutes = Math.round(minutesFromTop / SLOT_MINUTES) * SLOT_MINUTES;
                newEnd = addMinutes(
                    drag.columnDate,
                    clamp(
                        endMinutes,
                        differenceInMinutes(drag.originalStart, drag.columnDate) + SLOT_MINUTES,
                        24 * 60,
                    ),
                );
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
            eventElements.forEach((node) => {
                if (drag.columnElement && node.parentElement !== drag.columnElement) {
                    drag.columnElement.appendChild(node);
                    node.dataset.columnIndex = String(drag.columnIndex);
                    node.style.left = '0px';
                    node.style.width = '100%';
                }
                const startMinutes = Math.max(0, differenceInMinutes(newStart, columnStart));
                const endMinutes = Math.min(24 * 60, differenceInMinutes(newEnd, columnStart));
                node.style.top = `${startMinutes * MINUTE_HEIGHT}px`;
                node.style.height = `${Math.max(
                    MIN_EVENT_HEIGHT,
                    (endMinutes - startMinutes) * MINUTE_HEIGHT,
                )}px`;
                node.classList.add('planner-event--dragging');
            });

            if (drag.placeholder) {
                const placeholderParent = drag.columnElement;
                if (placeholderParent && drag.placeholder.parentElement !== placeholderParent) {
                    placeholderParent.appendChild(drag.placeholder);
                }
                const placeholderStartMinutes = Math.max(0, differenceInMinutes(newStart, columnStart));
                const placeholderEndMinutes = Math.max(
                    placeholderStartMinutes + SLOT_MINUTES,
                    differenceInMinutes(newEnd, columnStart),
                );
                drag.placeholder.style.top = `${placeholderStartMinutes * MINUTE_HEIGHT}px`;
                drag.placeholder.style.height = `${Math.max(
                    MIN_EVENT_HEIGHT,
                    (placeholderEndMinutes - placeholderStartMinutes) * MINUTE_HEIGHT,
                )}px`;
                drag.placeholder.dataset.visible = 'true';
                if (drag.placeholderTime) {
                    drag.placeholderTime.textContent = `${formatTime(newStart)} – ${formatTime(newEnd)}`;
                }
            }

            drag.previewStart = newStart;
            drag.previewEnd = newEnd;
            evt.preventDefault();
        }

        handlePointerUp(evt) {
            const drag = this.dragState;
            if (!drag || evt.pointerId !== drag.pointerId) {
                return;
            }
            if (drag.kind === 'month') {
                this.finishMonthDrag(evt);
                return;
            }
            const eventElements = this.element.querySelectorAll(
                `.planner-event[data-event-id="${drag.event.id}"]`,
            );
            eventElements.forEach((element) => {
                element.classList.remove('planner-event--dragging');
                if (element.hasPointerCapture?.(drag.pointerId)) {
                    element.releasePointerCapture(drag.pointerId);
                }
                if (element._plannerMoveHandler) {
                    element.removeEventListener('pointermove', element._plannerMoveHandler);
                    delete element._plannerMoveHandler;
                }
            });

            if (drag.activeColumnMeta?.element) {
                drag.activeColumnMeta.element.removeAttribute('data-drag-target');
            }

            if (drag.placeholder?.parentElement) {
                drag.placeholder.parentElement.removeChild(drag.placeholder);
            }

            const newStart = drag.previewStart || drag.originalStart;
            const newEnd = drag.previewEnd || drag.originalEnd;
            const changed =
                newStart.getTime() !== drag.originalStart.getTime() ||
                newEnd.getTime() !== drag.originalEnd.getTime();
            this.dragState = null;

            if (!changed) {
                return;
            }

            this.markEventRecentlyDragged(drag.event.id);

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
        const modalTitle = document.getElementById('event-modal-title');
        const quickCreateButton = document.getElementById('quick-create-event');
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

        const planner = new PlannerCalendar(calendarElement, {
            initialView: config.initialView || 'timeGridWeek',
            initialDate: config.initialDate || new Date().toISOString(),
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
            updateViewButtons();
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

        function updateViewButtons() {
            const activeType = planner.getViewInfo().type;
            document.querySelectorAll('[data-calendar-view]').forEach((button) => {
                const isActive = button.dataset.calendarView === activeType;
                ['bg-white', 'text-sky-600', 'shadow-sm'].forEach((klass) => {
                    button.classList.toggle(klass, isActive);
                });
                button.classList.toggle('text-slate-500', !isActive);
            });
        }

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
