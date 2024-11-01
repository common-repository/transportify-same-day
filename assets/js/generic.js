
function formatMoney(amount, decimalCount = 2, decimal = ".", thousands = ",") {
    try {
        decimalCount = Math.abs(decimalCount);
        decimalCount = isNaN(decimalCount) ? 2 : decimalCount;

        const negativeSign = amount < 0 ? "-" : "";

        let i = parseInt(amount = Math.abs(Number(amount) || 0).toFixed(decimalCount)).toString();
        let j = (i.length > 3) ? i.length % 3 : 0;

        return negativeSign + (j ? i.substr(0, j) + thousands : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands) + (decimalCount ? decimal + Math.abs(amount - i).toFixed(decimalCount).slice(2) : "");
    } catch (e) {
        console.log(e)
    }
};

function formatCurrencyWithSymbol(number) {
    let numericStr  = $currency.symbol;
    numericFormated	= formatMoney(number, $currency.decimal_length, $currency.decimal_symbol, $currency.grouping_symbol);
    if ( $currency.symbol_location == 1 ) {
        numericStr = numericStr + numericFormated;
    } else {
        numericStr = numericFormated + numericStr;
    }
    if (numericStr.indexOf('-') >= 0) {
        numericStr = '-' + numericStr.replace( '-', '' );
    }
    return numericStr;
}

function dateTimeReverse(fromDateFormat, dateString) {
    if (fromDateFormat == 'd/m/Y H:i') {
        const splits = dateString.split(' ');
        const date = splits[0].split("/").reverse().join("/");
        return date + ' ' + splits[1];
    }
}

Date.prototype.toMongoISOString = function() {
    var tzo = -this.getTimezoneOffset(),
        dif = tzo >= 0 ? '+' : '-',
        pad = function(num) {
            var norm = Math.floor(Math.abs(num));
            return (norm < 10 ? '0' : '') + norm;
        };
    return this.getFullYear() +
        '-' + pad(this.getMonth() + 1) +
        '-' + pad(this.getDate()) +
        'T' + pad(this.getHours()) +
        ':' + pad(this.getMinutes()) +
        ':' + pad(this.getSeconds()) +
        dif + pad(tzo / 60) +
        ':' + pad(tzo % 60);
};