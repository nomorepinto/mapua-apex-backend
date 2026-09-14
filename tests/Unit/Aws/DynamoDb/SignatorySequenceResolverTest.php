<?php

namespace Tests\Unit\Aws\DynamoDb;

use App\Aws\DynamoDb\FindSignatoryByRole;
use App\Aws\DynamoDb\SignatorySequenceResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SignatorySequenceResolverTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool, 2: list<string>}>
     */
    public static function sequences(): array
    {
        return [
            'co-curricular without reservation' => ['co-curricular', false, ['adviser', 'dean', 'osaar']],
            'co-curricular with reservation' => ['co-curricular', true, ['adviser', 'dean', 'osaar', 'cdm']],
            'extra-curricular without reservation' => ['extra-curricular', false, ['adviser', 'osaar']],
            'extra-curricular with reservation' => ['extra-curricular', true, ['adviser', 'osaar', 'cdm']],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('sequences')]
    public function test_builds_the_role_sequence_from_activity_type_and_reservation(
        string $activityType,
        bool $hasReservation,
        array $expected,
    ): void {
        $roles = (new SignatorySequenceResolver($this->app->make(FindSignatoryByRole::class)))->rolesFor([
            'activity_classification' => ['activity_type' => $activityType],
            'venue_reservation' => ['has_reservation' => $hasReservation],
        ]);

        $this->assertSame($expected, $roles);
    }
}
