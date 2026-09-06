<?php

namespace Garak\Rummy;

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
        return \array_sum(\array_map(static fn ($card): int => CardValue::penalty($card, $jokerPenalty), $this->cards));
    }
}
