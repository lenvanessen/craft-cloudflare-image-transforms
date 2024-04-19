<?php

namespace lenvanessen\cit;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use lenvanessen\cit\models\Settings;
use craft\imagetransforms\ImageTransformer as CraftImageTransformer;
use craft\imagetransforms\FallbackTransformer;

/**
 * Cloudflare Image Transforms plugin
 *
 * @method static CloudflareImageTransforms getInstance()
 * @method Settings getSettings()
 * @author Len van Essen <len@wndr.digital>
 * @copyright Len van Essen
 * @license MIT
 */
class CloudflareImageTransforms extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;


    public function init(): void
    {
        parent::init();
		Craft::$app->getImages()->supportedImageFormats = ImageTransformer::SUPPORTED_IMAGE_FORMATS;
		$this->overrideDefaultTransformer();
    }

	/**
	 * Injects the CloudFlare transformer as default transformer
	 */
	protected function overrideDefaultTransformer(): void
	{
		Craft::$container->set(
			CraftImageTransformer::class,
			ImageTransformer::class,
		);

		Craft::$container->set(
			FallbackTransformer::class,
			ImageTransformer::class,
		);
	}

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('cloudflare-image-transforms/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }
}
