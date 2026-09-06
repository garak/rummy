<?php

namespace Garak\Rummy;

use Garak\Card\Card;
use Garak\Card\Rank;

/**
 * Game parameters. Defaults match the classic Rummikub setup:
 * two decks, two jokers, 14 cards each, 30 points to open, 30 points penalty for a joker left in hand.
 */
final readonly class Rules
{
    public const int MIN_MELD_LENGTH = 3;
    public const int MAX_SET_LENGTH = 4;
    public const int MAX_RUN_LENGTH = 13;

    public function __construct(
        public int $decks = 2,
        public int $jokers = 2,
        public int $handSize = 14,
        public int $openingPoints = 30,
        public int $jokerPenalty = 30,
    ) {
        if ($decks < 1) {
            throw new \InvalidArgumentException('At least one deck is required.');
        }
        if ($jokers < 0 || $jokers > $decks * 2) {
            throw new \InvalidArgumentException(\sprintf('Jokers must be between 0 and %d.', $decks * 2));
        }
        if ($handSize < 1) {
            throw new \InvalidArgumentException('Hand size must be positive.');
        }
        if ($openingPoints < 0 || $jokerPenalty < 0) {
            throw new \InvalidArgumentException('Points cannot be negative.');
        }
    }

    /**
     * Builds the deck for a game, unshuffled: all regular cards from every deck, plus the configured number of jokers.
     *
     * @return list<Card>
     */
    public function createDeck(): array
    {
        $jokersLeft = $this->jokers;
        $deck = [];
        foreach (Card::getDeck(num: $this->decks, allowJokers: $this->jokers > 0) as $card) {
            if (Rank::Joker === $card->getRank()) {
                if ($jokersLeft <= 0) {
                    continue;
                }
                --$jokersLeft;
            }
            $deck[] = $card;
        }

        return $deck;
    }
}
