<?php

namespace Tests\Unit\Aws\DynamoDb;

use App\Aws\DynamoDb\FindSignatoryByRole;
use App\Aws\DynamoDb\SignatorySequenceResolver;
use Tests\TestCase;

class SignatorySequenceResolverTest extends TestCase
{
    public function test_extra_curricular_with_reservation_is_adviser_then_cdm(): void
    {
        $roles = (new SignatorySequenceResolver($this->app->make(FindSignatoryByRole::class)))->rolesFor([
            'activity_classification' => ['activity_type' => 'extra-curricular'],
            'venue_reservation' => ['has_reservation' => true],
        ]);

        $this->assertSame(['adviser', 'cdm'], $roles);
    }

    public function test_other_activity_types_are_adviser_only(): void
    {
        $roles = (new SignatorySequenceResolver($this->app->make(FindSignatoryByRole::class)))->rolesFor([
            'activity_classification' => ['activity_type' => 'co-curricular'],
            'venue_reservation' => ['has_reservation' => true],
        ]);

        $this->assertSame(['adviser'], $roles);
    }
}
