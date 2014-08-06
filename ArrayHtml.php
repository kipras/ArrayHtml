<?php

/**
 * Class ArrayHtml
 *
 * PHP class for pretty-printing nested arrays/objects using HTML, CSS and JavaScript, providing buttons
 * to expand/collapse each level. Supports printing recursive objects, i.e. objects that have children
 * that point back to the parent object.
 *
 * If a printed object has a __toArray() method - that method will be used to retrieve pretty-printed
 * data from the object, instead of get_object_vars()
 *
 * Requirements:
 *      PHP >= 5.3 (uses static:: keyword)
 *
 * @version 1.0
 */
class ArrayHtml
{
    /**
     * @param mixed $data
     */
    public static function show($data)
    {
        echo static::get($data);
    }

    protected static function _getArrayDebugData(
        $item, &$debugItems = Array(), &$debugItemInstanceIndexes = Array()
        , &$debugItemSourceList = Array())
    {
        $existingIndex = array_search($item, $debugItemSourceList, TRUE);
        if ($existingIndex !== FALSE) {
            $instanceIndex = count($debugItemInstanceIndexes);
            $debugItemInstanceIndexes[] = $existingIndex;
            return $instanceIndex;
        }

        $index = count($debugItemSourceList);
        $debugItemSourceList[$index] =& $item;

        $debugItem = Array(
            'type' => gettype($item),
            'expands' => FALSE,
            'valCount' => 0, // This is used when 'expands' == TRUE
            'val' => NULL,
        );
        if ($item instanceof Traversable) {
            $debugItem['type'] = get_class($item);
            $debugItem['expands'] = TRUE;
            foreach ($item as $name => $val) {
                $debugItem['val']["[{$name}]"] = static::_getArrayDebugData(
                    $val, $debugItems, $debugItemInstanceIndexes
                    , $debugItemSourceList);
            }
            $debugItem['valCount'] = count($debugItem['val']);
        } else if (is_array($item)) {
            $debugItem['expands'] = TRUE;
            foreach ($item as $name => $val) {
                $debugItem['val']["[{$name}]"] = static::_getArrayDebugData(
                    $val, $debugItems, $debugItemInstanceIndexes
                    , $debugItemSourceList);
            }
            $debugItem['valCount'] = count($debugItem['val']);
        } else if (is_object($item)) {
            $debugItem['type'] = get_class($item);
            $debugItem['expands'] = TRUE;
            if (method_exists($item, '__toArray')) {
                $props = $item->__toArray();
            } else {
                $props = get_object_vars($item);
            }
            foreach ($props as $name => $val) {
                $debugItem['val']["->{$name}"] = static::_getArrayDebugData(
                    $val, $debugItems, $debugItemInstanceIndexes
                    , $debugItemSourceList);
            }
            $debugItem['valCount'] = count($debugItem['val']);
        } else if ($item === NULL) {
            $debugItem['val'] = 'NULL';
        } else if ($item === TRUE) {
            $debugItem['val'] = 'TRUE';
        } else if ($item === FALSE) {
            $debugItem['val'] = 'FALSE';
        } else {
            $debugItem['val'] = $item;
        }

        $debugItems[$index] = $debugItem;

        $instanceIndex = count($debugItemInstanceIndexes);
        $debugItemInstanceIndexes[] = $index;

        return $instanceIndex;
    }

