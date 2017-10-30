<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\Db\Adapter\Pdo;

use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;

class Mysql extends PdoMysql
{
    private const MYSQL_GONE_AWAY_EXCEPTION = 'MySQL server has gone away';

    /**
     * {@inheritdoc}
     */
    public function query($sqlStatement, $bindParams = null, $bindTypes = null)
    {
        try {
            return parent::query($sqlStatement, $bindParams, $bindTypes);
        } catch (\Exception $e) {
            if ($this->isGoneAwayException($e)) {
                $this->reconnect();

                return parent::query($sqlStatement, $bindParams, $bindTypes);
            } else {
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute($sqlStatement, $bindParams = null, $bindTypes = null): bool
    {
        try {
            return parent::execute($sqlStatement, $bindParams, $bindTypes);
        } catch (\Exception $e) {
            if ($this->isGoneAwayException($e)) {
                $this->reconnect();

                return parent::execute($sqlStatement, $bindParams, $bindTypes);
            } else {
                throw $e;
            }
        }
    }

    public function reconnect(): void
    {
        $this->close();
        $this->connect();
    }

    private function isGoneAwayException(\Exception $exception)
    {
        return false !== stripos($exception->getMessage(), self::MYSQL_GONE_AWAY_EXCEPTION);
    }
}
