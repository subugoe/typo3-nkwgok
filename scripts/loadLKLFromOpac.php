<?php
/*
 * Script for downloading LKL Opac data to XML files.
 * 2010 by Nils Windisch <windisch@sub.uni-goettingen.de>
 */


/* ************** FUNCTIONS **************** */

function getURLs($conf)
{
	$url["url"][0] = ereg_replace("XXX", "1", $conf["url"]); // first URL to fetch
	$thisOne = file_get_contents($url["url"][0]) or die("What's that? Chicken?"); // go fetch stuff
	$xml = new SimpleXMLElement($thisOne); // read as XML
	$total = (string)$xml->SET->attributes()->hits; // we get this many records
	$sequenceCount = (ceil($total/500)-1); // chunk total records in 500 steps
	for ($i=0;$i<$sequenceCount;$i++) // build all query urls
	{
		$count += 500; // steps of 500
		$url["url"][] = str_replace("XXX", ($count+1), $conf["url"]); // let's make a url
	}
	$url["total"] = sizeof($url["url"]); // we got this many urls
	return $url; // show me
}


function openWrite($conf,$i,$urls)
{
	$content = file_get_contents($urls["url"][$i]) or die("What's that? Chicken?"); // fetch url content
	$fp = fopen($conf["folder"]."/".$conf["time"]."/".$i.".xml","w"); // make file
	fwrite($fp, $content); // write file
	fclose($fp); // done, yeah!
}

function savePicaXml($conf)
{
	mkdir($conf["folder"]."/".$conf["time"]);
	$urls = getURLs($conf); // get all urls
	for($i=0;$i<$urls["total"];$i++) openWrite($conf,$i,$urls); // write a file for every url
}



/* **************  DO **************** */

$conf = array(
			#"url" => "http://opac.sub.uni-goettingen.de/DB=1/CMD?ACT=SRCHA/IKT=8600/TRM=tev/XML=1.0/PRS=XML/SHRTST=500/FRST=XXX",
			"url" => "http://opac.sub.uni-goettingen.de/DB=1/CMD?ACT=SRCHA/IKT=8600/TRM=tev+not+LKL+p%3F/REC=2/XML=1.0/PRS=XML/SHRTST=500/FRST=XXX",
			"time" => date("Y-m-d_H-i-s"),
			"folder" => "data");

savePicaXml($conf);

?>