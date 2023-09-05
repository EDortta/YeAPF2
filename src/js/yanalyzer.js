/**
 * @name YeAPF2
 * @version 2.0
 * @description Yet Another PHP Framework v2
 * @license MIT License
 *
 * (c) 2004-2023 Esteban D.Dortta <dortta@yahoo.com>
 * Website: https://www.yeapf.com
 */



/*********************************************
 * First Version (C) 2014 - esteban daniel dortta - dortta@yahoo.com
 * yLexObj introduced in 2016-08-22 0.8.50-0
 * mantained for YeAPF2 in 2023
**********************************************/

function yAnalise(aLine, aStack, aObject) {
    "use strict";
    if (aLine !== undefined) {

        if ("string" == typeof aLine)
            aLine = yMisc.unmaskHTML(aLine);
        if (true === window.__allowInsecureJSCalls__) {
            aObject = aObject || window;
        } else {
            aObject = aObject || {};
        }

        /* var yPattern = /%[+(\w)]|[]\(/gi; */
        var yPattern = /\%(|\w+)\(/gi;
        var yFunctions = ',int,integer,intz,intn,decimal,ibdate,tsdate,tstime,tsdatetime,date,time,lat2deg,lon2deg,words,image,nl2br,quoted,singleQuoted,condLabel,checked,rg,cpf,cnpj,phone,cep,brDocto,bool2str,str2bool,';
        var p, p1, p2, c1, c2, p3;
        var aValue = '';

        while ((typeof aLine == 'string') && (p = aLine.search(yPattern)) >= 0) {
            p1 = aLine.slice(p).search(/\(/);
            if (p1 >= 0) {
                c1 = aLine.slice(p + p1 + 1, p + p1 + 2);
                if ((c1 == '"') || (c1 == "'")) {
                    p3 = p + p1 + 1;
                    do {
                        p3++;
                        c2 = aLine.slice(p3, p3 + 1);
                    } while ((c2 != c1) && (p3 < aLine.length));
                    p2 = p3 + aLine.slice(p3).search(/\)/) - p;
                } else
                    p2 = aLine.slice(p).search(/\)/);

                var funcName = aLine.slice(p + 1, p + p1);
                var funcParams = aLine.slice(p + p1 + 1, p + p2);
                var parametros = funcParams, n = null;
                funcParams = funcParams.split(',');
                for (n = 0; n < funcParams.length; n++) {
                    funcParams[n] = (funcParams[n] || '').trim();
                    funcParams[n] = yAnalise(funcParams[n], aStack, aObject);
                }

                aValue = undefined;
                var fParamU = funcParams[0].toUpperCase();
                var fParamN = funcParams[0];
                if (aStack !== undefined) {
                    // can come a stack or a simple unidimensional array
                    if (aStack[0] == undefined) {
                        if (aStack[fParamU])
                            aValue = yAnalise(aStack[fParamU], aStack, aObject);
                        else
                            aValue = yAnalise(aStack[fParamN], aStack, aObject);
                    } else {
                        for (var sNdx = aStack.length - 1;
                            (sNdx >= 0) && (aValue == undefined); sNdx--)
                            if (aStack[sNdx][fParamU] != undefined)
                                aValue = yAnalise(aStack[sNdx][fParamU], aStack, aObject);
                            else if (aStack[sNdx][fParamN] != undefined)
                                aValue = yAnalise(aStack[sNdx][fParamN], aStack, aObject);
                    }
                } else {
                    if ("" == fParamN) {
                        aValue = "";
                    } else {
                        if ((fParamN.substr(0, 1) != '.') && (eval('typeof ' + fParamN) == 'string'))
                            aValue = eval(fParamN);
                        else
                            aValue = yAnalise(fParamN, null, aObject);
                    }
                }

                if (aValue == undefined)
                    aValue = '';
                funcParams[0] = aValue;

                switch (funcName) {
                    case 'integer':
                    case 'int':
                    case 'intz':
                    case 'intn':
                        aValue = str2int(aValue);
                        if (aValue == 0) {
                            if (funcName == 'intz')
                                aValue = '-';
                            else if (funcName == 'intn')
                                aValue = '';
                        }
                        break;
                    case 'decimal':
                        var aDecimals = Math.max(0, parseInt(funcParams[1]));
                        aValue = str2double(aValue);
                        aValue = aValue.toFixed(aDecimals);
                        break;
                    case 'phone':
                        aValue = (aValue || '').asPhone();
                        break;
                    case 'bool2str':
                        aValue = bool2str(aValue, false);
                        break;
                    case 'str2bool':
                        aValue = str2bool("" + aValue, false);
                        break;
                    case 'lon2deg':
                        aValue = dec2deg(aValue, false);
                        break;
                    case 'lat2deg':
                        aValue = dec2deg(aValue, true);
                        break;
                    case 'deg2dec':
                        aValue = deg2dec(aValue, false);
                        break;
                    case 'ibdate':
                        aValue = IBDate2Date(aValue);
                        break;
                    case 'tsdate':
                        aValue = timestamp2date(aValue);
                        break;
                    case 'tstime':
                        aValue = timestamp2time(aValue);
                        break;
                    case 'tsdatetime':
                        aValue = timestamp2date(aValue) + ' ' + timestamp2time(aValue);
                        break;
                    case 'date':
                        if (funcParams[1])
                            aValue = UDate2Date(aValue, funcParams[1]);
                        else
                            aValue = UDate2Date(aValue);
                        break;
                    case 'time':
                        if (funcParams[1])
                            aValue = UDate2Time(aValue, funcParams[1]);
                        else
                            aValue = UDate2Time(aValue);
                        break;

                    case 'rg':
                        aValue = ('' + (aValue || '')).asRG();
                        break;

                    case 'cpf':
                        aValue = ('' + (aValue || '')).asCPF();
                        break;

                    case 'cnpj':
                        aValue = ('' + (aValue || '')).asCNPJ();
                        break;

                    case 'brDocto':
                        var aux = ('' + (aValue || ''));
                        if (aux.isCNPJ())
                            aValue = aux.asCNPJ();
                        else if (aux.isCPF())
                            aValue = aux.asCPF();
                        else
                            aValue = aux.asRG();
                        break;

                    case 'phone':
                        aValue = ('' + (aValue || '')).asPhone(funcParams[1]);
                        break;

                    case 'cep':
                        aValue = ('' + (aValue || '')).asCEP();
                        break;

                    case 'nl2br':
                        aValue = aValue.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br>' + '$2');
                        break;

                    case 'abbreviate':
                        aValue = aValue.abbreviate(funcParams[1] || 20, funcParams[2] || false);
                        break;

                    case 'words':
                        var auxValue = aValue.split(' ');

                        var aStart = Math.max(0, str2int(funcParams[1]));
                        var aCount = Math.max(auxValue.length - 1, str2int(funcParams[2]));
                        var aWrap = Math.max(0, str2int(funcParams[3]));

                        aValue = '';
                        for (n = aStart; n < aStart + aCount; n++) {
                            var tmpValue = onlyDefinedValue(auxValue[n]);
                            if (tmpValue > '')
                                aValue += ' ' + tmpValue;
                        }

                        if (aWrap > 0)
                            aValue = wordwrap(aValue, aWrap, '<br>', true);

                        break;
                    case 'quoted':
                        aValue = ('"' + aValue).trim() + '"';
                        break;
                    case 'singleQuoted':
                        aValue = ("'" + aValue).trim() + "'";
                        break;
                    case 'condLabel':

                        break;
                    case 'checked':
                        aValue = countCheckedElements(aValue);
                        break;

                    default:
                        if (funcName > '') {
                            if ('function' == typeof aObject[funcName]) {
                                aValue = aObject[funcName].apply(null, funcParams);
                            } else {
                                if ('object' == typeof aObject[funcName]) {
                                    if ('function' == typeof aObject[funcName][aValue]) {
                                        aValue = aObject[funcName][aValue].apply(null, funcParams);
                                    } else {
                                        aValue = aObject[funcName];
                                    }
                                } else {
                                    if ('undefined' !== typeof aObject[funcName]) {
                                        aValue = aObject[funcName];
                                    }
                                }
                            }
                            /*
                            if (eval('typeof '+funcName) == 'function') {
                              var parametros='';
                              for (var n=0; n<funcParams.length; n++) {
                                if (parametros>'')
                                  parametros += ','

                                parametros += "'"+funcParams[n]+"'";
                              }

                              var chamada = '<script>'+funcName+'('+parametros+');</'+'script>';
                              aValue = chamada.evalScripts();
                            }*/
                        }
                        break;
                }

                aLine = aLine.slice(0, p) + aValue + aLine.slice(p + p2 + 1);
            } else {
                console.error("HALTING yAnalise as entering in loop");
                break;
            }

        }

        /* disabled until we can recognize quickly if is an HTML code */

        /*
        var needEval=false;
        var ops=['<', '>', '==', '!=', '<=', '>='];
        for(var i=0; i<ops.length; i++) {
          needEval|=(aLine.indexOf(ops[i])>=0);
        }

        if ((needEval) && (false)) {
          try{
            aLine=eval(aLine);
          } catch(err) {

          }
        }
        */

    } else
        aLine = '';

    return aLine;
}

