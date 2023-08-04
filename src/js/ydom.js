/**
 * @name YeAPF2
 * @version 2.0
 * @description Yet Another PHP Framework v2
 * @license MIT License
 *
 * (c) 2004-2023 Esteban D.Dortta <dortta@yahoo.com>
 * Website: https://www.yeapf.com
 */

(
    function () {
        window.y$ = (elementId, tagName, index) => {
            if (typeof elementId !== 'string' || elementId === '') {
                return undefined;
            }

            let element;

            if (elementId.startsWith('#')) {
                if (elementId.indexOf(' ') > 0) {
                    element = document.querySelector(elementId);
                } else {
                    elementId = elementId.substring(1);
                }
            }

            if (!element) {
                element = document.getElementById(elementId);
            }

            if (!element) {
                const elements = document.getElementsByName(elementId);
                if (elements.length === 1) {
                    element = elements[0];
                } else if (elements.length === 0) {
                    element = undefined;
                }
            }

            if (!element) {
                const classes = elementId.split(' ');
                let classesReturn, first = true;
                for (let i = 0; i < classes.length; i++) {
                    let className = classes[i].trim();
                    if (className.startsWith('.')) {
                        className = className.substring(1);
                    }
                    const auxElements = getElementsByClassName(document, '*', className);
                    if (auxElements.length > 0) {
                        if (first) {
                            classesReturn = auxElements;
                        } else {
                            first = false;
                            classesReturn = array_intersect(classesReturn || [], auxElements);
                        }
                    }
                }
                element = classesReturn;
            } else {
                if (typeof tagName !== 'undefined') {
                    index = 0 + index;
                    if (element.getElementsByTagName) {
                        const innerElements = element.getElementsByTagName(tagName);
                        if (innerElements.length > 0) {
                            element = innerElements[index];
                        }
                    }
                }
            }

            return element;
        };
    }
)();

if (typeof window == "object") {
    if (typeof getElementsByClassName == "undefined") {
        window.getElementsByClassName = function (oRootElem, strTagName,
            aClassName) {
            var arrElements = oRootElem.getElementsByTagName(strTagName);
            var arrReturnElements = [];
            var oCurrent;
            for (var i = 0; i < arrElements.length; i++) {
                oCurrent = arrElements[i];
                if ((oCurrent) && (typeof oCurrent.hasClass == 'function'))
                    if (oCurrent.hasClass(aClassName))
                        arrReturnElements.push(oCurrent);
            }
            if (arrReturnElements === null)
                arrReturnElements = document.getElementsByClassName(
                    aClassName);
            return arrReturnElements;
        };

        window.createDOMEvent = function(eventName) {
            var ret = null;
            if (yDevice.isOnMobile()) {
              ret = document.createEvent('Event');
              ret.initEvent(eventName, true, true);
            } else {
              ret = new Event(eventName);
            }
            return ret;
          };
    }
}

class yDom {
    static _elem_templates = [];
    static cfgColors = ['#F2F0F0', '#CFCFCF'];

    static suggestRowColor(rowGroup) {
        return yDom.cfgColors[rowGroup % 2];
    }

