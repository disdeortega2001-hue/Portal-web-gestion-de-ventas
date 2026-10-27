<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Las pruebas revisan HTML y reglas de negocio, no los recursos compilados por Vite:
        // así corren igual en un equipo donde todavía no se ejecutó `npm run build`.
        $this->withoutVite();
    }
}
