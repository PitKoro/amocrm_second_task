document.querySelector('.js-validation-age-fields').addEventListener('input',
    function (e) {
        this.value = this.value.replace(/[^0-9\+$]/, '');
    }
)

document.querySelector('.js-validation-phone-fields').addEventListener('input',
    function (e) {
        this.value = this.value.replace(/[^\d.]/, '');
    }
)

document.querySelector('.js-validation-name-fields').addEventListener('input',
    function (e) {
        this.value = this.value.replace(/[^а-яА-Яa-zA-Z\+$]/, '');
    }
)

document.querySelector('.js-validation-surname-fields').addEventListener('input',
    function (e) {
        this.value = this.value.replace(/[^а-яА-Яa-zA-Z\+$]/, '');
    }
)


const INPUT = document.querySelector('.js-validation-email-fields');

function validateEmail(value) {
    return /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/.test(value);
}


INPUT.addEventListener('input', function () {
    if (!validateEmail(INPUT.value)) {
        $('.js-button-submit').attr('disabled', true);
        INPUT.style.borderColor = 'red';
    } else {
        $('.js-button-submit').attr('disabled', false);
        INPUT.style.borderColor = 'green';
    }
});
