<?php

namespace Garak\Rummy;

use Garak\Card\Card;
use Garak\Rummy\Exception\InvalidMeldException;

/**
 * A valid combination of cards laid on the table: a Set or a Run.
 * Melds are immutable and validated on construction.
 */
abstract class Meld implements \Countable, \Stringable
{
    /** @var list<Card> */
    protected array $cards;

    /**
     * @param array<int|string, Card> $cards for runs, in ascending order
     *
     * @throws InvalidMeldException
     */
    final public function __construct(array $cards)
    {
        $this->cards = \array_values($cards);
        $count = \count($this->cards);
        if ($count < Rules::MIN_MELD_LENGTH) {
            throw new InvalidMeldException(\sprintf('A meld needs at least %d cards, %d given.', Rules::MIN_MELD_LENGTH, $count));
        }
        if ([] === $this->getRegularCards()) {
            throw new InvalidMeldException('A meld cannot be made of jokers only.');
        }
        $this->validate();
    }

    /**
     * Guesses the meld type: a Set when all regular cards share the rank (and the size allows it), a Run otherwise.
     * When both readings are possible (a single regular card plus jokers), a Set is assumed.
     *
     * With $anyOrder, a run may be given scrambled: cards refused as given are tried again in run order
     * (see Run::sort()). A meld valid as given is kept as it is, jokers where they were put.
     *
     * @param array<int|string, Card> $cards
     *
     * @throws InvalidMeldException the one raised by the cards as given
     */
    public static function fromCards(array $cards, bool $anyOrder = false): self
    {
        try {
            return self::guess($cards);
        } catch (InvalidMeldException $e) {
            if (!$anyOrder) {
                throw $e;
            }
            try {
                return new Run(Run::sort($cards));
            } catch (InvalidMeldException) {
                throw $e;
            }
        }
    }

    /**
     * @param string $cards    comma-separated cards (e.g. "5h,5d,wb")
     * @param bool   $anyOrder see fromCards()
     */
    public static function createFromString(string $cards, bool $anyOrder = false): self
    {
        return static::fromCards(\array_map(static fn (string $rs): Card => Card::fromRankSuit($rs), \explode(',', $cards)), $anyOrder);
    }

    /**
     * @param array<int|string, Card> $cards
     *
     * @throws InvalidMeldException
     */
    private static function guess(array $cards): self
    {
        $regular = \array_filter($cards, static fn (Card $card): bool => !CardValue::isJoker($card));
        $ranks = \array_unique(\array_map(static fn (Card $card): string => $card->getRank()->value, $regular));
        if (1 === \count($ranks) && \count($cards) <= Rules::MAX_SET_LENGTH) {
            return new Set($cards);
        }

        return new Run($cards);
    }

    /**
     * @throws InvalidMeldException
     */
    abstract protected function validate(): void;

    /**
     * Total value of the meld, jokers counted as the cards they stand for.
     */
    abstract public function getPoints(): int;

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(bool $withBack = false): string
    {
        return \implode(',', \array_map(static fn (Card $card): string => $card->toString($withBack), $this->cards));
    }

    /** @return list<Card> */
    public function getCards(): array
    {
        return $this->cards;
    }

    /** @return list<Card> */
    public function getRegularCards(): array
    {
        return \array_values(\array_filter($this->cards, static fn (Card $card): bool => !CardValue::isJoker($card)));
    }

    public function hasJoker(): bool
    {
        return \count($this->cards) !== \count($this->getRegularCards());
    }

    public function count(): int
    {
        return \count($this->cards);
    }

    /**
     * Same cards, regardless of order.
     */
    public function hasSameCards(self $meld): bool
    {
        return (new CardBag($this->cards))->equals(new CardBag($meld->cards));
    }
}
