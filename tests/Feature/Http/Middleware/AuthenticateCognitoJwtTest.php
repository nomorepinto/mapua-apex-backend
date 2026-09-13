<?php

namespace Tests\Feature\Http\Middleware;

use App\Auth\CognitoJwtVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Fakes\FakeCognitoJwtVerifier;
use Tests\TestCase;

class AuthenticateCognitoJwtTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api.token:student', 'cognito.jwt:student'])
            ->get('/api/_test/cognito', function (Request $request) {
                return [
                    'organization_id' => $request->attributes->get('cognito.organization_id'),
                    'signatory_id' => $request->attributes->get('cognito.signatory_id'),
                ];
            });
    }

    public function test_returns_401_when_jwt_is_missing(): void
    {
        $this->fakeCognitoJwt();

        $response = $this->withApiKey()->getJson('/api/_test/cognito');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_jwt_is_invalid(): void
    {
        $this->fakeCognitoJwt(shouldFail: true);

        $response = $this->withApiKey()
            ->withToken('invalid-jwt')
            ->getJson('/api/_test/cognito');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_organization_claim_is_missing(): void
    {
        $this->app->instance(CognitoJwtVerifier::class, new FakeCognitoJwtVerifier([
            'custom:organization_id' => '',
            'cognito:groups' => ['student'],
        ]));

        $response = $this->withApiKey()
            ->withToken('fake-jwt')
            ->getJson('/api/_test/cognito');

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_cognito_group_does_not_match_route_role(): void
    {
        $this->fakeCognitoJwt([
            'cognito:groups' => ['signatory'],
        ]);

        $response = $this->withApiKey()
            ->withToken('fake-jwt')
            ->getJson('/api/_test/cognito');

        $response->assertUnauthorized();
    }

    public function test_allows_admin_group_on_a_student_route(): void
    {
        $this->fakeCognitoJwt([
            'cognito:groups' => ['admin'],
            'custom:organization_id' => '',
        ]);

        $response = $this->withApiKey('admin')
            ->withToken('fake-jwt')
            ->getJson('/api/_test/cognito');

        $response->assertOk();
    }

    public function test_returns_503_when_jwks_is_unavailable(): void
    {
        $this->fakeCognitoJwt(jwksUnavailable: true);

        $response = $this->withApiKey()
            ->withToken('fake-jwt')
            ->getJson('/api/_test/cognito');

        $response->assertStatus(503);
    }

    public function test_attaches_organization_id_from_claims(): void
    {
        $this->fakeCognitoJwt([
            'custom:organization_id' => 'ORGANIZATION#a1b2',
            'custom:signatory_id' => 'SIGNATORY#adv001',
        ]);

        $response = $this->withApiKey()
            ->withToken('fake-jwt')
            ->getJson('/api/_test/cognito');

        $response->assertOk()
            ->assertExactJson([
                'organization_id' => 'a1b2',
                'signatory_id' => 'adv001',
            ]);
    }

    public function test_attaches_signatory_id_from_unprefixed_claim(): void
    {
        $this->fakeCognitoJwt([
            'custom:signatory_id' => '',
            'signatory_id' => 'SIGNATORY#adv001',
        ]);

        $response = $this->withApiKey()
            ->withToken('fake-jwt')
            ->getJson('/api/_test/cognito');

        $response->assertOk()
            ->assertJsonPath('signatory_id', 'adv001');
    }
}
