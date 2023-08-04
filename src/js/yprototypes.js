/**
 * @name YeAPF2
 * @version 2.0
 * @description Yet Another PHP Framework v2
 * @license MIT License
 *
 * (c) 2004-2023 Esteban D.Dortta <dortta@yahoo.com>
 * Website: https://www.yeapf.com
 */


/**
   #####
  #     # ##### #####  # #    #  ####
  #         #   #    # # ##   # #    #
   #####    #   #    # # # #  # #
        #   #   #####  # #  # # #  ###
  #     #   #   #   #  # #   ## #    #
   #####    #   #    # # #    #  ####
**/
if (!String.prototype.trim) {
    String.prototype.trim = function () {
        return this.replace(/^\s+|\s+$/g, "");
    };
}

if (!String.prototype.asPhone) {
    String.prototype.asPhone = function () {
        var aux = this.replace(/\D+/g, ''),
            i;
        return aux.replace(/(\d{2,3})(\d{3})(\d{3})/, '$1-$2-$3');
    };
}

if (!String.prototype.abbreviate) {
    String.prototype.abbreviate = function (maxLength,
        prioritizeFirstName) {
        var addLastName = function () {
            if (p2 > '') {
                if (name.length + lastname.length + p2.length + (name.length >
                    0 ? 1 : 0 + lastname.length > 0 ? 1 : 0) <
                    maxLength) {
                    lastname = trim(p2 + ' ' + lastname);
                    changes++;
                } else if (name.length + lastname.length + (name.length >
                    0 ? 1 : 0 + lastname.length > 0 ? 1 : 0 + 1) <
                    maxLength) {
                    lastname = trim(p2.substr(0, 1) + '.' + lastname);
                    changes++;
                }
            }
        };

        var addName = function () {
            if (p1 > '') {
                if (name.length + lastname.length + p1.length + (name.length >
                    0 ? 1 : 0 + lastname.length > 0 ? 1 : 0) <
                    maxLength) {
                    name = trim(name + ' ' + p1);
                    changes++;
                } else {
                    if (name.length + lastname.length + (name.length > 0 ?
                        1 : 0 + lastname.length > 0 ? 1 : 0 + 1) <
                        maxLength) {
                        name = trim(name + ' ' + p1.substr(0, 1) + '.');
                        changes++;
                    }
                }
            }
        };

        prioritizeFirstName = prioritizeFirstName || false;
        maxLength = str2int(maxLength);
        if (this.indexOf(' ') > 0) {
            var ni, li, piece = this.toString(),
                name = '',
                lastname = '',
                p1, p2, changes;
            while (name.length + lastname.length + 1 <= maxLength) {
                piece = trim(piece);
                ni = piece.indexOf(' ');
                if (ni < 0)
                    ni = piece.length;
                li = piece.lastIndexOf(' ');
                p1 = trim(piece.substr(0, ni));
                p2 = '';

                if (ni < li) {
                    p2 = trim(piece.substr(li));
                    piece = trim(piece.substr(0, li));
                }

                piece = trim(piece.substr(ni));
                changes = 0;

                if (prioritizeFirstName)
                    addName();
                else
                    addLastName();

                if (p1 != p2) {
                    if (prioritizeFirstName)
                        addLastName();
                    else
                        addName();
                }
                if (changes === 0)
                    break;
            }
            return trim(name + ' ' + lastname);
        } else
            return this.substr(0, maxLength);
    };
}

if (!String.prototype.ucFirst) {
    String.prototype.ucFirst = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };
}

if (!String.prototype.lcFirst) {
    String.prototype.lcFirst = function () {
        return this.charAt(0).toLowerCase() + this.slice(1);
    };
}

if (!String.prototype.stripTags) {
    String.prototype.stripTags = function () {
        return (this || '').replace(/<(?:.|\n)*?>/gm, '');
    };
}

if (!String.prototype.repeat) {
    String.prototype.repeat = function (n, aChar) {
        n = n || 1;
        aChar = aChar || this;
        return Array(n + 1).join(aChar);
    };
}
/* returns a quoted string if it is not a number
 * or a parsed float otherwise */
if (!String.prototype.quoteString) {
    String.prototype.quoteString = function (emptyAsNull) {
        if (emptyAsNull === undefined)
            emptyAsNull = false;
        var aux = this.valueOf();
        if (!isNumber(aux)) {
            if ((emptyAsNull) && (aux === ''))
                aux = null;
            else {
                aux = this.replace(/\"/g, "\\\"");
                aux = '"' + aux + '"';
            }
        } else
            aux = parseFloat(aux);
        return aux;
    };
}

