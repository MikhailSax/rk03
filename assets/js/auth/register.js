import Inputmask from 'inputmask';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[name="registration_form"]');
    if (!form) {
        return;
    }

    const nameInput = document.querySelector('#registration_form_first_name');
    const emailInput = document.querySelector('#registration_form_email');
    const phoneInput = document.querySelector('#registration_form_phone');
    const passwordInput = document.querySelector('#registration_form_plainPassword');
    const agreeCheckbox = document.querySelector('#registration_form_agreeTerms');

    if (phoneInput) {
        Inputmask('+7 (999) 999-99-99').mask(phoneInput);
    }

    if (!nameInput || !emailInput || !phoneInput || !passwordInput || !agreeCheckbox) {
        return;
    }

    const showError = (input, message) => {
        const targetContainer = input.closest('div') ?? input.parentElement;
        if (!targetContainer) {
            return;
        }

        const errorElem = document.createElement('div');
        errorElem.className = 'js-client-error mt-1 text-sm text-red-600';
        errorElem.textContent = message;

        input.classList.add('border-red-500');
        targetContainer.appendChild(errorElem);
    };

    const clearErrors = () => {
        form.querySelectorAll('.js-client-error').forEach((element) => element.remove());
        [nameInput, emailInput, phoneInput, passwordInput, agreeCheckbox].forEach((input) => {
            input.classList.remove('border-red-500');
        });
    };

    form.addEventListener('submit', (event) => {
        clearErrors();
        let hasError = false;

        if (nameInput.value.trim().length < 2) {
            showError(nameInput, 'Имя должно быть не короче 2 символов');
            hasError = true;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim())) {
            showError(emailInput, 'Введите корректный email');
            hasError = true;
        }

        const phoneDigits = phoneInput.value.replace(/\D/g, '');
        if (phoneDigits.length !== 11 || !phoneDigits.startsWith('7')) {
            showError(phoneInput, 'Введите корректный номер телефона');
            hasError = true;
        }

        const password = passwordInput.value;
        if (password.length < 6 || !/\d/.test(password) || !/[a-zA-Zа-яА-Я]/.test(password)) {
            showError(passwordInput, 'Пароль должен быть от 6 символов и содержать буквы и цифры');
            hasError = true;
        }

        if (!agreeCheckbox.checked) {
            showError(agreeCheckbox, 'Необходимо принять условия');
            hasError = true;
        }

        if (hasError) {
            event.preventDefault();
        }
    });
});
