/**
 * @name YeAPF2
 * @version 2.0
 * @description Yet Another PHP Framework v2
 * @license MIT License
 *
 * (c) 2004-2023 Esteban D.Dortta <dortta@yahoo.com>
 * Website: https://www.yeapf.com
 */


class yMisc {
    static isEmail(email) {
        var aux = (email && email.unquote()) || '';
        var re =
          /^(([^\*<>()[\]\\.,;:\s@\"]+(\.[^\*<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(aux);
      }

    static pad(number, length) {
        var str = '' + number;
        while (str.length < length)
            str = '0' + str;
        return str;
    }

    static unmaskHTML(auxLine) {
        if (typeof auxLine === 'string') {
            if (auxLine.length > 0) {
                var c = auxLine.charAt(0);
                if ((c === '"') || (c === "'")) {
                    var z = auxLine.charAt(auxLine.length - 1);
                    if (c === z)
                        auxLine = auxLine.substring(1, auxLine.length - 1);
                }
            }
            auxLine = auxLine.replace(/!!/g, '&');
            auxLine = auxLine.replace(/\[/g, '<');
            auxLine = auxLine.replace(/\]/g, '>');
            auxLine = auxLine.replace(/&#91;/g, '[');
            auxLine = auxLine.replace(/&#93;/g, ']');
        } else if (typeof auxLine === 'number') {
            auxLine = auxLine.toString();
        } else if (typeof auxLine === 'object') {
            for (var aux in auxLine) {
                if (auxLine.hasOwnProperty(aux)) {
                    auxLine[aux] = yMisc.unmaskHTML(auxLine[aux]);
                }
            }
        } else {
            auxLine = '';
        }
        return auxLine;
    }


    static escapeRegExp(string) {
        return string.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
    }

    static maskHTML(auxLine) {
        auxLine = auxLine || '';
        if (typeof auxLine === 'string') {
            auxLine = auxLine.replace(/</g, '[')
                .replace(/>/g, ']')
                .replace(/&/g, '!!');
        }
        return auxLine;
    }

    static unparentesis(v) {
        if (v.length > 1) {
            if ((v.substring(0, 1) == '(') || (v.substring(0, 1) == '[') ||
                (v.substring(0, 1) == '{'))
                v = v.substring(1, v.length - 1);
        }
        return (v);
    }

    static wordwrap(str, width, brk, cut) {
        brk = brk || '\n';
        width = width || 75;
        cut = cut || false;

        if (!str) {
            return str;
        }

        var regex = '.{1,' + width + '}(\\s|$)' + (cut ? '|.{' + width +
            '}|.+$' : '|\\S+?(\\s|$)');

        return str.match(RegExp(regex, 'g')).join(brk);
    }

    static nl2br(aString) {
        var ret = '';

        if (aString !== undefined) {
            ret = aString.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' +
                '<br>' + '$2');
        }

        return ret;
    }

    static dec2deg(dec, asLatitude) {
        asLatitude = "undefined" == typeof asLatitude ? true : asLatitude;
        if (sign(dec) != 0) {
            var positive = sign(dec) > 0,
                gpsdeg, r, gpsmin,
                D, M, S, suffix;

            dec = Math.abs(dec);
            gpsdeg = parseInt(dec),
                r = dec - (gpsdeg * 1.0);
            gpsmin = r * 60.0;
            r = gpsmin - (parseInt(gpsmin) * 1.0);
            D = gpsdeg;
            M = parseInt(gpsmin);
            S = parseInt(r * 60.0);

            if (asLatitude) {
                suffix = positive ? 'N' : 'S';
            } else {
                suffix = positive ? 'E' : 'W';
            }
            return D + "&deg; " + M + "' " + S + "'' " + suffix;
        } else {
            return 'NULL';
        }
    }

    static deg2dec(deg) {
        var suffix = deg.replace(/[^SNEW]+/g, '');
        var ret, d = deg.replace(/[\W_]+/g, " ").split(' ');
        for (var i = 0; i < 2; i++)
            d[i] = str2int(d[i] || 0);
        ret = d[0] + d[1] / 60 + d[2] / 3600;
        if ((suffix == 'S') || (suffix == 'W'))
            ret = ret * -1;
        return ret;
    }

    static str2double(aStr) {
        if (typeof aStr === 'undefined')
            aStr = '0';

        aStr = "" + aStr;

        var a = "";
        if ((aStr.indexOf(',') > 0) && (aStr.indexOf('.') > 0))
            a = aStr.replace('.', '');
        else
            a = aStr;
        a = a.replace(',', '.');

        if (a === '')
            a = '0.00';

        a = parseFloat(a);
        if (isNaN(a))
            a = 0;
        var ret = parseFloat(a);
        ret = parseFloat(ret);
        return ret;
    }

    static str2int(value) {
        var n = parseInt(value);
        return n === null || isNaN(n) ? 0 : n;
    }

    static str2bool(aStr, aDefaultValue) {
        if (aDefaultValue === undefined)
            aDefaultValue = false;

        aStr = aStr || aDefaultValue;
        aStr = ("" + aStr).toUpperCase();
        aStr = aStr == 'YES' || aStr == 'TRUE' || aStr == '1' || (str2int(aStr) > 0);

        return aStr;
    }

    static bool2str(aBool) {
        return aBool ? 'TRUE' : 'FALSE';
    }

    static sign(aValue) {
        aValue = str2int(aValue);
        if (aValue == 0)
            return 0;
        else if (aValue < 0)
            return -1;
        else
            return 1;
    }

    static dec2hex(d) {
        return d.toString(16);
    }

    static hex2dec(h) {
        return parseInt(h, 16);
    }

}



var mergeObject = function (srcObj, trgObj, overwriteIfExists) {
    if (overwriteIfExists === undefined)
        overwriteIfExists = false;
    trgObj = trgObj || {};
    for (var i in srcObj)
        if (srcObj.hasOwnProperty(i)) {
            if ((undefined === trgObj[i]) || (overwriteIfExists))
                trgObj[i] = srcObj[i];
        }
};


function isPropertySupported(property) {
    return property in document.body.style;
}

function isEmpty(obj) {
    for (var prop in obj) {
        if (obj.hasOwnProperty(prop))
            return false;
    }
    return true;
}


         /* as the array keys could be used with data coming from
          * interbase (UPPERCASE) postgresql (lowercase most of the time)
          * or mysql (mixed case when configured properly), we need
          * to let the programmer use which one he wants in the data model
          * while keep the array untoched.
          * Not only that, the field names on client side can be prefixed and/or
          * postfixed, so we need to chose the more adequated
          * This function guess which one is the best */
         var suggestKeyName = function(aObj, aKeyName, fieldPrefix,
            fieldPostfix) {
            var ret = null;
            if (aKeyName) {
              var aColList;
              if (!Array.isArray(aObj))
                aColList = aObj;
              else {
                aColList = {};
                for (var a = 0; a < aObj.length; a++) {
                  aColName = aObj[a];
                  aColList[aColName] = aColName;
                }
              }

              var UKey = aKeyName.toUpperCase();
              for (var i in aColList) {
                if (aColList.hasOwnProperty(i))
                  if (i.toUpperCase() == UKey)
                    ret = i;
              }

              if (fieldPrefix || fieldPostfix) {
                if (ret === null) {
                  fieldPrefix = fieldPrefix || '';
                  fieldPostfix = fieldPostfix || '';
                  if ((UKey.substr(0, fieldPrefix.length)) == fieldPrefix.toUpperCase()) {
                    aKeyName = aKeyName.substr(fieldPrefix.length);
                    ret = suggestKeyName(aColList, aKeyName);
                  }

                  if (ret === null) {
                    if (UKey.substr(UKey.length - fieldPostfix.length) ==
                      fieldPostfix.toUpperCase()) {
                      aKeyName = aKeyName.substr(0, aKeyName.length -
                        fieldPostfix.length);
                      ret = suggestKeyName(aColList, aKeyName);
                    }
                  }
                }
              }
            }
            return ret;
          };

