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

            this.syncFromWire();

            this.$watch('$wire.pickerFrom', () => {
                this.syncFromWire();
            });

            this.$watch('$wire.pickerTo', () => {
                this.syncFromWire();
            });

            this.$watch('$wire.rangePickerOpen', (open) => {
                if (open) {
                    this.picking = 'start';
                    this.hoverDate = null;
                    this.syncFromWire();
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
            }
        },

        get activeQuickPreset() {
            if (this.$wire.rangePickerOpen) {
                return 'custom';
            }

            const preset = this.$wire.rangePreset;

            return ['7d', '1m', 'ytd'].includes(preset) ? preset : 'custom';
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

            if (this.startDate === this.endDate && this.picking !== 'end') {
                return `Start date: ${this.formatDisplay(this.startDate)}`;
            }

            const lo = this.startDate <= this.endDate ? this.startDate : this.endDate;
            const hi = this.startDate <= this.endDate ? this.endDate : this.startDate;
            const days = this.dayCount(lo, hi);

            return `${this.formatDisplay(lo)} – ${this.formatDisplay(hi)} · ${days} day${days > 1 ? 's' : ''} selected`;
        },

        get hintText() {
            return this.picking === 'start'
                ? 'Pick a start date'
                : 'Pick an end date, or click again to reset';
        },

        syncFromWire() {
            const from = this.$wire.pickerFrom || '';
            const to = this.$wire.pickerTo || from;

            this.startDate = from;
            this.endDate = to;

            if (from) {
                const parts = from.split('-');

                if (parts.length === 3) {
                    const year = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10) - 1;

                    if (! isNaN(year) && ! isNaN(month)) {
                        this.leftYear = year;
                        this.leftMonth = month;
                        this.updateRightFromLeft();
                    }
                }
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
            const endY = currentY + 6;
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

        updateLeftFromRight() {
            if (this.rightMonth === 0) {
                this.leftMonth = 11;
                this.leftYear = this.rightYear - 1;
            } else {
                this.leftMonth = this.rightMonth - 1;
                this.leftYear = this.rightYear;
            }
        },

        prevLeftMonth() {
            if (this.leftMonth === 0) {
                this.leftMonth = 11;
                this.leftYear--;
            } else {
                this.leftMonth--;
            }

            this.updateRightFromLeft();
        },

        nextRightMonth() {
            if (this.rightMonth === 11) {
                this.rightMonth = 0;
                this.rightYear++;
            } else {
                this.rightMonth++;
            }

            if (this.rightMonth === 0) {
                this.leftMonth = 11;
                this.leftYear = this.rightYear - 1;
            } else {
                this.leftMonth = this.rightMonth - 1;
                this.leftYear = this.rightYear;
            }
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
            if (this.picking === 'end') {
                this.hoverDate = dateStr;
            }
        },

        clearPreview() {
            this.hoverDate = null;
        },

        selectDate(dateStr) {
            if (this.picking === 'start') {
                this.startDate = dateStr;
                this.endDate = dateStr;
                this.picking = 'end';
            } else if (dateStr < this.startDate) {
                this.endDate = this.startDate;
                this.startDate = dateStr;
                this.picking = 'start';
            } else {
                this.endDate = dateStr;
                this.picking = 'start';
            }

            this.hoverDate = null;
            this.$wire.updatePickerDates(this.startDate, this.endDate);
        },

        dayClasses(dateStr) {
            const classes = ['day'];

            if (dateStr === this.todayStr) {
                classes.push('today');
            }

            const lo = this.startDate <= this.endDate ? this.startDate : this.endDate;
            const hi = this.startDate <= this.endDate ? this.endDate : this.startDate;

            let previewLo = lo;
            let previewHi = hi;

            if (this.picking === 'end' && this.hoverDate) {
                if (this.hoverDate > this.startDate) {
                    previewHi = this.hoverDate;
                } else if (this.hoverDate < this.startDate) {
                    previewLo = this.hoverDate;
                }
            }

            if (this.startDate === this.endDate && this.picking !== 'end') {
                if (dateStr === this.startDate) {
                    classes.push('range-single');
                }

                return classes.join(' ');
            }

            if (dateStr === previewLo) {
                classes.push(previewLo === previewHi ? 'range-single' : 'range-start');
            } else if (dateStr === previewHi) {
                classes.push('range-end');
            } else if (dateStr > previewLo && dateStr < previewHi) {
                classes.push('in-range');
            }

            return classes.join(' ');
        },
    }));
});
