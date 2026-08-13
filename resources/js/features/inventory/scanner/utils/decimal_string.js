/**
 * Exact Decimal-4 String Arithmetic Utility for Inventory Quantities.
 * Operates on DECIMAL(14,4) values without JS Number/parseFloat conversion.
 */

/**
 * Normalizes any quantity input into an exact 4-decimal place string (e.g., "1" -> "1.0000", "2.5" -> "2.5000").
 * @param {string|number} input
 * @returns {string}
 */
export function normalizeDecimal4String(input) {
    if (input === null || input === undefined || input === '') {
        return '0.0000';
    }

    const str = String(input).trim();
    if (!str || str === 'NaN') {
        return '0.0000';
    }

    let isNegative = false;
    let cleanStr = str;
    if (cleanStr.startsWith('-')) {
        isNegative = true;
        cleanStr = cleanStr.slice(1);
    }

    const parts = cleanStr.split('.');
    const integerPart = parts[0].replace(/^0+/, '') || '0';
    let fractionalPart = (parts[1] || '').slice(0, 4);

    while (fractionalPart.length < 4) {
        fractionalPart += '0';
    }

    const result = `${integerPart}.${fractionalPart}`;
    return isNegative && result !== '0.0000' ? `-${result}` : result;
}

/**
 * Converts a 4-decimal string to BigInt integer representation (e.g., "2.5000" -> 25000n).
 * @param {string} str
 * @returns {bigint}
 */
function decimalToBigInt(str) {
    const norm = normalizeDecimal4String(str);
    const isNegative = norm.startsWith('-');
    const clean = isNegative ? norm.slice(1) : norm;
    const parts = clean.split('.');
    const integerStr = parts[0];
    const fractionalStr = parts[1];

    const combinedStr = integerStr + fractionalStr;
    const val = BigInt(combinedStr);
    return isNegative ? -val : val;
}

/**
 * Converts a BigInt scaled value back to a 4-decimal string.
 * @param {bigint} val
 * @returns {string}
 */
function bigIntToDecimal(val) {
    const isNegative = val < 0n;
    let absValStr = (isNegative ? -val : val).toString();

    while (absValStr.length <= 4) {
        absValStr = '0' + absValStr;
    }

    const intPart = absValStr.slice(0, absValStr.length - 4);
    const fracPart = absValStr.slice(absValStr.length - 4);
    const result = `${intPart}.${fracPart}`;
    return isNegative ? `-${result}` : result;
}

/**
 * Exact 4-decimal string addition: a + b
 * @param {string} a
 * @param {string} b
 * @returns {string}
 */
export function addDecimal4Strings(a, b) {
    const bgA = decimalToBigInt(a);
    const bgB = decimalToBigInt(b);
    return bigIntToDecimal(bgA + bgB);
}

/**
 * Compares two 4-decimal strings.
 * @param {string} a
 * @param {string} b
 * @returns {number} -1 if a < b, 0 if a === b, 1 if a > b
 */
export function compareDecimal4Strings(a, b) {
    const bgA = decimalToBigInt(a);
    const bgB = decimalToBigInt(b);
    if (bgA < bgB) return -1;
    if (bgA > bgB) return 1;
    return 0;
}
