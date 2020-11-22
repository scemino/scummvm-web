<?php
namespace ScummVM;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../include/Constants.php';

use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\Yaml\Yaml;

/**
 * DataUtils
 * This class pulls down the latest data from the ScummVM Data spreadsheet
 * and converts it to YAML files used to power the site.
 */
class DataUtils
{
    const SHEET_URL = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQamumX0p-DYQa5Umi3RxX-pHM6RZhAj1qvUP0jTmaqutN9FwzyriRSXlO9rq6kR60pGIuPvCDzZL3s/pub?output=tsv';
    // filename => sheet id
    const SHEET_IDS = [
        'platforms' => '1061029686',
        'compatibility' => '854570757',
        'games' => '1946612063',
        'engines' => '0',
        'companies' => '226191984',
        'versions' => '1225902887',
        'game_demos' => '713475305',
        'series' => '1095671818',
        'screenshots' => '1985243204',
        'downloads' => '373699606',
        'game_resources' => '1287892109',
    ];


    /**
     * Gets the TSV representation from sheets and converts it to YAML on file
     *
     * @return void
     */
    public function getAllData()
    {
        foreach (self::SHEET_IDS as $name => $gid) {
            $tsv = \file_get_contents(self::SHEET_URL . "&gid=" . $gid);
            $reader = Reader::createFromString($tsv);
            $reader->setDelimiter("\t");
            $reader->setHeaderOffset(0);
            $stmt = new Statement();

            $records = $stmt->process($reader);
            // Convert to JSON because records are serializable
            // and cannot be converted directly to yaml
            $json = \json_encode($records);
            $data = \json_decode($json, true);

            // Convert TRUE/FALSE strings to Booleans
            foreach ($data as $objKey => $obj) {
                foreach ($obj as $key => $val) {
                    if ($val === 'TRUE') {
                        $data[$objKey][$key] = true;
                    } elseif ($val === 'FALSE') {
                        $data[$objKey][$key] = false;
                    }
                }
            }

            // Convert to YAML
            $yaml = Yaml::dump($data);
            $yaml = "# This is a generated file, please do not edit manually\n" . $yaml;
            $outFile = DIR_DATA . "/" . DEFAULT_LOCALE . "/$name.yaml";
            echo("Writing $name data to $outFile\n");
            \file_put_contents($outFile, $yaml);
        }
    }
}

$dataUtils = new DataUtils();
$dataUtils->getAllData();
