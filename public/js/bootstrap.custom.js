// Disable bootstrap 3.4 HTML sanitizer to avoid poor performance.
// We are responsible for sanitizing inputs/outputs (3.3 behaviour)
$.fn.tooltip.Constructor.DEFAULTS.sanitize = false;
$.fn.popover.Constructor.DEFAULTS.sanitize = false;