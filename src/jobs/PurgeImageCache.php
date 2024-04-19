<?php

namespace lenvanessen\cit\jobs;

use Craft;
use craft\helpers\Db;
use craft\mail\Message;
use DOMDocument;
use Carbon\Carbon;
use craft\helpers\App;
use GuzzleHttp\Client;
use craft\queue\BaseJob;
use craft\elements\Asset;
use craft\elements\Entry;
use GuzzleHttp\RequestOptions;
use craft\records\VolumeFolder;
use craft\helpers\ElementHelper;
use lenvanessen\cit\CloudflareImageTransforms;
use lenvanessen\cit\exceptions\CachePurgeFailed;
use Mimey\MimeTypes;
use modules\personio\connectors\PersonioConnector;
use modules\personio\requests\GetCompanyEmployees;
use modules\personio\requests\GetProfilePicture;
use modules\sitemodule\records\JobAlert;
use nystudio107\seomatic\models\jsonld\DepartAction;
use putyourlightson\blitz\Blitz;
use craft\elements\db\AssetQuery;
use craft\helpers\Queue as QueueHelper;
use modules\personio\helpers\AssetHelper;
use yii\log\Logger;
use yii\web\ServerErrorHttpException;

class PurgeImageCache extends BaseJob
{

	public array $files;

	public function execute($queue): void
	{
		$client = new Client();
		$settings = CloudflareImageTransforms::getInstance()->getSettings();
		$zoneId = App::parseEnv($settings->zoneId);
		$apiKey = App::parseEnv($settings->apiKey);

		$response = $client->post(
			"https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache",
			[
				RequestOptions::HEADERS => [
					'Authorization' => "Bearer $apiKey"
				],
				RequestOptions::JSON => [
					'files' => $this->files
				]
			]
		);

		if($response->getStatusCode() !== 200) {
			throw new CachePurgeFailed($response->getBody());
		}
	}


	/**
	 * {@inheritdoc}
	 */
	protected function defaultDescription(): string
	{
		return Craft::t('cloudflare-image-transforms', "Purging urls from CloudFlare");
	}
}
