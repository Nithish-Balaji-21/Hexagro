document.addEventListener('alpine:init', () => {
    Alpine.data('hexRangePicker', () => ({
        startDate: '',
        endDate: '',
        picking: 'start',
        hoverDate: null,
        leftYear: new Date().getFullYear(),
        leftMonth: new Date().getMonth(),
        rightYear: new Date().getFullYear(),
        rightMonth: new Date().getMonth() === 11 ? 0 : new Date().getMonth() + 1,
        calendarsSynced: false,

        monthNames: [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ],

        dowLabels: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],

        todayStr: '',

        init() {
            this.todayStr = this.formatDateStr(
                new Date().getFullYear(),
                new Date().getMonth(),
                new Date().getDate(),
            );

            this.syncDatesFromWire();

            this.$watch('$wire.pickerFrom', () => {
                this.syncDatesFromWire();
                this.positionCalendarsFromStart();
            });

            this.$watch('$wire.pickerTo', () => {
                this.syncDatesFromWire();
                this.positionCalendarsFromStart();
            });

            this.$watch('$wire.pickerPreset', () => {
                this.syncDatesFromWire();
                this.positionCalendarsFromStart();
            });

            this.$watch('$wire.rangePickerOpen', (open) => {
                if (open) {
                    this.syncDatesFromWire();
                    this.positionCalendarsFromStart();
                    this.picking = (this.startDate && this.endDate && this.startDate !== this.endDate) ? 'complete' : 'start';
                    this.hoverDate = null;
                }

                this.$nextTick(() => this.updateIndicator());
            });

            this.$watch('$wire.rangePreset', () => {
                this.$nextTick(() => this.updateIndicator());
            });

            this.$nextTick(() => this.updateIndicator());

            window.addEventListener('resize', () => this.updateIndicator());

            if (typeof Livewire !== 'undefined') {
                Livewire.hook('morph.updated', () => {
                    this.$nextTick(() => this.updateIndicator());
                });

                this.$watch('$wire.rangePreset', () => {
                    this.$nextTick(() => this.dispatchRangeChanged());
                });
            }

            this.initDaterangepicker();
        },

        initDaterangepicker() {
            if (typeof window.$ === 'undefined' || ! this.$refs.trigger || typeof window.$.fn.daterangepicker === 'undefined') {
                return;
            }

            const now = new Date();
            const curY = now.getFullYear();
            const curM = now.getMonth();
            const fyStartYear = curM >= 3 ? curY : curY - 1;
            const fyStart = window.moment ? window.moment([fyStartYear, 3, 1]) : null;
            const prevFyStart = window.moment ? window.moment([fyStartYear - 1, 3, 1]) : null;
            const prevFyEnd = window.moment ? window.moment([fyStartYear, 2, 31]) : null;

            const ranges = {
                'Today': [window.moment(), window.moment()],
                'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                'This Month': [window.moment().startOf('month'), window.moment()],
                'Last Month': [
                    window.moment().subtract(1, 'month').startOf('month'),
                    window.moment().subtract(1, 'month').endOf('month'),
                ],
                'This Year (FY)': [fyStart || window.moment().startOf('year'), window.moment()],
                'Last Year (FY)': [prevFyStart || window.moment().subtract(1, 'year').startOf('year'), prevFyEnd || window.moment().subtract(1, 'year').endOf('year')],
                'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                'Last 1 Month': [window.moment().subtract(1, 'month').add(1, 'day'), window.moment()],
                'YTD': [fyStart || window.moment().startOf('year'), window.moment()],
            };

            window.$(this.$refs.trigger).daterangepicker({
                autoUpdateInput: false,
                ranges,
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Cancel',
                    applyLabel: 'Apply',
                },
            }, (start, end) => {
                const fromStr = start.format('YYYY-MM-DD');
                const toStr = end.format('YYYY-MM-DD');

                this.$wire.updatePickerDates(fromStr, toStr);
                this.$wire.applyRangePicker();

                this.dispatchRangeChanged();
            });
        },

        detectPresetJs(from, to) {
            if (! from || ! to) {
                return 'custom';
            }

            const now = new Date();
            const today = this.formatDateStr(now.getFullYear(), now.getMonth(), now.getDate());

            if (from === today && to === today) {
                return 'today';
            }

            const yest = new Date(now);
            yest.setDate(yest.getDate() - 1);
            const yestStr = this.formatDateStr(yest.getFullYear(), yest.getMonth(), yest.getDate());
            if (from === yestStr && to === yestStr) {
                return 'yesterday';
            }

            const d7 = new Date(now);
            d7.setDate(d7.getDate() - 6);
            const d7Str = this.formatDateStr(d7.getFullYear(), d7.getMonth(), d7.getDate());
            if (from === d7Str && to === today) {
                return '7d';
            }

            const d1m = new Date(now);
            d1m.setMonth(d1m.getMonth() - 1);
            d1m.setDate(d1m.getDate() + 1);
            const d1mStr = this.formatDateStr(d1m.getFullYear(), d1m.getMonth(), d1m.getDate());
            if (from === d1mStr && to === today) {
                return '1m';
            }

            const curY = now.getFullYear();
            const curM = now.getMonth();
            const fyStartYear = curM >= 3 ? curY : curY - 1;
            const fyStartStr = this.formatDateStr(fyStartYear, 3, 1);

            if (from === fyStartStr && to === today) {
                return 'ytd';
            }

            const thisMonthStart = this.formatDateStr(curY, curM, 1);
            if (from === thisMonthStart && to === today) {
                return 'this_month';
            }

            const lastMDate = new Date(curY, curM - 1, 1);
            const lastMStart = this.formatDateStr(lastMDate.getFullYear(), lastMDate.getMonth(), 1);
            const lastMEndDate = new Date(curY, curM, 0);
            const lastMEnd = this.formatDateStr(lastMEndDate.getFullYear(), lastMEndDate.getMonth(), lastMEndDate.getDate());
            if (from === lastMStart && to === lastMEnd) {
                return 'last_month';
            }

            const prevFyStartStr = this.formatDateStr(fyStartYear - 1, 3, 1);
            const prevFyEndStr = this.formatDateStr(fyStartYear, 2, 31);
            if (from === prevFyStartStr && to === prevFyEndStr) {
                return 'last_year';
            }

            return 'custom';
        },

        dispatchRangeChanged() {
            const from = this.$wire.rangeFrom || this.startDate;
            const to = this.$wire.rangeTo || this.endDate;
            const preset = this.$wire.rangePreset || this.detectPresetJs(from, to);
            const label = (from && to)
                ? `${this.formatDisplay(from)} – ${this.formatDisplay(to)}`
                : 'Pick a date range';

            const detail = { from, to, preset, label };

            this.$el.dispatchEvent(new CustomEvent('pulse:range-changed', {
                detail,
                bubbles: true,
                composed: true,
            }));

            window.dispatchEvent(new CustomEvent('pulse:range-changed', {
                detail,
                bubbles: true,
            }));
        },

        get activeQuickPreset() {
            if (this.$wire.rangePickerOpen) {
                const preset = this.$wire.pickerPreset;
                if (['7d', '1m', 'ytd'].includes(preset)) {
                    return preset;
                }
                const detected = this.detectPresetJs(this.startDate, this.endDate);
                if (['7d', '1m', 'ytd'].includes(detected)) {
                    return detected;
                }

                return 'custom';
            }

            const preset = this.$wire.rangePreset;
            if (['7d', '1m', 'ytd'].includes(preset)) {
                return preset;
            }
            const detected = this.detectPresetJs(this.startDate, this.endDate);

            return ['7d', '1m', 'ytd'].includes(detected) ? detected : 'custom';
        },

        get activeSidebarPreset() {
            const wirePreset = this.$wire.pickerPreset;
            if (wirePreset && wirePreset !== 'custom') {
                return wirePreset;
            }

            return this.detectPresetJs(this.startDate, this.endDate);
        },

        get leftMonthName() {
            return `${this.monthNames[this.leftMonth]} ${this.leftYear}`;
        },

        get rightMonthName() {
            return `${this.monthNames[this.rightMonth]} ${this.rightYear}`;
        },

        get leftWeeks() {
            return this.buildWeeks(this.leftYear, this.leftMonth);
        },

        get rightWeeks() {
            return this.buildWeeks(this.rightYear, this.rightMonth);
        },

        get panelHeadText() {
            if (! this.startDate) {
                return 'Pick a start date';
            }

            if (this.startDate === this.endDate && this.picking === 'end') {
                return `Start date: ${this.formatDisplay(this.startDate)}`;
            }

            const lo = this.startDate <= this.endDate ? this.startDate : this.endDate;
            const hi = this.startDate <= this.endDate ? this.endDate : this.startDate;
            const days = this.dayCount(lo, hi);

            return `${this.formatDisplay(lo)} – ${this.formatDisplay(hi)} · ${days} day${days > 1 ? 's' : ''} selected`;
        },

        get hintText() {
            if (this.picking === 'end') {
                return 'Pick an end date';
            }

            return 'Click any date after start date to adjust end date';
        },

        syncDatesFromWire() {
            const from = this.$wire.pickerFrom || '';
            const to = this.$wire.pickerTo || from;

            this.startDate = from;
            this.endDate = to;
        },

        positionCalendarsFromStart() {
            const from = this.$wire.pickerFrom || '';
            const to = this.$wire.pickerTo || from;

            if (! from) {
                const now = new Date();
                this.leftYear = now.getFullYear();
                this.leftMonth = now.getMonth();
                this.updateRightFromLeft();

                return;
            }

            const fromParts = from.split('-');
            const toParts = to.split('-');

            if (fromParts.length === 3) {
                const fy = parseInt(fromParts[0], 10);
                const fm = parseInt(fromParts[1], 10) - 1;

                if (! isNaN(fy) && ! isNaN(fm)) {
                    this.leftYear = fy;
                    this.leftMonth = fm;
                }
            }

            if (toParts.length === 3) {
                const ty = parseInt(toParts[0], 10);
                const tm = parseInt(toParts[1], 10) - 1;

                if (! isNaN(ty) && ! isNaN(tm)) {
                    if (this.leftYear === ty && this.leftMonth === tm) {
                        this.updateRightFromLeft();
                    } else {
                        this.rightYear = ty;
                        this.rightMonth = tm;
                    }
                } else {
                    this.updateRightFromLeft();
                }
            } else {
                this.updateRightFromLeft();
            }
        },

        updateIndicator() {
            const picker = this.$refs.picker;
            const indicator = this.$refs.indicator;

            if (! picker || ! indicator) {
                return;
            }

            const active = picker.querySelector(`[data-range="${this.activeQuickPreset}"]`);

            if (! active) {
                return;
            }

            const pickerRect = picker.getBoundingClientRect();
            const activeRect = active.getBoundingClientRect();

            indicator.style.width = `${activeRect.width}px`;
            indicator.style.transform = `translateX(${activeRect.left - pickerRect.left - 4}px)`;
        },

        get yearOptions() {
            const currentY = new Date().getFullYear();
            const startY = currentY - 6;
            const endY = currentY;
            const years = [];

            for (let y = startY; y <= endY; y++) {
                years.push(y);
            }

            return years;
        },

        updateRightFromLeft() {
            if (this.leftMonth === 11) {
                this.rightMonth = 0;
                this.rightYear = this.leftYear + 1;
            } else {
                this.rightMonth = this.leftMonth + 1;
                this.rightYear = this.leftYear;
            }
        },

        ensureCalendarOrdering(changedSide) {
            const leftVal = this.leftYear * 12 + this.leftMonth;
            const rightVal = this.rightYear * 12 + this.rightMonth;

            if (changedSide === 'left' && leftVal >= rightVal) {
                this.rightMonth = this.leftMonth === 11 ? 0 : this.leftMonth + 1;
                this.rightYear = this.leftMonth === 11 ? this.leftYear + 1 : this.leftYear;
            } else if (changedSide === 'right' && rightVal <= leftVal) {
                this.leftMonth = this.rightMonth === 0 ? 11 : this.rightMonth - 1;
                this.leftYear = this.rightMonth === 0 ? this.rightYear - 1 : this.rightYear;
            }
        },

        onLeftMonthChange() {
            this.ensureCalendarOrdering('left');
        },

        onLeftYearChange() {
            this.ensureCalendarOrdering('left');
        },

        onRightMonthChange() {
            this.ensureCalendarOrdering('right');
        },

        onRightYearChange() {
            this.ensureCalendarOrdering('right');
        },

        prevLeftMonth() {
            if (this.leftMonth === 0) {
                this.leftMonth = 11;
                this.leftYear--;
            } else {
                this.leftMonth--;
            }
            this.ensureCalendarOrdering('left');
        },

        nextLeftMonth() {
            if (this.leftMonth === 11) {
                this.leftMonth = 0;
                this.leftYear++;
            } else {
                this.leftMonth++;
            }
            this.ensureCalendarOrdering('left');
        },

        prevRightMonth() {
            if (this.rightMonth === 0) {
                this.rightMonth = 11;
                this.rightYear--;
            } else {
                this.rightMonth--;
            }
            this.ensureCalendarOrdering('right');
        },

        nextRightMonth() {
            if (this.rightMonth === 11) {
                this.rightMonth = 0;
                this.rightYear++;
            } else {
                this.rightMonth++;
            }
            this.ensureCalendarOrdering('right');
        },

        isFuture(dateStr) {
            return dateStr > this.todayStr;
        },

        isDisabled(dateStr, muted) {
            return muted || this.isFuture(dateStr);
        },

        buildWeeks(year, month) {
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            const cells = [];

            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const prevM = month === 0 ? 11 : month - 1;
                const prevY = month === 0 ? year - 1 : year;

                cells.push({
                    day,
                    dateStr: this.formatDateStr(prevY, prevM, day),
                    muted: true,
                });
            }

            for (let d = 1; d <= daysInMonth; d++) {
                cells.push({
                    day: d,
                    dateStr: this.formatDateStr(year, month, d),
                    muted: false,
                });
            }

            while (cells.length % 7 !== 0) {
                const d = cells.length - firstDay - daysInMonth + 1;
                const nextM = month === 11 ? 0 : month + 1;
                const nextY = month === 11 ? year + 1 : year;

                cells.push({
                    day: d,
                    dateStr: this.formatDateStr(nextY, nextM, d),
                    muted: true,
                });
            }

            const weeks = [];

            for (let i = 0; i < cells.length; i += 7) {
                weeks.push(cells.slice(i, i + 7));
            }

            return weeks;
        },

        formatDateStr(y, m, d) {
            const mm = String(m + 1).padStart(2, '0');
            const dd = String(d).padStart(2, '0');

            return `${y}-${mm}-${dd}`;
        },

        formatDisplay(dateStr) {
            const parts = dateStr.split('-');

            if (parts.length !== 3) {
                return dateStr;
            }

            const date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            });
        },

        dayCount(from, to) {
            const start = this.parseDate(from);
            const end = this.parseDate(to);

            return Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
        },

        parseDate(dateStr) {
            const parts = dateStr.split('-');

            return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        },

        previewDate(dateStr) {
            if (! this.isFuture(dateStr)) {
                this.hoverDate = dateStr;
            }
        },

        clearPreview() {
            this.hoverDate = null;
        },

        selectDate(dateStr) {
            if (this.isFuture(dateStr)) {
                return;
            }

            if (this.picking === 'start' || this.picking === 'complete' || ! this.startDate) {
                this.startDate = dateStr;
                this.endDate = dateStr;
                this.picking = 'end';
                this.maybeAdvanceRightCalendar(dateStr);
            } else {
                // picking === 'end'
                if (dateStr < this.startDate) {
                    this.endDate = this.startDate;
                    this.startDate = dateStr;
                } else {
                    this.endDate = dateStr;
                }
                this.picking = 'complete';
            }

            this.hoverDate = null;
            this.$wire.updatePickerDates(this.startDate, this.endDate);
        },

        maybeAdvanceRightCalendar(dateStr) {
            const parts = dateStr.split('-');

            if (parts.length !== 3) {
                return;
            }

            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;

            if (isNaN(year) || isNaN(month)) {
                return;
            }

            const leftEnd = this.formatDateStr(year, month, new Date(year, month + 1, 0).getDate());

            if (dateStr >= leftEnd) {
                if (month === 11) {
                    this.rightMonth = 0;
                    this.rightYear = year + 1;
                } else {
                    this.rightMonth = month + 1;
                    this.rightYear = year;
                }
            }
        },

        dayClasses(cell) {
            if (! cell) {
                return 'day';
            }

            const isMuted = typeof cell === 'object' ? Boolean(cell.muted) : false;

            if (isMuted) {
                return 'day muted';
            }

            const dateStr = typeof cell === 'object' ? cell.dateStr : String(cell);
            const classes = ['day'];

            if (this.isFuture(dateStr)) {
                classes.push('disabled');

                return classes.join(' ');
            }

            if (dateStr === this.todayStr) {
                classes.push('today');
            }

            if (! this.startDate) {
                return classes.join(' ');
            }

            const lo = this.startDate <= this.endDate ? this.startDate : this.endDate;
            const hi = this.startDate <= this.endDate ? this.endDate : this.startDate;

            let previewLo = lo;
            let previewHi = hi;

            if (this.hoverDate) {
                if (this.picking === 'end' && this.startDate === this.endDate) {
                    if (this.hoverDate >= this.startDate) {
                        previewLo = this.startDate;
                        previewHi = this.hoverDate;
                    } else {
                        previewLo = this.hoverDate;
                        previewHi = this.startDate;
                    }
                } else if (this.hoverDate >= lo) {
                    previewLo = lo;
                    previewHi = this.hoverDate;
                } else {
                    previewLo = this.hoverDate;
                    previewHi = hi;
                }
            }

            if (previewLo === previewHi) {
                if (dateStr === previewLo) {
                    classes.push('range-single');
                }

                return classes.join(' ');
            }

            if (dateStr === previewLo) {
                classes.push('range-start');
            } else if (dateStr === previewHi) {
                classes.push('range-end');
            } else if (dateStr > previewLo && dateStr < previewHi) {
                classes.push('in-range');
            }

            return classes.join(' ');
        },
    }));
});
