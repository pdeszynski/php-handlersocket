--TEST--
HandlerSocketIndex: nulls
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
    'k int PRIMARY KEY, ' .
    'v1 varchar(30), ' .
    'v2 varchar(30), ' .
    'key idxv1 (v1)) ' .
    'Engine = innodb',
    mysql_real_escape_string($table));
if (!mysql_query($sql, $mysql))
{
    die(mysql_error());
}

$valmap = array();

for ($i = 0; $i < $tablesize; $i++)
{
    $k = (string)$i;
    $v1 = '1v' . _rand($i, 1) . $i;
    $v2 = '2v' . _rand($i, 2) . $i;

    if ($i % 10 == 3)
    {
        $sql = sprintf(
            'INSERT INTO ' . $table . ' values (%d, NULL, \'%s\')',
            mysql_real_escape_string($k),
            mysql_real_escape_string($v2));
        $v1 = null;
    }
    else
    {
        $sql = sprintf(
            'INSERT INTO ' . $table . ' values (%d, \'%s\', \'%s\')',
            mysql_real_escape_string($k),
            mysql_real_escape_string($v1),
            mysql_real_escape_string($v2));
    }
    if (!mysql_query($sql, $mysql))
    {
        break;
    }

    $valmap[$k] = $v1;
}

echo 'MY', PHP_EOL;
$sql = 'SELECT k,v1,v2 FROM ' . $table . ' ORDER BY k';
$result = mysql_query($sql, $mysql);
if ($result)
{
    while ($row = mysql_fetch_assoc($result))
    {
        if ($row['v1'] == null)
        {
            $row['v1'] = '[NULL]';
        }

        echo $row['k'], ' ', $row['v1'], ' ', $row['v2'], PHP_EOL;
    }
}
mysql_free_result($result);


echo 'HS', PHP_EOL;
try
{
    $hs = new HandlerSocket(MYSQL_HOST, MYSQL_HANDLERSOCKET_PORT);
    $index = $hs->createIndex(1, MYSQL_DBNAME, $table, '', 'k,v1,v2');
}
catch (HandlerSocketException $exception)
{
    echo $exception->getMessage(), PHP_EOL;
    die();
}

$retval = $index->find(array('>=' => ''), 10000, 0);

for ($i = 0; $i < $tablesize; $i++)
{
    $k = $retval[$i][0];
    $v1 = $retval[$i][1];
    $v2 = $retval[$i][2];

    if ($v1 == null)
    {
        $v1 = '[NULL]';
    }

    echo $k, ' ', $v1, ' ', $v2, PHP_EOL;

}

echo '2ndIDX', PHP_EOL;
try
{
    $index = $hs->createIndex(2, MYSQL_DBNAME, $table, 'idxv1', 'k,v1,v2');
}
catch (HandlerSocketException $exception)
{
    echo $exception->getMessage(), PHP_EOL;
    die();
}

for ($i = 0; $i < $tablesize; $i++)
{
    $k = (string)$i;
    $v1 = $valmap[$k];

    if ($v1 == null)
    {
        continue;
    }

    $retval = $index->find(array('=' => $v1), 1, 0);

    $ret_k = $retval[0][0];
    $ret_v1 = $retval[0][1];
    $ret_v2 = $retval[0][2];

    echo '2ndidx ', $k, ' ', $v1, ' => ', $ret_k, ' ', $ret_v1, ' ', $ret_v2, PHP_EOL;
}


echo '2ndIDX NULL', PHP_EOL;

$retval = $index->find(array('=' => null), 10000, 0);

$rvals = array();
$count = count($retval);

for ($i = 0; $i < $count; $i++)
{
    $k = $retval[$i][0];
    $v1 = $retval[$i][1];
    $v2 = $retval[$i][2];

    $rvals[$k] = array($k, $v1, $v2);
}

asort($rvals);

foreach ($rvals as $i => $val)
{
    echo '2ndidxnull ', $val[0], ' ', $val[2], PHP_EOL;
}

mysql_close($mysql);

