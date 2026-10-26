export function bindDuelCodeInputs(form) {
    const inputs = Array.from(form.querySelectorAll('[data-duel-digit]'));
    const focus = index => {
        inputs[index].focus();
        inputs[index].select();
    };
    const insert = (index, text) => {
        const digits = text.replace(/\D/g, '').slice(0, inputs.length);
        if (!digits) return;
        // A complete pasted or autofilled code replaces the whole code.
        const start = digits.length === inputs.length ? 0 : index;
        const count = Math.min(digits.length, inputs.length - start);
        for (let offset = 0; offset < count; offset++) {
            inputs[start + offset].value = digits[offset];
        }
        focus(Math.min(start + count, inputs.length - 1));
    };

    inputs.forEach((input, index) => {
        input.addEventListener('focus', () => input.select());
        input.addEventListener('input', () => {
            const text = input.value;
            input.value = '';
            insert(index, text);
        });
        input.addEventListener('paste', event => {
            event.preventDefault();
            insert(index, event.clipboardData.getData('text'));
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Backspace' && !input.value && index > 0) {
                event.preventDefault();
                inputs[index - 1].value = '';
                focus(index - 1);
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                focus(index - 1);
            } else if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                event.preventDefault();
                focus(index + 1);
            }
        });
    });

    return {
        value: () => inputs.map(input => input.value).join(''),
        focus: () => focus(Math.max(0, inputs.findIndex(input => !input.value))),
    };
}
