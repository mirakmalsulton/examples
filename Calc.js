let btcUsd = $('#btc-usd');
let btc = $('#btc');
let usd = $('#usd');
let usdPercent = $('#usd-percent');
let uzs = $('#uzs');
let uzsCurrency = uzs.data('currency');
let timer;
let timerElement = document.getElementById('timer');
let seconds = 0;

let commissionPercent = $('#config').data('commission-percent');
let commissionFixed = $('#config').data('commission-fixed');

let delayer;

//===========
btc.bind('keydown keyup change blur', function () {
    clearTimeout(delayer);
})

btc.bind('keyup', function () {
    delayer = setTimeout(function () {
        calcFromBtc();
    }, 1000);
})

btc.bind('blur', function () {
    calcFromBtc();
})

//===========
usd.bind('keydown keyup change blur', function () {
    clearTimeout(delayer);
})

usd.bind('keyup', function () {
    delayer = setTimeout(function () {
        calcFromUsd()
    }, 1000);
})

usd.bind('blur', function () {
    calcFromUsd()
})

//===========
uzs.bind('keydown keyup change blur', function () {
    clearTimeout(delayer);
})

uzs.bind('keyup', function () {
    delayer = setTimeout(function () {
        calcFromUzs()
    }, 1000);
})

uzs.bind('blur', function () {
    //calcFromUzs() //NaN error appears
})

//=====================

timer = function () {
    setInterval(function () {
        seconds++;
        timerElement.innerHTML = seconds + ' seconds ago';
    }, 1000);
};
timer();


function calcFromBtc() {
    let btcUsdVal = btcUsd.val().replace(',', '.') * 1;
    let btcVal = btc.val().replace(',', '.') * 1;
    let usdVal = btcVal * btcUsdVal;
    let usdPercentVal = usdVal + (usdVal / 100 * commissionPercent) + commissionFixed;
    let uzsVal = (Math.round(usdPercentVal * uzsCurrency / 1000)) * 1000;

    btc.val(parseFloat(btcVal).toFixed(8));
    usd.val(parseFloat(usdVal).toFixed(2));
    usdPercent.val(parseFloat(usdPercentVal).toFixed(2) + '$');
    uzs.val(parseFloat(uzsVal + '').toLocaleString('fr') + ' сум');
}

function calcFromUsd() {
    let btcUsdVal = btcUsd.val().replace(',', '.') * 1;
    let usdVal = usd.val().replace(',', '.') * 1;
    let btcVal = usdVal / btcUsdVal;
    let usdPercentVal = usdVal + (usdVal / 100 * commissionPercent) + commissionFixed;
    let uzsVal = (Math.round(usdPercentVal * uzsCurrency / 1000)) * 1000;

    btc.val(parseFloat(btcVal).toFixed(8));
    usd.val(parseFloat(usdVal).toFixed(2));
    usdPercent.val(parseFloat(usdPercentVal).toFixed(2) + '$');
    uzs.val(parseFloat(uzsVal + '').toLocaleString('fr') + ' сум');
}

function calcFromUzs() {
    let btcUsdVal = btcUsd.val().replace(',', '.') * 1;
    let uzsVal = uzs.val().replace(',', '.').replace(' сум', '') * 1;

    let commission = uzsCurrency * commissionFixed;
    let withoutFixedCommission = uzsVal - commission;
    let noCommission = withoutFixedCommission * 100 / (100 + commissionPercent);

    let usdVal = noCommission / uzsCurrency;
    let btcVal = usdVal / btcUsdVal;


    let usdPercentVal = usdVal + (usdVal / 100 * commissionPercent) + commissionFixed;

    btc.val(parseFloat(btcVal).toFixed(8));
    usd.val(parseFloat(usdVal).toFixed(2));
    usdPercent.val(parseFloat(usdPercentVal).toFixed(2) + '$');
    uzs.val(parseFloat(uzsVal + '').toLocaleString('fr') + ' сум');

}
