<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class LocaleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanSwitchLanguage(): void
    {
        $response = $this->post('/locale/language', [
            'language' => 'es',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'es');

        self::assertSame('es', Session::get('locale'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanSwitchCurrency(): void
    {
        $response = $this->post('/locale/currency', [
            'currency' => 'EUR',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect();
        $response->assertSessionHas('currency', 'EUR');

        self::assertSame('EUR', Session::get('currency'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itValidatesLanguageSwitchRequest(): void
    {
        $response = $this->post('/locale/language', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['language']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReturns422ForInvalidLanguage(): void
    {
        $response = $this->post('/locale/language', [
            'language' => 'invalid_language',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['language']);
        $response->assertJsonFragment([
            'message' => 'The selected language is invalid.',
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itValidatesCurrencySwitchRequest(): void
    {
        $response = $this->post('/locale/currency', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReturns422ForInvalidCurrency(): void
    {
        $response = $this->post('/locale/currency', [
            'currency' => 'INVALID',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency']);
        $response->assertJsonFragment([
            'message' => 'The selected currency is invalid.',
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itSwitchesToValidLanguages(): void
    {
        $validLanguages = ['en', 'es', 'fr', 'de'];

        foreach ($validLanguages as $language) {
            $response = $this->post('/locale/language', [
                'language' => $language,
            ]);

            $response->assertStatus(302);
            $response->assertSessionHas('locale', $language);
        }
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itSwitchesToValidCurrencies(): void
    {
        $validCurrencies = ['USD', 'EUR', 'GBP', 'JPY'];

        foreach ($validCurrencies as $currency) {
            $response = $this->post('/locale/currency', [
                'currency' => $currency,
            ]);

            $response->assertStatus(302);
            $response->assertSessionHas('currency', $currency);
        }
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itRedirectsBackAfterLanguageSwitch(): void
    {
        $response = $this->from('/products')
            ->post('/locale/language', [
                'language' => 'fr',
            ])
        ;

        $response->assertStatus(302);
        $response->assertRedirect('/products');
        $response->assertSessionHas('locale', 'fr');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itRedirectsBackAfterCurrencySwitch(): void
    {
        $response = $this->from('/cart')
            ->post('/locale/currency', [
                'currency' => 'GBP',
            ])
        ;

        $response->assertStatus(302);
        $response->assertRedirect('/cart');
        $response->assertSessionHas('currency', 'GBP');
    }
}
