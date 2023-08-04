/**
 * @name YeAPF2
 * @version 2.0
 * @description Yet Another PHP Framework v2
 * @license MIT License
 *
 * (c) 2004-2023 Esteban D.Dortta <dortta@yahoo.com>
 * Website: https://www.yeapf.com
 */

class yColors {
    static decomposeColor(color) {
        if (color.substring(0, 4) == 'rgb(') {
            var aux = color.replace(/[^\d,]/g, '').split(','),
                ret = [],
                n;
            for (n = 0; n < aux.length; n++)
                ret[n] = yMisc.str2int(aux[n]);
            return ret;
        } else {
            if (color.substring(0, 1) == '#')
                color = color.substring(1);


            var r = yMisc.hex2dec(color.substring(0, 2));
            var g = yMisc.hex2dec(color.substring(2, 2));
            var b = yMisc.hex2dec(color.substring(4, 2));

            return [r, g, b];
        }
    }

    static complementaryColor(color) {
        var xDiv = 32;
        var xLimite = 250;
        var xDivContraste = 3;

        var dc = yColors.decomposeColor(color);
        for (var n = 0; n < 3; n++) {
            dc[n] = Math.floor(dc[n] / xDivContraste);
            dc[n] = Math.floor(dc[n] / xDiv) * xDiv;
            if (xLimite > 0)
                dc[n] = xLimite - Math.min(xLimite, dc[n]);
        }

        var res = yMisc.dec2hex(dc[0]) + yMisc.dec2hex(dc[1]) + yMisc.dec2hex(dc[2]);

        return '#' + res;
    }

    static grayColor(color) {
        var xDiv = 32;

        var dc = yColors.decomposeColor(color);

        var r = Math.floor(dc[0] / xDiv) * xDiv;
        var g = Math.floor(dc[1] / xDiv) * xDiv;
        var b = Math.floor(dc[2] / xDiv) * xDiv;

        var gray = (r + g + b) / 3;

        gray = yMisc.dec2hex(gray);

        var res = gray + gray + gray;

        return res;
    }

    static rgb2hex(rgb) {
        var res;
        if (typeof rgb.b === 'undefined') {
            res = yMisc.pad(yMisc.dec2hex(rgb[0]), 2) + yMisc.pad(yMisc.dec2hex(rgb[1]), 2) + yMisc.pad(
                yMisc.dec2hex(rgb[2]), 2);
        } else {
            res = yMisc.pad(yMisc.dec2hex(rgb.r), 2) + yMisc.pad(yMisc.dec2hex(rgb.g), 2) + yMisc.pad(
                yMisc.dec2hex(rgb.b), 2);
        }
        return res;
    }

    static pickColorFromGradient(firstColor, lastColor, weight) {
        var w1 = Math.max(0, Math.min(weight, 100)) / 100,
            w2 = 1 - w1,
            color1 = yColors.decomposeColor(firstColor),
            color2 = yColors.decomposeColor(lastColor);

        var rgb = [Math.round(color1[0] * w1 + color2[0] * w2),
        Math.round(color1[1] * w1 + color2[1] * w2),
        Math.round(color1[2] * w1 + color2[2] * w2)
        ];
        return rgb2hex(rgb);
    }

    static brighterColor(color, percent) {
        percent = percent || 50;
        color = yColors.decomposeColor(color);

        var r = parseInt(color[0]),
            g = parseInt(color[1]),
            b = parseInt(color[2]);

        return '#' +
            ((0 | (1 << 8) + r + (256 - r) * percent / 100).toString(16)).substring(
                1) +
            ((0 | (1 << 8) + g + (256 - g) * percent / 100).toString(16)).substring(
                1) +
            ((0 | (1 << 8) + b + (256 - b) * percent / 100).toString(16)).substring(
                1);
    }

    static hsmColorBase = function () {
        function min3(a, b, c) {
            return (a < b) ? ((a < c) ? a : c) : ((b < c) ? b : c);
        }

        function max3(a, b, c) {
            return (a > b) ? ((a > c) ? a : c) : ((b > c) ? b : c);
        }

        var that = {};

        that.HueShift = function (h, s) {
            h += s;
            while (h >= 360.0) h -= 360.0;
            while (h < 0.0) h += 360.0;
            return h;
        };

        /* original source: http://color.twysted.net/  and  http://colormatch.dk/ */

        that.RGB2HSV = function (rgb) {
            var hsv = {};
            var max;
            if (typeof rgb.r == 'undefined') {
                var aux = { r: rgb[0], g: rgb[1], b: rgb[2] };
                rgb = aux;
            }
            max = max3(rgb.r, rgb.g, rgb.b);
            var dif = max - min3(rgb.r, rgb.g, rgb.b);
            hsv.saturation = (max === 0.0) ? 0 : (100 * dif / max);
            if (hsv.saturation === 0) hsv.hue = 0;
            else if (rgb.r == max) hsv.hue = 60.0 * (rgb.g - rgb.b) /
                dif;
            else if (rgb.g == max) hsv.hue = 120.0 + 60.0 * (rgb.b -
                rgb.r) / dif;
            else if (rgb.b == max) hsv.hue = 240.0 + 60.0 * (rgb.r -
                rgb.g) / dif;
            if (hsv.hue < 0.0) hsv.hue += 360.0;
            hsv.value = Math.round(max * 100 / 255);
            hsv.hue = Math.round(hsv.hue);
            hsv.saturation = Math.round(hsv.saturation);
            return hsv;
        };

        that.HSV2RGB = function (hsv, positionalRGB) {
            positionalRGB = positionalRGB || true;

            var aux = {},
                rgb = {};
            if (hsv.saturation == 0) {
                aux.r = aux.g = aux.b = Math.round(hsv.value * 2.55);
            } else {
                hsv.hue /= 60;
                hsv.saturation /= 100;
                hsv.value /= 100;
                var i = Math.floor(hsv.hue);
                var f = hsv.hue - i;
                var p = hsv.value * (1 - hsv.saturation);
                var q = hsv.value * (1 - hsv.saturation * f);
                var t = hsv.value * (1 - hsv.saturation * (1 - f));
                switch (i) {
                    case 0:
                        aux.r = hsv.value;
                        aux.g = t;
                        aux.b = p;
                        break;
                    case 1:
                        aux.r = q;
                        aux.g = hsv.value;
                        aux.b = p;
                        break;
                    case 2:
                        aux.r = p;
                        aux.g = hsv.value;
                        aux.b = t;
                        break;
                    case 3:
                        aux.r = p;
                        aux.g = q;
                        aux.b = hsv.value;
                        break;
                    case 4:
                        aux.r = t;
                        aux.g = p;
                        aux.b = hsv.value;
                        break;
                    default:
                        aux.r = hsv.value;
                        aux.g = p;
                        aux.b = q;
                }
                aux.r = Math.round(aux.r * 255);
                aux.g = Math.round(aux.g * 255);
                aux.b = Math.round(aux.b * 255);
            }

            if (positionalRGB) {
                rgb[0] = aux.r;
                rgb[1] = aux.g;
                rgb[2] = aux.b;
            } else {
                rgb = aux;
            }
            return rgb;
        };

        return that;
    };

}