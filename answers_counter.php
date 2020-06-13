<?php
print_mem("Start");

require __DIR__ . '/bootstrap/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$host = $_ENV["DB_HOST"] . ":" . $_ENV["DB_PORT"];
$user = $_ENV["DB_USERNAME"];
$pass = $_ENV["DB_PASSWORD"];
$db_name = $_ENV["DB_DATABASE"];

$mysqli = mysqli_connect($host, $user, $pass, $db_name);

$lipsum = new joshtronic\LoremIpsum();

$test_title = "Test title ";
//foreach(range(1, 250) as $i) {
//    $test_title .= "a";
//}

print_mem("After init");

function progress_bar($done, $total, $info = "", $width = 50)
{
    $perc = round(($done * 100) / $total);
    $bar = round(($width * $perc) / 100);
    return sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width - $bar), $info);
}

function gen_random_values_for_insert_to_threads($n, $max_titles)
{
    global $lipsum, $test_title;
    $s = "";
    foreach (range(1, $n) as $i) {
        $t = rand(1, $max_titles);
        $title = "$test_title $t";
        $body = $lipsum->words(rand(1, 4));
        if ($s) $s .= ",\n";
        $s .= "(1, 1, '$title', '$body')";
    }
    return $s;
}

$__title_id = 1;

function gen_values_for_insert_to_threads($n)
{
    global $lipsum, $test_title, $__title_id;
    $s = "";
    foreach (range(1, $n) as $i) {
        $title = "$test_title $__title_id";
        $__title_id += 1;
        $body = $lipsum->words(rand(1, 4));
        if ($s) $s .= ",\n";
        $s .= "(1, 1, '$title', '$body')";
    }
    return $s;
}

function gen_values_for_insert_to_replies($n)
{
    global $lipsum, $test_title, $__title_id;
    $s = "";
    foreach (range(1, $n) as $i) {
        $title = "$test_title $__title_id";
        $__title_id += 1;
        $body = $lipsum->words(rand(1, 4));
        if ($s) $s .= ",\n";
        $s .= "(1, 1, '$title', '$body')";
    }
    return $s;
}

function fill_threads_table($n, $N, $max_titles = 100)
{
    global $mysqli;
    mysqli_query($mysqli, "ALTER TABLE `threads` AUTO_INCREMENT = 1");
    foreach (range(1, $N) as $i) {
        echo progress_bar($i, $N, "Filling threads");
        progress_bar($i, $N, "Filling threads");
        $values = gen_values_for_insert_to_threads($n);
        mysqli_query($mysqli, "INSERT INTO `threads` (user_id, channel_id, title, body) VALUES $values");
    }
    print_mem("After filling threads");
}

function fill_replies_table($n, $N, $max_titles = 100)
{
    global $mysqli;
    mysqli_query($mysqli, "ALTER TABLE `replies` AUTO_INCREMENT = 1");
    foreach (range(1, $N) as $i) {
        echo progress_bar($i, $N, "Filling replies_");
        progress_bar($i, $N, "Filling replies_");
        $values = gen_values_for_insert_to_threads($n);
        mysqli_query($mysqli, "INSERT INTO `replies` (thread_id, user_id, body) VALUES $values");
    }
    print_mem("After filling threads");
}

function get_titles($a, $b) {
    global $mysqli;
    $res = mysqli_query($mysqli, "SELECT (title) FROM `threads` LIMIT $a,$b");
    $titles = [];
    while($row = $res->fetch_assoc()) {
        array_push($titles, $row['title']);
    }
    return $titles;
}

function main()
{
    $titles = get_titles(0, 10000);
}

function print_mem($title = "")
{
    echo "\n\n$title\n";
    $mem_usage = memory_get_usage();
    $mem_peak = memory_get_peak_usage();
    echo "\tThe script is now using: " . round($mem_usage / 1024 / 1024) . "MB of memory.\n";
    echo "\tPeak usage: " . round($mem_peak / 1024 / 1024) . "MB of memory.";
    echo "\n\n\n";
}

fill_threads_table(3000, 10);
//main();

print_mem("end");
