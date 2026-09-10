<?php

namespace Garak\Rummy;

use Garak\Card\Card;
use Garak\Card\Hand as BaseHand;

final class Hand extends BaseHand
{
    public function __construct(array $cards, bool $start = true, ?callable $checking = null, ?callable $sorting = null)
    {
        $this->cards = \array_values($cards);
        $this->sorting = $sorting ?? function (): void {
            CardSorter::sort($this->cards);
        };
        if ($start && null !== $checking) {
            $checking($cards);
        }
    }

    /**
     * Points charged for the cards still in hand at the end of the game.
     */
    public function getPenalty(int $jokerPenalty = 30): int
    {
        return \array_sum(\array_map(static fn (Card $c): int => CardValue::penalty($c, $jokerPenalty), $this->cards));
    }
}