    static fillElement(aElementID, xData, aLineSpec,
        aFlags) {
        if ((aLineSpec === undefined) || (aLineSpec === null))
            aLineSpec = {};

        if (typeof aFlags == "boolean")
            aFlags = { deleteRows: aFlags };

        aFlags = aFlags || {};

        if (typeof aFlags.deleteRows == 'undefined')
            aFlags.deleteRows = true;
        if (typeof aFlags.paintRows == 'undefined')
            aFlags.paintRows = true;
        if (typeof aFlags.insertAtTop == 'undefined')
            aFlags.insertAtTop = false;

        var idFieldName, colName, cNdx, newRow, canCreateRow,
            aElement = y$(aElementID),
            rowIdOffset = 0,
            first_time = typeof yDom._elem_templates[aElementID] ==
                "undefined";

        /* grants filledEvent exists */
        if ("undefined" == typeof window._evtFilled) {
            window._evtFilled = window.createDOMEvent("filled");
        }

        if (aElement) {

            idFieldName = aLineSpec.idFieldName || aElement.getAttribute(
                "data-id-fieldname") || 'id';
            aElement.setAttribute("data-id-fieldname", idFieldName);
            if (typeof aFlags.unlearn == "boolean")
                first_time = aFlags.unlearn;

            var getDataFromXData = function (xDataItem) {
                /* this function extract the pouchdb data from xDataItem if exists. otherwise, return xDataItem */
                if ((xDataItem.doc) && (xDataItem.id) && (xDataItem.value))
                    xDataItem = xDataItem.doc;
                return xDataItem;
            };

            var saveInplaceData = function (opt, xDataItem) {
                if (typeof aLineSpec.inplaceData != "undefined") {
                    for (var c = 0; c < aLineSpec.inplaceData.length; c++) {
                        if (typeof xDataItem[aLineSpec.inplaceData[c]] !==
                            "undefined") {
                            var colName = aLineSpec.inplaceData[c];
                            opt.setAttribute("data-" + colName, (xDataItem[
                                colName] || ''));
                        }
                    }
                }
            };

            var setNewRowAttributes = function (aNewRow, j, rowGroup) {
                var auxIdSequence,
                    xDataItem = getDataFromXData(xData[j]);

                cNdx = 0;
                if (aNewRow.nodeName == 'TR') {
                    if (aFlags.paintRows)
                        aNewRow.style.backgroundColor = yDom.suggestRowColor(
                            rowGroup);
                }
                if (xDataItem[idFieldName]) {
                    if (y$(xDataItem[idFieldName])) {
                        auxIdSequence = 0;
                        while (y$(xDataItem[idFieldName] + '_' +
                            auxIdSequence))
                            auxIdSequence++;
                        aNewRow.id = xDataItem[idFieldName] + '_' +
                            auxIdSequence;
                    } else
                        aNewRow.id = xDataItem[idFieldName];
                }

                saveInplaceData(aNewRow, xDataItem);

                if ((aLineSpec.onClick) || (aLineSpec.onSelect)) {
                    aNewRow.addEventListener('click', ((aLineSpec.onClick) ||
                        (aLineSpec.onSelect)), false);
                }
            };

            var addCell = function (columnName, auxData) {
                if (columnName !== idFieldName) {
                    var newCell = newRow.insertCell(cNdx);
                    var dataItem = getDataFromXData(auxData);
                    var cellValue = columnName !== null ? yMisc.unmaskHTML(dataItem[columnName] || '') : yMisc.unmaskHTML(dataItem);

                    if (aLineSpec.columns && aLineSpec.columns[columnName]) {
                        var column = aLineSpec.columns[columnName];
                        if (column.align) {
                            newCell.style.textAlign = column.align;
                        }
                        if (column.type) {
                            cellValue = yAnalise('%' + column.type + '(' + cellValue + ')');
                        }
                    }

                    if (!canCreateRow) {
                        newCell.classList.add('warning');
                    }

                    newCell.innerHTML = cellValue.length === 0 ? '&nbsp;' : cellValue;
                    newCell.style.verticalAlign = 'top';
                    newCell.id = aElementID + '_' + cNdx + '_' + oTable.rows.length;
                    newCell.setAttribute('colName', columnName);
                    if (typeof aLineSpec.onNewItem === 'function') {
                        aLineSpec.onNewItem(aElementID, newCell, dataItem);
                    }

                    cNdx++;
                }
            };


            var oTable,  j, cNdx, newCell,
                internalRowId =
                (new Date()).getTime() - 1447265735470,
                rowGroup=0,
                xDataItem;


            if (aElement.nodeName == 'TABLE') {
                oTable = aElement;
                const tbodyElements = aElement.getElementsByTagName('tbody');
                if (tbodyElements.length > 0) {
                    oTable = tbodyElements[0];
                }

                let templateExists = typeof aLineSpec.columns !== 'undefined' ||
                    typeof aLineSpec.rows !== 'undefined' ||
                    typeof aLineSpec.html !== 'undefined';

                if (first_time) {
                    yDom._elem_templates[aElementID] = {};

                    if (!templateExists) {
                        yDom._elem_templates[aElementID].rows = [];
                        for (let i = 0; i < oTable.rows.length; i++) {
                            yDom._elem_templates[aElementID].rows[i] = (oTable.rows[i].innerHTML.trim() + "").replace(/\s+/g, '');
                        }
                    } else {
                        yDom._elem_templates[aElementID].columns = aLineSpec.columns;
                        yDom._elem_templates[aElementID].rows = aLineSpec.rows;
                        yDom._elem_templates[aElementID].html = aLineSpec.html;
                    }
                }

                mergeObject(yDom._elem_templates[aElementID], aLineSpec, true);

                templateExists = typeof aLineSpec.columns !== 'undefined' ||
                typeof aLineSpec.rows !== 'undefined' ||
                typeof aLineSpec.html !== 'undefined';


                if (aFlags.deleteRows) {
                    while (oTable.rows.length > 0) {
                        oTable.deleteRow(oTable.rows.length - 1);
                    }
                } else {
                    rowIdOffset = oTable.rows.length;
                }

                rowGroup = oTable.rows.length % 2;
                cNdx = null;

                for (const j in xData) {
                    if (xData.hasOwnProperty(j)) {
                        const xDataItem = getDataFromXData(xData[j]);
                        rowGroup++;

                        let canCreateRow = true;

                        if (!aFlags.deleteRows && xDataItem[idFieldName]) {
                            for (let i = 0; canCreateRow && i < oTable.rows.length; i++) {
                                if (oTable.rows[i].id == xDataItem[idFieldName]) {
                                    newRow = oTable.rows[i];
                                    while (newRow.cells.length > 0) {
                                        newRow.deleteCell(0);
                                    }
                                    canCreateRow = false;
                                    xDataItem.rowid = i;
                                }
                            }
                        }

                        if (canCreateRow) {
                            newRow = aFlags.insertAtTop ? oTable.insertRow(0) : oTable.insertRow(oTable.rows.length);
                        }

                        internalRowId++;
                        xDataItem.rowid = (!aFlags.insertAtTop && typeof newRow.rowIndex !== "undefined") ? newRow.rowIndex : internalRowId + '';
                        xDataItem._elementid_ = aElementID;

                        setNewRowAttributes(newRow, j, rowGroup);

                        if (typeof aLineSpec.onBeforeNewItem === 'function') {
                            aLineSpec.onBeforeNewItem(aElementID, xDataItem);
                        }

                        if (!templateExists) {
                            if (typeof xDataItem === 'string') {
                                addCell(null, xData[j]);
                            } else {
                                for (const colName in xDataItem) {
                                    if (xDataItem.hasOwnProperty(colName) && colName !== idFieldName && colName !== 'rowid' && colName !== '_elementid_') {
                                        addCell(colName, xData[j]);
                                    }
                                }
                            }
                        } else if (typeof aLineSpec.columns !== 'undefined') {
                            if (Array.isArray(aLineSpec.columns)) {
                                for (const c of aLineSpec.columns) {
                                    addCell(c, xData[j]);
                                }
                            } else {
                                for (const c in aLineSpec.columns) {
                                    if (aLineSpec.columns.hasOwnProperty(c)) {
                                        addCell(c, xData[j]);
                                    }
                                }
                            }
                        } else if (typeof aLineSpec.html !== 'undefined') {
                            newCell = newRow.insertCell(0);
                            newCell.innerHTML = yAnalise(aLineSpec.html, xDataItem);
                            newCell.style.verticalAlign = 'top';
                            newCell.id = `${aElementID}_${cNdx}_${oTable.rows.length}`;

                            if (typeof aLineSpec.onNewItem === 'function') {
                                aLineSpec.onNewItem(aElementID, newCell, xDataItem);
                            }
                        } else if (typeof aLineSpec.rows !== 'undefined') {
                            let firstRow = true;
                            for (const r of aLineSpec.rows) {
                                if (!firstRow) {
                                    newRow = oTable.insertRow(oTable.rows.length);
                                    setNewRowAttributes(newRow, j, rowGroup);
                                }
                                newRow.innerHTML = yAnalise(r, xDataItem);
                                if (!canCreateRow && aFlags.deleteRows) {
                                    for (const c of newRow.cells) {
                                        c.style.borderLeft = 'solid 1px red';
                                    }
                                }
                                if (typeof aLineSpec.onNewItem === 'function') {
                                    aLineSpec.onNewItem(aElementID, newRow, xDataItem);
                                }
                                firstRow = false;
                            }
                        }

                        if (typeof aLineSpec.onNewRowReady === 'function') {
                            aLineSpec.onNewRowReady(aElementID, newRow);
                        }
                    }
                }

                aElement.dispatchEvent(window._evtFilled);
            } else if (aElement.nodeName == 'UL') {
                var ulElement = aElement;

                if (first_time) {
                    // Check if line spec columns, rows, or html are undefined
                    if (!aLineSpec.columns && !aLineSpec.rows && !aLineSpec.html) {
                        // Store rows in element templates if element has children
                        if (aElement.children.length > 0) {
                            yDom._elem_templates[aElementID] = {};
                            yDom._elem_templates[aElementID].rows = Array.from(aElement.children).map(child => child.innerHTML.trim().replace(/\s+/g, ''));
                        }
                    } else {
                        // Store columns, rows, and html in element templates
                        yDom._elem_templates[aElementID] = {
                            columns: aLineSpec.columns,
                            rows: aLineSpec.rows,
                            html: aLineSpec.html
                        };
                    }
                }

                // Merge line spec with element templates
                mergeObject(yDom._elem_templates[aElementID], aLineSpec, true);

                // Delete existing rows if flag is set
                if (aFlags.deleteRows) {
                    ulElement.innerHTML = '';
                }

                // Iterate over xData and create new list items
                for (var key in xData) {
                    if (xData.hasOwnProperty(key)) {
                        var xDataItem = getDataFromXData(xData[key]);

                        // Call onBeforeNewItem if it's a function
                        if (typeof aLineSpec.onBeforeNewItem === 'function') {
                            aLineSpec.onBeforeNewItem(aElementID, xDataItem);
                        }

                        var entry = document.createElement('li');
                        saveInplaceData(entry, xDataItem);

                        var innerText = '';
                        var asHTML = false;

                        if (Array.isArray(aLineSpec.rows)) {
                            // Render rows as inner text
                            for (var r = 0; r < aLineSpec.rows.length; r++) {
                                innerText += yAnalise(aLineSpec.rows[r], xDataItem) + '';
                            }
                            asHTML = true;
                        } else if (typeof aLineSpec.html === 'string') {
                            // Render html as inner text
                            innerText = yAnalise(aLineSpec.html, xDataItem) + '';
                            asHTML = true;
                        } else {
                            // Render other properties as inner text
                            for (var colName in xDataItem) {
                                if (innerText === '') {
                                    if (xDataItem.hasOwnProperty(colName) && colName !== idFieldName && colName !== 'rowid' && colName !== '_elementid_') {
                                        innerText += xDataItem[colName] || '';
                                    }
                                }
                            }
                        }

                        setNewRowAttributes(entry, key, 0);

                        if (asHTML) {
                            entry.innerHTML = innerText;
                        } else {
                            entry.appendChild(document.createTextNode(innerText));
                        }

                        if (typeof aLineSpec.beforeElement === 'string') {
                            var item = y$(aLineSpec.beforeElement);
                            ulElement.insertBefore(entry, item);
                        } else {
                            ulElement.appendChild(entry);
                        }

                        // Call onNewItem if it's a function
                        if (typeof aLineSpec.onNewItem === 'function') {
                            aLineSpec.onNewItem(aElementID, entry, xDataItem);
                        }
                    }
                }

                // Dispatch filled event
                aElement.dispatchEvent(window._evtFilled);


            } else if (aElement.nodeName == 'LISTBOX') {
                const oListBox = aElement;

                if (first_time) {
                    if (typeof aLineSpec.columns === 'undefined' &&
                        typeof aLineSpec.rows === 'undefined' &&
                        typeof aLineSpec.html === 'undefined') {
                        yDom._elem_templates[aElementID] = {};
                        if (oListBox.options.length > 0) {
                            yDom._elem_templates[aElementID].rows = [];
                            for (let i = 0; i < oListBox.options.length; i++) {
                                yDom._elem_templates[aElementID].rows[i] = (oListBox.options[i].innerHTML.trim() + "").replace(/\s+/g, '');
                            }
                        }
                    } else {
                        yDom._elem_templates[aElementID] = {};
                        yDom._elem_templates[aElementID].columns = aLineSpec.columns;
                        yDom._elem_templates[aElementID].rows = aLineSpec.rows;
                        yDom._elem_templates[aElementID].html = aLineSpec.html;
                    }
                }
                mergeObject(yDom._elem_templates[aElementID], aLineSpec, true);

                if (aFlags.deleteRows) {
                    while (oListBox.childElementCount > 0) {
                        oListBox.childNodes[0].remove();
                    }
                }

                let cRow = 0;

                for (const j in xData) {
                    if (xData.hasOwnProperty(j)) {
                        const xDataItem = getDataFromXData(xData[j]);
                        xDataItem._elementid_ = aElementID;
                        if (typeof aLineSpec.onBeforeNewItem === 'function') {
                            aLineSpec.onBeforeNewItem(aElementID, xDataItem);
                        }

                        const newRow = document.createElement('listitem');
                        let cNdx = 0;

                        if (typeof aLineSpec.columns === 'undefined') {
                            if (typeof xDataItem === 'string') {
                                _dumpy(2, 1, "ERRO: yeapf-dom.js - string cell not implemented");
                            } else {
                                for (const colName in xDataItem) {
                                    if (xDataItem.hasOwnProperty(colName) &&
                                        colName !== idFieldName &&
                                        colName !== 'rowid' &&
                                        colName !== '_elementid_') {
                                        const newCell = document.createElement('listcell');
                                        newCell.innerHTML = xDataItem[colName] || '';
                                        newCell.id = `${aElementID}_${cNdx}_${cRow}`;
                                        if (typeof aLineSpec.onNewItem === 'function') {
                                            aLineSpec.onNewItem(aElementID, newCell, xDataItem);
                                        }
                                        cNdx++;
                                        newRow.appendChild(newCell);
                                    }
                                }
                            }
                        } else {
                            for (const colName in aLineSpec.columns) {
                                if (colName !== idFieldName) {
                                    const newCell = document.createElement('listcell');
                                    newCell.innerHTML = xDataItem[colName] || '';
                                    newCell.id = `${aElementID}_${cNdx}_${cRow}`;
                                    if (typeof aLineSpec.onNewItem === 'function') {
                                        aLineSpec.onNewItem(aElementID, newCell, xDataItem);
                                    }
                                    cNdx++;
                                    newRow.appendChild(newCell);
                                }
                            }
                        }
                        saveInplaceData(newRow, xDataItem);
                        oListBox.appendChild(newRow);
                        cRow++;
                    }
                }

                aElement.dispatchEvent(window._evtFilled);

            } else if ((aElement.nodeName == 'SELECT') || (aElement.nodeName ==
                'DATALIST')) {
                if (first_time) {
                    if (typeof aLineSpec.columns === 'undefined' &&
                        typeof aLineSpec.rows === 'undefined' &&
                        typeof aLineSpec.html === 'undefined') {
                        yDom._elem_templates[aElementID] = {};
                        if (aElement.options.length > 1) {
                            yDom._elem_templates[aElementID].rows = [];
                            for (let i = 0; i < aElement.options.length; i++) {
                                yDom._elem_templates[aElementID].rows[i] = (aElement.options[i].innerHTML.trim() + "").replace(/\s+/g, '');
                            }
                        } else if (aElement.options.length === 1) {
                            yDom._elem_templates[aElementID].html = (aElement.options[0].outerHTML.trim() + "").replace(/\s+/g, '');
                        }
                    } else {
                        yDom._elem_templates[aElementID] = {};
                        yDom._elem_templates[aElementID].columns = aLineSpec.columns;
                        yDom._elem_templates[aElementID].rows = aLineSpec.rows;
                        yDom._elem_templates[aElementID].html = aLineSpec.html;
                    }
                }
                mergeObject(yDom._elem_templates[aElementID], aLineSpec, true);

                if (aFlags.deleteRows) {
                    while (aElement.options.length > 0) {
                        aElement.removeChild(aElement.options[0]);
                    }
                }
                let cNdx = 0;

                for (const j in xData) {
                    if (xData.hasOwnProperty(j)) {
                        const xDataItem = getDataFromXData(xData[j]);
                        xDataItem._elementid_ = aElementID;

                        if (typeof aLineSpec.onBeforeNewItem === 'function') {
                            aLineSpec.onBeforeNewItem(aElementID, xDataItem);
                        }

                        let auxHTML = '';
                        if (typeof aLineSpec.html !== 'undefined') {
                            auxHTML = yAnalise(aLineSpec.html, xDataItem);
                        } else if (typeof aLineSpec.columns !== 'undefined') {
                            const sep = aLineSpec.sep || '';
                            if (Array.isArray(aLineSpec.columns)) {
                                for (const c of aLineSpec.columns) {
                                    if (auxHTML !== '') {
                                        auxHTML += sep;
                                    }
                                    auxHTML += (xDataItem[c] || '');
                                }
                            } else {
                                if (typeof xDataItem === 'string') {
                                    _dumpy(2, 1, "ERRO: yeapf-dom.js - string cell not implemented");
                                } else {
                                    for (const colName in aLineSpec.columns) {
                                        if (colName !== idFieldName) {
                                            auxHTML += (xDataItem[colName] || '') + sep;
                                        }
                                    }
                                }
                            }
                        } else {
                            for (const colName in xDataItem) {
                                if (xDataItem.hasOwnProperty(colName) &&
                                    colName !== idFieldName &&
                                    colName !== 'rowid' &&
                                    colName !== '_elementid_') {
                                    auxHTML += (xDataItem[colName] || '');
                                }
                            }
                        }

                        const opt = document.createElement('option');
                        if (typeof xDataItem[idFieldName] !== 'undefined') {
                            if (aElement.nodeName === 'DATALIST') {
                                opt.setAttribute('data-' + idFieldName, xDataItem[idFieldName]);
                            } else {
                                opt.value = xDataItem[idFieldName];
                            }
                        }
                        if (typeof aLineSpec.html === 'undefined') {
                            opt.innerHTML = auxHTML;
                        }
                        opt.id = `${aElementID}_${cNdx}`;
                        saveInplaceData(opt, xDataItem);

                        if (typeof aLineSpec.onNewItem === 'function') {
                            aLineSpec.onNewItem(aElementID, opt, xDataItem);
                        }
                        aElement.appendChild(opt);
                        if (typeof aLineSpec.html !== 'undefined') {
                            opt.outerHTML = auxHTML;
                        }
                        cNdx++;
                    }
                }

                aElement.dispatchEvent(window._evtFilled);

                if (aElement.onclick) {
                    aElement.onclick();
                }

            } else if (aElement.nodeName == 'FORM') {
                var classHasName = function (elem, name) {
                    name = name.toUpperCase();
                    return elem.className.toUpperCase().includes(name);
                };

                var fillFormField = function (element, value) {
                    const fieldType = element.type.toLowerCase();

                    switch (fieldType) {
                        case "tel":
                            element.value = String(value).asPhone();
                            break;
                        case "text":
                        case "password":
                        case "textarea":
                        case "email":
                        case "hidden":
                        case "color":
                        case "date":
                        case "datetime":
                        case "datetime-local":
                        case "month":
                        case "number":
                        case "range":
                        case "search":
                        case "time":
                        case "url":
                        case "week":
                            element.value = value;
                            break;
                        case "radio":
                        case "checkbox":
                            element.checked = element.value === value;
                            break;
                        case "select-one":
                        case "select-multi":
                            for (let i = 0; i < element.options.length; i++) {
                                if (element.options[i].value === value) {
                                    element.selectedIndex = i;
                                    break;
                                }
                            }
                            break;
                    }

                    if (classHasName(element, "cpf")) {
                        element.value = String(value).asCPF();
                    }

                    if (classHasName(element, "cnpj")) {
                        element.value = String(value).asCNPJ();
                    }

                    if (classHasName(element, "rg")) {
                        element.value = String(value).asRG();
                    }

                    if (classHasName(element, "cep")) {
                        element.value = String(value).asCEP();
                    }
                };

                let aElements;

                if (aFlags.deleteRows) {
                    aElements = this.cleanForm(aElementID);
                } else {
                    aElements = this.selectElements(aElementID);
                }

                if (xData) {
                    const xDataArray = Array.isArray(xData) ? xData : [xData];
                    const yData = getDataFromXData(xDataArray[0]);
                    saveInplaceData(aElement, yData);

                    if (typeof aLineSpec.onBeforeNewItem === "function") {
                        aLineSpec.onBeforeNewItem(aElementID, yData);
                    }

                    const fieldPrefix = aLineSpec.elementPrefixName || aLineSpec.prefix || aElement.getAttribute("data-prefix") || "";
                    const fieldPostfix = aLineSpec.elementPostixName || aLineSpec.postfix || aElement.getAttribute("data-postfix") || "";

                    for (let i = 0; i < aElements.length; i++) {
                        const element = aElements[i];
                        const fieldName = suggestKeyName(yData, element.name || element.id, fieldPrefix, fieldPostfix);
                        const colName = (aLineSpec.columns && suggestKeyName(aLineSpec.columns, element.name || element.id)) || null;

                        if (typeof yData[fieldName] !== "undefined") {
                            const fieldValue = yMisc.unmaskHTML(yData[fieldName]);
                            const valueType = element.getAttribute("data-value-type") || element.getAttribute("valueType") || "text";

                            if ((!aLineSpec.columns) || (colName > "")) {
                                if (colName > "") {
                                    if (!Array.isArray(aLineSpec.columns)) {
                                        valueType = aLineSpec.columns[colName].type;

                                        editMask = aLineSpec.columns[colName].editMask || editMask; storageMask = aLineSpec.columns[colName].storageMask || storageMask;
                                    }
                                }

                                if (valueType !== "text") {
                                    if (editMask && storageMask) {
                                        if (valueType.includes("date")) {
                                            fieldValue = dateTransform(fieldValue, storageMask, editMask) || "";
                                        }
                                    } else {
                                        fieldValue = yAnalise(`%${valueType}(${fieldValue})`);
                                    }
                                }

                                fillFormField(element, fieldValue);

                                if (typeof aLineSpec.onNewItem === "function") {
                                    aLineSpec.onNewItem(aElementID, element, yData);
                                }
                            }
                        }
                    }

                    aElement.dispatchEvent(window._evtFilled);
                } else {
                    if (Array.isArray(xData) && xData.length > 1)
                        _dump("There are more than one record returning from the server");
                }

            } else if (aElement.nodeName == 'DIV') {
                if (first_time) {
                    const elemTemplate = yDom._elem_templates[aElementID] || {};
                    if (typeof aLineSpec.columns === "undefined" || typeof aLineSpec.rows === "undefined" || typeof aLineSpec.html === "undefined") {
                        elemTemplate.html = aElement.innerHTML;
                    } else {
                        elemTemplate.columns = aLineSpec.columns;
                        elemTemplate.rows = aLineSpec.rows;
                        elemTemplate.html = aLineSpec.html;
                    }
                    yDom._elem_templates[aElementID] = elemTemplate;
                }

                mergeObject(yDom._elem_templates[aElementID], aLineSpec, true);

                if (aFlags.deleteRows) {
                    aElement.innerHTML = '';
                }

                let auxHTML = aElement.innerHTML;

                if (xData) {
                    for (const xItem of xData) {
                        const xDataItem = getDataFromXData(xItem);
                        saveInplaceData(aElement, xDataItem);

                        if (typeof aLineSpec.onBeforeNewItem === 'function') {
                            aLineSpec.onBeforeNewItem(aElementID, xDataItem);
                        }

                        if (aLineSpec.html) {
                            auxHTML += yAnalise(aLineSpec.html, xDataItem);
                        } else {
                            for (const colName in xDataItem) {
                                if (xDataItem.hasOwnProperty(colName)) {
                                    auxHTML += `<div><div class=tnFieldName><b><small>${colName}</small></b></div>${xDataItem[colName] || ''}</div>`;
                                }
                            }
                        }
                    }

                    aElement.innerHTML = auxHTML;
                    aElement.dispatchEvent(window._evtFilled);
                }
            }
        } else {
            console.error("Element '{0}' not found".format(aElementID));
        }
    }

