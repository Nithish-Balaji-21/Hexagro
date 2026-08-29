document.addEventListener('alpine:init', () => {
    Alpine.data('hexRangePicker', () => ({
        startDate: '',
        endDate: '',
        leftYear: new Date().getFullYear(),
        leftMonth: new Date().getMonth(),
        rightYear: new Date().getFullYear(),
        rightMonth: new Date().getMonth() === 11 ? 0 : new Date().getMonth() + 1,

        months: [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ],

        years: Array.from({ length: 15 }, (_, i) => new Date().getFullYear() - 7 + i),

        init() {
            this.syncFromWire();

            this.$watch('$wire.pickerFrom', () => this.syncFromWire());
            this.$watch('$wire.pickerTo', () => this.syncFromWire());
            this.$watch('$wire.rangePickerOpen', (open) => {
                if (open) {
                    this.syncFromWire();
                }
            });
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

                    if (!isNaN(year) && !isNaN(month)) {
                        this.leftYear = year;
                        this.leftMonth = month;
                        this.updateRightFromLeft();
                    }
                }
            }
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

        prevLeftMonth() {
            if (this.leftMonth === 0) {
                this.leftMonth = 11;
                this.leftYear--;
            } else {
                this.leftMonth--;
            }
            this.updateRightFromLeft();
        },

        nextLeftMonth() {
            if (this.leftMonth === 11) {
                this.leftMonth = 0;
                this.leftYear++;
            } else {
                this.leftMonth++;
            }
            this.updateRightFromLeft();
        },

        prevRightMonth() {
            if (this.rightMonth === 0) {
                this.rightMonth = 11;
                this.rightYear--;
            } else {
                this.rightMonth--;
            }
            if (this.rightMonth === 0) {
                this.leftMonth = 11;
                this.leftYear = this.rightYear - 1;
            } else {
                this.leftMonth = this.rightMonth - 1;
                this.leftYear = this.rightYear;
            }
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

        onLeftMonthChange() {
            this.leftMonth = parseInt(this.leftMonth, 10);
            this.updateRightFromLeft();
        },

        onLeftYearChange() {
            this.leftYear = parseInt(this.leftYear, 10);
            this.updateRightFromLeft();
        },

        onRightMonthChange() {
            this.rightMonth = parseInt(this.rightMonth, 10);
            if (this.rightMonth === 0) {
                this.leftMonth = 11;
                this.leftYear = this.rightYear - 1;
            } else {
                this.leftMonth = this.rightMonth - 1;
                this.leftYear = this.rightYear;
            }
        },

        onRightYearChange() {
            this.rightYear = parseInt(this.rightYear, 10);
            if (this.rightMonth === 0) {
                this.leftMonth = 11;
                this.leftYear = this.rightYear - 1;
            } else {
                this.leftMonth = this.rightMonth - 1;
                this.leftYear = this.rightYear;
            }
        },

        getMonthDays(year, month) {
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
                    isCurrentMonth: false,
                });
            }

            for (let d = 1; d <= daysInMonth; d++) {
                cells.push({
                    day: d,
                    dateStr: this.formatDateStr(year, month, d),
                    isCurrentMonth: true,
                });
            }

            const totalRemaining = (7 - (cells.length % 7)) % 7;
            for (let d = 1; d <= totalRemaining; d++) {
                const nextM = month === 11 ? 0 : month + 1;
                const nextY = month === 11 ? year + 1 : year;
                cells.push({
                    day: d,
                    dateStr: this.formatDateStr(nextY, nextM, d),
                    isCurrentMonth: false,
                });
            }

            return cells;
        },

        formatDateStr(y, m, d) {
            const mm = String(m + 1).padStart(2, '0');
            const dd = String(d).padStart(2, '0');
            return `${y}-${mm}-${dd}`;
        },

        selectDate(dateStr) {
            if (!this.startDate || (this.startDate && this.endDate && this.startDate !== this.endDate)) {
                this.startDate = dateStr;
                this.endDate = dateStr;
            } else if (this.startDate && (!this.endDate || this.startDate === this.endDate)) {
                if (dateStr < this.startDate) {
                    this.endDate = this.startDate;
                    this.startDate = dateStr;
                } else {
                    this.endDate = dateStr;
                }
            }

            this.$wire.updatePickerDates(this.startDate, this.endDate);
        },

        isStart(dateStr) {
            return this.startDate === dateStr;
        },

        isEnd(dateStr) {
            return this.endDate === dateStr;
        },

        isInRange(dateStr) {
            if (!this.startDate || !this.endDate) return false;
            return dateStr >= this.startDate && dateStr <= this.endDate;
        },

        formattedRangeLabel() {
            if (!this.startDate) return 'Pick a date range';
            const s = this.startDate.split('-').reverse().join('/');
            const e = (this.endDate || this.startDate).split('-').reverse().join('/');
            return `${s} - ${e}`;
        }
    }));
});
