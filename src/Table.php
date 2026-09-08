<?php

namespace Garak\Rummy;

use Garak\Card\Card;

/**
 * The melds currently laid down. Immutable: each play replaces the whole table.
 */
final readonly class Table implements \Countable, \Stringable
{
    /** @var list<Meld> */
    private array $melds;

    /** @param array<int|string, Meld> $melds */
    public function __construct(array $melds = [])
    {
        $this->melds = \array_values($melds);
    }

    /**
     * @param string $melds    semicolon-separated melds, each one comma-separated (e.g. "5h,5d,5s;7c,8c,9c")
     * @param bool   $anyOrder whether runs may be given scrambled, see Meld::fromCards()
     */
    public static function createFromString(string $melds, bool $anyOrder = false): self
    {
        if ('' === $melds) {
            return new self();
        }

        return new self(\array_map(static fn (string $meld): Meld => Meld::createFromString($meld, $anyOrder), \explode(';', $melds)));
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(bool $withBack = false): string
    {
        return \implode(';', \array_map(static fn (Meld $meld): string => $meld->toString($withBack), $this->melds));
    }

    /** @return list<Meld> */
    public function getMelds(): array
    {
        return $this->melds;
    }

    /** @return list<Card> */
    public function getCards(): array
    {
        return \array_merge(...\array_map(static fn (Meld $meld): array => $meld->getCards(), $this->melds));
    }

    public function isEmpty(): bool
    {
        return [] === $this->melds;
    }

    public function count(): int
    {
        return \count($this->melds);
    }

    /**
     * Whether every meld of this table is present, with the same cards, in the given one.
     */
    public function isPreservedIn(self $table): bool
    {
        $remaining = $table->melds;
        foreach ($this->melds as $meld) {
            $index = \array_find_key($remaining, static fn (Meld $candidate): bool => $meld->hasSameCards($candidate));
            if (null === $index) {
                return false;
            }
            unset($remaining[$index]);
        }

        return true;
    }

    /**
     * Melds of the given table that are not in this one (same cards, any order).
     *
     * @return list<Meld>
     */
    public function newMeldsIn(self $table): array
    {
        $remaining = $this->melds;
        $new = [];
        foreach ($table->melds as $meld) {
            $index = \array_find_key($remaining, static fn (Meld $candidate): bool => $meld->hasSameCards($candidate));
            if (null === $index) {
                $new[] = $meld;
                continue;
            }
            unset($remaining[$index]);
        }

        return $new;
    }
}
