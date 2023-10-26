<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';


$myRecord = new \YeAPF\SanitizedKeyData();

echo "This first example shows the usage of a single key\n";
echo "whitout any constraint\n";
echo "In such way, it can change it type freely\n";
echo "    \$myRecord = new YeAPF\SanitizedKeyData();\n";
echo "    \$myRecord->aux = 120;\n";

$myRecord->aux = 120;
echo "@".__LINE__."\n";
echo "aux is: ".$myRecord->aux."\n";
echo "    \$myRecord->aux = 'foo';\n";

$myRecord->aux = "foo";
echo "@".__LINE__."\n";
echo "aux is: ".$myRecord->aux."\n";

echo "@".__LINE__."\n";
echo "\nNow, let's use a constraint\n";
echo "It's expected to receive an exception when trying to put a string value\n";
echo "    unset(\$myRecord->aux);\n";
unset($myRecord->aux);
$myRecord->setConstraint("aux", YeAPF_TYPE_INT);
echo "    \$myRecord->setConstraint('aux', YeAPF_TYPE_INT);\n";
echo "    \$myRecord->aux = 120;\n";

try {
    $myRecord->aux = 120;
    echo "at line ".__LINE__." aux is: ".$myRecord->aux."\n";
    echo "    \$myRecord->aux = 'foo';\n";


    $myRecord->aux = "foo";
    echo "at line ".__LINE__." aux is: ".$myRecord->aux."\n";
} catch (\Throwable $th) {
    echo "  ***> It was generated an exception: \"".$th->getMessage()."\" when trying\n";
    echo "  to set a string value to an integer.\n";
}

echo "@".__LINE__."\n";
echo "\nLet's try to assign a null value\n";
echo "(It cannot be accepted and an exception should be risen)\n";
echo "    \$myRecord->aux = null;\n";
try {
$myRecord -> aux = null;
} catch (\Throwable $th) {
    echo "  ***> It was generated an exception: \"".$th->getMessage()."\" when trying\n";
    echo "  to set a null value to an integer that does not accept null.\n";
}

echo "@".__LINE__."\n";
echo "\nLet's change the constraint\n";
echo "    unset(\$myRecord->aux);\n";
unset($myRecord->aux);
$myRecord->setConstraint("aux", YeAPF_TYPE_INT, true);
echo "    \$myRecord->setConstraint('aux', YeAPF_TYPE_INT, true);\n";
echo "Now let's try to assign a null value again\n";
echo "    \$myRecord->aux = null;\n";
try {
  $myRecord -> aux = null;
} catch (\Throwable $th) {
    echo "  ***> It was generated an exception: \"".$th->getMessage()."\" when trying\n";
    echo "  to set a null value to an integer that does not accept null.\n";
}

echo "@".__LINE__."\n";
echo "\nLet's try to assign a float value\n";
echo "(Again, it cannot be accepted)\n";
echo "    \$myRecord->aux = 250.4;\n";
try {
    $myRecord -> aux = 250.4;
} catch (\Throwable $th) {
    echo "  ***> It was generated an exception: \"".$th->getMessage()."\" when trying\n";
    echo "  to set a float value to an integer that does not accept float.\n";
}

$values = [
    null,
    true,
    false,
    "foo",
    50,
    2723,
    5791935,
    183.23,
    0.009,
    "2023-05-23",
    "2022-02-30",
    "2022-04-19 10:43:23",
    "2018-12-32 23:59:59",
    "15:31:59",
    "23:59:60"
];
echo "\n---------------------------\n";
echo "@".__LINE__."\n";
echo "Now let's try to use a set of values on this key with this constraint\n";
echo "Here are our values:\n  ";
echo json_encode($values);
echo "\nand here is our constraint:\n";
echo json_encode($myRecord->getConstraint("aux"), JSON_PRETTY_PRINT);
echo "\nThe next table shows the result of trying to use these values\n"        ;
echo "in the same way as before. The second column is the triggered exception number\n";

