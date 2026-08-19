<?php

declare(strict_types=1);

namespace App\Core;

use App\Entities\GalleryImage;

/**
 * Données de partage social d'une image : citation (dernier commentaire,
 * tronquée) et URLs X/Facebook. Immutable, calculé une fois côté vue.
 */
final class ShareData
{
    public const int MAX_QUOTE_LENGTH = 120;
    private const string DEFAULT_QUOTE = 'Découvrez cette image sur Camagru !';

    public function __construct(
        public readonly string $quote,
        public readonly string $twitterUrl,
        public readonly string $facebookUrl,
    ) {
    }

    public static function forImage(GalleryImage $image, string $appUrl): self
    {
        $quote = self::DEFAULT_QUOTE;
        $comments = $image->comments;
        if ($comments !== []) {
            $last = end($comments);
            $quote = trim($last->content);
            if (mb_strlen($quote) > self::MAX_QUOTE_LENGTH) {
                $quote = mb_substr($quote, 0, self::MAX_QUOTE_LENGTH - 3) . '…';
            }
        }

        $imageUrl = $appUrl . '/image/' . $image->id;

        return new self(
            $quote,
            'https://twitter.com/intent/tweet?url=' . rawurlencode($imageUrl) . '&text=' . rawurlencode($quote),
            'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($imageUrl),
        );
    }
}
