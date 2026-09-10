<?php

namespace Garak\Rummy;

use Garak\Card\Card;
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
     * The given cards in run order: the regular ones ascending, jokers filling the gaps between them,
     * the spare ones after the last card, or before the first one when the run already reaches the king.
     * Nothing is validated: the result is a valid run only when the cards can make one.
     *
     * @param array<int|string, Card> $cards
     *
     * @return list<Card>
     */
    public static function sort(array $cards): array
    {
        $jokers = \array_values(\array_filter($cards, static fn (Card $c): bool => CardValue::isJoker($c)));
        $regular = \array_values(\array_filter($cards, static fn (Card $c): bool => !CardValue::isJoker($c)));
        if ([] === $regular) {
            return \array_values($cards);
        }
        \usort($regular, static fn (Card $c1, Card $c2): int => CardValue::of($c1) <=> CardValue::of($c2));
        $run = [];
        $last = null;
        foreach ($regular as $card) {
            $value = CardValue::of($card);
            while (null !== $last && $last + 1 < $value && [] !== $jokers) {
                $run[] = \array_shift($jokers);
                ++$last;
            }
            $run[] = $card;
            $last = $value;
        }
        $before = [];
        foreach ($jokers as $joker) {
            if ($last < CardValue::MAX) {
                $run[] = $joker;
                ++$last;
            } else {
                $before[] = $joker;
            }
        }

        return \array_merge($before, $run);
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
