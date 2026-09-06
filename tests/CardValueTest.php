<?php

namespace Garak\Rummy\Test;

use Garak\Card\Card;
use Garak\Rummy\CardValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardValueTest extends TestCase
{
    #[Test]
    #[DataProvider('valueProvider')]
    public function valueOfRegularCards(string $card, int $expected): void
    {
        self::assertSame($expected, CardValue::of(Card::fromRankSuit($card)));
    }

    /** @return iterable<string, array{string, int}> */
    public static function valueProvider(): iterable
    {
        yield 'ace is low' => ['Ah', 1];
        yield 'two' => ['2c', 2];
        yield 'ten' => ['Td', 10];
        yield 'jack' => ['Js', 11];
        yield 'queen' => ['Qh', 12];
        yield 'king' => ['Kc', 13];
    }

    #[Test]
    public function jokerHasNoValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CardValue::of(Card::fromRankSuit('wb'));
    }

    #[Test]
    public function isJoker(): void
    {
        self::assertTrue(CardValue::isJoker(Card::fromRankSuit('wr')));
        self::assertFalse(CardValue::isJoker(Card::fromRankSuit('Ah')));
    }

    #[Test]
    public function penaltyOfCardsLeftInHand(): void
    {
        self::assertSame(13, CardValue::penalty(Card::fromRankSuit('Kh'), 30));
        self::assertSame(30, CardValue::penalty(Card::fromRankSuit('wb'), 30));
        self::assertSame(50, CardValue::penalty(Card::fromRankSuit('wb'), 50));
    }
}
