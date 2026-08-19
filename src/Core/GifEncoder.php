<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encodeur GIF animé minimal (GIF89a, boucle infinie) en PHP pur,
 * sans dépendance : chaque frame est d'abord quantifiée et encodée par GD
 * (imagegif), puis ses blocs (descripteur d'image, palette locale, données
 * LZW) sont réassemblés avec les extensions de contrôle graphique (GCE)
 * et l'extension d'application NETSCAPE2.0 (boucle infinie).
 */
final class GifEncoder
{
    /**
     * @param list<\GdImage> $frames images vraies couleurs, mêmes dimensions
     */
    public static function encode(array $frames, int $delayMs = 120): string
    {
        if ($frames === []) {
            throw new \InvalidArgumentException('Au moins une frame est requise.');
        }

        $parsed = [];
        foreach ($frames as $frame) {
            $tmp = tempnam(sys_get_temp_dir(), 'camagru_gif_');
            if ($tmp === false || !imagegif($frame, $tmp)) {
                throw new \RuntimeException("Impossible d'encoder une frame en GIF.");
            }
            $raw = (string) file_get_contents($tmp);
            @unlink($tmp);
            $parsed[] = self::parseFrame($raw);
        }

        [$width, $height] = self::dimensions($parsed);
        $delay = max(2, (int) round($delayMs / 10)); // en centièmes de seconde

        $out = 'GIF89a';
        // Logical Screen Descriptor : pas de table de couleurs globale.
        $out .= pack('v', $width) . pack('v', $height) . "\x00\x00\x00";
        // Extension d'application NETSCAPE2.0 : boucle infinie.
        $out .= "\x21\xFF\x0BNETSCAPE2.0\x03\x01\x00\x00\x00";

        foreach ($parsed as $part) {
            // Graphic Control Extension : disposal 1 (ne pas effacer), pas de transparence.
            $out .= "\x21\xF9\x04\x04" . pack('v', $delay) . "\x00\x00";
            $out .= $part['descriptor'];
            $out .= $part['lct'];
            $out .= $part['data'];
        }

        $out .= "\x3B"; // Trailer

        return $out;
    }

    /**
     * Décompose un GIF statique produit par GD :
     * header + LSD [+ table globale] + Image Descriptor + données LZW.
     *
     * @return array{descriptor: string, lct: string, data: string}
     */
    private static function parseFrame(string $raw): array
    {
        if (strlen($raw) < 13 || !str_starts_with($raw, 'GIF8')) {
            throw new \RuntimeException('Frame GIF invalide produite par GD.');
        }

        $packed = ord($raw[10]); // octet « packed » du LSD
        $lct = '';
        $lctSizeBits = 0;
        $offset = 13;
        if (($packed & 0x80) !== 0) {
            $lctSizeBits = $packed & 0x07;
            $entries = 2 << $lctSizeBits; // 2^(size+1) couleurs
            $lct = substr($raw, $offset, 3 * $entries);
            $offset += 3 * $entries;
        }

        $rest = substr($raw, $offset);
        if (str_ends_with($rest, "\x3B")) {
            $rest = substr($rest, 0, -1);
        }
        if (strlen($rest) < 10) {
            throw new \RuntimeException('Frame GIF tronquée.');
        }

        // Image Descriptor : séparateur 0x2C + left + top + width + height (9 octets)
        // puis « packed » reconstruit : table de couleurs LOCALE. Attention : GD
        // laisse le champ size du descripteur à 0 (il n'a pas de LCT) — on doit
        // reprendre la taille de la table copiée, sinon tout le flux est décalé.
        $descriptor = substr($rest, 0, 9) . chr(0x80 | $lctSizeBits);

        return [
            'descriptor' => $descriptor,
            'lct' => $lct,
            'data' => substr($rest, 10),
        ];
    }

    /**
     * @param list<array{descriptor: string, lct: string, data: string}> $parsed
     * @return array{int, int}
     */
    private static function dimensions(array $parsed): array
    {
        $descriptor = $parsed[0]['descriptor'];
        $width = unpack('v', substr($descriptor, 5, 2))[1];
        $height = unpack('v', substr($descriptor, 7, 2))[1];

        return [(int) $width, (int) $height];
    }
}
