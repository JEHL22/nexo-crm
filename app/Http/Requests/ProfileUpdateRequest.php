<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'crm_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'crm_secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'crm_theme_mode' => ['required', 'in:light,dark'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'crm_primary_color.regex' => 'El color principal debe tener formato HEX, por ejemplo #0f172a.',
            'crm_secondary_color.regex' => 'El color secundario debe tener formato HEX, por ejemplo #06b6d4.',
            'crm_theme_mode.required' => 'Selecciona si quieres usar el tema claro u oscuro.',
            'crm_theme_mode.in' => 'El tema elegido no es válido.',
            'profile_photo.image' => 'La foto de perfil debe ser una imagen válida.',
            'profile_photo.mimes' => 'La foto de perfil debe estar en formato JPG, PNG o WEBP.',
            'profile_photo.max' => 'La foto de perfil no debe superar los 4 MB.',
        ];
    }
}