function _rand($i = 0, $j = 1)
{
    if ($j == 1)
    {
        $rand = array(102, 803, 775, 592, 590, 704, 367, 397, 719, 587, 523,
                      433, 283, 205, 545, 52, 614, 805, 115, 218, 878, 512,
                      408, 858, 710, 682, 621, 574, 298, 983, 144, 187, 8,
                      651, 701, 413, 86, 670, 806, 26, 802, 557, 777, 987,
                      646, 120, 374, 370, 828, 759, 843, 401, 367, 887, 799,
                      539, 560, 579, 887, 345, 430, 845, 624, 828, 53, 809,
                      175, 61, 358, 627, 46, 119, 14, 226, 875, 827, 598, 770,
                      538, 357, 36, 331, 769, 465, 696, 406, 395, 36, 160, 759,
                      31, 650, 852, 577, 813, 87, 196, 79, 323, 37);
    }
    else
    {
        $rand = array(635, 925, 537, 414, 302, 751, 400, 170, 734, 494, 954,
                      820, 837, 415, 583, 323, 679, 451, 269, 617, 345, 969,
                      291, 953, 142, 934, 965, 204, 134, 444, 152, 215, 697,
                      280, 537, 69, 822, 370, 688, 66, 171, 847, 730, 115,
                      496, 684, 65, 174, 867, 703, 942, 362, 307, 167, 829,
                      379, 858, 26, 507, 898, 801, 325, 480, 676, 736, 270,
                      839, 222, 505, 906, 981, 4, 480, 405, 639, 344, 563, 516,
                      548, 322, 370, 815, 668, 281, 452, 1, 324, 738, 79, 657,
                      783, 824, 68, 546, 775, 696, 380, 751, 217, 701);
    }

    return $rand[$i];
}

