<?php

namespace Garak\Rummy\Test;

use Garak\Rummy\Meld;
use Garak\Rummy\Table;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    #[Test]
    public function emptyTable(): void
    {
        $table = Table::createFromString('');
        self::assertTrue($table->isEmpty());
        self::assertCount(0, $table);
        self::assertSame([], $table->getCards());
        self::assertSame('', (string) $table);
    }

    #[Test]
    public function cardsOfAllMelds(): void
    {
        $table = Table::createFromString('Kh,Kd,Ks;Ahr,2hb,3h');
        self::assertCount(2, $table);
        self::assertSame('Kh,Kd,Ks,Ah,2h,3h', \implode(',', $table->getCards()));
        self::assertSame('Kh,Kd,Ks;Ah,2h,3h', (string) $table);
        self::assertSame('Kh,Kd,Ks;Ahr,2hb,3h', $table->toString(true));
        self::assertCount(2, $table->getMelds());
    }

    #[Test]
    public function runsInAnyOrder(): void
    {
        self::assertSame('Kh,Kd,Ks;Ah,2h,3h', (string) Table::createFromString('Kh,Kd,Ks;3h,Ah,2h', anyOrder: true));
    }

    #[Test]
    public function isPreservedIn(): void
    {
        $table = Table::createFromString('Kh,Kd,Ks;Ah,2h,3h');
        self::assertTrue($table->isPreservedIn(Table::createFromString('Ah,2h,3h;Ks,Kd,Kh;5c,6c,7c')));
        self::assertFalse($table->isPreservedIn(Table::createFromString('Kh,Kd,Ks;Ah,2h,3h,4h')));
        self::assertFalse($table->isPreservedIn(Table::createFromString('Kh,Kd,Ks')));
        self::assertTrue((new Table())->isPreservedIn($table));
    }

    #[Test]
    public function newMeldsIn(): void
    {
        $table = Table::createFromString('Kh,Kd,Ks;Ah,2h,3h');
        $new = $table->newMeldsIn(Table::createFromString('Ah,2h,3h;5c,6c,7c;Ks,Kd,Kh;9d,9s,9c'));
        self::assertSame('5c,6c,7c 9d,9s,9c', \implode(' ', $new));
        self::assertSame([], $table->newMeldsIn($table));
    }

    #[Test]
    public function meldsAreReIndexed(): void
    {
        $table = new Table(['a' => Meld::createFromString('Kh,Kd,Ks')]);
        self::assertSame([0], \array_keys($table->getMelds()));
    }
}