    static getElementsByAttribute(oRootElem, strTagName,
        strAttributeName, strAttributeValue) {
        console.log("getElementsByAttribute()");
        var arrElements = oRootElem.getElementsByTagName(strTagName);
        var arrReturnElements = [];
        var oAttributeValue = (typeof strAttributeValue != "undefined") ?
            new RegExp("(^|\\s)" + strAttributeValue + "(\\s|$)", "i") :
            null;
        var oCurrent;
        var oAttribute;
        for (var i = 0; i < arrElements.length; i++) {
            oCurrent = arrElements[i];
            oAttribute = oCurrent.getAttribute && oCurrent.getAttribute(
                strAttributeName);
            if (typeof oAttribute == "string" && oAttribute.length > 0) {
                if (typeof strAttributeValue == "undefined" || (
                    oAttributeValue && oAttributeValue.test(oAttribute))) {
                    arrReturnElements.push(oCurrent);
                }
            }
        }
        return arrReturnElements;
    }

    static getStyleRuleValue(className, styleItemName) {
        /* original from http://stackoverflow.com/questions/6338217/get-a-css-value-with-javascript */
        className = className || '';

        for (var i = 0; i < document.styleSheets.length; i++) {
            var mysheet = document.styleSheets[i];
            var myrules = mysheet.cssRules ? mysheet.cssRules : mysheet.rules;
            for (var j = 0; j < myrules.length; j++) {
                if (myrules[j].selectorText && myrules[j].selectorText.toLowerCase() ===
                    className) {
                    if (typeof styleItemName == "string")
                        return myrules[j].style[styleItemName];
                    else
                        return myrules[j].style;
                }
            }

        }
    }

