<?php
namespace Shedi;

use Shedi\RuntimeException;

class Util
{
    public static function splitFile($infile, $outdir)
    {
        if (!file_exists($infile)) {
            throw new RuntimeException("File not found: $infile");
        }

        if (!is_writable($outdir)) {
            throw new RuntimeException("Not writable: $outdir");
        }

        $currentState = 0;
        $nextState    = 0;

        $types = array(
            '130' => array(),
            '131' => array(),
            '997' => array(),
            '189' => array(),
            'other' => array(),
        );

        $handles = array();

        foreach ($types as $k => $v)
        {
            $handles[$k] = fopen("{$outdir}/{$k}.txt", 'w');
            fwrite($handles[$k], 'ISA|' . PHP_EOL);
            fwrite($handles[$k], 'GS|' . PHP_EOL);
        }

        $tokens = self::getTokens();
        $fsa    = self::getStateTable();

        $lines = file($infile);

        foreach ($lines as $line) {
            if (substr($line, 0, 3) == 'ISA') {
                $delimiter = substr($line, 3, 1);
            }

            $line = trim($line);
            $line = substr($line, 0, -1);
            $line = str_replace($delimiter, '|', $line);

            list($token, $rest) = explode('|', $line, 2);

            if (!in_array($token, $tokens)) {
                $token = 'ANY';
            }

            $nextState = $fsa[$currentState][$token];

            switch ($nextState) {
                case 1:
                    // start of Envelope (ISA)
                    $currentState = $nextState;
                    break;

                case 2:
                    // start of Functional Group (GS)
                    $currentState = $nextState;
                    break;

                case 3:
                    // start of Transaction Set (ST)
                    $data = explode('|', $rest);

                    $type = $data[0];

                    if (!array_key_exists($type, $types)) {
                        $type = 'other';
                    }

                    fwrite($handles[$type], $line . PHP_EOL);
                    $currentState = $nextState;
                    break;

                case 4:
                    // Transaction Set data
                    fwrite($handles[$type], $line . PHP_EOL);
                    $currentState = $nextState;
                    break;

                case 5:
                    // end of ST (SE)
                    fwrite($handles[$type], $line . PHP_EOL);
                    $currentState = $nextState;
                    break;

                case 6:
                    // end of GS (GE)
                    //fwrite($handles[$type], $line . PHP_EOL);
                    $currentState = $nextState;
                    break;

                case 0:
                    // end of Envelope
                    //fwrite($handles[$type], $line . PHP_EOL);
                    $currentState = 0;
                    break;

                default:
                    trigger_error('Unknown State');
                    break;
            }
        }

        foreach ($types as $k => $v)
        {
            fwrite($handles[$k], 'GE|' . PHP_EOL);
            fwrite($handles[$k], 'IEA|' . PHP_EOL);
            fclose($handles[$k]);
        }
    }

    private static function getStateTable()
    {
        $fsa = array(
            0 => array(),
            1 => array(),
            2 => array(),
            3 => array(),
            4 => array(),
            5 => array(),
            6 => array(),
            100 => 'ERROR',
        );

        $fsa[0]['ISA'] = 1;   $fsa[1]['ISA'] = 100; $fsa[2]['ISA'] = 100; $fsa[3]['ISA'] = 100;
        $fsa[0]['GS']  = 100; $fsa[1]['GS']  = 2;   $fsa[2]['GS']  = 100; $fsa[3]['GS']  = 100;
        $fsa[0]['ST']  = 100; $fsa[1]['ST']  = 100; $fsa[2]['ST']  = 3;   $fsa[3]['ST']  = 100;
        $fsa[0]['ANY'] = 100; $fsa[1]['ANY'] = 100; $fsa[2]['ANY'] = 100; $fsa[3]['ANY'] = 4;
        $fsa[0]['SE']  = 100; $fsa[1]['SE']  = 100; $fsa[2]['SE']  = 100; $fsa[3]['SE']  = 100;
        $fsa[0]['GE']  = 100; $fsa[1]['GE']  = 100; $fsa[2]['GE']  = 100; $fsa[3]['GE']  = 100;
        $fsa[0]['IEA'] = 100; $fsa[1]['IEA'] = 100; $fsa[2]['IEA'] = 100; $fsa[3]['IEA'] = 100;

        $fsa[4]['ISA'] = 100; $fsa[5]['ISA'] = 100; $fsa[6]['ISA'] = 100;
        $fsa[4]['GS']  = 100; $fsa[5]['GS']  = 100; $fsa[6]['GS']  = 2;
        $fsa[4]['ST']  = 100; $fsa[5]['ST']  = 3;   $fsa[6]['ST']  = 100;
        $fsa[4]['ANY'] = 4;   $fsa[5]['ANY'] = 100; $fsa[6]['ANY'] = 100;
        $fsa[4]['SE']  = 5;   $fsa[5]['SE']  = 100; $fsa[6]['SE']  = 100;
        $fsa[4]['GE']  = 100; $fsa[5]['GE']  = 6;   $fsa[6]['GE']  = 100;
        $fsa[4]['IEA'] = 100; $fsa[5]['IEA'] = 100; $fsa[6]['IEA'] = 0;

        return $fsa;
    }

    private static function getTokens()
    {
        return array(
            'ISA',
            'GS',
            'ST',
            'SE',
            'GE',
            'IEA',
        );
    }
}

