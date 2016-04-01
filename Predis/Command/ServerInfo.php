<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @link http://redis.io/commands/info
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerInfo extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'INFO';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $info = array();
        $infoLines = preg_split('/\r?\n/', $data);

        foreach ($infoLines as $row) {
            @list($k, $v) = explode(':', $row);

            if ($row === '' || !isset($v)) {
                continue;
            }

            if (!preg_match('/^db\d+$/', $k)) {
                if ($k === 'allocation_stats') {
                    $info[$k] = $this->parseAllocationStats($v);
                    continue;
                }

                $info[$k] = $v;
            } else {
                $info[$k] = $this->parseDatabaseStats($v);
            }
        }

        return $info;
    }

    /**
     * Extracts the statistics of each logical DB from the string buffer.
     *
     * @param  string $str Response buffer.
     * @return array
     */
    protected function parseDatabaseStats($str)
    {
        $db = array();

        foreach (explode(',', $str) as $dbvar) {
            list($dbvk, $dbvv) = explode('=', $dbvar);
            $db[trim($dbvk)] = $dbvv;
        }

        return $db;
    }

    /**
     * Parses the response and extracts the allocation statistics.
     *
     * @param  string $str Response buffer.
     * @return array
     */
    protected function parseAllocationStats($str)
    {
        $stats = array();

        foreach (explode(',', $str) as $kv) {
            @list($size, $objects, $extra) = explode('=', $kv);

            // hack to prevent incorrect values when parsing the >=256 key
            if (isset($extra)) {
                $size = ">=$objects";
                $objects = $extra;
            }

            $stats[$size] = $objects;
        }

        return $stats;
    }
}