if (!String.prototype.quote) {
    String.prototype.quote = function () {
        var aux = this.replace(/\"/g, "\\\"");
        return '"' + aux + '"';
    };
}

if (!String.prototype.unquote) {
    String.prototype.unquote = function () {
        var firstChar = '',
            lastChar = '';
        if (this.length > 1) {
            firstChar = this.substr(0, 1);
            lastChar = this.substr(this.length - 1, 1);
            if (firstChar == lastChar) {
                if ((lastChar == '"') || (lastChar == "'"))
                    return this.substr(1, this.length - 2);
                else
                    return this.toString() + '';
            } else if (((firstChar == '(') && (lastChar == ')')) ||
                ((firstChar == '[') && (lastChar == ']')) ||
                ((firstChar == '{') && (lastChar == '}'))) {
                return this.substr(1, this.length - 2);
            } else
                return this.toString() + '';
        } else
            return this.toString() + '';
    };
}

if (!String.prototype.format) {
    String.prototype.format = function () {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function (match, number) {
            return typeof args[number] != 'undefined' ? args[number] :
                match;
        });
    };
}

if (!String.prototype.isCreditCard) {
    String.prototype.isCreditCard = function () {
        var digit, digits, flag, sum, _i, _len;
        flag = true;
        sum = 0;
        var auxCartao = this.replace(/\D/g, '');
        digits = (auxCartao + '').split('').reverse();
        for (_i = 0, _len = digits.length; _i < _len; _i++) {
            digit = digits[_i];
            digit = parseInt(digit, 10);
            if ((flag = !flag)) {
                digit *= 2;
            }
            if (digit > 9) {
                digit -= 9;
            }
            sum += digit;
        }
        return (auxCartao.length > 0) && (sum % 10 === 0);
    };
}

if (!String.prototype.isEmail) {
    String.prototype.isEmail = function () {
        return yMisc.isEmail(this);
    };
}

if (!String.prototype.toFloat) {
    String.prototype.toFloat = function () {
        n = this;
        // n=n.replace(":", "");
        if (n.match(/^-?((\d*[,.]){1,4})?\d*$/)) {
            var p = n.indexOf('.'),
                c = n.indexOf(',');
            if (p < c) {
                n = n.replace(".", "");
            }
            n = n.replace(",", ".");
            return parseFloat(n);
        } else {
            return NaN;
        }
    };
}

Function.prototype.method = function (name, func) {
    this.prototype[name] = func;
    return this;
};

Function.method('inherits', function (Parent) {
    this.prototype = new Parent();
    return this;
});