function _dump($data = array())
{
    foreach ($data as $value)
    {
        foreach ($value as $key => $val)
        {
            echo '[', $key, ']';
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

--EXPECT--
MY
0 1v1020 2v6350
1 1v8031 2v9251
2 1v7752 2v5372
3 [NULL] 2v4143
4 1v5904 2v3024
5 1v7045 2v7515
6 1v3676 2v4006
7 1v3977 2v1707
8 1v7198 2v7348
9 1v5879 2v4949
10 1v52310 2v95410
11 1v43311 2v82011
12 1v28312 2v83712
13 [NULL] 2v41513
14 1v54514 2v58314
15 1v5215 2v32315
16 1v61416 2v67916
17 1v80517 2v45117
18 1v11518 2v26918
19 1v21819 2v61719
20 1v87820 2v34520
21 1v51221 2v96921
22 1v40822 2v29122
23 [NULL] 2v95323
24 1v71024 2v14224
25 1v68225 2v93425
26 1v62126 2v96526
27 1v57427 2v20427
28 1v29828 2v13428
29 1v98329 2v44429
30 1v14430 2v15230
31 1v18731 2v21531
32 1v832 2v69732
33 [NULL] 2v28033
34 1v70134 2v53734
35 1v41335 2v6935
36 1v8636 2v82236
37 1v67037 2v37037
38 1v80638 2v68838
39 1v2639 2v6639
40 1v80240 2v17140
41 1v55741 2v84741
42 1v77742 2v73042
43 [NULL] 2v11543
44 1v64644 2v49644
45 1v12045 2v68445
46 1v37446 2v6546
47 1v37047 2v17447
48 1v82848 2v86748
49 1v75949 2v70349
50 1v84350 2v94250
51 1v40151 2v36251
52 1v36752 2v30752
53 [NULL] 2v16753
54 1v79954 2v82954
55 1v53955 2v37955
56 1v56056 2v85856
57 1v57957 2v2657
58 1v88758 2v50758
59 1v34559 2v89859
60 1v43060 2v80160
61 1v84561 2v32561
62 1v62462 2v48062
63 [NULL] 2v67663
64 1v5364 2v73664
65 1v80965 2v27065
66 1v17566 2v83966
67 1v6167 2v22267
68 1v35868 2v50568
69 1v62769 2v90669
70 1v4670 2v98170
71 1v11971 2v471
72 1v1472 2v48072
73 [NULL] 2v40573
74 1v87574 2v63974
75 1v82775 2v34475
76 1v59876 2v56376
77 1v77077 2v51677
78 1v53878 2v54878
79 1v35779 2v32279
80 1v3680 2v37080
81 1v33181 2v81581
82 1v76982 2v66882
83 [NULL] 2v28183
84 1v69684 2v45284
85 1v40685 2v185
86 1v39586 2v32486
87 1v3687 2v73887
88 1v16088 2v7988
89 1v75989 2v65789
90 1v3190 2v78390
91 1v65091 2v82491
92 1v85292 2v6892
93 [NULL] 2v54693
94 1v81394 2v77594
95 1v8795 2v69695
96 1v19696 2v38096
97 1v7997 2v75197
98 1v32398 2v21798
99 1v3799 2v70199
HS
0 1v1020 2v6350
1 1v8031 2v9251
2 1v7752 2v5372
3 [NULL] 2v4143
4 1v5904 2v3024
5 1v7045 2v7515
6 1v3676 2v4006
7 1v3977 2v1707
8 1v7198 2v7348
9 1v5879 2v4949
10 1v52310 2v95410
11 1v43311 2v82011
12 1v28312 2v83712
13 [NULL] 2v41513
14 1v54514 2v58314
15 1v5215 2v32315
16 1v61416 2v67916
17 1v80517 2v45117
18 1v11518 2v26918
19 1v21819 2v61719
20 1v87820 2v34520
21 1v51221 2v96921
22 1v40822 2v29122
23 [NULL] 2v95323
24 1v71024 2v14224
25 1v68225 2v93425
26 1v62126 2v96526
27 1v57427 2v20427
28 1v29828 2v13428
29 1v98329 2v44429
30 1v14430 2v15230
31 1v18731 2v21531
32 1v832 2v69732
33 [NULL] 2v28033
34 1v70134 2v53734
35 1v41335 2v6935
36 1v8636 2v82236
37 1v67037 2v37037
38 1v80638 2v68838
39 1v2639 2v6639
40 1v80240 2v17140
41 1v55741 2v84741
42 1v77742 2v73042
43 [NULL] 2v11543
44 1v64644 2v49644
45 1v12045 2v68445
46 1v37446 2v6546
47 1v37047 2v17447
48 1v82848 2v86748
49 1v75949 2v70349
50 1v84350 2v94250
51 1v40151 2v36251
52 1v36752 2v30752
53 [NULL] 2v16753
54 1v79954 2v82954
55 1v53955 2v37955
56 1v56056 2v85856
57 1v57957 2v2657
58 1v88758 2v50758
59 1v34559 2v89859
60 1v43060 2v80160
61 1v84561 2v32561
62 1v62462 2v48062
63 [NULL] 2v67663
64 1v5364 2v73664
65 1v80965 2v27065
66 1v17566 2v83966
67 1v6167 2v22267
68 1v35868 2v50568
69 1v62769 2v90669
70 1v4670 2v98170
71 1v11971 2v471
72 1v1472 2v48072
73 [NULL] 2v40573
74 1v87574 2v63974
75 1v82775 2v34475
76 1v59876 2v56376
77 1v77077 2v51677
78 1v53878 2v54878
79 1v35779 2v32279
80 1v3680 2v37080
81 1v33181 2v81581
82 1v76982 2v66882
83 [NULL] 2v28183
84 1v69684 2v45284
85 1v40685 2v185
86 1v39586 2v32486
87 1v3687 2v73887
88 1v16088 2v7988
89 1v75989 2v65789
90 1v3190 2v78390
91 1v65091 2v82491
92 1v85292 2v6892
93 [NULL] 2v54693
94 1v81394 2v77594
95 1v8795 2v69695
96 1v19696 2v38096
97 1v7997 2v75197
98 1v32398 2v21798
99 1v3799 2v70199
2ndIDX
2ndidx 0 1v1020 => 0 1v1020 2v6350
2ndidx 1 1v8031 => 1 1v8031 2v9251
2ndidx 2 1v7752 => 2 1v7752 2v5372
2ndidx 4 1v5904 => 4 1v5904 2v3024
2ndidx 5 1v7045 => 5 1v7045 2v7515
2ndidx 6 1v3676 => 6 1v3676 2v4006
2ndidx 7 1v3977 => 7 1v3977 2v1707
2ndidx 8 1v7198 => 8 1v7198 2v7348
2ndidx 9 1v5879 => 9 1v5879 2v4949
2ndidx 10 1v52310 => 10 1v52310 2v95410
2ndidx 11 1v43311 => 11 1v43311 2v82011
2ndidx 12 1v28312 => 12 1v28312 2v83712
2ndidx 14 1v54514 => 14 1v54514 2v58314
2ndidx 15 1v5215 => 15 1v5215 2v32315
2ndidx 16 1v61416 => 16 1v61416 2v67916
2ndidx 17 1v80517 => 17 1v80517 2v45117
2ndidx 18 1v11518 => 18 1v11518 2v26918
2ndidx 19 1v21819 => 19 1v21819 2v61719
2ndidx 20 1v87820 => 20 1v87820 2v34520
2ndidx 21 1v51221 => 21 1v51221 2v96921
2ndidx 22 1v40822 => 22 1v40822 2v29122
2ndidx 24 1v71024 => 24 1v71024 2v14224
2ndidx 25 1v68225 => 25 1v68225 2v93425
2ndidx 26 1v62126 => 26 1v62126 2v96526
2ndidx 27 1v57427 => 27 1v57427 2v20427
2ndidx 28 1v29828 => 28 1v29828 2v13428
2ndidx 29 1v98329 => 29 1v98329 2v44429
2ndidx 30 1v14430 => 30 1v14430 2v15230
2ndidx 31 1v18731 => 31 1v18731 2v21531
2ndidx 32 1v832 => 32 1v832 2v69732
2ndidx 34 1v70134 => 34 1v70134 2v53734
2ndidx 35 1v41335 => 35 1v41335 2v6935
2ndidx 36 1v8636 => 36 1v8636 2v82236
2ndidx 37 1v67037 => 37 1v67037 2v37037
2ndidx 38 1v80638 => 38 1v80638 2v68838
2ndidx 39 1v2639 => 39 1v2639 2v6639
2ndidx 40 1v80240 => 40 1v80240 2v17140
2ndidx 41 1v55741 => 41 1v55741 2v84741
2ndidx 42 1v77742 => 42 1v77742 2v73042
2ndidx 44 1v64644 => 44 1v64644 2v49644
2ndidx 45 1v12045 => 45 1v12045 2v68445
2ndidx 46 1v37446 => 46 1v37446 2v6546
2ndidx 47 1v37047 => 47 1v37047 2v17447
2ndidx 48 1v82848 => 48 1v82848 2v86748
2ndidx 49 1v75949 => 49 1v75949 2v70349
2ndidx 50 1v84350 => 50 1v84350 2v94250
2ndidx 51 1v40151 => 51 1v40151 2v36251
2ndidx 52 1v36752 => 52 1v36752 2v30752
2ndidx 54 1v79954 => 54 1v79954 2v82954
2ndidx 55 1v53955 => 55 1v53955 2v37955
2ndidx 56 1v56056 => 56 1v56056 2v85856
2ndidx 57 1v57957 => 57 1v57957 2v2657
2ndidx 58 1v88758 => 58 1v88758 2v50758
2ndidx 59 1v34559 => 59 1v34559 2v89859
2ndidx 60 1v43060 => 60 1v43060 2v80160
2ndidx 61 1v84561 => 61 1v84561 2v32561
2ndidx 62 1v62462 => 62 1v62462 2v48062
2ndidx 64 1v5364 => 64 1v5364 2v73664
2ndidx 65 1v80965 => 65 1v80965 2v27065
2ndidx 66 1v17566 => 66 1v17566 2v83966
2ndidx 67 1v6167 => 67 1v6167 2v22267
2ndidx 68 1v35868 => 68 1v35868 2v50568
2ndidx 69 1v62769 => 69 1v62769 2v90669
2ndidx 70 1v4670 => 70 1v4670 2v98170
2ndidx 71 1v11971 => 71 1v11971 2v471
2ndidx 72 1v1472 => 72 1v1472 2v48072
2ndidx 74 1v87574 => 74 1v87574 2v63974
2ndidx 75 1v82775 => 75 1v82775 2v34475
2ndidx 76 1v59876 => 76 1v59876 2v56376
2ndidx 77 1v77077 => 77 1v77077 2v51677
2ndidx 78 1v53878 => 78 1v53878 2v54878
2ndidx 79 1v35779 => 79 1v35779 2v32279
2ndidx 80 1v3680 => 80 1v3680 2v37080
2ndidx 81 1v33181 => 81 1v33181 2v81581
2ndidx 82 1v76982 => 82 1v76982 2v66882
2ndidx 84 1v69684 => 84 1v69684 2v45284
2ndidx 85 1v40685 => 85 1v40685 2v185
2ndidx 86 1v39586 => 86 1v39586 2v32486
2ndidx 87 1v3687 => 87 1v3687 2v73887
2ndidx 88 1v16088 => 88 1v16088 2v7988
2ndidx 89 1v75989 => 89 1v75989 2v65789
2ndidx 90 1v3190 => 90 1v3190 2v78390
2ndidx 91 1v65091 => 91 1v65091 2v82491
2ndidx 92 1v85292 => 92 1v85292 2v6892
2ndidx 94 1v81394 => 94 1v81394 2v77594
2ndidx 95 1v8795 => 95 1v8795 2v69695
2ndidx 96 1v19696 => 96 1v19696 2v38096
2ndidx 97 1v7997 => 97 1v7997 2v75197
2ndidx 98 1v32398 => 98 1v32398 2v21798
2ndidx 99 1v3799 => 99 1v3799 2v70199
2ndIDX NULL
2ndidxnull 3 2v4143
2ndidxnull 13 2v41513
2ndidxnull 23 2v95323
2ndidxnull 33 2v28033
2ndidxnull 43 2v11543
2ndidxnull 53 2v16753
2ndidxnull 63 2v67663
2ndidxnull 73 2v40573
2ndidxnull 83 2v28183
2ndidxnull 93 2v54693
