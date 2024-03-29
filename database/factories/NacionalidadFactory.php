<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Nacionalidad>
 */
class NacionalidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
          'codPaisNacimiento' => $this->faker->randomNumber(),
          'descripcion' => $this->faker->word
        ];
    }
}
