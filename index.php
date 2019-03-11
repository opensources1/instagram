<?php
require 'vendor/autoload.php';

\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;

/////// CONFIG ///////
#http://www.13dk.net/m-0n5fbbf9.html
$username = '0n5fbbf9@13dk.net';
$password = '0n5fbbf9@13dk.net';
$tag      = 'ojesizgezmeyenlerkulubu';
$minLikeCount = 1000;
$addPostLimit = 10;

$debug          = false;
$truncatedDebug = false;
//////////////////////
$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
try {
	$ig->login($username, $password);
} catch (\Exception $e) {
	echo 'Something went wrong: '.$e->getMessage()."\n";
	exit(0);
}

try {

	$rankToken = \InstagramAPI\Signatures::generateUUID();

	$maxId        = NULL;
	$backupFolder = 'download/';
	$addedCount = 0;
	do {
		if ($addedCount > $addPostLimit) {
			die('Tamamlandı');
		}
		$response = $ig->hashtag->getFeed($tag, $rankToken, $maxId);

		foreach ($response->getItems() as $item) {
			if ($item->getLikeCount() < $minLikeCount) {
				continue;
			}

			$userName    = $item->getUser()->getUsername();
			$captionText = '@'.$userName.' in #ojesinebak '.$item->getCaption()->getText();
			$mediaUrl    = $item->getImageVersions2();
			if (!$mediaUrl) {
				continue;
			}
			$mediaUrl = $mediaUrl->getCandidates();
			$mediaUrl = current($mediaUrl)->getUrl();
			$mediaId  = $item->getId();

			$fileExtension = pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filePath      = $backupFolder.$mediaId.'.'.$fileExtension;

			copy($mediaUrl, $filePath);

			if (is_file($filePath)) {
				touch($filePath, $item->getTakenAt());
			}

			try {
				$photo    = new \InstagramAPI\Media\Photo\InstagramPhoto($filePath);
				$timeLine = $ig->timeline->uploadPhoto($photo->getFile(), ['caption' => $captionText]);
			} catch (\Exception $e) {
				continue;
			}
			echo $timeLine->getMedia()->getPk().'<br>';
			$addedCount++;

		}

		$maxId = $response->getNextMaxId();

		echo "Sleeping for 2s...\n";
		sleep(2);

	} while ($maxId !== NULL);
} catch (\Exception $e) {
	echo 'Something went wrong: '.$e->getMessage()."\n";
}
