<?php

declare(strict_types=1);

/**
 * This file is part of the GEO-6 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    GNU General Public License v3.0
 */

namespace Geo6\Text;

use ErrorException;

class Text
{
    /**
     * @author Paul Butler <github@paulbutler.org>
     * @author Mike Robinson
     * @author Jonathan Beliën <jbe@geo6.be>
     *
     * @see https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
     *
     * @param string|array $old
     * @param string|array $new
     */
    public static function diff($old, $new): array
    {
        if (!is_array($old)) {
            $old = preg_split("/[\s]+/", $old);
        }
        if (!is_array($new)) {
            $new = preg_split("/[\s]+/", $new);
        }

        $oldNormalized = array_map(function (string $str) {
            return strtoupper(self::removeAccents($str));
        }, $old);
        $newNormalized = array_map(function (string $str) {
            return strtoupper(self::removeAccents($str));
        }, $new);

        $matrix = [];
        $maxlen = 0;

        foreach($oldNormalized as $oindex => $ovalue){
            $nkeys = array_keys($newNormalized, $ovalue);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = (isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1);

                if ($matrix[$oindex][$nindex] > $maxlen){
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }

        if ($maxlen == 0) {
            return [
                [
                    'deleted' => $old,
                    'inserted' => $new,
                ]
            ];
        }

        return array_merge(
            self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
    }

    /**
     * @author Paul Butler <github@paulbutler.org>
     * @author Mike Robinson
     * @author Jonathan Beliën <jbe@geo6.be>
     *
     * @see https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
     */
    public static function renderDiff(array $diff): array
    {
        $returnOld = '';
        $returnNew = '';

        foreach ($diff as $k) {
            if (is_array($k)) {
                $returnOld .= (!empty($k['deleted']) ? '<del>'.implode(' ', $k['deleted']).'</del> ' : '');
                $returnNew .= (!empty($k['inserted']) ? '<ins>'.implode(' ', $k['inserted']).'</ins> ' : '');
            } else {
                $returnOld .= $k . ' ';
                $returnNew .= $k . ' ';
            }
        }
        return [
            'old' => $returnOld,
            'new' => $returnNew,
        ];
    }


    /**
     * @author Olivier Laviale <olivier.laviale@gmail.com>
     * @link http://weirdog.com/blog/php/supprimer-les-accents-des-caracteres-accentues.html
     */
    public static function removeAccents(string $str, string $charset = 'utf-8'): string
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        return $str;
    }
}
