<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Cache\NullCache;
use Yiisoft\Db\Connection;
use Yiisoft\Db\Tests\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    /**
     * @var [type]
     */
    protected $database;

    /**
     * @var string the driver name of this test class. Must be set by a subclass.
     */
    protected $driverName;

    /**
     * @var Connection
     */
    private $db;

    protected function setUp(): void
    {
        if ($this->driverName === null) {
            throw new \Exception('driverName is not set for a DatabaseTestCase.');
        }

        parent::setUp();

        $databases = self::getParam('databases');

        $this->database = $databases[$this->driverName];

        $pdo_database = 'pdo_' . $this->driverName;

        if ($this->driverName === 'oci') {
            $pdo_database = 'oci8';
        }

        if (!\extension_loaded('pdo') || !\extension_loaded($pdo_database)) {
            $this->markTestSkipped('pdo and '.$pdo_database.' extension are required.');
        }

        //$this->mockApplication();
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            $this->db->close();
        }

        //$this->destroyApplication();
    }

    /**
     * @param bool $reset whether to clean up the test database
     * @param bool $open  whether to open and populate test database
     *
     * @return \Yiisoft\Db\Connection
     */
    public function getConnection($reset = true, $open = true)
    {
        if (!$reset && $this->db) {
            return $this->db;
        }

        $config = $this->database;

        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }

        try {
            $this->db = $this->prepareDatabase($config, $fixture, $open);
        } catch (\Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: '.$e->getMessage());
        }

        return $this->db;
    }

    public function prepareDatabase($config, $fixture, $open = true)
    {
        if (!isset($config['__class'])) {
            $config['__class'] = \Yiisoft\Db\Connection::class;
        }

        /* @var $db \Yiisoft\Db\Connection */
        $db = new Connection($config['dsn']);
        $db->setUsername($config['username']);
        $db->setPassword($config['password']);

        if (!$open) {
            return $db;
        }

        $db->open();

        if ($fixture !== null) {
            if ($this->driverName === 'oci') {
                [$drops, $creates] = explode('/* STATEMENTS */', file_get_contents($fixture), 2);
                [$statements, $triggers, $data] = explode('/* TRIGGERS */', $creates, 3);
                $lines = array_merge(explode('--', $drops), explode(';', $statements), explode('/', $triggers), explode(';', $data));
            } else {
                $lines = explode(';', file_get_contents($fixture));
            }
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $db->pdo->exec($line);
                }
            }
        }

        return $db;
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param $sql
     *
     * @return mixed
     */
    protected function replaceQuotes($sql)
    {
        switch ($this->driverName) {
            case 'mysql':
            case 'sqlite':
                return str_replace(['[[', ']]'], '`', $sql);
            case 'oci':
                return str_replace(['[[', ']]'], '"', $sql);
            case 'pgsql':
                // more complex replacement needed to not conflict with postgres array syntax
                return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))\]\])/', '"', $sql));
            case 'sqlsrv':
                return str_replace(['[[', ']]'], ['[', ']'], $sql);
            default:
                return $sql;
        }
    }

    /**
     * @return \Yiisoft\Db\Connection
     */
    protected function getConnectionWithInvalidSlave()
    {
        $config = array_merge($this->database, [
            'serverStatusCache' => new NullCache(),
            'slaves'            => [
                [], // invalid config
            ],
        ]);

        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }

        return $this->prepareDatabase($config, $fixture, true);
    }
}