/**
  #     # ####### #     # #       #######
  #     #    #    ##   ## #       #       #      ###### #    # ###### #    # #####
  #     #    #    # # # # #       #       #      #      ##  ## #      ##   #   #
  #######    #    #  #  # #       #####   #      #####  # ## # #####  # #  #   #
  #     #    #    #     # #       #       #      #      #    # #      #  # #   #
  #     #    #    #     # #       #       #      #      #    # #      #   ##   #
  #     #    #    #     # ####### ####### ###### ###### #    # ###### #    #   #
**/
if ((typeof HTMLElement == "object") || (typeof HTMLElement ==
    "function")) {
    HTMLElement.prototype.hasClass = function (aClassName) {
        var ret = false;
        if (this.className) {
            var asterisk = aClassName.indexOf('*');
            var aClasses = this.className.split(' ');
            for (var i = 0; i < aClasses.length; i++) {
                if (asterisk >= 0) {
                    if (aClasses[i].substr(0, asterisk) == aClassName.substr(
                        0, asterisk))
                        ret = true;
                } else {
                    if (aClasses[i] == aClassName)
                        ret = true;
                }
            }
        }
        return ret;
    };

    HTMLElement.prototype.setOpacity = function (value) {
        value = Math.max(0, Math.min(value, 100));
        this.style.opacity = value / 100;
        this.style.filter = "alpha(opacity={0})".format(value);
    };

    HTMLElement.prototype.deleteClass = function (aClassName) {
        var aNewClasses = '';
        var aClasses = this.className.split(' ');
        var aParamClasses = (aClassName || '').split(' ');
        for (var i = 0; i < aClasses.length; i++) {
            if (aParamClasses.indexOf(aClasses[i]) < 0) {
                aNewClasses = aNewClasses + ' ' + aClasses[i];
            }
        }
        this.className = trim(aNewClasses);
        return this;
    };

    HTMLElement.prototype.removeClass = HTMLElement.prototype.deleteClass;

    HTMLElement.prototype.addClass = function (aClassName) {
        var aClassModified = false;
        var aNewClasses = this.className;
        var aClasses = this.className.split(' ');
        var aParamClasses = (aClassName || '').split(' ');
        for (var i = 0; i < aParamClasses.length; i++) {
            if (aClasses.indexOf(aParamClasses[i]) < 0) {
                aNewClasses = aNewClasses + ' ' + aParamClasses[i];
                aClassModified = true;
            }
        }
        if (aClassModified)
            this.className = trim(aNewClasses);
        return this;
    };

    HTMLElement.prototype.siblings = function () {
        var buildChildrenList = function (aNode, aExceptionNode) {
            var ret = [];
            while (aNode) {
                if ((aNode != aExceptionNode) && (aNode.nodeType == 1)) {
                    ret.push(aNode);
                }
                aNode = aNode.nextSibling;
            }
            return ret;
        };
        return buildChildrenList(this.parentNode.firstChild, this);
    };
    if (!HTMLElement.prototype.getAttribute)
        HTMLElement.prototype.getAttribute = function (attributeName) {
            var ret = '';
            for (var i = 0; i < this.attributes.length; i++)
                if (this.attributes[i].name == attributeName)
                    ret = attributes[i].value;
            return ret;
        };

    if (!HTMLElement.prototype.block)
        HTMLElement.prototype.block = function () {
            this.setAttribute('blocked', 'blocked');
            return this;
        };

    if (!HTMLElement.prototype.unblock)
        HTMLElement.prototype.unblock = function () {
            this.removeAttribute('blocked');
            return this;
        };


    if (!HTMLElement.prototype.isBlocked)
        HTMLElement.prototype.isBlocked = function () {
            var hasBlock = this.getAttribute('blocked');
            return ((typeof hasBlock == 'string') &&
                (hasBlock.toLowerCase() == 'blocked'));
        };

    if (!HTMLElement.prototype.lock)
        HTMLElement.prototype.lock = function () {
            if (!this.isBlocked())
                this.readOnly = true;
            return this;
        };

    if (!HTMLElement.prototype.unlock)
        HTMLElement.prototype.unlock = function () {
            if (!this.isBlocked())
                this.readOnly = false;
            return this;
        };

    if (!HTMLElement.prototype.selected) {
        HTMLElement.prototype.selected = function () {
            var ret = this;
            if (typeof this.list == 'object') {
                var v1 = trim(this.value),
                    op = this.list.options;
                for (var i in op) {
                    if (op.hasOwnProperty(i)) {
                        if (trim(op[i].innerHTML) == v1) {
                            ret = op[i];
                            break;
                        }
                    }
                }
            }
            return ret;
        };
    }


}
/**
 #######
 #     # #####       # ######  ####  #####
 #     # #    #      # #      #    #   #
 #     # #####       # #####  #        #
 #     # #    #      # #      #        #
 #     # #    # #    # #      #    #   #
 ####### #####   ####  ######  ####    #

**/
if (typeof Object.create !== 'function') {
    Object.create = function (o) {
        var F = function () { };
        F.prototype = o;
        return new F();
    };
}

/**
    #
   # #   #####  #####    ##   #   #
  #   #  #    # #    #  #  #   # #
 #     # #    # #    # #    #   #
 ####### #####  #####  ######   #
 #     # #   #  #   #  #    #   #
 #     # #    # #    # #    #   #
**/


if (!Array.prototype.forEach) {
    Array.prototype.forEach = function (fun /*, thisArg */) {
        "use strict";

        if (this === void 0 || this === null)
            throw new TypeError();

        var t = Object(this);
        var len = t.length >>> 0;
        if (typeof fun !== "function")
            throw new TypeError();

        var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
        for (var i = 0; i < len; i++) {
            if (i in t)
                fun.call(thisArg, t[i], i, t);
        }
    };
}

if (!Array.prototype.unique) {
    Array.prototype.unique = function () {
        var a = this;
        return a.filter(function (item, pos) {
            return a.indexOf(item) == pos;
        });
    };
}