    static setStyleRuleValue(className, styleItemName, value) {
        /* original from http://stackoverflow.com/questions/6338217/get-a-css-value-with-javascript */
        className = className || '';

        for (var i = 0; i < document.styleSheets.length; i++) {
            var mysheet = document.styleSheets[i];
            var myrules = mysheet.cssRules ? mysheet.cssRules : mysheet.rules;
            for (var j = 0; j < myrules.length; j++) {
                if (myrules[j].selectorText && myrules[j].selectorText.toLowerCase() ===
                    className) {
                    if (typeof styleItemName == "string")
                        myrules[j].style[styleItemName] = value;
                    else
                        myrules[j].style = value;
                }
            }

        }
    }

    static createStyleRule(className, styleDefinition) {
        var style = document.createElement('style');
        style.type = 'text/css';
        aux = '.{0} {'.format(className);
        for (var e in styleDefinition) {
            if (styleDefinition.hasOwnProperty(e)) {
                aux += "\n\t{0}: {1};".format(e, styleDefinition[e]);
            }
        }
        aux += '}\n';

        style.innerHTML = aux;
        document.getElementsByTagName('head')[0].appendChild(style);
    }

    static extractStyleRule(className) {
        var ret = {};
        var k = getStyleRuleValue(className);
        for (var j in k) {
            if (k.hasOwnProperty(j))
                if (k[j] > '')
                    if (!isNumber(j))
                        ret[j] = k[j];
        }
        return ret;
    }

    static getClientSize() {
        var auxDE = (document && document.documentElement) ? document.documentElement :
            { clientWidth: 800, clientHeight: 600 };
        var auxW = (window) ? window : { innerWidth: 800, innerHeight: 600 };
        var w = Math.max(auxDE.clientWidth, auxW.innerWidth || 0);
        var h = Math.max(auxDE.clientHeight, auxW.innerHeight || 0);
        return [w, h];
    }


    static resizeIframe(obj, objMargin) {
        objMargin = objMargin || {};

        objMargin.width = objMargin.width || 0;
        objMargin.height = objMargin.height || 0;

        var s1, s2, bestSize, onResize;

        s1 = screen.height;
        s2 = (obj.contentWindow || obj.contentDocument || obj).document
            .body.scrollHeight + 40 - objMargin.height;
        bestSize = Math.max(s1, s2);
        obj.style.height = bestSize + 'px';

        s1 = screen.width;
        s2 = (obj.contentWindow || obj.contentDocument || obj).document
            .body.scrollWidth + 40 - objMargin.width;
        bestSize = Math.max(s1, s2);
        obj.style.width = bestSize + 'px';

        onResize = obj.getAttribute('onResize');
        if (onResize) {
            eval(onResize);
        }
    };


};