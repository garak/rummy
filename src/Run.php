<?php

namespace Garak\Rummy;

use Garak\Card\Suit;
use Garak\Rummy\Exception\InvalidMeldException;

/**
 * Three or more consecutive cards of the same suit, ace low (A,2,3 is valid; Q,K,A is not).
 * Jokers stand for the card implied by their position.
 */
final class Run extends Meld
{
    /** @var list<int> */
    private array $values;

    protected function validate(): void
    {
        if (\count($this->cards) > Rules::MAX_RUN_LENGTH) {
            throw new InvalidMeldException(\sprintf('A run cannot have more than %d cards.', Rules::MAX_RUN_LENGTH));
        }
        $suit = null;
        $first = null;
        foreach ($this->cards as $index => $card) {
            if (CardValue::isJoker($card)) {
                continue;
            }
            $suit ??= $card->getSuit();
            if (!$card->getSuit()->isEqual($suit)) {
                throw new InvalidMeldException(\sprintf('All cards in a run must share the suit, got %s.', $this));
            }
            $first ??= CardValue::of($card) - $index;
            if (CardValue::of($card) !== $first + $index) {
                throw new InvalidMeldException(\sprintf('Cards in a run must be consecutive, got %s.', $this));
            }
        }
        \assert(null !== $first);
        $last = $first + \count($this->cards) - 1;
        if ($first < CardValue::MIN || $last > CardValue::MAX) {
            throw new InvalidMeldException(\sprintf('A run cannot go below the ace or above the king, got %s.', $this));
        }
        $this->values = \range($first, $last);
    }

    public function getPoints(): int
    {
        return \array_sum($this->values);
    }

    /**
     * Value of each position in the run, jokers resolved.
     *
     * @return list<int>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getSuit(): Suit
    {
        return $this->getRegularCards()[0]->getSuit();
    }
}
