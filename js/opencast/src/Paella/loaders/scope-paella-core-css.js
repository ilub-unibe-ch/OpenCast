'use strict';

const globalRule = 'button,a{font-size:14px}';
const scopedRule = '#playerContainer button,#playerContainer a{font-size:14px}';

module.exports = function scopePaellaCoreCss(source) {
    const occurrences = source.split(globalRule).length - 1;

    if (occurrences !== 1) {
        throw new Error(
            `Expected exactly one occurrence of "${globalRule}" in paella-core.css, found ${occurrences}.`
        );
    }

    return source.replace(globalRule, scopedRule);
};