if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (elt /*, from*/) {
        var len = this.length >>> 0;

        var from = Number(arguments[1]) || 0;
        from = (from < 0) ?
            Math.ceil(from) :
            Math.floor(from);
        if (from < 0)
            from += len;

        for (; from < len; from++) {
            if (from in this &&
                this[from] === elt)
                return from;
        }
        return -1;
    };
}

/**

 ######
 #     #   ##   ##### ######
 #     #  #  #    #   #
 #     # #    #   #   #####
 #     # ######   #   #
 #     # #    #   #   #
 ######  #    #   #   ######

**/

if (typeof Date.prototype.getFirstDayOfWeek == 'undefined') {
    Date.prototype.getFirstDayOfWeek = function (weekStart) {
        /* weekStart - By default it is sunday (0)
         */
        weekStart = (weekStart || 0);
        var date = (new Date(this.getTime()));
        date.setHours(0, 0, 0, 0);
        while (date.getDay() != weekStart) {
            date.setDate(date.getDate() - 1);
            date.setHours(0, 0, 0, 0);
        }
        return date;
    };
}

if (typeof Date.prototype.monthFirstDOW == 'undefined') {
    Date.prototype.monthFirstDOW = function (aDate) {
        var auxDate = new Date((aDate || this).getTime());
        auxDate.setDate(1);
        return auxDate.getDay();
    };
}

if (typeof Date.prototype.monthLastDay == 'undefined') {
    Date.prototype.monthLastDay = function (aDate) {
        var auxDate = new Date((aDate || this).getTime());
        return new Date(auxDate.getYear(), auxDate.getMonth() + 1, 0)
            .getDate();
    };
}

if (typeof Date.prototype.monthLastDOW == 'undefined') {
    Date.prototype.monthLastDOW = function (aDate) {
        var auxDate = new Date((aDate || this).getTime());
        auxDate.setDate(this.monthLastDay(auxDate));
        return auxDate.getDay();
    };
}

if (typeof Date.prototype.nextMonth == 'undefined')
    Date.prototype.nextMonth = function (aDate) {
        var auxDate = new Date((aDate || this).getTime());
        var thisMonth = auxDate.getMonth();
        auxDate.setMonth(thisMonth + 1);
        if (auxDate.getMonth() != thisMonth + 1 && auxDate.getMonth() !==
            0)
            auxDate.setDate(0);
        return auxDate;
    };

if (typeof Date.prototype.prevMonth == 'undefined')
    Date.prototype.prevMonth = function (aDate) {
        var auxDate = new Date((aDate || this).getTime());
        var thisMonth = auxDate.getMonth();
        auxDate.setMonth(thisMonth - 1);
        if (auxDate.getMonth() != thisMonth - 1 && (auxDate.getMonth() !=
            11 || (thisMonth == 11 && auxDate.getDate() == 1)))
            auxDate.setDate(0);
        return auxDate;
    };

if (typeof Date.prototype.incMonth == 'undefined')
    Date.prototype.incMonth = function (aInc) {
        var thisMonth = this.getMonth();
        this.setMonth(thisMonth + aInc);
        if (this.getMonth() != thisMonth + aInc && (this.getMonth() !=
            11 || (thisMonth == 11 && this.getDate() == 1)))
            this.setDate(0);
    };

if (typeof Date.prototype.incDay == 'undefined')
    Date.prototype.incDay = function () {
        this.setDate(this.getDate() + 1);
    };

if (typeof Date.prototype.decDay == 'undefined')
    Date.prototype.decDay = function () {
        this.setDate(this.getDate() - 1);
    };

if (typeof Date.prototype.incWeek == 'undefined')
    Date.prototype.incWeek = function () {
        this.setDate(this.getDate() + 7);
    };

if (typeof Date.prototype.decWeek == 'undefined')
    Date.prototype.decWeek = function () {
        this.setDate(this.getDate() - 7);
    };

if (typeof Date.prototype.daysInMonth == 'undefined')
    Date.prototype.daysInMonth = function (iMonth, iYear) {
        if (!iYear)
            iYear = this.getFullYear();
        if (!iMonth)
            iMonth = this.getMonth() + 1;

        return 32 - new Date(parseInt(iYear), parseInt(iMonth) - 1, 32)
            .getDate();
    };

