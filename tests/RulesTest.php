<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Card\CardBack;
use Garak\Rummy\CardValue;
use Garak\Rummy\Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RulesTest extends TestCase
{
    #[Test]
    public function defaultsMatchRummikub(): void
    {
        $rules = new Rules();
        self::assertSame(2, $rules->decks);
        self::assertSame(2, $rules->jokers);
        self::assertSame(14, $rules->handSize);
        self::assertSame(30, $rules->openingPoints);
        self::assertSame(30, $rules->jokerPenalty);
    }

    #[Test]
    #[DataProvider('deckProvider')]
    public function createDeckWithGivenSize(int $decks, int $jokers, int $expectedCards): void
    {
        $deck = (new Rules(decks: $decks, jokers: $jokers))->createDeck();
        self::assertCount($expectedCards, $deck);
        self::assertCount($jokers, \array_filter($deck, static fn (Card $card): bool => CardValue::isJoker($card)));
    }

    /** @return iterable<string, array{int, int, int}> */
    public static function deckProvider(): iterable
    {
        yield 'rummikub' => [2, 2, 106];
        yield 'single deck, no jokers' => [1, 0, 52];
        yield 'single deck, one joker' => [1, 1, 53];
        yield 'two decks, all jokers' => [2, 4, 108];
        yield 'three decks, three jokers' => [3, 3, 159];
    }

    #[Test]
    public function jokersComeFromDifferentDecks(): void
    {
        $jokers = \array_values(\array_filter((new Rules())->createDeck(), static fn (Card $card): bool => CardValue::isJoker($card)));
        self::assertSame(CardBack::Red, $jokers[0]->getBack());
        self::assertSame(CardBack::Red, $jokers[1]->getBack());
        self::assertFalse($jokers[0]->isEqual($jokers[1]));
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function invalidParameters(int $decks, int $jokers, int $handSize, int $openingPoints, int $jokerPenalty): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Rules($decks, $jokers, $handSize, $openingPoints, $jokerPenalty);
    }

    /** @return iterable<string, array{int, int, int, int, int}> */
    public static function invalidProvider(): iterable
    {
        yield 'no decks' => [0, 0, 14, 30, 30];
        yield 'negative jokers' => [1, -1, 14, 30, 30];
        yield 'too many jokers' => [1, 3, 14, 30, 30];
        yield 'empty hand' => [1, 0, 0, 30, 30];
        yield 'negative opening' => [1, 0, 14, -1, 30];
        yield 'negative penalty' => [1, 0, 14, 30, -1];
    }
}
