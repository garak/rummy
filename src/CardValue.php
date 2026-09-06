<?php

namespace Garak\Rummy;

use Garak\Card\Card;
use Garak\Card\Rank;

/**
 * Rummy numbering: ace is 1, court cards are 11 to 13. No ace-high.
 */
final class CardValue
{
    public const int MIN = 1;
    public const int MAX = 13;

    public static function isJoker(Card $card): bool
    {
        return Rank::Joker === $card->getRank();
    }

    /**
     * Numeric value of a regular card, 1 (ace) to 13 (king).
     */
    public static function of(Card $card): int
    {
        return self::ofRank($card->getRank());
    }

    public static function ofRank(Rank $rank): int
    {
        return match ($rank) {
            Rank::Ace => 1,
            Rank::Joker => throw new \InvalidArgumentException('A joker has no value on its own.'),
            default => $rank->getInt(),
        };
    }

    /**
     * Points of a card left in hand at the end of the game.
     */
    public static function penalty(Card $card, int $jokerPenalty): int
    {
        return self::isJoker($card) ? $jokerPenalty : self::of($card);
    }
}
