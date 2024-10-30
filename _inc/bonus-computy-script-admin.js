document.addEventListener('DOMContentLoaded', function() {

    if (document.getElementById('exclude-role')) {
        new SlimSelect({
            select: '#exclude-role',
        });
    }

    if (document.getElementById('exclude-category')) {
        new SlimSelect({
            select: '#exclude-category',
        });
    }

    if (document.getElementById('exclude-payment-method')) {
        new SlimSelect({
            select: '#exclude-payment-method',
        });
    }

});

//Таймер обратного отсчета
function countdown(endDate) {
    let days, hours, minutes, seconds;

    endDate = new Date(endDate).getTime();

    if (isNaN(endDate)) {
        return;
    }

    setInterval(calculate, 1000);

    function calculate() {
        let startDate = new Date().getTime();
        let timeRemaining = parseInt((endDate - startDate) / 1000);

        if (timeRemaining >= 0) {
            days = parseInt(timeRemaining / 86400);
            timeRemaining = (timeRemaining % 86400);
            hours = parseInt(timeRemaining / 3600);
            timeRemaining = (timeRemaining % 3600);
            minutes = parseInt(timeRemaining / 60);
            seconds = parseInt(timeRemaining % 60);

            document.getElementById("days").innerHTML = parseInt(days, 10);
            document.getElementById("hours").innerHTML = ("0" + hours).slice(-2);
            document.getElementById("minutes").innerHTML = ("0" + minutes).slice(-2);
            document.getElementById("seconds").innerHTML = ("0" + seconds).slice(-2);
        } else {
            return;
        }
    }
}

(function () {
    const divElements = document.querySelectorAll(`div.countdown`);
    if (divElements.length > 0) {
        countdown('2024-10-25T23:59:59');
    }
})();