<?php

namespace Garak\Rummy;

use Garak\Rummy\Exception\InvalidMeldException;

/**
 * Three or four cards of the same rank, all of different suits. Jokers stand for a missing suit.
 */
final class Set extends Meld
{
    protected function validate(): void
    {
        if (\count($this->cards) > Rules::MAX_SET_LENGTH) {
            throw new InvalidMeldException(\sprintf('A set cannot have more than %d cards.', Rules::MAX_SET_LENGTH));
        }
        $regular = $this->getRegularCards();
        $rank = $regular[0]->getRank();
        $suits = [];
        foreach ($regular as $card) {
            if (!$card->getRank()->isEqual($rank)) {
                throw new InvalidMeldException(\sprintf('All cards in a set must share the rank, got %s.', $this));
            }
            if (\in_array($card->getSuit(), $suits, true)) {
                throw new InvalidMeldException(\sprintf('Suits in a set must be different, got %s.', $this));
            }
            $suits[] = $card->getSuit();
        }
    }

    public function getPoints(): int
    {
        return $this->getValue() * \count($this->cards);
    }

    /**
     * Value of each card in the set.
     */
    public function getValue(): int
    {
        return CardValue::of($this->getRegularCards()[0]);
    }
}
