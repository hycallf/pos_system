<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMidtransSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'midtrans_client_key' => 'required|string|max:100',
            'midtrans_server_key' => 'required|string|max:100',
            'midtrans_environment' => 'required|in:sandbox,production',
            'midtrans_is_sanitized' => 'required|boolean',
            'midtrans_is_3ds' => 'required|boolean',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('access_settings');
    }
}