var yLexObj = function (aString, toDebug) {
    "use strict";
    var that = {};

    that._debug = (toDebug || false) === true;

    that.optable = {
        '!': 'EXCLAMATION',
        // '"': 'DOUBLE_QUOTE',
        '#': 'NUMBER_SIGN',
        '$': 'DOLLAR',
        '%': 'MODULUS',
        '^': 'POWER',
        '&': 'AMPERSAND',
        '(': 'L_PAREN',
        ')': 'R_PAREN',
        '*': 'MULTIPLICATION',
        '+': 'ADDITION',
        ',': 'COMMA',
        '-': 'SUBSTRACTION',
        '.': 'PERIOD',
        '/': 'DIVISION',
        ':': 'COLON',
        ';': 'SEMICOLON',
        '<': 'LESS_THAN',
        '=': 'EQUALS',
        '>': 'GREATER_THAN',
        '?': 'QUESTION',
        '[': 'L_BRACKET',
        '\\': 'BACKSLASH',
        ']': 'R_BRACKET',
        '{': 'L_BRACE',
        '|': 'PIPE',
        '}': 'R_BRACE',
        '~': 'TILDE',
        '++': 'INCREMENT',
        '--': 'DECREMENT',
        '==': 'EQUAL2',
        '!=': 'NOT_EQUAL2',
        '>=': 'GREATER_EQUALS2',
        '<=': 'LESS_EQUALS2'
    };

    that.opprecedence = {
        'LIKE': 6,
        '<': 6,
        '>': 6,
        '<=': 6,
        '>=': 6,
        '==': 6,

        '^': 4,
        '/': 4,
        '*': 4,
        'AND': 4,

        'OR': 3,

        '+': 2,
        '-': 2,

        '(': 1,
    };

    that._ALPHA = 1;
    that._ALPHA_NUM = 2;
    that._NEW_LINE = 4;
    that._DIGIT = 8;
    that._QUOTE = 16;

    that.voidToken = {
        type: null,
        token: null,
        token_string: null,
        pos: null
    };

    that.error = function () {
        var ret = {};
        mergeObject(that.voidToken, ret);
        ret.type = 'ERROR';
        ret.pos = that.pos;
        return ret;
    };


    that.oneChar = function (offset) {
        offset = offset || 0;
        return that.buf.charAt(that.pos + offset);
    };

    that._isnewline = function (c) {
        c = that.oneChar();
        return (c === '\r' || c === '\n') ? that._NEW_LINE : 0;
    };

    that._isdigit = function (c) {
        return (c >= '0' && c <= '9') ? that._DIGIT : 0;
    };

    that._isalpha = function (c) {
        return ((c >= 'a' && c <= 'z') ||
            (c >= 'A' && c <= 'Z') ||
            (c === '_') || (c === '$')) ? that._ALPHA : 0;
    };

    that._isalphanum = function (c) {
        return that._isdigit(c) | that._isalpha(c);
    };

    that._isquote = function (c) {
        return ((c == "'") || (c == '"')) ? that._QUOTE : 0;
    };

    that._whatis = function (c) {
        return that._isalpha(c) | that._isdigit(c) | that._isquote(c);
    };

    that._process_quote = function () {
        var quote = that.oneChar(),
            lq_pos = that.buf.indexOf(quote, that.pos + 1),
            ret = that.error();
        if (lq_pos > that.pos) {
            ret = {
                type: 'LITERAL',
                token: that.buf.substring(that.pos + 1, lq_pos),
                pos: that.pos
            };
            ret.token_string = ret.token;
            that.pos = lq_pos + 1;
        }
        return ret;
    };

    that._process_identifier = function () {
        var lq_pos = 1,
            ret = that.error();
        while ((that.pos + lq_pos < that.buf.length) &&
            (that._isalpha(that.oneChar(lq_pos))))
            lq_pos++;
        ret = {
            type: 'IDENTIFIER',
            token: that.buf.substring(that.pos, that.pos + lq_pos),
            pos: that.pos
        };
        ret.token_string = ret.token;
        that.pos = that.pos + lq_pos;
        return ret;
    };

    that._process_number = function () {
        var lq_pos = 1,
            ret = that.error();
        while ((that.pos + lq_pos < that.buf.length) &&
            (that._isdigit(that.oneChar(lq_pos))))
            lq_pos++;
        ret = {
            type: 'NUMBER',
            token: that.buf.substring(that.pos, that.pos + lq_pos),
            pos: that.pos
        };
        ret.token_string = ret.token;
        that.pos = that.pos + lq_pos;
        return ret;
    };

    that.getToken = function () {
        var c,
            ret = that.error(),
            sep = ' \t\r\n';

        /* jump to next valid oneChar */
        while (that.pos < that.buf.length) {
            c = that.oneChar();
            if (sep.indexOf(c) > -1) {
                that.pos++;
            } else {
                break;
            }
        }

        /* if still into string */
        if (that.pos < that.buf.length) {
            var canProcessOp = true;
            if (c == '/') {
                if (that.oneChar(1) == '*') {

                } else if (that.oneChar(1) == '/') {

                }
            }

            if (canProcessOp) {
                var op = that.optable[c];
                if (op === undefined) {
                    switch (that._whatis(c)) {
                        case (that._ALPHA):
                            ret = that._process_identifier();
                            var auxToken = String(ret.token_string).toUpperCase();
                            if ((auxToken == 'AND') || (auxToken == 'OR') || (auxToken == 'LIKE')) {
                                ret.token_string = auxToken;
                                ret.type = 'OPERATOR';
                            }
                            break;
                        case (that._DIGIT):
                            ret = that._process_number();
                            break;
                        case (that._QUOTE):
                            ret = that._process_quote();
                            break;
                    }
                } else {
                    var _type = 'OPERATOR',
                        _token_string = c,
                        c1 = that.oneChar(1),
                        r1 = that.voidToken,
                        _pos = that.pos;
                    if (that.optable[c + c1]) {
                        c = c + c1;
                        op = that.optable[c];
                        _token_string = c;
                    } else if ('-+'.indexOf(c) >= 0) {
                        var ptt = that.priorToken.type;
                        if ((ptt === null) || ((ptt == 'OPERATOR') && (that.priorToken.token == "L_PAREN"))) {
                            var c1t = that._whatis(c1);
                            if (c1t == that._DIGIT) {
                                r1 = that._process_number();
                            } else if (c1t == that._ALPHA)
                                r1 = that._process_identifier();
                        }
                    }

                    ret = {
                        type: r1.type || _type,
                        token: r1.token || op,
                        pos: _pos,
                        token_string: r1.token_string || _token_string
                    };
                    that.pos += c.length;
                }
            }
        } else {
            ret.type = 'EOF';
            ret.token = null;
        }
        that.priorToken = ret;
        return ret;
    };

    that.tokenTypeIs = function (token, expectedTypes) {
        expectedTypes = ',' + expectedTypes + ',';
        return (expectedTypes.indexOf(',' + token.type + ',') >= 0);
    };

    that.getExpectedToken = function (expectedTypes) {
        var priorPos = that.pos;
        var token = that.getToken();
        if (that.tokenTypeIs(token, expectedTypes)) {
            return token;
        } else {
            that.pos = priorPos;
            return false;
        }
    };

    that._analiseText = function () {
        var token, lastSym = that.voidToken,
            pct, ppt, itemSolved;
        do {
            itemSolved = false;
            token = that.getToken();
            if (token && token.type != "EOF") {
                if (that.symStack.length > 0)
                    lastSym = that.symStack[that.symStack.length - 1];
                lastSym = lastSym || that.voidToken;

                if (token.token_string == '(') {
                    that.symStack.push(token);
                } else if (token.token_string == ')') {
                    lastSym = that.symStack.pop();
                    while ((lastSym) && (lastSym.token_string != '(')) {
                        that.postFixStack.push(lastSym);
                        lastSym = that.symStack.pop();
                    }
                } else if (token.type == 'OPERATOR') {
                    var canTransfer = true;
                    while (canTransfer) {
                        canTransfer = false;
                        if (that.symStack.length > 0) {
                            lastSym = that.symStack[that.symStack.length - 1];
                            lastSym = lastSym || that.voidToken;

                            pct = that.opprecedence[token.token_string] || 99;
                            ppt = that.opprecedence[lastSym.token_string] || 10;
                            if (ppt > pct) {
                                canTransfer = true;
                                lastSym = that.symStack.pop();
                                that.postFixStack.push(lastSym);
                            }
                        }
                    }
                    that.symStack.push(token);

                } else {
                    that.postFixStack.push(token);
                }

            }

        } while (!((token.type == 'ERROR') || (token.type == 'EOF')));

        do {
            lastSym = that.symStack.pop();
            if ((lastSym) && (lastSym.type != 'EOF'))
                that.postFixStack.push(lastSym);
        } while ((lastSym) && (lastSym.type != 'EOF'));

        if (that._debug) {
            _dumpy(32, 2, "postFixStack:");
            that.showStack(that.postFixStack);
            _dumpy(32, 2, "symStack:");
            that.showStack(that.symStack);
        }
    };

    that.solve = function (data) {
        var i, stack = [],
            token, aux, canPush, op1, op2, ret, noErrors = true,
            errorMessage = '';
        data = data || {};
        data['true'] = true;
        data['false'] = false;
        for (i = 0;
            (i < that.postFixStack.length) && (noErrors); i++) {
            canPush = false;

            token = that.postFixStack[i];
            if (token) {
                if ((token.type == 'NUMBER') || (token.type == 'LITERAL')) {
                    aux = token.token_string;
                    if (!isNumber(aux))
                        aux = String(aux).toUpperCase();
                    canPush = true;
                }
                if (token.type == 'IDENTIFIER') {
                    aux = data[token.token_string];
                    if (typeof aux == 'undefined') {
                        errorMessage = "'" + token.token_string + "' is not defined on data";
                        _dumpy(32, 1, errorMessage);
                        aux = false;
                        canPush = true;
                    } else {
                        if (typeof aux == "string")
                            aux = String(aux).toUpperCase();
                        canPush = true;
                    }
                }

                if (canPush) {
                    stack.push(aux);
                } else {
                    op2 = stack.pop();
                    op1 = stack.pop();
                    ret = null;
                    switch (("" + token.token_string).toUpperCase()) {
                        case '+':
                            ret = str2double(op1) + str2double(op2);
                            break;
                        case '-':
                            ret = str2double(op1) - str2double(op2);
                            break;
                        case '*':
                            ret = str2double(op1) * str2double(op2);
                            break;
                        case '/':
                            ret = str2double(op1) / str2double(op2);
                            break;
                        case '^':
                            ret = Math.pow(str2double(op1), str2double(op2));
                            break;
                        case '>':
                            ret = op1 > op2;
                            break;
                        case '<':
                            ret = op1 < op2;
                            break;
                        case '>=':
                            ret = op1 >= op2;
                            break;
                        case '<=':
                            ret = op1 <= op2;
                            break;
                        case '<>':
                        case '!=':
                            ret = op1 != op2;
                            break;
                        case '==':
                            ret = op1 == op2;
                            break;
                        case 'AND':
                        case '&&':
                            ret = op1 && op2;
                            break;
                        case 'OR':
                        case '||':
                            ret = op1 || op2;
                            break;

                        case 'LIKE':
                            op1 = String(op1).toUpperCase();
                            op2 = String(op2).toUpperCase();
                            var cleanFilter = op2.replace(/\%/g, '');
                            if (op2.substr(0, 1) != '%') {
                                if (op2.substr(op2.length - 1) != '%') {
                                    /* bla */
                                    ret = op1 == op2;
                                } else {
                                    /* bla% */
                                    ret = op1.substr(0, cleanFilter.length) == cleanFilter;
                                }
                            } else {
                                if (op2.substr(op2.length - 1, 1) == '%') {
                                    /* %bla% */
                                    ret = op1.indexOf(cleanFilter) >= 0;
                                } else {
                                    /* %bla */
                                    ret = op1.substr(op1.length - cleanFilter.length) == cleanFilter;
                                }
                            }
                            break;
                        default:
                            errorMessage = "'" + token.token_string + "' is not a recognized operator";
                            console.error(errorMessage);
                            throw new Error();
                    }


                    if (that._debug)
                        _dumpy(32, 2, "{0} = {1} {2} {3}".format(ret, op1, token.token_string, op2));


                    if (ret !== null)
                        stack.push(ret);
                }
            }
        }
        ret = stack.pop();

        if (that._debug) _dumpy(32, 2, JSON.stringify(ret));

        return ret;
    };

    that.showStack = function (s) {
        var stackString = "\t";
        for (var i = 0; i < s.length; i++)
            stackString += (s[i].token_string) + ' ';
        _dumpy(32, 2, stackString);
    };

    that.parse = function () {
        that.reset();
        that._analiseText();
        return that.stack;
    };

    that.reset = function () {
        that.pos = 0;
        that.symStack = [];
        that.postFixStack = [];
        that.priorToken = that.voidToken;
        return that;
    };

    that.init = function (aString) {
        that.buf = aString || that.buf || "";
        that.parse();
        return that;
    };

    return that.init(aString);
};