<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profile;

/**
 * Server profile for Redis 1.2.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion120 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\KeyExists',
            'DEL' => 'Predis\Command\KeyDelete',
            'TYPE' => 'Predis\Command\KeyType',
            'KEYS' => 'Predis\Command\KeyKeysV12x',
            'RANDOMKEY' => 'Predis\Command\KeyRandom',
            'RENAME' => 'Predis\Command\KeyRename',
            'RENAMENX' => 'Predis\Command\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\KeyExpire',
            'EXPIREAT' => 'Predis\Command\KeyExpireAt',
            'TTL' => 'Predis\Command\KeyTimeToLive',
            'MOVE' => 'Predis\Command\KeyMove',
            'SORT' => 'Predis\Command\KeySort',

            /* commands operating on string values */
            'SET' => 'Predis\Command\StringSet',
            'SETNX' => 'Predis\Command\StringSetPreserve',
            'MSET' => 'Predis\Command\StringSetMultiple',
            'MSETNX' => 'Predis\Command\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\StringGet',
            'MGET' => 'Predis\Command\StringGetMultiple',
            'GETSET' => 'Predis\Command\StringGetSet',
            'INCR' => 'Predis\Command\StringIncrement',
            'INCRBY' => 'Predis\Command\StringIncrementBy',
            'DECR' => 'Predis\Command\StringDecrement',
            'DECRBY' => 'Predis\Command\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\ListPushTail',
            'LPUSH' => 'Predis\Command\ListPushHead',
            'LLEN' => 'Predis\Command\ListLength',
            'LRANGE' => 'Predis\Command\ListRange',
            'LTRIM' => 'Predis\Command\ListTrim',
            'LINDEX' => 'Predis\Command\ListIndex',
            'LSET' => 'Predis\Command\ListSet',
            'LREM' => 'Predis\Command\ListRemove',
            'LPOP' => 'Predis\Command\ListPopFirst',
            'RPOP' => 'Predis\Command\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\SetAdd',
            'SREM' => 'Predis\Command\SetRemove',
            'SPOP' => 'Predis\Command\SetPop',
            'SMOVE' => 'Predis\Command\SetMove',
            'SCARD' => 'Predis\Command\SetCardinality',
            'SISMEMBER' => 'Predis\Command\SetIsMember',
            'SINTER' => 'Predis\Command\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\SetIntersectionStore',
            'SUNION' => 'Predis\Command\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\SetUnionStore',
            'SDIFF' => 'Predis\Command\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\ZSetRemove',
            'ZRANGE' => 'Predis\Command\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\ConnectionPing',
            'AUTH' => 'Predis\Command\ConnectionAuth',
            'SELECT' => 'Predis\Command\ConnectionSelect',
            'ECHO' => 'Predis\Command\ConnectionEcho',
            'QUIT' => 'Predis\Command\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\ServerInfo',
            'SLAVEOF' => 'Predis\Command\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\ServerMonitor',
            'DBSIZE' => 'Predis\Command\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\ServerFlushAll',
            'SAVE' => 'Predis\Command\ServerSave',
            'BGSAVE' => 'Predis\Command\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\ServerBackgroundRewriteAOF',
        );
    }
}