    /**
     * @param mixed $item
     * @return string
     */
    public static function get($item)
    {
        $debugItems = Array();
        $debugItemInstanceIndexes = Array();
        $rootItemIndex = static::_getArrayDebugData($item, $debugItems, $debugItemInstanceIndexes);
        $rootItem = $debugItems[$debugItemInstanceIndexes[$rootItemIndex]];

        static $_arrayHtmlIndex = 0;
        $_arrayHtmlIndex++;
        $divId = "__arrayHtml_{$_arrayHtmlIndex}";

        ob_start();
        ?>

        <style type="text/css">
            /*<![CDATA[*/
            .__arrayHtml {
                font-family:arial;
                font-size:11px;
                border:1px solid #000000;

            }
            .__arrayHtml .__arrayHtmlRow {
                padding-left:20px;
            }
            .__arrayHtml .__arrayHtmlFieldName {
                padding:0 5px;
            }
            .__arrayHtml .__arrayHtmlValue {
                font-weight:bold;
            }

            .__arrayHtml .__arrayHtmlPlus,
            .__arrayHtml .__arrayHtmlMinus {
                border:1px solid #666666;
                font-size:20px;
                line-height:12px;
                width:12px;
                height:12px;
                cursor:pointer;
                text-align:center;

                display:-moz-inline-stack;
                display:inline-block;
                zoom:1;
                *display:inline;
            }
            .__arrayHtml .__arrayHtmlMinus span {
                position:relative;
                top:-2px;
            }
            /*]]>*/
        </style>

        <div id="<?php echo $divId ?>" class="__arrayHtml">
            <div class="__arrayHtmlRow" id="__arrayHtmlTr_<?php echo $_arrayHtmlIndex?>_<?php echo $rootItemIndex ?>">
        <span>
            <?php if ($rootItem['expands']): ?>
                <span class="__arrayHtmlPlus" id="__arrayHtmlPlusMinus_<?php echo $_arrayHtmlIndex?>_<?php echo $rootItemIndex ?>"><span>+</span></span>
            <?php endif ?>
        </span>

                <span class="__arrayHtmlFieldName"> :</span>
        <span class="__arrayHtmlValue">
            <?php if ($rootItem['expands']): ?>
                <?php echo $rootItem['type'] ?> (<?php echo $rootItem['valCount'] ?>)
            <?php else: ?>
                <?php echo $rootItem['val'] ?>
            <?php endif ?>
        </span>
            </div>
        </div>

        <script type="text/javascript">
            /*<![CDATA[*/
            (function ()
            {
                var arrayHtmlDiv = document.getElementById('<?php echo $divId ?>');
                var debugItems = <?php echo @json_encode($debugItems) ?>;
                var debugItemInstanceIndexes = <?php echo json_encode($debugItemInstanceIndexes) ?>;

                var isArray = Array.isArray || function( obj ) {
                    return jQuery.type(obj) === "array";
                };

                var rowExpandStatus = {
                    <?php echo $rootItemIndex ?>: false
                };

                function rowPlusInit(index, keySoFar, indexSoFar)
                {
                    var rowIndex = indexSoFar + index;
                    var row = document.getElementById('__arrayHtmlTr_<?php echo $_arrayHtmlIndex?>_' + rowIndex);
                    var plusDiv = document.getElementById('__arrayHtmlPlusMinus_<?php echo $_arrayHtmlIndex?>_' + rowIndex);
                    plusDiv.onclick = function ()
                    {
                        if (rowExpandStatus[rowIndex] == false) {
                            // Expand
                            var rowInnerItems = debugItems[debugItemInstanceIndexes[index]].val;
                            var itemTr = null;
                            for (var key in rowInnerItems) {
                                if (rowInnerItems.hasOwnProperty(key)) {
                                    var itemIndex = rowInnerItems[key];
                                    var itemRowIndex = rowIndex + '_' + itemIndex;
                                    var itemTrId = '__arrayHtmlTr_<?php echo $_arrayHtmlIndex?>_' + itemRowIndex;
                                    var previousItemTr = itemTr;
                                    itemTr = document.getElementById(itemTrId);
                                    if (itemTr) {
                                        itemTr.style.display = 'block';
                                        showRowChildren(itemIndex, itemRowIndex);
                                    } else {
                                        // Create a new table row
                                        rowExpandStatus[itemRowIndex] = false;

                                        var itemData = debugItems[debugItemInstanceIndexes[itemIndex]];
                                        var rowToInsertAfter = previousItemTr ? previousItemTr : row;
                                        itemTr = document.createElement('div');
                                        itemTr.className = '__arrayHtmlRow';
                                        itemTr.id = itemTrId;
                                        row.appendChild(itemTr);
                                        var plusMinusTd = document.createElement('span');
                                        itemTr.appendChild(plusMinusTd);
                                        if (itemData.expands) {
                                            plusMinusTd.innerHTML = '<span class="__arrayHtmlPlus" id="__arrayHtmlPlusMinus_<?php echo $_arrayHtmlIndex?>_' + itemRowIndex + '"><span>+</span></span>';
                                            rowPlusInit(itemIndex, keySoFar + key, rowIndex + '_');
                                        }

                                        var keyTd = document.createElement('span');
                                        keyTd.className = '__arrayHtmlFieldName';
                                        itemTr.appendChild(keyTd);
                                        /*var keyShown;
                                         if (isArray(rowInnerItems))
                                         keyShown = '[' + key + ']';
                                         else
                                         keyShown = '->' + key;
                                         keyTd.innerHTML = keyShown;*/
                                        keyTd.innerHTML = keySoFar + key + ' :';

                                        var dataTd = document.createElement('span');
                                        dataTd.className = '__arrayHtmlValue';
                                        itemTr.appendChild(dataTd);
                                        if (itemData.expands) {
                                            dataTd.innerHTML = itemData.type + ' (' + itemData.valCount + ')';
                                        } else {
                                            dataTd.innerHTML = itemData.val;
                                        }
                                    }
                                }
                            }


                            this.innerHTML = '<span>&ndash;</span>';
                            rowExpandStatus[rowIndex] = true;
                        } else {
                            // Collapse
                            hideRowChildren(index, rowIndex);

                            rowExpandStatus[rowIndex] = false;
                            var plusDiv = document.getElementById('__arrayHtmlPlusMinus_<?php echo $_arrayHtmlIndex?>_' + rowIndex);
                            plusDiv.innerHTML = '<span>+</span>';
                        }
                    }
                }

                // Recursively hides a row
                function hideRowChildren(index, rowIndex)
                {
                    if (rowExpandStatus[rowIndex] !== undefined && rowExpandStatus[rowIndex] == true) {
                        var rowInnerItems = debugItems[debugItemInstanceIndexes[index]].val;
                        for (var key in rowInnerItems) {
                            if (rowInnerItems.hasOwnProperty(key)) {
                                var itemIndex = rowInnerItems[key];
                                var itemRowIndex = rowIndex + '_' + itemIndex;
                                var innerRow = document.getElementById('__arrayHtmlTr_<?php echo $_arrayHtmlIndex?>_' + itemRowIndex);
                                if (innerRow) {
                                    innerRow.style.display = 'none';
                                }

                                hideRowChildren(itemIndex, itemRowIndex);
                            }
                        }
                    }
                }

                // Recursively shows a row
                function showRowChildren(index, rowIndex)
                {
                    if (rowExpandStatus[rowIndex] !== undefined && rowExpandStatus[rowIndex] == true) {
                        var rowInnerItems = debugItems[debugItemInstanceIndexes[index]].val;
                        for (var key in rowInnerItems) {
                            if (rowInnerItems.hasOwnProperty(key)) {
                                var itemIndex = rowInnerItems[key];
                                var itemRowIndex = rowIndex + '_' + itemIndex;
                                var innerRow = document.getElementById('__arrayHtmlTr_<?php echo $_arrayHtmlIndex?>_' + itemRowIndex);
                                if (innerRow) {
                                    innerRow.style.display = 'block';
                                }

                                showRowChildren(itemIndex, itemRowIndex);
                            }
                        }
                    }
                }


                var rootRow = document.getElementById('__arrayHtmlTr_<?php echo $_arrayHtmlIndex?>_<?php echo $rootItemIndex ?>');
                var rootItemData = debugItems[debugItemInstanceIndexes[<?php echo $rootItemIndex ?>]];
                if (rootItemData.expands) {
                    rowPlusInit(<?php echo $rootItemIndex ?>, '', '');
                }
            })();
            /*]]>*/
        </script>

        <?php
        return ob_get_clean();
    }
}
