import './bootstrap';
import $ from 'jquery';
import moment from 'moment';
import 'daterangepicker';
import './range-picker';
import Chart from 'chart.js/auto';

window.$ = $;
window.jQuery = $;
window.moment = moment;

window.Chart = Chart;

document.addEventListener('livewire:init', () => {
    Livewire.on('toast', (event) => {
        const message = Array.isArray(event) ? event[0]?.message ?? event[0] : event?.message ?? event;

        if (! message) {
            return;
        }

        window.dispatchEvent(new CustomEvent('hexagro-toast', { detail: { message } }));
    });
});
