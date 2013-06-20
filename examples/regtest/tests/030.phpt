--TEST--
HandlerSocketIndex: not-found
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/../common/config.php';

$mysql = get_mysql_connection();

init_mysql_testdb($mysql);

$table = 'hstesttbl';
$tablesize = 100;
$sql = sprintf(
    'CREATE TABLE %s ( ' .
    'k varchar(30) PRIMARY KEY, ' .
    'v varchar(30) NOT NULL) ' .
    'Engine = innodb',
    mysql_real_escape_string($table));
if (!mysql_query($sql, $mysql))
{
    die(mysql_error());
}

$valmap = array();

for ($i = 0; $i < $tablesize; $i++)
{
    $k = 'k' . $i;
    $v = 'v' . _rand($i) . $i;

    $sql = sprintf(
        'INSERT INTO ' . $table . ' values (\'%s\', \'%s\')',
        mysql_real_escape_string($k),
        mysql_real_escape_string($v));
    if (!mysql_query($sql, $mysql))
    {
        break;
    }

    $valmap[$k] = $v;
}

try
{
    $hs = new HandlerSocket(MYSQL_HOST, MYSQL_HANDLERSOCKET_PORT);
    $index = $hs->createIndex(1, MYSQL_DBNAME, $table, '', 'k,v');
}
catch (HandlerSocketException $exception)
{
    echo $exception->getMessage(), PHP_EOL;
    die();
}

//found
$retval = $index->find(array('=' => 'k5'), 1, 0);
_dump($retval);

//not found
$retval = $index->find(array('=' => 'k000000'), 1, 0);
_dump($retval);


mysql_close($mysql);

function _rand($i = 0)
{
    $rand = array(102, 635, 803, 925, 775, 537, 592, 414, 590, 302, 704, 751,
                  367, 400, 397, 170, 719, 734, 587, 494, 523, 954, 433, 820,
                  283, 837, 205, 415, 545, 583, 52, 323, 614, 679, 805, 451,
                  115, 269, 218, 617, 878, 345, 512, 969, 408, 291, 858, 953,
                  710, 142, 682, 934, 621, 965, 574, 204, 298, 134, 983, 444,
                  144, 152, 187, 215, 8, 697, 651, 280, 701, 537, 413, 69, 86,
                  822, 670, 370, 806, 688, 26, 66, 802, 171, 557, 847, 777,
                  730, 987, 115, 646, 496, 120, 684, 374, 65, 370, 174, 828,
                  867, 759, 703);
    return $rand[$i];
}

function _dump($data = array())
{
    if (empty($data))
    {
        echo '[0]', PHP_EOL;
    }
    else
    {
        foreach ($data as $key => $value)
        {
            echo '[', $key, ']';
            foreach ($value as $val)
            {
                if (is_array($val))
                {
                    foreach ($val as $v)
                    {
                        echo '[', $v, ']';
                    }
                }
                else
                {
                    echo '[', $val, ']';
                }
            }
            echo PHP_EOL;
        }
    }
}
--EXPECT--
[0][k5][v5375]
[0]
