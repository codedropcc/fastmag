<?php

namespace Fastmag;

use Fastmag\Exception;

class Csv
{
    public static function array2csv(array &$array, $filePath, $header = false, $delimiter = ';')
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen($filePath, 'w');
        if (!$header)
            $header = array_keys(reset($array));
        fputcsv($df, $header, $delimiter);
        foreach ($array as $row) {
            fputcsv($df, $row, $delimiter);
        }
        fclose($df);
        return ob_get_clean();
    }

    public static function csvToArray($file, $delimiter = null)
    {
        $rows = array();
        $headers = array();
        if ($delimiter === null)
            $delimiter = ',';
        if (file_exists($file) && is_readable($file)) {
            $handle = fopen($file, 'r');
            while (!feof($handle)) {
                $row = fgetcsv($handle, 10240, $delimiter, '"');
                if (empty($headers))
                    $headers = $row;
                else if (is_array($row)) {
                    array_splice($row, count($headers));
                    $rows[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }
        else {
            throw new Exception($file . ' doesn`t exist or is not readable.');
        }
        return $rows;
    }
}