/* french style is dd/mm/yyyy */
if (typeof Date.prototype.toFrenchString == 'undefined')
    Date.prototype.toFrenchString = function () {
        return '' + this.getDate() + '/' +
            (this.getMonth() + 1) + '/' +
            this.getFullYear();
    };

/* UDate is like ISO8601 but with no separations and without milliseconds */
if (typeof Date.prototype.toUDate == 'undefined')
    Date.prototype.toUDate = function () {

        return '' + pad(this.getFullYear(), 4) +
            pad(this.getMonth() + 1, 2) +
            pad(this.getDate(), 2) +
            pad(this.getHours(), 2) +
            pad(this.getMinutes(), 2) +
            pad(this.getSeconds(), 2);
    };


if (typeof Date.prototype.toISOString == 'undefined') {

    Date.prototype.toISOString = function () {
        return this.getUTCFullYear() +
            '-' + pad(this.getUTCMonth() + 1, 2) +
            '-' + pad(this.getUTCDate(), 2) +
            'T' + pad(this.getUTCHours(), 2) +
            ':' + pad(this.getUTCMinutes(), 2) +
            ':' + pad(this.getUTCSeconds(), 2) +
            '.' + String((this.getUTCMilliseconds() / 1000).toFixed(3))
                .slice(2, 5) +
            'Z';
    };

}

if (typeof Date.prototype.frenchFormat == 'undefined')
    Date.prototype.frenchFormat = function () {
        return this.getDate() + '/' + (this.getMonth() + 1) + '/' +
            this.getFullYear();
    };

Date.prototype.getWeekNumber = function () {
    var d = new Date(Date.UTC(this.getFullYear(), this.getMonth(),
        this.getDate()));
    var dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
};

/* http://stackoverflow.com/questions/11887934/how-to-check-if-the-dst-daylight-saving-time-is-in-effect-and-if-it-is-whats */
Date.prototype.stdTimezoneOffset = function () {
    var jan = new Date(this.getFullYear(), 0, 1);
    var jul = new Date(this.getFullYear(), 6, 1);
    return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
};

Date.prototype.dst = function () {
    return this.getTimezoneOffset() < this.stdTimezoneOffset();
};

/*
 * aStrDate: string
 * aFormat: string where 'y' means year, 'm' month, 'd' day,
 *                       'H' hour, 'M': minutes and 'S' seconds
 *
 * It can understand yyyy-mm-dd HH:MM:SS or yy/m/dd H:MM:SS from
 * the same format string and diferent dates
 */
