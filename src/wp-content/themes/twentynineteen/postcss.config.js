var postcssFocusWithin = require('postcss-focus-within');
var autoprefixer = require('autoprefixer');

var oneSelectorPerLine = function () {
    return {
        postcssPlugin: 'one-selector-per-line',
        Rule: function (rule) {
            if (rule.selector.indexOf(',') !== -1) {
                var before = rule.raws.before || '';
                var indent = before.substring(before.lastIndexOf('\n') + 1);
                rule.selector = rule.selector.replace(/, :lang/g, ',:lang').split(/,\s+/).join(',\n' + indent);
            }
            if (rule.selector.indexOf(':not(:focus)::first-letter:') !== -1) {
                rule.selector = rule.selector.replace(':not(:focus)::first-letter', '') + ':not(:focus)::first-letter';
            }
        }
    };
};
oneSelectorPerLine.postcss = true;

module.exports = {
    plugins: [
        postcssFocusWithin({
            disablePolyfillReadyClass: true
        }),
		oneSelectorPerLine,
        autoprefixer()
    ]
};
