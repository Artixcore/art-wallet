/**
 * Inline validation: map Laravel-style errors to [data-error-for] elements.
 * @param {Record<string, string[]|string>} errors
 */
export function applyFieldErrors(errors) {
    clearFieldErrors();
    Object.entries(errors).forEach(([field, messages]) => {
        const list = Array.isArray(messages) ? messages : [messages];
        const text = list.filter(Boolean).join(' ');
        const el = document.querySelector(`[data-error-for="${cssEscape(field)}"]`);
        if (el) {
            el.textContent = text;
            el.classList.remove('hidden');
        }
        const input = document.querySelector(`[name="${cssEscape(field)}"]`);
        if (input) {
            input.setAttribute('aria-invalid', 'true');
        }
    });
}

export function clearFieldErrors() {
    document.querySelectorAll('[data-error-for]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });
    document.querySelectorAll('[aria-invalid="true"]').forEach((node) => {
        node.removeAttribute('aria-invalid');
    });
}

/**
 * @param {string} value
 */
function cssEscape(value) {
    if (typeof CSS !== 'undefined' && CSS.escape) {
        return CSS.escape(value);
    }

    return value.replace(/["\\]/g, '\\$&');
}
