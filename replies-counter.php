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
$title_id = 1;

$filename_with_result = "replies-counter-result.txt";

print_mem("After init");

function progress_bar($done, $total, $info = "", $width = 50)
{
    $perc = round(($done * 100) / $total);
    $bar = round(($width * $perc) / 100);
    return sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width - $bar), $info);
}


function make_insert_query_of_random_values_for_threads($n)
{
    global $lipsum, $test_title, $title_id;
    $values = "";
    foreach (range(1, $n) as $i) {
        $title = "$test_title $title_id";
        $title_id += 1;
        $body = $lipsum->words(rand(1, 4));
        $replies_count = rand(0, 1000);
        if ($values) $values .= ",\n";
        $values .= "(1, 1, '$title', '$body', $replies_count)";
    }
    return "INSERT INTO `threads` (user_id, channel_id, title, body, replies_count) VALUES $values";
}

function fill_threads_table($n, $N, $max_titles = 100)
{
    global $mysqli;
    mysqli_query($mysqli, "ALTER TABLE `threads` AUTO_INCREMENT = 1");
    foreach (range(1, $N) as $i) {
        echo progress_bar($i, $N, "Filling threads");
        $insert_query = make_insert_query_of_random_values_for_threads($n);
        mysqli_query($mysqli, $insert_query);
    }
    print_mem("After filling threads");
}

function get_titles_with_replies_count($start, $count) {
    global $mysqli;
    $res = mysqli_query($mysqli, "SELECT title, replies_count FROM `threads` LIMIT $start,$count");
    $titles_with_replies_count = [];
    while($row = $res->fetch_assoc()) {
        array_push($titles_with_replies_count, $row);
    }
    return $titles_with_replies_count;
}

function add_titles_with_replies_count_to_file($titles_with_replies_count, $mode = "w") {
    global $filename_with_result;
    $fres = fopen($filename_with_result, $mode);
    foreach($titles_with_replies_count as $row) {
        $title = $row['title'];
        $replies_count = $row['replies_count'];
        fwrite($fres, "$title - $replies_count\n");
    }
    fclose($fres);
}

function main()
{
    $interval = 20000;
    $i = 0;
    $mode = 'w';
    while (true) {
        $titles_with_replies_count = get_titles_with_replies_count($i, $interval);
        if (count($titles_with_replies_count) < 1) {
            break;
        }
        add_titles_with_replies_count_to_file($titles_with_replies_count, $mode);
        $i += $interval;
        $mode = 'a';
        echo "$i done\n";
        print_mem('main');
    }
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

//fill_threads_table(5000, 20);
main();

print_mem("end");
