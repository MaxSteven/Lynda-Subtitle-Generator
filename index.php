<?php
/**
 * Lynda Subtitle Generator - PHP application
 * https://github.com/qolami/Lynda-Subtitle-Generator
 * Copyright 2013 Hashem Qolami <hashem@qolami.com>
 * Version 0.5.1
 * Released under the MIT and GPL licenses.
*/

# Get transcript url
$url = $_GET['url'] or die('Insert a URL to grab transcript.');

# No time limit, RUN forever BABY :D
set_time_limit(0);

# Path to subtitle folder
define('DIR', './subtitle');

# Load HTML DOM library
include('lib/simple_html_dom.php');


function get_path($url)
{
	$param = explode('/', preg_replace('#^https?://#i', '', $url));
	return rtrim(DIR, '/') ."/$param[1]-$param[2]";
}

function str_pure($str)
{
	return trim(str_replace(array('\\','/',':','*','?','"','<','>','|'), '', $str));
}

function to_dir($path)
{
	# Make directory if not exists. set permission to 0777.
	is_dir($path) and @chmod($path, 0777) or mkdir($path, 0777, true);
	return $path;
}

function to_srt($data, $path, $title)
{
	if (function_exists('mb_convert_encoding')) {
		$data = mb_convert_encoding($data, 'UTF-8', 'HTML-ENTITIES');
	}
	
	file_put_contents(rtrim($path, '/').'/'.$title.'.srt', $data) or die('Unable to write the data.');

	# Change permission of folder according to security issues.
	@chmod($path, 0755);
}

function process_chapter($e, $path)
{
	$chapter = $e->find('span.chTitle', 0)->plaintext;
	$sections = $e->find('tr.showToggleDeltails');

	$dir = to_dir( $path .'/'. str_pure($chapter) );

	$j = 0;
	foreach ($sections as $section) {
		$num = $j<10?"0$j":$j;
		$title = "$num ".$section->find('a', 0)->plaintext;
		$rows = $section->find('td.tC');
		$sub = '';

		for ($i = 0; $i < count($rows)-1;) {
			$start = $rows[$i]->plaintext;
			$end = $rows[$i+1]->plaintext;
			$text = $rows[$i]->next_sibling()->plaintext;
			$i++;
			$sub .= "$i\n\r
00:{$start},000 --> 00:{$end},000\n\r
{$text}\n\r\n\r\n\r";
		}

		to_srt( $sub, $dir, str_pure($title) );
		$j++;
	}
}

# Course path
$path = get_path($url);

# Make an instance
$html = new simple_html_dom();

# Load the DOM
$html->load_file($url);

$chs = $html->find('td.tChap');

foreach ($chs as $ch) {
	process_chapter($ch, $path);
}

echo "Subtitles have been generated successfully! located at: $path";

# Clear DOM object
$html->clear();

# Free memory
unset($html);