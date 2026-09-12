/**
 * Strict Decimal-4 String Arithmetic Utility for Inventory Quantities.
 * Operates on DECIMAL(14,4) values using BigInt scaled integer arithmetic.
 * No JS floating point (Number / parseFloat / toFixed / Math.*) is used.
 */

// Strict pattern: optional leading minus, integer part (digits only), optional decimal point with 1-4 fractional digits only
const STRICT_DECIMAL_4_REGEX = /^-?(0|[1-9]\d*)(\.\d{1,4})?$/;

/**
 * Checks if an input is a valid decimal string conforming to max 4 decimal places.
 * Rejects scientific notation, >4 decimal places, multiple decimal points, and non-numeric strings.
 *
 * @param {string|number} input
 * @returns {boolean}
 */
export function isValidDecimal4String(input) {
    if (input === null || input === undefined || input === '') {
        return false;
    }
    const str = String(input).trim();
    return STRICT_DECIMAL_4_REGEX.test(str);
}

/**
 * Strictly normalizes a quantity input into an exact 4-decimal place string.
 * Valid: "1" -> "1.0000", "2.5" -> "2.5000", "1.2345" -> "1.2345".
 * Invalid: "1.23456", "1.2.3", "1e3", "abc", "--1" -> throws Error (never silently truncates).
 *
 * @param {string|number} input
 * @returns {string} Exact 4-decimal place string (e.g. "1.0000")
 * @throws {Error} If input does not strictly conform to max 4 decimal digits
 */
export function normalizeDecimal4String(input) {
    if (input === null || input === undefined || input === '') {
        return '0.0000';
    }

    const str = String(input).trim();
    if (str === '0' || str === '0.0' || str === '0.00' || str === '0.000' || str === '0.0000') {
        return '0.0000';
    }

    if (!STRICT_DECIMAL_4_REGEX.test(str)) {
        throw new Error(
            `Invalid decimal format for inventory quantity: "${input}". Value must be a valid number with at most 4 decimal places.`
        );
    }

    let isNegative = false;
    let cleanStr = str;
    if (cleanStr.startsWith('-')) {
        isNegative = true;
        cleanStr = cleanStr.slice(1);
    }

    const parts = cleanStr.split('.');
    const integerPart = parts[0].replace(/^0+(?=\d)/, '') || '0';
    let fractionalPart = parts[1] || '';

    while (fractionalPart.length < 4) {
        fractionalPart += '0';
    }

    const result = `${integerPart}.${fractionalPart}`;
    if (result === '0.0000') {
        return '0.0000';
    }

    return isNegative ? `-${result}` : result;
}

/**
 * Attempts to normalize input without throwing. Returns fallback if invalid.
 *
 * @param {string|number} input
 * @param {string|null} fallback
 * @returns {string|null}
 */
export function tryNormalizeDecimal4String(input, fallback = null) {
    try {
        return normalizeDecimal4String(input);
    } catch {
        return fallback;
    }
}

/**
 * Converts a strictly validated 4-decimal string to BigInt representation (e.g. "2.5000" -> 25000n).
 *
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
 * Converts a BigInt scaled integer back to an exact 4-decimal string.
 *
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
    return isNegative && result !== '0.0000' ? `-${result}` : result;
}

/**
 * Exact 4-decimal string addition: a + b (using scaled BigInt).
 * Both operands are strictly validated.
 *
 * @param {string} a
 * @param {string} b
 * @returns {string} Exact 4-decimal string
 */
export function addDecimal4Strings(a, b) {
    const bgA = decimalToBigInt(a);
    const bgB = decimalToBigInt(b);
    return bigIntToDecimal(bgA + bgB);
}

/**
 * Compares two 4-decimal strings (using scaled BigInt).
 * Both operands are strictly validated.
 *
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
