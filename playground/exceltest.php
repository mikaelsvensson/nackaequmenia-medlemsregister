<?php
// Test CVS
echo ":";
require_once 'phpexcelreader/Excel/reader.php';
echo ":";

// ExcelFile($filename, $encoding);
$data = new Spreadsheet_Excel_Reader();

echo ":";
// Set output Encoding.
$data->setOutputEncoding('CP1251');
echo ":";
/***
* if you want you can change 'iconv' to mb_convert_encoding:
* $data->setUTFEncoder('mb');
*
**/

/***
* By default rows & cols indeces start with 1
* For change initial index use:
* $data->setRowColOffset(0);
*
**/



/***
*  Some function for formatting output.
* $data->setDefaultFormat('%.2f');
* setDefaultFormat - set format for columns with unknown formatting
*
* $data->setColumnFormat(4, '%.3f');
* setColumnFormat - set format for column (apply only to number fields)
*
**/

$data->read('G3%5FAdresslista.xls');
echo ":";
/*


 $data->sheets[0]['numRows'] - count rows
 $data->sheets[0]['numCols'] - count columns
 $data->sheets[0]['cells'][$i][$j] - data from $i-row $j-column

 $data->sheets[0]['cellsInfo'][$i][$j] - extended info about cell
    
    $data->sheets[0]['cellsInfo'][$i][$j]['type'] = "date" | "number" | "unknown"
        if 'type' == "unknown" - use 'raw' value, because  cell contain value with format '0.00';
    $data->sheets[0]['cellsInfo'][$i][$j]['raw'] = value if cell without format 
    $data->sheets[0]['cellsInfo'][$i][$j]['colspan'] 
    $data->sheets[0]['cellsInfo'][$i][$j]['rowspan'] 
*/

error_reporting(E_ALL ^ E_NOTICE);
echo ":";
for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
    for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
        echo "\"".$data->sheets[0]['cells'][$i][$j]."\",";
    }
    echo "\n";

}
echo ":";


//print_r($data);
//print_r($data->formatRecords);
?>