echo "".str_pad("value", 20, " ", STR_PAD_LEFT)." | exception | final value\n";
foreach($values as $v) {
    if (is_null($v)){
        $v_tag = "NULL";
    } else if (is_bool($v)) {
        $v_tag = $v?"TRUE":"FALSE";
    } else {
        $v_tag = $v;
    }
    echo  str_pad("$v_tag", 20, " ", STR_PAD_LEFT);
    echo " | ";
    try {
        $myRecord -> aux = $v;
        echo "         ";
    } catch (\Throwable $th) {
        $ret = (string)  $th->getCode();
        echo  str_pad($ret, 9, " ", STR_PAD_LEFT);
    }
    echo " | ", is_null($myRecord->aux)?'NULL':(is_bool($myRecord->aux)?($myRecord->aux?"TRUE":"FALSE"):$myRecord->aux);
    echo "\n";
}

echo "@".__LINE__."\n";
echo "\nAnd now let's define a set of variables constraints in this record\n";
echo "so we can apply those values to these keys\n";
$myRecord->setConstraint('A', YeAPF_TYPE_FLOAT, true);
$myRecord->setConstraint('B', YeAPF_TYPE_FLOAT, false);
$myRecord->setConstraint(keyName:'C', keyType:YeAPF_TYPE_FLOAT, acceptNULL:false, minValue:-10.5, maxValue:183.00);
$myRecord->setConstraint('D', YeAPF_TYPE_INT, true);
$myRecord->setConstraint('E', YeAPF_TYPE_INT, false, null, null, 0, 100);
$myRecord->setConstraint('F', YeAPF_TYPE_BOOL, true);
$myRecord->setConstraint('G', YeAPF_TYPE_DATE, false);
$myRecord->setConstraint('H', YeAPF_TYPE_TIME, false);
$myRecord->setConstraint('I', YeAPF_TYPE_DATETIME, false);
$myRecord->setConstraint('J', YeAPF_TYPE_DATETIME, true);

echo "  \$myRecord->setConstraint('A', YeAPF_TYPE_FLOAT, true);\n";
echo "  \$myRecord->setConstraint('B', YeAPF_TYPE_FLOAT, false);\n";
echo "  \$myRecord->setConstraint(keyName:'C', keyType:YeAPF_TYPE_FLOAT, acceptNULL:false, \$minValue=-10.5, \$maxValue=183.00);\n";
echo "  \$myRecord->setConstraint('D', YeAPF_TYPE_INT, true);\n";
echo "  \$myRecord->setConstraint('E', YeAPF_TYPE_INT, false,  null, null, 0, 100);\n";
echo "etc..\n";

echo "@".__LINE__."\n";
echo "\nNow we can see how the keys are defined using getConstraint()\n";
echo "and try to put those values into these keys\n";

$myVars = range('A', 'J');
foreach($myVars as $k) {
    echo "  $k as ".json_encode($myRecord->getConstraint($k))."\n";
    echo "".str_pad("value", 20, " ", STR_PAD_LEFT)." | exception | final value\n";
    foreach($values as $v) {
        if (is_null($v)){
            $v_tag = "NULL";
        } else if (is_bool($v)) {
            $v_tag = $v?"TRUE":"FALSE";
        } else {
            $v_tag = $v;
        }
        echo  str_pad("$v_tag", 20, " ", STR_PAD_LEFT);
        echo " | ";
        try {
            $myRecord -> $k = $v;
            echo "         ";
        } catch (\Throwable $th) {
            $ret = (string)  $th->getCode();
            echo  str_pad($ret, 9, " ", STR_PAD_LEFT);
        }
        echo " | ", is_null($myRecord->$k)?'NULL':(is_bool($myRecord->$k)?($myRecord->$k?"TRUE":"FALSE"):$myRecord->$k);
        echo "\n";
    }
    echo "\n";
}
