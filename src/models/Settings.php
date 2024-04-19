<?php

namespace lenvanessen\cit\models;

use Craft;
use craft\base\Model;

/**
 * Cloudflare Image Transforms settings
 */
class Settings extends Model
{
	public ?string $zoneId = null;
	public ?string $apiKey = null;
	public bool $enableCachePurge = false;
}
