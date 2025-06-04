<?php

namespace lenvanessen\cit;

use Craft;
use craft\base\Component;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\helpers\Html;
use craft\models\ImageTransform;
use Illuminate\Support\Collection;
use lenvanessen\cit\jobs\PurgeImageCache;
use yii\base\NotSupportedException;

class ImageTransformer extends Component implements ImageTransformerInterface
{
    public const SUPPORTED_IMAGE_FORMATS = ['jpg', 'jpeg', 'gif', 'png', 'avif'];
    protected Asset $asset;

    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        $this->asset = $asset;
        $this->assertTransformable();

        $params = $this->buildTransformParams($imageTransform);
        return $this->assetUrl($params);
    }

    protected function assertTransformable(): void
    {
        $mimeType = $this->asset->getMimeType();

        if ($mimeType === 'image/gif' && !Craft::$app->getConfig()->getGeneral()->transformGifs) {
            throw new NotSupportedException('GIF files shouldn’t be transformed.');
        }

        if ($mimeType === 'image/svg+xml' && !Craft::$app->getConfig()->getGeneral()->transformSvgs) {
            throw new NotSupportedException('SVG files shouldn’t be transformed.');
        }
    }
    
    protected function assetUrl(Collection $params)
    {
        $basePath = rtrim(
            $this->asset->fs->getRootUrl(),
            '/'
        );


        $directive = $params->map(fn($v, $k) => "$k=$v")->implode(',');
        
        $parts = parse_url($basePath);

        // Get zone root URL from the provided asset filesystem root
        // cdn-cgi is located at the zone root irrespective of where the FS root is on the zone
        $base = '';
        if (isset($parts['host'])) {
            $auth = $parts['user'] ?? '';
            if (isset($parts['pass'])) $auth .= ":" . ($parts['pass']);
            if ($auth) $auth .= '@';
            
            $base = (isset($parts['scheme']) ? ($parts['scheme'] . ':') : '') . "//{$auth}{$parts['host']}" . (isset($parts['port']) ? (':' . $parts['port']) : '');
        }
        
        return Html::encodeSpaces(
            // Add the zone root URL, then add the path beyond the zone root as needed
            "$base/cdn-cgi/image/$directive" . ($parts['path'] ?? '') . "/{$this->asset->getPath()}"
        );
    }

    /**
     * Cloudflare Images does not support purging resized variants individually. URLs starting with /cdn-cgi/ cannot be purged. However, purging of the original image’s URL will also purge all of its resized variants.
     * @param Asset $asset
     * @return void
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
        if(CloudflareImageTransforms::getInstance()->getSettings()->enableCachePurge) {
            $job = new PurgeImageCache(['files' => [$asset->getUrl()]]);
            Craft::$app->getQueue()->push($job);
        }
    }

    public function buildTransformParams(ImageTransform $imageTransform): Collection
    {
        return Collection::make([
            'width' => $imageTransform->width,
            'height' => $imageTransform->height,
            'quality' => $imageTransform->quality ?: Craft::$app->getConfig()->general->defaultImageQuality,
            'format' => $this->getFormatValue($imageTransform),
            'fit' => $this->getFitValue($imageTransform),
            'background' => $this->getBackgroundValue($imageTransform),
            'gravity' => $this->getGravityValue($imageTransform),
        ])->whereNotNull();
    }

    protected function getGravityValue(ImageTransform $imageTransform): ?string
    {
        $value = $this->getGravity($imageTransform);

        if(!$value) {
            return null;
        }

        $value = array_values($value);

        return "$value[0]x$value[1]";
    }
    protected function getGravity(ImageTransform $imageTransform): ?array
    {
        if ($this->asset->getHasFocalPoint()) {
            return $this->asset->getFocalPoint();
        }

        if ($imageTransform->position === 'center-center') {
            return null;
        }

        // TODO: maybe just do this in Craft
        $parts = explode('-', $imageTransform->position);
        $yPosition = $parts[0] ?? null;
        $xPosition = $parts[1] ?? null;

        try {
            $x = match ($xPosition) {
                'top' => 0,
                'center' => 0.5,
                'bottom' => 1,
            };
            $y = match ($yPosition) {
                'top' => 0,
                'center' => 0.5,
                'bottom' => 1,
            };
        } catch (\UnhandledMatchError $e) {
            throw new ImageTransformException('Invalid `position` value.');
        }

        return [$x, $y];
    }

    protected function getBackgroundValue(ImageTransform $imageTransform): ?string
    {
        return $imageTransform->mode === 'letterbox'
            ? $imageTransform->fill ?? '#FFFFFF'
            : null;
    }

    protected function getFitValue(ImageTransform $imageTransform): string
    {
        return match ($imageTransform->mode) {
            'fit' => $imageTransform->upscale ? 'contain' : 'scale-down',
            'stretch', 'crop' => 'cover',
            'letterbox' => 'pad',
            default => 'crop',
        };
    }

    protected function getFormatValue(ImageTransform $imageTransform): string
    {
        if ($imageTransform->format === 'jpg' && $imageTransform->interlace === 'none') {
            return 'baseline-jpeg';
        }

        return match ($imageTransform->format) {
            'jpg' => 'jpeg',
            default => $imageTransform->format ?? 'auto',
        };
    }
}
