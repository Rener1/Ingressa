<?php

namespace App\Http\Requests;

use App\Models\User;
use Laravel\Jetstream\Jetstream;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->role == User::ROLE_ENUM['admin'];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'terms'     => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['required', 'accepted'] : '',
            'tipos_analista' => 'required',
            'cotas_analista' => 'required|exists:cotas,id',
            'cursos_analista' => 'required|exists:cursos,cod_curso',
        ];
    }

    public function messages()
    {
        return [
            'tipos_analista.required'     => "Selecione ao menos um cargo para o analista",
        ];
    }
}
