import * as bootstrap from 'bootstrap';
import '@coreui/coreui/dist/js/coreui.bundle.min.js';

// Make bootstrap available globally
window.bootstrap = bootstrap;

// Initialize tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip()
});