var extractDateValues = function (aStrDate, aFormat, aDateMap) {

    var getDateSplitter = function (aStr) {
        var splitter1 = (aStr.match(/\//g) || []).length;
        var splitter2 = (aStr.match(/\-/g) || []).length;

        return ((splitter1 > splitter2) ? '/' : ((splitter2 > 0) ?
            '-' : ''));
    };

    var dateSplitter = getDateSplitter(aFormat);
    var dateSplitterInUse = getDateSplitter(aStrDate);

    var i, dtSequence = null;
    if (dateSplitter > '') {
        dtSequence = [];
        var dtFormat = aFormat.split(dateSplitter);
        for (i = 0; i < dtFormat.length; i++)
            dtSequence[dtFormat[i].substr(0, 1)] = i;

        var aux = aStrDate.split(dateSplitter);
        while (aux.length < dtFormat.length) {
            aStrDate = aStrDate + dateSplitter + '01';
            aux[aux.length] = 0;
        }
    }

    var getElementValue = function (aElementTag) {
        var p = aFormat.indexOf(aElementTag);
        var l = 0;
        if (p >= 0) {
            var elementValue;
            while ((p + l < aFormat.length) && (aFormat.substr(p + l,
                1) == aElementTag))
                l++;

            if (((aElementTag.match(/[y,m,d]/g) || []).length > 0) &&
                (dtSequence !== null)) {
                elementValue = aStrDate.split(dateSplitter)[dtSequence[
                    aElementTag]].split(' ')[0];
            } else
                elementValue = str2int(aStrDate.substr(p, l));
            return [p, elementValue, aElementTag, l];
        } else
            return [null, null, aElementTag];
    };

    var parseDate = function () {
        return aStrDate.match(/\b[\d]+\b/g);
    };

    var getReturn = function (aDateArray) {
        var ret = [];
        for (var i = 0; i < aDateArray.length; i++) {
            var auxValue = aDateArray[i][1];
            if (auxValue !== null) {
                auxValue = auxValue.toString();
                if (auxValue.length == 1)
                    auxValue = pad(auxValue, 2);
                else if (auxValue.length == 3)
                    auxValue = pad(auxValue, 4);
            }
            ret[aDateArray[i][2]] = auxValue;
        }
        return ret;
    };

    var ret;

    if (aFormat === undefined)
        aFormat = 'yyyy-mm-ddThh:mm:ss';
    if (aStrDate === '') {
        ret = [];
        ret.y = '';
        ret.d = '';
        ret.m = '';
        ret.H = '';
        ret.M = '';
        ret.S = '';
        return ret;
    }

    if (aDateMap === undefined)
        aDateMap = {};

    aDateMap.elems = [getElementValue('y'),
    getElementValue('m'),
    getElementValue('d'),
    getElementValue('H'),
    getElementValue('M'),
    getElementValue('S')
    ];

    /* first we try with fixed position analisis
     * we test the minimum approach: month/day */
    if ((dateSplitterInUse == dateSplitter) &&
        (((aDateMap.elems[1][1] > 0) && (aDateMap.elems[1][1] < 13)) &&
            ((aDateMap.elems[2][1] >= 1) && (aDateMap.elems[2][1] <= 31))
        )) {
        ret = getReturn(aDateMap.elems);
    } else {
        /* secondly we try with relative position analisis
         * so we have in sortedInfo the field as it comes
         * from the user */
        var sortedInfo = aDateMap.elems;
        sortedInfo.sort(function (a, b) {
            if (b[0] == null)
                return -1;
            else {
                if (a[0] == null)
                    return 1;
                else {
                    if (a[0] === b[0])
                        return 0;
                    else if (a[0] < b[0])
                        return -1;
                    else if (a[0] > b[0])
                        return 1;
                }
            }
        });
        /* we extract the date elements */
        var auxDateInfo = parseDate(),
            total = 1;
        for (i = 0; i < sortedInfo.length && i < (auxDateInfo || []).length; i++) {
            sortedInfo[i][1] = auxDateInfo[i];
            total *= sortedInfo[i][1];
        }
        if (total > 0)
            ret = getReturn(sortedInfo);
        else {
            ret = null;
        }
    }
    return ret;
};

var array2date = function (aDate) {
    return pad(aDate.d, 2) + '-' + pad(aDate.m, 2) + '-' + aDate.y;
};

/* hh:mm (string) -> minutes (integer) */
function time2minutes(aTime) {
    if ((aTime === undefined) || (aTime == 'NaN'))
        aTime = 0;
    var h = 0;
    var m = 0;
    if (aTime > '') {
        aTime = "" + aTime + " ";
        var p = aTime.indexOf('h');
        if (p < 0)
            p = aTime.indexOf(':');
        if (p >= 0) {
            h = aTime.substring(0, p);
            m = parseInt(aTime.substring(p + 1));
            if (isNaN(m))
                m = 0;
        } else {
            h = 0;
            m = parseInt(aTime);
        }
        aTime = h * 60 + m;
    }

    if (aTime < 0)
        aTime = 0;

    return aTime;
}

/* minutes (integer) -> hh:mm (string) */
function minutes2time(aMinutes) {
    var h = pad(Math.floor(aMinutes / 60), 2);
    var m = pad(aMinutes % 60, 2);
    return h + ':' + m;
}

/* unix timestamp to day of week (0=sunday) */
function timestamp2dayOfWeek(aTimestamp) {
    var aux = new Date();
    aux.setTime(aTimestamp * 1000);
    return aux.getDay();
}


function TimezoneDetect() {
    /*
     * http://www.michaelapproved.com/articles/timezone-detect-and-ignore-daylight-saving-time-dst/
     */
    var dtDate = new Date('1/1/' + (new Date()).getUTCFullYear());
    var intOffset = 10000; //set initial offset high so it is adjusted on the first attempt
    var intMonth;
    var intHoursUtc;
    var intHours;
    var intDaysMultiplyBy;

    //go through each month to find the lowest offset to account for DST
    for (intMonth = 0; intMonth < 12; intMonth++) {
        //go to the next month
        dtDate.setUTCMonth(dtDate.getUTCMonth() + 1);

        //To ignore daylight saving time look for the lowest offset.
        //Since, during DST, the clock moves forward, it'll be a bigger number.
        if (intOffset > (dtDate.getTimezoneOffset() * (-1))) {
            intOffset = (dtDate.getTimezoneOffset() * (-1));
        }
    }

    return intOffset;
}



/* unix timestamp -> dd/mm/yyyy */
function timestamp2date(aTimestamp) {
    if ((!isNaN(aTimestamp)) && (aTimestamp > '')) {
        var aux = new Date();
        aux.setTime(aTimestamp * 1000 + (-TimezoneDetect() - aux.getTimezoneOffset()) *
            60 * 1000);
        return pad(aux.getDate(), 2) + '/' + pad(aux.getMonth() + 1, 2) +
            '/' + pad(aux.getFullYear(), 4);
    } else
        return '';
}

/* unix timestamp -> hh:mm */
function timestamp2time(aTimestamp, seconds) {
    var ret;
    if (aTimestamp === undefined)
        ret = '';
    else if ((aTimestamp === '') || (isNaN(aTimestamp)))
        ret = '';
    else {
        if (seconds === undefined)
            seconds = false;
        var aux = new Date();
        aux.setTime(aTimestamp * 1000);

        ret = pad(aux.getHours(), 2) + ':' + pad(aux.getMinutes(), 2);
        if (seconds)
            ret = ret + ':' + pad(aux.getSeconds(), 2);
    }
    return ret;
}


/* dd/mm/yyyy hh:mm:ss -> yyyymmddhhmmss */
function FDate2UDate(a) {
    a = a || (new Date("1/1/1900")).toFrenchString();
    if (a.indexOf('/') > 0)
        a = a.split('/');
    else
        a = a.split('-');
    var h = a[2] || '';
    h = h.split(' ');
    a[2] = h[0];
    h = h[1];
    if (h === undefined)
        h = '00:00:00';
    h = h.split(':');
    if (h[1] === undefined)
        h[1] = 0;
    if (h[2] === undefined)
        h[2] = 0;
    return pad(a[2], 4) + '-' + pad(a[1], 2) + '-' + pad(a[0], 2) +
        ' ' + pad(h[0], 2) + ':' + pad(h[1], 2) + ':' + pad(h[2], 2);
}

/* ISO8601 -> javascript date object */
function UDate2JSDate(aUDate) {
    var aDate = extractDateValues(aUDate, 'yyyymmddHHMMSS');
    var d = new Date(aDate.y, aDate.m - 1, aDate.d, aDate.H, aDate.M,
        aDate.S);

    return d;
}

/* ISO8601 -> french date dd/mm/yyyy */
function UDate2Date(aUDate, aFormat) {
    if (typeof aFormat === 'undefined')
        aFormat = "d/m/y";
    var ret = '';
    var aDate = extractDateValues(aUDate, 'yyyymmddHHMMSS');
    if (!(aDate === null)) {
        for (var i = 0; i < aFormat.length; i++)
            if (/^[d,m,y]+$/.test(aFormat[i]))
                ret += aDate[aFormat[i]];
            else
                ret += aFormat[i];
    }
    if (ret == '//')
        ret = '';
    return ret;
}

/* ISO8601 -> french time hh:mm:ss */
function UDate2Time(aUDate, aFormat) {
    if (typeof aFormat === 'undefined')
        aFormat = "H:M:S";
    var ret = '';
    var aDate = extractDateValues(aUDate, 'yyyymmddHHMMSS');
    if (aDate) {
        ret = '';
        for (var i = 0; i < aFormat.length; i++)
            if (/^[H,M,S]+$/.test(aFormat[i]))
                ret += aDate[aFormat[i]];
            else
                ret += aFormat[i];
    }
    if (ret == '::')
        ret = '';
    return ret;
}

/* interbase (english) date mmddyyyy -> french date dd-mm-yyyy */
function IBDate2Date(aIBDate) {
    var ret = '';
    var aDate = extractDateValues(aIBDate, 'mmddyyyyHHMMSS');
    if (aDate)
        ret = aDate.d + '-' + aDate.m + '-' + aDate.y;
    return ret;
}

// french date dd-mm-yyyy -> english date  mm-dd-yyyy
function date2IBDate(aFDate) {
    var ret = '';
    var aDate = extractDateValues(aFDate, 'ddmmyyyyHHMMSS');
    if (aDate)
        ret = pad(aDate.m, 2) + '-' + pad(aDate.d, 2) + '-' + aDate.y;
    return ret;
}

// french date dd-mm-yyyy -> ISO8601 date  yyyy-mm-dd
function date2UDate(aFDate) {
    var ret = '';
    var aDate = extractDateValues(aFDate, 'ddmmyyyyHHMMSS');
    if (aDate)
        ret = pad(aDate.y, 4) + '-' + pad(aDate.m, 2) + '-' + pad(aDate
            .d, 2);
    return ret;
}

function IBDate2timestamp(a) {
    a = IBDate2Date(a);
    a = date2timestamp(a);
    return a;
}

function timestamp2IBDate(a) {
    a = timestamp2date(a);
    a = date2IBDate(a);
    return a;
}

var dateTransform = function (aStrDate, srcFormat, destFormat) {
    if (aStrDate) {
        var ret = destFormat;
        var tmpDate = extractDateValues(aStrDate, srcFormat);
        if (tmpDate) {
            var auxMap = {};
            var emptyDate = extractDateValues("111111111111",
                destFormat, auxMap);

            for (var i = 0; i < auxMap.elems.length; i++) {
                /* e is a shortcut to the array map */
                var e = auxMap.elems[i];
                if (e[0] !== null) {
                    /* pos 2 is the date index (y,m,d,H,M,S)
                     * pos 3 is the target length */
                    var value = pad(tmpDate[e[2]], e[3]);

                    /* pos 0 is the start of the date element
                     * we expect to have enough space in date return */
                    while (ret.length < e[0] + e[3])
                        ret = ret + ' ';
                    ret = ret.substr(0, e[0]) + value + ret.substr(e[0] + e[
                        3], ret.length);
                }
            }

        }
        return ret;
    } else
        return null;
};

var isValidDate = function (aFrenchDate) {
    var ok = true,
        d;
    if ("string" == typeof aFrenchDate) {
        aFrenchDate = dateTransform(aFrenchDate, "dd/mm/yyyy",
            "yyyy-mm-ddT12:59:59");
    }

    try {
        d = new Date(aFrenchDate);
    } catch (err) {
        ok = false;
    }

    if (ok) {
        if (!isNaN(d.getTime())) {
            var f = dateTransform(d.getFullYear() + "/" + (d.getMonth() +
                1) + "/" + d.getDate(), "yyyy/mm/dd",
                "yyyy-mm-ddT12:59:59");
            ok = f == aFrenchDate;
        } else
            ok = false;
    }

    return ok;
};

var isValidTime = function (aTime) {
    var ret = false;
    var aux = (aTime || "").match(
        /^\d{1,2}:\d{1,2}(:\d{1,2}){0,1}$/) || [];
    if (aux.length > 0) {
        aux = aux[0].split(":");
        while (aux.length < 3) {
            aux[aux.length] = "00";
        }
        ret = (aux[0] >= 0) && (aux[0] <= 23) && (aux[1] >= 0) && (
            aux[1] <= 59) && (aux[2] >= 0) && (aux[2] <= 59);
    }
    return ret;
};

var dateInRange = function (aFrenchDate, aFrenchMinDate,
    aFrenchMaxDate) {
    /* determina se uma data em formato frances (dd/mm/yyyy) estÃ¡ no escopo indicado
       Na ausencia de um dos parÃ¡metros, ele assume hoje para aquele que falta
       Se faltam os dois, a Ãºnica data vÃ¡lida Ã© hoje */
    var ret = false;
    if (isValidDate(aFrenchDate)) {
        aFrenchMinDate = aFrenchMinDate || (new Date()).toFrenchString();
        aFrenchMaxDate = aFrenchMaxDate || (new Date()).toFrenchString();
        if (isValidDate(aFrenchMinDate)) {
            if (isValidDate(aFrenchMaxDate)) {
                aFrenchDate = dateTransform(aFrenchDate, "dd/mm/yyyy",
                    "yyyy-mm-dd");
                aFrenchMinDate = dateTransform(aFrenchMinDate,
                    "dd/mm/yyyy", "yyyy-mm-dd");
                aFrenchMaxDate = dateTransform(aFrenchMaxDate,
                    "dd/mm/yyyy", "yyyy-mm-dd");
                ret = ((aFrenchDate >= aFrenchMinDate) && (aFrenchDate <=
                    aFrenchMaxDate));
            }
        }
    }
    return ret;
};
